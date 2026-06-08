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
    private ?bool $think;

    public function __construct()
    {
        $this->url = rtrim((string) config('ia.url'), '/');
        $this->model = (string) config('ia.model');
        $this->timeout = (int) config('ia.timeout', 180);
        $this->keepAlive = (string) config('ia.keep_alive', '30m');
        $think = config('ia.think');
        $this->think = $think === null ? null : (bool) $think;
    }

    /**
     * Genera un resumen estructurado del evento a partir del detalle ya parseado.
     *
     * @param array<string, mixed> $detalle Resultado de CecocoExpedienteService::obtenerDetalleExpediente()
     * @return array{resumen: string, tipo: string, resultado: string, recursos: array<int, string>, personas: array<int, array{nombre: string, rol: string, dni: string}>, vehiculos: array<int, array{tipo: string, marca: string, modelo: string, color: string, distintivo: string, dominio: string}>, lugar: array{direccion: string, interseccion: string, localidad: string}, estado_final: string, modelo: string}
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

        // Modelos razonadores (qwen3): desactivar "thinking" para no disparar la latencia.
        if ($this->think !== null) {
            $payload['think'] = $this->think;
        }

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

            $salida = $this->normalizarSalida($datos);

            // Los recursos son dato duro del expediente: los tomamos de los trámites
            // parseados, no de lo que devuelva el modelo (que a veces los omite).
            $salida['recursos'] = $this->recursosDesdeDetalle($detalle);

            // Guardamos el modelo que generó el resumen para mostrarlo y trazarlo.
            $salida['modelo'] = $this->model;

            return $salida;
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
            . '"recursos": [string], '
            . '"personas": [{"nombre": string, "rol": string, "dni": string}], '
            . '"vehiculos": [{"tipo": string, "marca": string, "modelo": string, "color": string, "distintivo": string, "dominio": string}], '
            . '"lugar": {"direccion": string, "interseccion": string, "localidad": string}, '
            . '"estado_final": string}. '
            . 'No inventes datos: si algo no figura, usá string vacío o lista vacía. '
            . 'Escribí el "resumen" en tercera persona y respetá quién hizo qué: no inviertas el sujeto '
            . '(por ejemplo, si personas arrojan piedras a vehículos, NO digas que los vehículos arrojan piedras). '
            . 'En "personas" incluí solo a quienes se mencionen explícitamente (víctimas, demorados, autores, llamante). '
            . 'El campo "dni" SOLO se completa si el texto dice explícitamente "DNI" o "documento" seguido del número '
            . '(7 u 8 dígitos). NUNCA uses como DNI un número de teléfono ni un número entre paréntesis: esos son teléfonos, no documentos. '
            . 'En "vehiculos" incluí solo los vehículos mencionados explícitamente en el texto; si no se menciona ningún vehículo, devolvé lista vacía. '
            . '"tipo": moto, auto, camioneta, camión, bicicleta, colectivo, etc. "dominio" es la patente: copialo TAL CUAL aparece en el texto, '
            . 'solo si figura una patente real; si no figura, dejá "dominio" vacío. Nunca inventes una patente. '
            . '"distintivo": rasgos particulares (calcomanías, daños, inscripciones, llantas, etc.). '
            . 'En "lugar": "direccion" = calle y altura/numeral del hecho; "interseccion" = las dos calles si es una esquina o cruce; '
            . '"localidad" = ciudad o localidad (ej. Paraná, San Benito). Usá la dirección, barrio y municipio que te paso como referencia.';
    }

    /**
     * Extrae las unidades/móviles asignados desde los trámites parseados del expediente.
     *
     * @param array<string, mixed> $detalle
     * @return array<int, string>
     */
    private function recursosDesdeDetalle(array $detalle): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($t) => trim((string) ($t['unidad'] ?? $t['tr_amites'] ?? '')),
            $detalle['tramites'] ?? []
        ))));
    }

    /**
     * @param array<string, mixed> $detalle
     */
    private function construirEntrada(array $detalle): string
    {
        $recursos = $this->recursosDesdeDetalle($detalle);
        $historial = $detalle['historial'] ?? [];

        $lineas = [
            'Expediente: ' . ($detalle['nro_expediente'] ?? ''),
            'Tipo de servicio: ' . ($detalle['tipo_servicio'] ?? ''),
            'Dirección: ' . ($detalle['direccion'] ?? ''),
            'Barrio: ' . ($historial['barrio'] ?? ''),
            'Jurisdicción: ' . ($historial['jurisdiccion'] ?? ''),
            'Municipio/Localidad: ' . ($historial['municipio'] ?? ''),
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
                'dni' => $this->dniValido((string) ($persona['dni'] ?? '')),
            ];
        }

        $recursos = [];
        foreach ($datos['recursos'] ?? [] as $recurso) {
            if (is_string($recurso) && trim($recurso) !== '') {
                $recursos[] = trim($recurso);
            }
        }

        $vehiculos = [];
        foreach ($datos['vehiculos'] ?? [] as $vehiculo) {
            if (!is_array($vehiculo)) {
                continue;
            }
            $v = [
                'tipo' => trim((string) ($vehiculo['tipo'] ?? '')),
                'marca' => trim((string) ($vehiculo['marca'] ?? '')),
                'modelo' => trim((string) ($vehiculo['modelo'] ?? '')),
                'color' => trim((string) ($vehiculo['color'] ?? '')),
                'distintivo' => trim((string) ($vehiculo['distintivo'] ?? '')),
                'dominio' => strtoupper(trim((string) ($vehiculo['dominio'] ?? ''))),
            ];
            // Descartar vehículos totalmente vacíos
            if (implode('', $v) !== '') {
                $vehiculos[] = $v;
            }
        }

        $lugarRaw = is_array($datos['lugar'] ?? null) ? $datos['lugar'] : [];
        $lugar = [
            'direccion' => trim((string) ($lugarRaw['direccion'] ?? ($datos['direccion'] ?? ''))),
            'interseccion' => trim((string) ($lugarRaw['interseccion'] ?? '')),
            'localidad' => trim((string) ($lugarRaw['localidad'] ?? '')),
        ];

        return [
            'resumen' => (string) ($datos['resumen'] ?? ''),
            'tipo' => (string) ($datos['tipo'] ?? ''),
            'resultado' => (string) ($datos['resultado'] ?? ''),
            'recursos' => $recursos,
            'personas' => $personas,
            'vehiculos' => $vehiculos,
            'lugar' => $lugar,
            'estado_final' => (string) ($datos['estado_final'] ?? ''),
        ];
    }

    /**
     * Valida que el DNI tenga 7 u 8 dígitos. Si tiene más (típico de un teléfono que
     * el modelo confundió) o ninguno, lo descarta.
     */
    private function dniValido(string $dni): string
    {
        $digitos = preg_replace('/\D+/', '', $dni);
        $largo = strlen((string) $digitos);

        return ($largo === 7 || $largo === 8) ? $dni : '';
    }
}
