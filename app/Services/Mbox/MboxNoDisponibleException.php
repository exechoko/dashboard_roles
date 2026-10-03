<?php

namespace App\Services\Mbox;

use Illuminate\Http\Response;
use RuntimeException;

/**
 * El .mbox original no se puede abrir (unidad desmontada, archivo movido o
 * sin permisos de lectura). Se renderiza como una página simple y legible,
 * que también se ve bien dentro del iframe del cuerpo del mensaje.
 */
class MboxNoDisponibleException extends RuntimeException
{
    public function __construct(public readonly string $ruta)
    {
        parent::__construct("No se pudo abrir el archivo mbox: {$ruta}");
    }

    public function render(): Response
    {
        $archivo = e(preg_replace('#^.*[\\\\/]#', '', $this->ruta));

        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"></head>'
            .'<body style="font-family:sans-serif;color:#842029;background:#f8d7da;padding:16px;margin:0;">'
            .'<strong>No se puede acceder al archivo de correo.</strong>'
            .'<p style="margin:8px 0 0;">El archivo <code>'.$archivo.'</code> no está disponible en el servidor '
            .'(la unidad de backups puede estar desconectada o el archivo fue movido). '
            .'Avisá a Sección Técnica si el problema persiste.</p></body></html>';

        return response($html, 503)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
