<?php

namespace App\Services;

use App\Models\LlamadaCentralTelefonica;
use Illuminate\Support\Carbon;

class LlamadaCentralTelefonicaImportService
{
    private const CHUNK = 1000;

    /**
     * Importa (o actualiza) los CDR de un CSV de la central telefonica.
     *
     * @return array{total: int, omitidos: int}
     */
    public function importarArchivo(string $rutaAbsoluta, string $nombreOriginal): array
    {
        $handle = fopen($rutaAbsoluta, 'r');

        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$nombreOriginal}");
        }

        fgetcsv($handle);

        $lote = [];
        $total = 0;
        $omitidos = 0;

        while (($fila = fgetcsv($handle)) !== false) {
            $registro = $this->mapearFila($fila, $nombreOriginal);

            if ($registro === null) {
                $omitidos++;

                continue;
            }

            $lote[] = $registro;
            $total++;

            if (count($lote) >= self::CHUNK) {
                $this->guardarLote($lote);
                $lote = [];
            }
        }

        if (!empty($lote)) {
            $this->guardarLote($lote);
        }

        fclose($handle);

        return ['total' => $total, 'omitidos' => $omitidos];
    }

    /**
     * @param array<int, string|null> $fila
     * @return array<string, mixed>|null
     */
    private function mapearFila(array $fila, string $nombreArchivo): ?array
    {
        [$uid, $calldate, $ani, $dialedNumber, $finalDnis, $forwardedTo, $duration, $billDuration] = array_pad($fila, 8, null);

        if (empty($uid) || empty($calldate)) {
            return null;
        }

        try {
            $fecha = Carbon::parse($calldate);
        } catch (\Throwable $e) {
            return null;
        }

        $ani = $ani !== '' ? $ani : null;
        $finalDnis = $finalDnis !== '' ? $finalDnis : null;
        $billDuration = (int) $billDuration;

        return [
            'uid' => $uid,
            'calldate' => $fecha->format('Y-m-d H:i:s'),
            'ani' => $ani,
            'dialed_number' => $dialedNumber !== '' ? $dialedNumber : null,
            'final_dnis' => $finalDnis,
            'forwarded_to' => $forwardedTo !== '' ? $forwardedTo : null,
            'tipo_llamada' => $this->clasificar($ani, $finalDnis),
            'duration' => (int) $duration,
            'bill_duration' => $billDuration,
            'atendida' => $billDuration > 0,
            'periodo' => $fecha->format('Y-m'),
            'anio' => (int) $fecha->format('Y'),
            'mes' => (int) $fecha->format('n'),
            'archivo_origen' => $nombreArchivo,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Clasifica la llamada segun el conocimiento del operador de la central:
     * - Final DNIS 9999: linea general por la que ingresan las llamadas del publico a CeCoCo.
     * - ANI 8021: troncal saliente por la que CeCoCo llama hacia afuera (despacho a 107, avisos, etc).
     * - ANI y Final DNIS en el rango 5000-5999: intercomunicador entre extensiones internas.
     * - Cualquier otro caso (caller ID en blanco/anomalo, extensiones fuera del rango 5000, etc.) queda como "otra".
     */
    private function clasificar(?string $ani, ?string $finalDnis): string
    {
        if ($finalDnis === '9999') {
            return LlamadaCentralTelefonica::TIPO_RECIBIDA;
        }

        if ($ani === '8021') {
            return LlamadaCentralTelefonica::TIPO_SALIENTE;
        }

        if ($this->esNumeroInterno($ani) && $this->esNumeroInterno($finalDnis)) {
            return LlamadaCentralTelefonica::TIPO_INTERNA;
        }

        return LlamadaCentralTelefonica::TIPO_OTRA;
    }

    private function esNumeroInterno(?string $numero): bool
    {
        return $numero !== null && preg_match('/^5\d{3}$/', $numero) === 1;
    }

    /**
     * @param array<int, array<string, mixed>> $lote
     */
    private function guardarLote(array $lote): void
    {
        LlamadaCentralTelefonica::upsert(
            $lote,
            ['uid'],
            ['calldate', 'ani', 'dialed_number', 'final_dnis', 'forwarded_to', 'tipo_llamada', 'duration', 'bill_duration', 'atendida', 'periodo', 'anio', 'mes', 'archivo_origen', 'updated_at']
        );
    }
}
