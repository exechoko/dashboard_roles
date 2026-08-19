<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Carpeta raíz de los backups de correo
    |--------------------------------------------------------------------------
    |
    | Una subcarpeta por oficina (ej. F:\Backup_mails\Judiciales\), con uno o
    | más archivos .mbox dentro (Google Takeout genera uno nuevo por período).
    |
    */
    'ruta' => env('MBOX_PATH', 'F:\Backup_mails'),

    /*
    |--------------------------------------------------------------------------
    | Límites de indexación
    |--------------------------------------------------------------------------
    */
    'max_mensaje_mb' => (int) env('MBOX_MAX_MENSAJE_MB', 40),
    'max_cuerpo_kb'  => (int) env('MBOX_MAX_CUERPO_KB', 64),
    'lote_insert'    => 500,

    /*
    |--------------------------------------------------------------------------
    | Detección e indexación automática
    |--------------------------------------------------------------------------
    |
    | Cuando está activo, el scheduler corre mbox:detectar-nuevos a diario y
    | encola automáticamente cualquier .mbox nuevo o modificado. Se arranca
    | apagado para hacer las primeras cargas de forma controlada.
    |
    */
    'auto_indexar' => (bool) env('MBOX_AUTO_INDEXAR', false),

];
