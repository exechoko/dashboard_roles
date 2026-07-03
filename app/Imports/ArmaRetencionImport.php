<?php

namespace App\Imports;

use App\Models\ArmaMotivo;
use App\Models\ArmaRetencion;
use App\Models\Personal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ArmaRetencionImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $created = 0;
    private $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                // Buscar o crear personal
                $personal = $this->buscarOCrearPersonal($row);

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

                // Determinar tipo según el motivo
                $tipo = $motivo->tipo_asignado;

                // Crear retención
                ArmaRetencion::create([
                    'personal_id' => $personal->id,
                    'numeracion_arma' => $row['numeracion_arma'] ?? '',
                    'nro_chaleco' => $row['nro_chaleco'] ?? null,
                    'tipo' => $tipo,
                    'motivo_id' => $motivo->id,
                    'fecha_posesion' => $this->parseDate($row['fecha_posesion'] ?? now()),
                    'dias_restantes' => $row['dias_restantes'] ?? null,
                    'fecha_elevacion' => isset($row['fecha_elevacion']) ? $this->parseDate($row['fecha_elevacion']) : null,
                    'fecha_devolucion' => isset($row['fecha_devolucion']) ? $this->parseDate($row['fecha_devolucion']) : null,
                    'observaciones' => $row['observaciones'] ?? null,
                    'estado' => $this->determinarEstado($row),
                    'created_by' => Auth::id(),
                ]);

                $this->created++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$row['fila']}: " . $e->getMessage();
            }
        }
    }

    private function buscarOCrearPersonal($row): ?Personal
    {
        $lp = $row['legajo'] ?? null;
        $nombre = $row['nombre'] ?? '';
        $apellido = $row['apellido'] ?? '';
        $jerarquia = $row['jerarquia'] ?? '';

        // Buscar por legajo si existe
        if ($lp) {
            $personal = Personal::where('lp', $lp)->first();
            if ($personal) {
                return $personal;
            }
        }

        // Buscar por nombre y apellido
        if ($nombre && $apellido) {
            $personal = Personal::where('nombre', 'like', "%{$nombre}%")
                ->where('apellido', 'like', "%{$apellido}%")
                ->first();
            if ($personal) {
                return $personal;
            }
        }

        // Crear nuevo personal si no existe
        if ($nombre && $apellido && $lp) {
            return Personal::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'lp' => $lp,
                'jerarquia' => $jerarquia,
            ]);
        }

        return null;
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

    private function determinarEstado($row): string
    {
        if (!empty($row['fecha_devolucion'])) {
            return 'DEVUELTA';
        }

        if (!empty($row['fecha_elevacion'])) {
            return 'EN_JEF_CENTRAL';
        }

        return 'EN_ARMERIA';
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
