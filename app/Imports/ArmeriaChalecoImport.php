<?php

namespace App\Imports;

use App\Models\ArmeriaChaleco;
use App\Services\ArmeriaMovimientoService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa la "PLANILLA RELEVAMIENTO CHALECOS", cuyo encabezado real
 * (ORDEN, MOVIL, MARCA, MODELO/PROTECCION, TALLE, SERIE, EN SERVICIO)
 * está en la fila 6.
 */
class ArmeriaChalecoImport implements ToCollection, WithHeadingRow
{
    private int $created = 0;

    private int $omitidos = 0;

    private int $omitidosEliminados = 0;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(private ArmeriaMovimientoService $service)
    {
    }

    public function headingRow(): int
    {
        return 6;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $numeroSerie = trim((string) ($row['serie'] ?? ''));

            if ($numeroSerie === '') {
                continue;
            }

            if (ArmeriaChaleco::withTrashed()->where('numero_serie', $numeroSerie)->exists()) {
                if (ArmeriaChaleco::onlyTrashed()->where('numero_serie', $numeroSerie)->exists()) {
                    $this->omitidosEliminados++;
                } else {
                    $this->omitidos++;
                }
                continue;
            }

            try {
                $talle = strtoupper(trim((string) ($row['talle'] ?? '')));
                if (!in_array($talle, ArmeriaChaleco::TALLES, true)) {
                    $talle = null;
                }

                [$estado, $ubicacion] = $this->interpretarEstado((string) ($row['en_servicio'] ?? ''));

                $this->service->crear(ArmeriaChaleco::class, [
                    'movil' => trim((string) ($row['movil'] ?? '')) ?: null,
                    'marca' => trim((string) ($row['marca'] ?? '')) ?: null,
                    'modelo' => trim((string) ($row['modelo_proteccion'] ?? '')) ?: null,
                    'talle' => $talle,
                    'numero_serie' => $numeroSerie,
                    'estado' => $estado,
                    'ubicacion' => $ubicacion,
                    'comentario' => 'Importado desde planilla de relevamiento de chalecos.',
                ]);

                $this->created++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila con serie '{$numeroSerie}': " . $e->getMessage();
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function interpretarEstado(string $valor): array
    {
        $valor = strtoupper($valor);

        $estado = 'EN_SERVICIO';
        if (str_contains($valor, 'BAJA')) {
            $estado = 'DE_BAJA';
        } elseif (str_contains($valor, 'REPAR')) {
            $estado = 'EN_REPARACION';
        }

        $ubicacion = (str_contains($valor, 'JEFATURA') || str_contains($valor, 'CENTRAL'))
            ? 'JEFATURA_CENTRAL'
            : 'DIVISION_911';

        return [$estado, $ubicacion];
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getOmitidos(): int
    {
        return $this->omitidos;
    }

    public function getOmitidosEliminados(): int
    {
        return $this->omitidosEliminados;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
