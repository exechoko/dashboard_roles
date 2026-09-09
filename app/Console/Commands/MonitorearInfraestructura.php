<?php

namespace App\Console\Commands;

use App\Models\DispositivoEdificio;
use App\Models\Notificacion;
use App\Services\SnmpService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitorearInfraestructura extends Command
{
    protected $signature = 'infraestructura:monitorear
        {--tipo= : Sólo un tipo de dispositivos_edificio (pc, servidor, router, switch, camara_interna)}
        {--sin-telegram : Sólo muestra el estado en consola, no envía alertas}';

    protected $description = 'Releva por ping+SNMP las PCs, servidores, cámaras internas y equipos de red del edificio (tabla dispositivos_edificio) y avisa por Telegram cuando alguno cae o supera los umbrales de CPU/RAM/disco.';

    private const TIPOS_MONITOREADOS = ['pc', 'servidor', 'servidor_cecoco', 'servidor_nebula', 'router', 'switch', 'camara_interna'];

    private const CACHE_FLAG_ALERTA = 'infraestructura.alerta.';

    private const CACHE_COOLDOWN = 'infraestructura.cooldown.';

    public function handle(SnmpService $snmp, TelegramService $telegram): int
    {
        $tipoOpcion = $this->option('tipo');
        $tipos = $tipoOpcion ? [$tipoOpcion] : self::TIPOS_MONITOREADOS;

        $dispositivos = DispositivoEdificio::activos()
            ->conMonitoreoHabilitado()
            ->whereIn('tipo', $tipos)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        if ($dispositivos->isEmpty()) {
            $this->warn('No hay dispositivos activos con monitoreo habilitado para los tipos solicitados.');

            return Command::SUCCESS;
        }

        $umbrales = SnmpService::umbralesConfigurados();
        $umbralesRecuperacion = $this->umbralesRecuperacion($umbrales);

        $lecturas = [];
        $nuevasAlertas = [];
        $recuperados = [];
        $simulacro = (bool) $this->option('sin-telegram');

        foreach ($dispositivos as $dispositivo) {
            $lectura = $snmp->relevarDispositivo($dispositivo);
            $lectura['estado'] = SnmpService::clasificarEstado($lectura, $umbrales);
            $lecturas[] = $lectura;

            $flagKey = self::CACHE_FLAG_ALERTA . $dispositivo->id;
            $cooldownKey = self::CACHE_COOLDOWN . $dispositivo->id;
            $enAlerta = in_array($lectura['estado'], ['alerta', 'caido'], true);

            if ($enAlerta) {
                if (!Cache::has($cooldownKey)) {
                    $nuevasAlertas[] = $lectura;
                    if (!$simulacro) {
                        Cache::put($cooldownKey, true, now()->addMinutes($this->cooldownMinutos()));
                    }
                }
                if (!$simulacro) {
                    Cache::put($flagKey, true, now()->addDay());
                }

                continue;
            }

            if (!Cache::has($flagKey)) {
                continue;
            }

            $estadoConHisteresis = $lectura['alcanzable']
                ? SnmpService::clasificarEstado($lectura, $umbralesRecuperacion)
                : 'caido';

            if (in_array($estadoConHisteresis, ['ok', 'sin_snmp'], true)) {
                $recuperados[] = $lectura;
                if (!$simulacro) {
                    Cache::forget($flagKey);
                    Cache::forget($cooldownKey);
                }
            }
        }

        Cache::put(SnmpService::CACHE_KEY_ESTADO, [
            'dispositivos' => $lecturas,
            'consultado_en' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        $this->table(
            ['Equipo', 'Tipo', 'IP', 'Estado', 'CPU', 'RAM', 'Disco'],
            array_map(fn (array $l) => [
                $l['nombre'],
                $l['tipo'],
                $l['ip'],
                $l['estado'],
                $l['cpu_pct'] !== null ? $l['cpu_pct'] . '%' : '-',
                $l['ram_pct'] !== null ? $l['ram_pct'] . '%' : '-',
                $l['disco_pct'] !== null ? $l['disco_pct'] . '%' : '-',
            ], $lecturas)
        );

        if ($simulacro) {
            $this->info(sprintf('Modo sin Telegram: %d alerta(s) y %d recuperado(s) sin enviar.', count($nuevasAlertas), count($recuperados)));

            return Command::SUCCESS;
        }

        if (!empty($nuevasAlertas)) {
            $this->registrarNotificaciones($nuevasAlertas, Notificacion::TIPO_ALERTA);
            $this->enviarATodos($telegram, $this->mensajeAlerta($nuevasAlertas));
            $this->info('📨 Alerta enviada por Telegram: ' . implode(', ', array_column($nuevasAlertas, 'nombre')));
        }

        if (!empty($recuperados)) {
            $this->registrarNotificaciones($recuperados, Notificacion::TIPO_RECUPERACION);
            $this->enviarATodos($telegram, $this->mensajeRecuperacion($recuperados));
            $this->info('📨 Recuperación enviada por Telegram: ' . implode(', ', array_column($recuperados, 'nombre')));
        }

        if (empty($nuevasAlertas) && empty($recuperados)) {
            $this->info('✅ Sin novedades.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array{cpu: int, ram: int, disco: int} $umbrales
     * @return array{cpu: int, ram: int, disco: int}
     */
    private function umbralesRecuperacion(array $umbrales): array
    {
        $histeresis = (int) config('infraestructura.histeresis');

        return [
            'cpu' => $umbrales['cpu'] - $histeresis,
            'ram' => $umbrales['ram'] - $histeresis,
            'disco' => $umbrales['disco'] - $histeresis,
        ];
    }

    private function cooldownMinutos(): int
    {
        return (int) config('infraestructura.cooldown_minutos');
    }

    /**
     * @param array<int, array{nombre: string, tipo: string, ip: string, estado: string, cpu_pct: float|null, ram_pct: float|null, disco_pct: float|null}> $lecturas
     */
    private function mensajeAlerta(array $lecturas): string
    {
        $mensaje = "🖥 <b>Alerta de Infraestructura</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "⚠️ Equipos con problemas:\n";

        foreach ($lecturas as $l) {
            $detalle = $l['estado'] === 'caido' ? 'no responde' : $this->detalleMetricas($l);
            $mensaje .= "\n• <b>{$l['nombre']}</b> ({$l['ip']}): {$detalle}";
        }

        return $mensaje;
    }

    /**
     * @param array<int, array{nombre: string, ip: string}> $lecturas
     */
    private function mensajeRecuperacion(array $lecturas): string
    {
        $mensaje = "🖥 <b>Infraestructura normalizada</b>\n"
            . '🕐 ' . now()->format('d/m/Y H:i') . "\n"
            . "✅ Volvieron a la normalidad:\n";

        foreach ($lecturas as $l) {
            $mensaje .= "\n• <b>{$l['nombre']}</b> ({$l['ip']})";
        }

        return $mensaje;
    }

    /**
     * @param array{cpu_pct: float|null, ram_pct: float|null, disco_pct: float|null} $lectura
     */
    private function detalleMetricas(array $lectura): string
    {
        $partes = [];

        foreach (['cpu_pct' => 'CPU', 'ram_pct' => 'RAM', 'disco_pct' => 'Disco'] as $clave => $etiqueta) {
            if ($lectura[$clave] !== null) {
                $partes[] = "{$etiqueta} {$lectura[$clave]}%";
            }
        }

        return empty($partes) ? 'sin datos SNMP' : implode(', ', $partes);
    }

    /**
     * @param array<int, array{id: int, nombre: string, tipo: string, ip: string, estado: string, cpu_pct: float|null, ram_pct: float|null, disco_pct: float|null}> $lecturas
     */
    private function registrarNotificaciones(array $lecturas, string $tipo): void
    {
        foreach ($lecturas as $lectura) {
            $esAlerta = $tipo === Notificacion::TIPO_ALERTA;

            Notificacion::create([
                'categoria' => Notificacion::CATEGORIA_INFRAESTRUCTURA,
                'tipo' => $tipo,
                'nivel' => $esAlerta ? ($lectura['estado'] === 'caido' ? 'danger' : 'warning') : 'success',
                'titulo' => $esAlerta ? "Alerta: {$lectura['nombre']}" : "Recuperado: {$lectura['nombre']}",
                'mensaje' => $esAlerta
                    ? ($lectura['estado'] === 'caido' ? 'No responde' : $this->detalleMetricas($lectura)) . " ({$lectura['ip']})"
                    : "Volvió a la normalidad ({$lectura['ip']})",
                'dispositivo_edificio_id' => $lectura['id'],
                'datos' => $lectura,
            ]);
        }
    }

    private function enviarATodos(TelegramService $telegram, string $mensaje): void
    {
        $chatIds = array_filter(array_map('trim', explode(',', (string) config('infraestructura.telegram_chat_ids'))));

        if (empty($chatIds)) {
            $telegram->enviarMensaje($mensaje);

            return;
        }

        foreach ($chatIds as $chatId) {
            $telegram->enviarMensaje($mensaje, $chatId);
        }
    }
}
