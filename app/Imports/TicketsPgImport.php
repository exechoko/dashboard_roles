<?php

namespace App\Imports;

use App\Models\TicketTicketera;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Importa el histórico de tickets PG desde la hoja "Patagonia" del archivo
 * "CONTROL DE INCIDENCIAS 911.xlsm".
 *
 * Columnas (0-indexed):
 * 0=A Inc. (PG/aa-nnn), 1=B Ticket (Nro.), 2=C ID de Ref. (tracking HESK),
 * 3=D Fecha Plat. Web,
 * 4=E F. Inicio Inc., 6=G F. Solución, 8=I Categoría, 9=J Prior. Ticket,
 * 10=K Apl., 11=L Per. Fact., 12=M Estado, 13=N Incidencia / Prestación,
 * 14=O F. FIN falla, 15=P Respuestas P.G., 16=Q Subsist., 24=Y Cant. Items,
 * 29=AD Equipo (Modelo)
 *
 * La hoja tiene filas plantilla precargadas al final (solo código + estado
 * "Nuevo", sin fechas ni descripción): se omiten porque no son tickets
 * generados. Los códigos ya existentes en la base también se omiten, por lo
 * que la importación puede repetirse sin generar duplicados.
 */
class TicketsPgImport implements WithMultipleSheets, ToModel, WithStartRow, WithCalculatedFormulas
{
    public int $importados = 0;
    public int $omitidosExistentes = 0;
    public int $omitidosPlantilla = 0;

    /** @var array<int, int> Mayor número real detectado por año (aa => nnn), para sincronizar la secuencia. */
    public array $maximosPorAnio = [];

    /** @var array<string, true> Códigos internos ya cargados en la base. */
    private array $codigosExistentes;

    public function __construct(private string $nombreHojaExcel = 'Patagonia')
    {
        $this->codigosExistentes = TicketTicketera::query()
            ->pluck('codigo_interno')
            ->flip()
            ->all();
    }

    public function sheets(): array
    {
        return [$this->nombreHojaExcel => $this];
    }

    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row): ?TicketTicketera
    {
        $codigo = trim((string) ($row[0] ?? ''));
        if (!preg_match('#^PG/(\d{2})-(\d+)$#', $codigo, $partes)) {
            return null;
        }

        $fechaWeb    = $this->parsearFecha($row[3] ?? null);
        $fechaInicio = $this->parsearFecha($row[4] ?? null);
        $descripcion = trim((string) ($row[13] ?? ''));

        $esFilaPlantilla = $fechaWeb === null
            && $fechaInicio === null
            && ($descripcion === '' || $descripcion === $codigo);

        if ($esFilaPlantilla) {
            $this->omitidosPlantilla++;
            return null;
        }

        $anio   = (int) $partes[1];
        $numero = (int) $partes[2];
        $this->maximosPorAnio[$anio] = max($this->maximosPorAnio[$anio] ?? 0, $numero);

        if (isset($this->codigosExistentes[$codigo])) {
            $this->omitidosExistentes++;
            return null;
        }
        $this->codigosExistentes[$codigo] = true;

        $codigoTicketera = trim((string) ($row[1] ?? ''));
        $referencia      = strtoupper(trim((string) ($row[2] ?? '')));
        $categoria       = $this->normalizarCategoria(trim((string) ($row[8] ?? '')));
        $problema        = trim((string) preg_replace('#^' . preg_quote($codigo, '#') . '\s*#u', '', $descripcion));
        $asunto          = $problema !== '' ? $problema : trim($codigo . ' ' . $categoria);
        $aplicaRaw       = strtolower(trim((string) ($row[10] ?? 'si')));
        $respuestasPg    = trim((string) ($row[15] ?? ''));
        $cantidadItems   = $row[24] ?? null;

        $this->importados++;

        $ticket = new TicketTicketera([
            'codigo_interno'        => $codigo,
            'codigo_ticketera'      => $codigoTicketera !== '' ? mb_substr($codigoTicketera, 0, 80) : null,
            'referencia_ticketera'  => $referencia !== '' ? mb_substr($referencia, 0, 40) : null,
            'url_seguimiento'       => $this->urlSeguimiento($referencia),
            'asunto'                => mb_substr($asunto, 0, 200),
            'texto_enviado'         => $descripcion !== '' ? $descripcion : $asunto,
            'tipo_equipo'           => $categoria !== '' ? mb_substr($categoria, 0, 80) : null,
            'modelo_equipo'         => $this->valorTexto($row[29] ?? null, 80),
            'problema_detectado'    => $problema !== '' ? mb_substr($problema, 0, 250) : null,
            'fecha_inicio_falla'    => $fechaInicio,
            'fecha_fin_falla'       => $this->parsearFecha($row[14] ?? null) ?? $this->parsearFecha($row[6] ?? null),
            'prioridad'             => $this->valorTexto($row[9] ?? null, 40) ?? 'Alto',
            'subsistema'            => $this->valorTexto($row[16] ?? null, 250),
            'cantidad_items'        => is_numeric($cantidadItems) ? (int) $cantidadItems : null,
            'periodo_facturado'     => $this->valorTexto($row[11] ?? null, 30),
            'aplica_calculo'        => !in_array($aplicaRaw, ['no', 'false', '0'], true),
            'observaciones'         => $respuestasPg !== '' ? mb_substr($respuestasPg, 0, 2000) : null,
            'estado_envio'          => $codigoTicketera !== '' ? 'importado' : 'borrador',
            'estado_ticketera'      => $this->valorTexto($row[12] ?? null, 80),
            'enviado_en'            => $codigoTicketera !== '' ? ($fechaWeb ?? $fechaInicio) : null,
        ]);

        $ticket->created_at = $fechaWeb ?? $fechaInicio;

        return $ticket;
    }

    /**
     * Empareja la categoría del Excel con la lista controlada del config
     * ignorando mayúsculas y tildes ("Camara" => "Cámara", "CeCoco" => "Cecoco"),
     * para que el ticket importado sea editable con el select del formulario.
     */
    private function normalizarCategoria(string $categoria): string
    {
        if ($categoria === '') {
            return $categoria;
        }

        $slug = self::ALIAS_CATEGORIAS[Str::slug($categoria)] ?? Str::slug($categoria);

        foreach (config('ticketera_categorias.categorias', []) as $categoriaValida) {
            if (Str::slug($categoriaValida) === $slug) {
                return $categoriaValida;
            }
        }

        return $categoria;
    }

    /** @var array<string, string> Typos conocidos de la planilla (slug => slug correcto). */
    private const ALIAS_CATEGORIAS = [
        'intenet' => 'internet',
    ];

    /**
     * URL pública de seguimiento en HESK a partir del ID de referencia,
     * misma convención que TicketeraService (ticket.php?track=REF).
     */
    private function urlSeguimiento(string $referencia): ?string
    {
        $urlBase = rtrim((string) config('services.ticketera.url'), '/');
        if ($referencia === '' || $urlBase === '') {
            return null;
        }

        $urlBase = (string) preg_replace('#/admin(/.*)?$#', '', $urlBase);
        $urlBase = (string) preg_replace('#/[^/]+\.php$#', '', $urlBase);

        return $urlBase . '/ticket.php?track=' . $referencia;
    }

    private function valorTexto(mixed $valor, int $maximo): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' ? mb_substr($texto, 0, $maximo) : null;
    }

    private function parsearFecha(mixed $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }
        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d H:i:s');
            }
            $limpio = trim((string) $valor);
            foreach (['d/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $formato) {
                try {
                    return Carbon::createFromFormat($formato, $limpio)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    continue;
                }
            }
        } catch (\Exception $e) {
            // ignorar valores no parseables
        }

        return null;
    }
}
