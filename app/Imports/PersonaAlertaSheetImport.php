<?php

namespace App\Imports;

use App\Imports\Concerns\ParseaFechaExcel;
use App\Models\PersonaAlerta;
use App\Services\AlertaVideoService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

/**
 * Importa la hoja "Personas" de la planilla "Registro Dominios y rostros
 * en sistema de Video", cuyo encabezado real está en la fila 3. Se leen
 * las celdas por posición porque hay filas con D.N.I. desconocido ("-").
 *
 * La hoja legada reporta un rango usado inflado (formato aplicado a
 * columnas/filas completas), por lo que se acotan filas y columnas para
 * evitar que el reader intente procesar más de un millón de celdas vacías.
 */
class PersonaAlertaSheetImport implements ToCollection, WithHeadingRow, WithLimit, WithColumnLimit
{
    use ParseaFechaExcel;

    public function limit(): int
    {
        return 5000;
    }

    public function endColumn(): string
    {
        return 'M';
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
            $apellidoNombre = trim((string) ($valores[2] ?? ''));

            if ($apellidoNombre === '') {
                continue;
            }

            $dni = PersonaAlerta::normalizarDni($valores[1] ?? null);

            $existe = $dni !== null
                ? PersonaAlerta::withTrashed()->where('dni', $dni)->exists()
                : PersonaAlerta::withTrashed()->whereRaw('UPPER(apellido_nombre) = ?', [strtoupper($apellidoNombre)])->exists();

            if ($existe) {
                $this->omitidos++;
                continue;
            }

            try {
                $this->service->crear(PersonaAlerta::class, [
                    'dni' => $dni,
                    'apellido_nombre' => $apellidoNombre,
                    'direccion' => $this->limpiar($valores[3] ?? null),
                    'solicitado_por' => $this->limpiar($valores[4] ?? null),
                    'fecha_carga' => $this->parsearFecha($valores[5] ?? null),
                    'funcionario_carga' => $this->limpiar($valores[6] ?? null),
                    'motivo' => $this->limpiar($valores[7] ?? null),
                    'activo' => $this->esActivo($valores[8] ?? null),
                    'identificado' => $this->esSi($valores[9] ?? null),
                    'comentario' => 'Importado desde la planilla de Dominios y Personas.',
                ]);

                $this->created++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila con '{$apellidoNombre}': " . $e->getMessage();
            }
        }
    }

    private function esSi(mixed $valor): bool
    {
        return strtoupper(trim((string) $valor)) === 'SI';
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
