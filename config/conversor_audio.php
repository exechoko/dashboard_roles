<?php

return [
    /*
     | Conversor de audios GSM/OGG/WAV a MP3 (Herramientas). Cada archivo se
     | sube y convierte en un request aparte (para poder mostrar progreso real
     | "N de M"), acumulándose en storage/app/conversor_audio_temp/{user}/{token}
     | hasta que el lote se descarga como ZIP.
     */

    // Tope de archivos por lote. Coincide con max_file_uploads de PHP (no hay
    // override en .htaccess para esa directiva), así que no tiene sentido
    // permitir más desde la app.
    'max_archivos' => (int) env('CONVERSOR_AUDIO_MAX_ARCHIVOS', 20),

    // Antigüedad, en horas, a partir de la cual un lote sin descargar se
    // considera abandonado (pestaña cerrada a mitad de la conversión) y lo
    // borra el comando conversor-audio:limpiar-lotes-huerfanos.
    'lote_expiracion_horas' => (int) env('CONVERSOR_AUDIO_LOTE_EXPIRACION_HORAS', 2),
];
