<?php

return [
    'tamano_maximo_kb' => env('DESCARGAS_TAMANO_MAX', 10485760),

    // Tamaño de cada parte al subir por chunks (create.blade.php). El sitio
    // pasa por un tunel de Cloudflare (plan Free: ~100MB por request), asi
    // que cada chunk tiene que quedar bien por debajo de eso.
    'chunk_size_mb' => env('DESCARGAS_CHUNK_SIZE_MB', 20),

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

    // Chunks de subidas por partes abandonadas (ver LimpiarChunksHuerfanos)
    'chunks_temp_expiracion_horas' => 6,

    // QR Codes
    'qr_default_expiracion_horas' => 24,
    'qr_tamano_px' => 300,
];
