<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ResumenEventoIaService
{
    private string $url;
    private string $model;
    private int $timeout;
    private string $keepAlive;

    public function __construct()
    {
        $this->url = rtrim((string) config('ia.url'), '/');
        $this->model = (string) config('ia.model');
        $this->timeout = (int) config('ia.timeout', 180);
        $this->keepAlive = (string) config('ia.keep_alive', '30m');
    }

    /**
     * Genera un resumen estructurado del evento a partir del detalle ya parseado.
     *
     * @param array<string, mixed> $detalle Resultado de CecocoExpedienteService::obtenerDetalleExpediente()
     * @return array{resumen: string, tipo: string, resultado: string, recursos: array<int, string>, personas: array<int, array{nombre: string, rol: string, dni: string}>, direccion: string, estado_final: string}
     */
    public function resumir(array $detalle): array
    {
        if (!config('ia.enabled')) {
            throw new Exception('La función de resumen con IA está desactivada.');
        }

        $payload = [
            'model' => $this->model,
            'stream' => false,
            'format' => 'json',
            'keep_alive' => $this->keepAlive,
            'options' => [
                'temperature' => 0.2,
                'num_ctx' => 4096,
            ],
            'messages' => [
                ['role' => 'system', 'content' => $this->promptSistema()],
                ['role' => 'user', 'content' => $this->construirEntrada($detalle)],
            ],
        ];

        try {
            $respuesta = Http::timeout($this->timeout)
                ->post($this->url . '/api/chat', $payload);

            if (!$respuesta->successful()) {
                throw new Exception('El servidor de IA respondió con estado ' . $respuesta->status());
            }

            $contenido = (string) $respuesta->json('message.content');
            $datos = json_decode($contenido, true);

            if (!is_array($datos)) {
                throw new Exception('La respuesta de la IA no es un JSON válido.');
            }

            return $this->normalizarSalida($datos);
        } catch (Exception $e) {
            Log::error('Error al generar resumen IA del evento', ['error' => $e->getMessage()]);
            throw new Exception('No se pudo generar el resumen con IA: ' . $e->getMessage());
        }
    }

    private function promptSistema(): string
    {
        return 'Sos un asistente del centro de comando 911 de la policía. Te paso los datos de un evento '
            . 'policial. Devolvé SOLO un JSON válido (sin texto adicional) con esta estructura exacta: '
            . '{"resumen": string de 2 a 3 frases en español neutro, "tipo": string, "resultado": string corto, '
            . '"recursos": [string], "personas": [{"nombre": string, "rol": string, "dni": string}], '
            . '"direccion": string, "estado_final": string}. '
            . 'No inventes datos: si algo no figura, usá string vacío o lista vacía. '
            . 'En "personas" incluí solo a quienes se mencionen explícitamente (víctimas, demorados, autores, llamante).';
    }

    /**
     * @param array<string, mixed> $detalle
     */
    private function construirEntrada(array $detalle): string
    {
        $recursos = array_values(array_filter(array_map(
            static fn ($t) => $t['unidad'] ?? $t['tr_amites'] ?? '',
            $detalle['tramites'] ?? []
        )));

        $lineas = [
            'Expediente: ' . ($detalle['nro_expediente'] ?? ''),
            'Tipo de servicio: ' . ($detalle['tipo_servicio'] ?? ''),
            'Dirección: ' . ($detalle['direccion'] ?? ''),
            'Fecha: ' . ($detalle['fecha_hora_inicial'] ?? ''),
            'Descripción: ' . ($detalle['descripcion_inicial'] ?? ''),
            'Observaciones de cierre: ' . ($detalle['cierre']['observaciones'] ?? ''),
            'Recursos asignados: ' . implode(', ', $recursos),
        ];

        return implode("\n", $lineas);
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{resumen: string, tipo: string, resultado: string, recursos: array<int, string>, personas: array<int, array{nombre: string, rol: string, dni: string}>, direccion: string, estado_final: string}
     */
    private function normalizarSalida(array $datos): array
    {
        $personas = [];
        foreach ($datos['personas'] ?? [] as $persona) {
            if (!is_array($persona)) {
                continue;
            }
            $personas[] = [
                'nombre' => (string) ($persona['nombre'] ?? ''),
                'rol' => (string) ($persona['rol'] ?? ''),
                'dni' => (string) ($persona['dni'] ?? ''),
            ];
        }

        $recursos = [];
        foreach ($datos['recursos'] ?? [] as $recurso) {
            if (is_string($recurso) && trim($recurso) !== '') {
                $recursos[] = trim($recurso);
            }
        }

        return [
            'resumen' => (string) ($datos['resumen'] ?? ''),
            'tipo' => (string) ($datos['tipo'] ?? ''),
            'resultado' => (string) ($datos['resultado'] ?? ''),
            'recursos' => $recursos,
            'personas' => $personas,
            'direccion' => (string) ($datos['direccion'] ?? ''),
            'estado_final' => (string) ($datos['estado_final'] ?? ''),
        ];
    }
}
