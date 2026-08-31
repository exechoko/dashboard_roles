<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Backup y restauración de la base de datos principal (conexión 'mysql',
 * base `equipamiento`) vía mysqldump/mysql. Las credenciales nunca viajan en
 * la línea de comandos (visible en el listado de procesos del SO): se pasan
 * por un archivo --defaults-extra-file temporal que se borra al terminar.
 *
 * `crear()`/`restaurar()` son lentas (varios minutos en bases grandes), así
 * que se disparan desde un Job (ver App\Jobs\GenerarBackupBaseDatos y
 * RestaurarBackupBaseDatos) en vez de correr dentro del request HTTP: en
 * producción, atrás de Cloudflare, una request de más de ~100s se corta
 * aunque el proceso siga vivo en el servidor. El estado de la operación en
 * curso se guarda en caché para que la pantalla lo consulte por polling.
 */
class BackupBaseDatosService
{
    private const PATRON_ARCHIVO = '/^equipamiento_\d{8}_\d{6}\.sql$/';

    private const TIMEOUT_SEGUNDOS = 1700;

    public const CACHE_ESTADO = 'configuracion_sistema.backup_estado';

    /**
     * Rutas típicas donde puede vivir mysqldump/mysql cuando no están en el
     * PATH del sistema operativo.
     *
     * @var array<int, string>
     */
    private const RUTAS_COMUNES_WINDOWS = [
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin',
        'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin',
        'C:\\Program Files\\MySQL\\MySQL Workbench 8.0',
        'C:\\xampp\\mysql\\bin',
    ];

    private string $carpeta;

    public function __construct(?string $carpeta = null)
    {
        $this->carpeta = $carpeta ?? storage_path('app/backups/db');
    }

    /**
     * @return Collection<int, array{archivo: string, tamano_mb: float, creado_en: Carbon, nota: string|null}>
     */
    public function listar(): Collection
    {
        $archivos = glob($this->carpetaAsegurada() . DIRECTORY_SEPARATOR . '*.sql') ?: [];

        return collect($archivos)
            ->map(fn (string $ruta) => [
                'archivo'   => basename($ruta),
                'tamano_mb' => round(filesize($ruta) / 1048576, 2),
                'creado_en' => Carbon::createFromTimestamp(filemtime($ruta)),
                'nota'      => $this->leerNota(basename($ruta)),
            ])
            ->sortByDesc('creado_en')
            ->values();
    }

    public function crear(?string $nota = null): string
    {
        $config = config('database.connections.mysql');
        $archivo = 'equipamiento_' . now()->format('Ymd_His') . '.sql';
        $ruta = $this->carpetaAsegurada() . DIRECTORY_SEPARATOR . $archivo;

        $this->ejecutarMysqldump($config, $ruta);

        if ($nota) {
            file_put_contents($this->rutaNota($archivo), json_encode(['nota' => $nota], JSON_UNESCAPED_UNICODE));
        }

        return $archivo;
    }

    /**
     * Restaura un backup existente. Genera automáticamente un backup de
     * seguridad de la base actual antes de pisarla.
     */
    public function restaurar(string $archivo): string
    {
        $ruta = $this->rutaSegura($archivo);

        $backupSeguridad = $this->crear('Automático — previo a restaurar ' . $archivo);

        $config = config('database.connections.mysql');
        $this->ejecutarMysqlImport($config, $ruta);

        return $backupSeguridad;
    }

    public function eliminar(string $archivo): void
    {
        $ruta = $this->rutaSegura($archivo);
        @unlink($ruta);
        @unlink($this->rutaNota($archivo));
    }

    public function ruta(string $archivo): string
    {
        return $this->rutaSegura($archivo);
    }

    public function binariosDisponibles(): bool
    {
        try {
            $this->resolverBinario('mysqldump');

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Estado de la operación (crear/restaurar) en curso o de la última
     * terminada, para el polling de la pantalla. `null` = nunca se disparó
     * ninguna desde que se reinició el caché.
     *
     * @return array<string, mixed>|null
     */
    public function estado(): ?array
    {
        return Cache::get(self::CACHE_ESTADO);
    }

    public function marcarPendiente(string $accion, ?string $archivo = null, ?string $nota = null): void
    {
        Cache::put(self::CACHE_ESTADO, [
            'estado'      => 'procesando',
            'accion'      => $accion,
            'archivo'     => $archivo,
            'nota'        => $nota,
            'iniciado_en' => now()->toIso8601String(),
        ], now()->addHours(2));
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    public function marcarCompletado(array $resultado): void
    {
        Cache::put(self::CACHE_ESTADO, array_merge((array) $this->estado(), [
            'estado'       => 'completado',
            'terminado_en' => now()->toIso8601String(),
            'resultado'    => $resultado,
        ]), now()->addHours(2));
    }

    public function marcarError(string $mensaje): void
    {
        Cache::put(self::CACHE_ESTADO, array_merge((array) $this->estado(), [
            'estado'       => 'error',
            'terminado_en' => now()->toIso8601String(),
            'error'        => $mensaje,
        ]), now()->addHours(2));
    }

    private function rutaSegura(string $archivo): string
    {
        $archivo = basename($archivo);
        if (!preg_match(self::PATRON_ARCHIVO, $archivo)) {
            throw new RuntimeException('Nombre de backup inválido.');
        }

        $ruta = $this->carpetaAsegurada() . DIRECTORY_SEPARATOR . $archivo;
        if (!is_file($ruta)) {
            throw new RuntimeException('El backup no existe.');
        }

        return $ruta;
    }

    private function rutaNota(string $archivo): string
    {
        return $this->carpetaAsegurada() . DIRECTORY_SEPARATOR . $archivo . '.json';
    }

    private function leerNota(string $archivo): ?string
    {
        $ruta = $this->rutaNota($archivo);
        if (!is_file($ruta)) {
            return null;
        }

        $datos = json_decode((string) file_get_contents($ruta), true);

        return $datos['nota'] ?? null;
    }

    private function carpetaAsegurada(): string
    {
        if (!is_dir($this->carpeta)) {
            mkdir($this->carpeta, 0755, true);
        }

        return $this->carpeta;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function ejecutarMysqldump(array $config, string $rutaDestino): void
    {
        $binario = $this->resolverBinario('mysqldump');
        $archivoCredenciales = $this->archivoCredenciales($config);

        try {
            $proceso = new Process([
                $binario,
                '--defaults-extra-file=' . $archivoCredenciales,
                '--host=' . $config['host'],
                '--port=' . $config['port'],
                '--single-transaction',
                '--routines',
                '--result-file=' . $rutaDestino,
                $config['database'],
            ]);
            $proceso->setTimeout(self::TIMEOUT_SEGUNDOS);
            $proceso->setEnv($this->entornoProceso());
            $proceso->run();

            if (!$proceso->isSuccessful()) {
                @unlink($rutaDestino);

                throw new RuntimeException('mysqldump falló: ' . $proceso->getErrorOutput());
            }
        } finally {
            @unlink($archivoCredenciales);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function ejecutarMysqlImport(array $config, string $rutaOrigen): void
    {
        $binario = $this->resolverBinario('mysql');
        $archivoCredenciales = $this->archivoCredenciales($config);

        $entrada = fopen($rutaOrigen, 'r');
        if ($entrada === false) {
            @unlink($archivoCredenciales);

            throw new RuntimeException("No se pudo abrir el backup para restaurar: {$rutaOrigen}");
        }

        try {
            $proceso = new Process([
                $binario,
                '--defaults-extra-file=' . $archivoCredenciales,
                '--host=' . $config['host'],
                '--port=' . $config['port'],
                $config['database'],
            ]);
            $proceso->setTimeout(self::TIMEOUT_SEGUNDOS);
            $proceso->setInput($entrada);
            $proceso->setEnv($this->entornoProceso());
            $proceso->run();

            if (!$proceso->isSuccessful()) {
                throw new RuntimeException('La restauración falló: ' . $proceso->getErrorOutput());
            }
        } finally {
            @unlink($archivoCredenciales);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function archivoCredenciales(array $config): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'my_cnf_');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal de credenciales.');
        }

        file_put_contents($ruta, sprintf(
            "[client]\nuser=%s\npassword=%s\n",
            $config['username'],
            $config['password']
        ));

        return $ruta;
    }

    /**
     * Entorno explícito para el proceso hijo. Bajo el servidor embebido de PHP
     * (`php -S`, usado en desarrollo) `proc_open()` a veces no hereda variables
     * de sistema como `SystemRoot`; sin ellas, el cliente de MySQL en Windows
     * falla al inicializar Winsock ("Can't create TCP/IP socket", error 10106)
     * aunque el mismo binario funcione perfecto desde una consola normal.
     *
     * @return array<string, string>
     */
    private function entornoProceso(): array
    {
        $entorno = [];
        foreach (getenv() as $clave => $valor) {
            if (is_string($valor)) {
                $entorno[$clave] = $valor;
            }
        }

        if (PHP_OS_FAMILY === 'Windows' && !isset($entorno['SystemRoot'])) {
            $entorno['SystemRoot'] = getenv('SystemRoot') ?: getenv('windir') ?: 'C:\\Windows';
        }

        return $entorno;
    }

    private function resolverBinario(string $nombre): string
    {
        $configurado = config('configuracion_sistema.mysql.bin_path');
        if ($configurado) {
            $candidato = rtrim((string) $configurado, '\\/') . DIRECTORY_SEPARATOR . $this->conExtension($nombre);
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        $encontrado = (new ExecutableFinder())->find($nombre, null, self::RUTAS_COMUNES_WINDOWS);
        if ($encontrado) {
            return $encontrado;
        }

        throw new RuntimeException(
            "No se encontró el binario '{$nombre}'. Configurá la ruta en Configuración del Sistema > ".
            'Variables de entorno (clave MYSQL_BIN_PATH, carpeta que contiene mysqldump y mysql).'
        );
    }

    private function conExtension(string $nombre): string
    {
        return PHP_OS_FAMILY === 'Windows' ? $nombre . '.exe' : $nombre;
    }
}
