<?php

return [
    'tamano_maximo_kb' => env('DESCARGAS_TAMANO_MAX', 10485760),

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

    // Configuración de Jobs/Queues
    'job_timeout' => 7200, // 2 horas en segundos
    'job_tries' => 2,
    'job_backoff' => 300, // 5 minutos entre reintentos

    // ZIPs temporales
    'zip_temp_expiracion_horas' => 24,
    'zip_tamano_maximo_gb' => 10,

    // QR Codes
    'qr_default_expiracion_horas' => 24,
    'qr_tamano_px' => 300,
];
