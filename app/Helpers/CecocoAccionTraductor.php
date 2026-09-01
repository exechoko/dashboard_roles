<?php

namespace App\Helpers;

class CecocoAccionTraductor
{
    /**
     * Frases fijas que el reporte de CECOCO genera en inglés para la columna
     * "Acción" del historial del expediente. Si aparece una frase nueva no
     * mapeada, se devuelve tal cual la entrega CECOCO (no se pierde información).
     */
    private const TRADUCCIONES = [
        'service of type creation' => 'Creación del servicio',
        'service creation' => 'Creación del servicio',
        'event section update' => 'Actualización de la sección de eventos',
        'event creation' => 'Creación del evento',
        'event closed' => 'Evento cerrado',
        'close section update' => 'Actualización de la sección de cierre',
        'type of service change' => 'Cambio de tipo de servicio',
        'service change of status' => 'Cambio de estado del servicio',
        'service update' => 'Actualización del servicio',
        'service closed' => 'Servicio cerrado',
        'service geographical position change' => 'Cambio de posición geográfica del servicio',
        'service address change' => 'Cambio de dirección del servicio',
        'service phone change' => 'Cambio de teléfono del servicio',
        'resource assignation' => 'Asignación de recurso',
        'resource change of status' => 'Cambio de estado del recurso',
        'resource un-assignation' => 'Desasignación de recurso',
        'resource unassignation' => 'Desasignación de recurso',
        'processing section addition' => 'Alta de trámite',
        'processing section update' => 'Actualización de trámite',
        'processing section removal' => 'Baja de trámite',
    ];

    public static function traducir(?string $accion): string
    {
        $accion = trim((string) $accion);
        if ($accion === '') {
            return '';
        }

        $clave = mb_strtolower($accion, 'UTF-8');

        return self::TRADUCCIONES[$clave] ?? $accion;
    }
}
