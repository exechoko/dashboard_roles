<?php

namespace App\Imports;

use App\Models\ArmaMotivo;
use App\Models\Personal;
use App\Services\ArmaRetencionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ArmaRetencionImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $created = 0;
    private $errors = [];

    public function __construct(private ArmaRetencionService $service)
    {
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $buscarTipo = $this->buscarTipoArma($row['modelo_arma'] ?? '');

                // Buscar o crear personal
                $personal = $this->buscarOCrearPersonal($row, $buscarTipo);

                if (!$personal) {
                    $this->errors[] = "Fila {$row['fila']}: No se pudo encontrar/crear el personal";
                    continue;
                }

                // Buscar motivo
                $motivo = $this->buscarMotivo($row['motivo'] ?? '');

                if (!$motivo) {
                    $this->errors[] = "Fila {$row['fila']}: Motivo no válido '{$row['motivo']}'";
                    continue;
                }

                $this->service->crear([
                    'personal_id' => $personal->id,
                    'motivo_id' => $motivo->id,
                    'fecha_posesion' => $this->parseDate($row['fecha_posesion'] ?? now()),
                    'fecha_elevacion' => isset($row['fecha_elevacion']) ? $this->parseDate($row['fecha_elevacion']) : null,
                    'fecha_devolucion' => isset($row['fecha_devolucion']) ? $this->parseDate($row['fecha_devolucion']) : null,
                    'observaciones' => $row['observaciones'] ?? null,
                ]);

                $this->created++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$row['fila']}: " . $e->getMessage();
            }
        }
    }

    private function buscarOCrearPersonal($row, ?int $armaTipoId): ?Personal
    {
        $lp = $row['legajo'] ?? null;
        $nombre = $row['nombre'] ?? '';
        $apellido = $row['apellido'] ?? '';
        $jerarquia = $row['jerarquia'] ?? '';

        // Buscar por legajo si existe
        if ($lp) {
            $personal = Personal::where('lp', $lp)->first();
            if ($personal) {
                $this->asegurarInventario($personal, $row, $armaTipoId);
                return $personal;
            }
        }

        // Buscar por nombre y apellido
        if ($nombre && $apellido) {
            $personal = Personal::where('nombre', 'like', "%{$nombre}%")
                ->where('apellido', 'like', "%{$apellido}%")
                ->first();
            if ($personal) {
                $this->asegurarInventario($personal, $row, $armaTipoId);
                return $personal;
            }
        }

        // Crear nuevo personal si no existe
        if ($nombre && $apellido && $lp) {
            $personal = Personal::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'lp' => $lp,
                'jerarquia' => $jerarquia,
            ]);

            $this->asegurarInventario($personal, $row, $armaTipoId);

            return $personal;
        }

        return null;
    }

    private function buscarTipoArma(string $nombre): ?int
    {
        $tipo = \App\Models\ArmaTipo::where('nombre', 'like', "%{$nombre}%")->first();

        return $tipo?->id;
    }

    private function asegurarInventario(Personal $personal, Collection $row, ?int $armaTipoId): void
    {
        $numeroArma = trim((string) ($row['numeracion_arma'] ?? ''));

        if ($personal->tieneArmaAsignada() || $numeroArma === '' || $armaTipoId === null) {
            return;
        }

        $personal->cambiarArma(
            $numeroArma,
            $armaTipoId,
            $row['nro_chaleco'] ?? null,
            $this->parseDate($row['fecha_posesion'] ?? now()) ?? now()->toDateString(),
            'Asignación creada durante importación de retenciones'
        );
    }

    private function buscarMotivo(string $motivoNombre): ?ArmaMotivo
    {
        return ArmaMotivo::where('nombre', 'like', "%{$motivoNombre}%")
            ->where('activo', true)
            ->first();
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Intentar parsear diferentes formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return now()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'legajo' => 'required|string',
            'numeracion_arma' => 'required|string',
            'motivo' => 'required|string',
            'fecha_posesion' => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'legajo.required' => 'El legajo es obligatorio',
            'numeracion_arma.required' => 'La numeración del arma es obligatoria',
            'motivo.required' => 'El motivo es obligatorio',
            'fecha_posesion.required' => 'La fecha de posesión es obligatoria',
        ];
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
