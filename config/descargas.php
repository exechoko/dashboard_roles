<?php

return [
    'tamano_maximo_kb' => env('DESCARGAS_TAMANO_MAX', 512000),

    'extensiones_permitidas' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'rar', '7z',
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'txt', 'csv',
    ],

    'preview_extensiones' => ['pdf', 'jpg', 'jpeg', 'png', 'gif'],

    'links_expiracion_horas' => env('DESCARGAS_LINKS_EXPIRACION_HORAS', 24),

    'links_max_usos_default' => 1,

    'notificar_nuevos_archivos' => env('DESCARGAS_NOTIFICAR', true),
];
