<?php

namespace App\Imports;

use App\Imports\Concerns\ParseaFechaExcel;
use App\Models\DominioAlerta;
use App\Services\AlertaVideoService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

/**
 * Importa la hoja "Vehiculos" de la planilla "Registro Dominios y rostros
 * en sistema de Video", cuyo encabezado real está en la fila 3. Se leen
 * las celdas por posición (no por nombre de columna) porque el último
 * encabezado ("Procedimiento (...)") no se puede mapear de forma fiable.
 *
 * Se acotan filas y columnas porque hojas legadas suelen reportar un rango
 * usado inflado (formato aplicado a columnas/filas completas).
 */
class DominioAlertaSheetImport implements ToCollection, WithHeadingRow, WithLimit, WithColumnLimit
{
    use ParseaFechaExcel;

    public function limit(): int
    {
        return 5000;
    }

    public function endColumn(): string
    {
        return 'N';
    }

    private int $created = 0;

    private int $omitidos = 0;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(private AlertaVideoService $service)
    {
    }

    public function headingRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $valores = $row->values()->all();
            $dominio = DominioAlerta::normalizar($valores[1] ?? '');

            if ($dominio === '' || $dominio === '-') {
                continue;
            }

            if (DominioAlerta::withTrashed()->where('dominio', $dominio)->exists()) {
                $this->omitidos++;
                continue;
            }

            try {
                $this->service->crear(DominioAlerta::class, [
                    'dominio' => $dominio,
                    'marca' => $this->limpiar($valores[2] ?? null),
                    'modelo' => $this->limpiar($valores[3] ?? null),
                    'color' => $this->limpiar($valores[4] ?? null),
                    'solicitado_por' => $this->limpiar($valores[5] ?? null),
                    'fecha_carga' => $this->parsearFecha($valores[6] ?? null),
                    'funcionario_carga' => $this->limpiar($valores[7] ?? null),
                    'motivo' => $this->limpiar($valores[8] ?? null),
                    'activo' => $this->esActivo($valores[9] ?? null),
                    'camara_texto' => $this->limpiar($valores[10] ?? null),
                    'observaciones' => $this->limpiar($valores[11] ?? null),
                    'comentario' => 'Importado desde la planilla de Dominios y Personas.',
                ]);

                $this->created++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila con dominio '{$dominio}': " . $e->getMessage();
            }
        }
    }

    private function limpiar(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return ($valor === '' || $valor === '-') ? null : $valor;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getOmitidos(): int
    {
        return $this->omitidos;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
