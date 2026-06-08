<?php

return [
    // Servidor de inferencia Ollama en la red local (API compatible).
    'url'   => env('IA_URL', 'http://193.169.1.246:11434'),
    'model' => env('IA_MODEL', 'qwen2.5:3b'),

    // Timeout alto: en CPU un evento extenso puede tardar ~60-90s.
    'timeout' => env('IA_TIMEOUT', 180),

    // Mantener el modelo cargado en memoria entre pedidos para evitar la carga en frío.
    'keep_alive' => env('IA_KEEP_ALIVE', '30m'),

    // Permite desactivar la funcionalidad sin tocar código.
    'enabled' => env('IA_ENABLED', true),
];
