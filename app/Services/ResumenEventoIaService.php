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
     * @return array{resumen: string, mensaje: string, tipo: string, resultado: string, recursos: array<int, string>, personas: array<int, array{nombre: string, rol: string, dni: string}>, vehiculos: array<int, array{tipo: string, marca: string, modelo: string, color: string, distintivo: string, dominio: string}>, lugar: array{direccion: string, interseccion: string, localidad: string}, estado_final: string, modelo: string}
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
                'num_ctx' => 8192,
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

            // Texto listo para copiar y enviar por mensaje (fecha/hora + narrativa).
            $salida['mensaje'] = $this->mensajeParaEnviar($detalle, $salida['resumen']);

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
            . '{"resumen": string, "tipo": string, "resultado": string corto, '
            . '"recursos": [string], '
            . '"personas": [{"nombre": string, "rol": string, "dni": string}], '
            . '"vehiculos": [{"tipo": string, "marca": string, "modelo": string, "color": string, "distintivo": string, "dominio": string}], '
            . '"lugar": {"direccion": string, "interseccion": string, "localidad": string}, '
            . '"estado_final": string}. '
            . 'No inventes datos: si algo no figura, usá string vacío o lista vacía. '
            . 'El "resumen" es un parte policial narrativo de hasta 3 párrafos separados por un salto de línea (\n): '
            . '1) cómo se origina el evento: quién se comunica al 911 (si figura el nombre del llamante), '
            . 'el lugar del hecho y qué informa, por ejemplo "Se comunica la Sra. X al 911 informando que en calle Y..."; '
            . '2) los móviles o recursos comisionados y a cargo de quién (jerarquía y apellido, si figuran en las novedades) '
            . 'y qué hicieron en el lugar, por ejemplo "Se comisionó al móvil 901, a cargo del Sargento X, cuyos efectivos..."; '
            . '3) cómo concluye la intervención, empezando con "Finalmente, ...", por ejemplo '
            . '"Finalmente, la intervención concluyó sin novedad.". '
            . 'Si no figuran móviles o no figura el cierre, omití ese párrafo; NO empieces el resumen con la fecha. '
            . 'Incluí en la narrativa TODOS los datos aportados sobre las personas o vehículos involucrados: '
            . 'vestimenta, tatuajes, características físicas, edad aproximada, apodos, dirección de fuga, patente, etc. '
            . 'Esos datos sirven para identificar a los involucrados y no se pueden perder. '
            . 'Escribí el "resumen" en tercera persona, en pasado, y respetá quién hizo qué: no inviertas el sujeto '
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
            'Novedades del evento (en orden cronológico):',
        ];

        foreach ($this->novedadesDesdeDetalle($detalle) as $novedad) {
            $lineas[] = '- ' . $novedad;
        }

        $lineas[] = 'Observaciones de cierre: ' . ($detalle['cierre']['observaciones'] ?? '');
        $lineas[] = 'Recursos asignados: ' . implode(', ', $recursos);

        return implode("\n", $lineas);
    }

    /**
     * Devuelve las descripciones del timeline (novedades de los operadores), que es donde
     * figura a quién se comisiona y a cargo de quién está cada móvil. Se acota el total
     * para no exceder la ventana de contexto del modelo.
     *
     * @param array<string, mixed> $detalle
     * @return array<int, string>
     */
    private function novedadesDesdeDetalle(array $detalle, int $maxCaracteres = 6000): array
    {
        $novedades = [];
        $acumulado = 0;

        foreach ($detalle['timeline'] ?? [] as $evento) {
            $descripcion = trim((string) ($evento['descripcion'] ?? ''));
            if ($descripcion === '' || $descripcion === '-') {
                continue;
            }

            $linea = trim((string) ($evento['fecha_hora'] ?? '')) . ' ' . $descripcion;
            $acumulado += strlen($linea);
            if ($acumulado > $maxCaracteres) {
                break;
            }

            $novedades[] = $linea;
        }

        return $novedades;
    }

    /**
     * Arma el texto listo para enviar por mensaje: fecha/hora del evento en la primera
     * línea y la narrativa con el prefijo "Descripción:".
     *
     * @param array<string, mixed> $detalle
     */
    private function mensajeParaEnviar(array $detalle, string $resumen): string
    {
        $fecha = trim((string) ($detalle['fecha_hora_inicial'] ?? ''));
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i'] as $formato) {
            $dt = \DateTime::createFromFormat($formato, $fecha);
            if ($dt instanceof \DateTime) {
                $fecha = $dt->format('d/m/Y H:i');
                break;
            }
        }

        $cuerpo = trim($resumen) === '' ? '' : 'Descripción: ' . trim($resumen);

        return trim($fecha . "\n" . $cuerpo);
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
            'resumen' => $this->separarParrafos((string) ($datos['resumen'] ?? '')),
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
     * El modelo a veces devuelve la narrativa en un solo bloque aunque se le pidan
     * párrafos: si no trae saltos de línea, los inserta antes del párrafo de los
     * móviles comisionados y del cierre ("Finalmente, ...").
     */
    private function separarParrafos(string $resumen): string
    {
        $resumen = trim($resumen);
        if ($resumen === '' || str_contains($resumen, "\n")) {
            return $resumen;
        }

        return (string) preg_replace('/\s+(?=Se comision|Finalmente,)/u', "\n", $resumen, 2);
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
