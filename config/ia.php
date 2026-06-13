<?php

return [
    // Servidor de inferencia Ollama en la red local (API compatible).
    'url'   => env('IA_URL', 'http://193.169.1.246:11434'),

    // qwen3:4b-instruct: mejor balance en el Xeon sin AVX2 del server (2.5 GB de RAM,
    // ~2.5 min evento corto / ~5 min largo). gemma4:e4b da un poco más de calidad pero
    // tarda el doble y ocupa 9.6 GB de RAM en un server compartido con el RAG.
    'model' => env('IA_MODEL', 'qwen3:4b-instruct'),

    // Timeout alto: la generación corre en segundo plano (sin Cloudflare de por medio)
    // y en CPU un evento extenso puede tardar varios minutos (más aún en frío).
    'timeout' => env('IA_TIMEOUT', 900),

    // Mantener el modelo cargado en memoria entre pedidos para evitar la carga en frío.
    'keep_alive' => env('IA_KEEP_ALIVE', '30m'),

    // Modo "thinking" de modelos razonadores (qwen3, etc.). Dejar en false para esos
    // modelos: el razonamiento dispara la latencia. null = no enviar el parámetro
    // (para modelos que no lo soportan, como qwen2.5 o gemma).
    'think' => env('IA_THINK', null),

    // Permite desactivar la funcionalidad sin tocar código.
    'enabled' => env('IA_ENABLED', true),
];
