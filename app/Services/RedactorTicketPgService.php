<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Throwable;

class RedactorTicketPgService
{
    /**
     * @param array<string, mixed> $datosTicket
     */
    public function redactar(array $datosTicket): string
    {
        return match ($this->tipoBloque($datosTicket)) {
            'camaras' => $this->redactarCamaras($datosTicket),
            'tetra'   => $this->redactarTetra($datosTicket),
            'oficina' => $this->redactarOficina($datosTicket),
            default   => $this->redactarGenerico($datosTicket),
        };
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    public function asunto(array $datosTicket): string
    {
        $modeloEquipo = trim((string) ($datosTicket['modelo_equipo'] ?? 'Equipo'));
        $movil = trim((string) ($datosTicket['movil'] ?? ''));

        switch ($this->tipoBloque($datosTicket)) {
            case 'camaras':
                $cantidad = count((array) ($datosTicket['camaras_afectadas'] ?? []));
                $problema = trim((string) ($datosTicket['problema_detectado'] ?? '')) ?: 'Falla';

                return $cantidad > 1
                    ? "{$problema} - {$cantidad} cámaras"
                    : "{$problema} - cámara";
            case 'tetra':
                return $movil !== ''
                    ? "Revision {$modeloEquipo} - Movil {$movil}"
                    : "Revision {$modeloEquipo}";
            case 'oficina':
                $oficina = trim((string) ($datosTicket['oficina'] ?? ''));

                return $oficina !== ''
                    ? "Aire acondicionado - {$oficina}"
                    : 'Aire acondicionado';
            default:
                $categoria = trim((string) ($datosTicket['tipo_equipo'] ?? ''));

                return $categoria !== '' ? "{$categoria} - {$modeloEquipo}" : "Revision {$modeloEquipo}";
        }
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function redactarCamaras(array $datosTicket): string
    {
        $codigoInterno = trim((string) ($datosTicket['codigo_interno'] ?? ''));
        $problema = trim((string) ($datosTicket['problema_detectado'] ?? 'inconvenientes en su funcionamiento'));
        $camaras = (array) ($datosTicket['camaras_afectadas'] ?? []);
        $cantidad = count($camaras);

        $listado = collect($camaras)
            ->map(function ($camara): string {
                $nombre = trim((string) ($camara['nombre'] ?? ''));
                $tipo = trim((string) ($camara['tipo'] ?? ''));

                return $tipo !== '' ? "{$nombre} ({$tipo})" : $nombre;
            })
            ->filter()
            ->implode(', ');

        $sustantivo = $cantidad === 1 ? 'la camara' : "las {$cantidad} camaras";
        $verbo = $cantidad === 1 ? 'la cual se encuentra' : 'las cuales se encuentran';
        $problemaTexto = $problema !== '' ? mb_strtolower(mb_substr($problema, 0, 1)) . mb_substr($problema, 1) : $problema;
        $texto = "{$codigoInterno} Se solicita la revision de {$sustantivo}: {$listado}, {$verbo} {$problemaTexto}.";
        $texto .= $this->textoFechas($datosTicket);
        $texto .= ' Se requiere verificacion tecnica, diagnostico y resolucion del inconveniente informado.';
        $texto .= $this->textoObservaciones($datosTicket);

        return $this->normalizarEspacios($texto);
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function redactarTetra(array $datosTicket): string
    {
        $codigoInterno = trim((string) ($datosTicket['codigo_interno'] ?? ''));
        $modeloEquipo = trim((string) ($datosTicket['modelo_equipo'] ?? ''));
        $movil = trim((string) ($datosTicket['movil'] ?? ''));
        $tei = trim((string) ($datosTicket['tei'] ?? ''));
        $dependencia = trim((string) ($datosTicket['dependencia'] ?? ''));
        $problema = trim((string) ($datosTicket['problema_detectado'] ?? 'inconvenientes en su funcionamiento'));

        $equipo = $modeloEquipo !== '' ? $modeloEquipo : 'terminal TETRA';
        $teiTexto = $tei !== '' ? " (TEI {$tei})" : '';
        $movilTexto = $movil !== '' ? " del movil {$movil}" : '';
        $dependenciaTexto = $dependencia !== '' ? " de {$dependencia}" : '';

        $texto = "{$codigoInterno} Se solicita la revision del equipo {$equipo}{$teiTexto}{$movilTexto}{$dependenciaTexto}, el cual presenta {$problema}.";
        $texto .= $this->textoFechas($datosTicket);
        $texto .= ' Se requiere verificacion tecnica, diagnostico y resolucion del inconveniente informado.';
        $texto .= $this->textoObservaciones($datosTicket);

        return $this->normalizarEspacios($texto);
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function redactarOficina(array $datosTicket): string
    {
        $codigoInterno = trim((string) ($datosTicket['codigo_interno'] ?? ''));
        $oficina = trim((string) ($datosTicket['oficina'] ?? ''));
        $problema = trim((string) ($datosTicket['problema_detectado'] ?? 'inconvenientes en su funcionamiento'));

        $ubicacion = $oficina !== '' ? " de la oficina {$oficina}" : '';

        $texto = "{$codigoInterno} Se informa una falla en el equipo de climatizacion{$ubicacion}, el cual presenta {$problema}.";
        $texto .= $this->textoFechas($datosTicket);
        $texto .= ' Se requiere verificacion tecnica, diagnostico y resolucion del inconveniente informado.';
        $texto .= $this->textoObservaciones($datosTicket);

        return $this->normalizarEspacios($texto);
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function redactarGenerico(array $datosTicket): string
    {
        $codigoInterno = trim((string) ($datosTicket['codigo_interno'] ?? ''));
        $modeloEquipo = trim((string) ($datosTicket['modelo_equipo'] ?? ''));
        $tipoEquipo = trim((string) ($datosTicket['tipo_equipo'] ?? 'equipo'));
        $movil = trim((string) ($datosTicket['movil'] ?? ''));
        $dependencia = trim((string) ($datosTicket['dependencia'] ?? ''));
        $problema = trim((string) ($datosTicket['problema_detectado'] ?? 'inconvenientes en su funcionamiento'));

        $equipo = $modeloEquipo !== '' ? $modeloEquipo : $tipoEquipo;
        $ubicacion = $movil !== '' ? " del movil {$movil}" : '';
        $dependenciaTexto = $dependencia !== '' ? " de {$dependencia}" : '';

        $texto = "{$codigoInterno} Se solicita la revision del equipo {$equipo}{$ubicacion}{$dependenciaTexto}, el cual presenta {$problema}.";
        $texto .= $this->textoFechas($datosTicket);
        $texto .= ' Se requiere verificacion tecnica, diagnostico y resolucion del inconveniente informado.';
        $texto .= $this->textoObservaciones($datosTicket);

        return $this->normalizarEspacios($texto);
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function tipoBloque(array $datosTicket): string
    {
        $categoria = trim((string) ($datosTicket['tipo_equipo'] ?? ''));
        $campos = (array) config('ticketera_categorias.campos', []);

        return $campos[$categoria] ?? 'generico';
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function textoFechas(array $datosTicket): string
    {
        $inicio = $this->formatearFecha($datosTicket['fecha_inicio_falla'] ?? null);
        $fin = $this->formatearFecha($datosTicket['fecha_fin_falla'] ?? null);

        if ($inicio === null) {
            return '';
        }

        $texto = " La falla se registro el {$inicio}";
        $texto .= $fin !== null ? " y fue resuelta el {$fin}." : ' y continua sin resolucion.';

        return $texto;
    }

    /**
     * @param array<string, mixed> $datosTicket
     */
    private function textoObservaciones(array $datosTicket): string
    {
        $observaciones = trim((string) ($datosTicket['observaciones'] ?? ''));

        return $observaciones !== '' ? " Observaciones: {$observaciones}." : '';
    }

    private function formatearFecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y H:i');
        } catch (Throwable) {
            return (string) $valor;
        }
    }

    private function normalizarEspacios(string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', $texto) ?? $texto);
    }
}
