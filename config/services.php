<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'tareas_chat_ids' => env('TELEGRAM_TAREAS_CHAT_IDS'),
    ],

    'ia' => [
        'whisper_url'       => env('WHISPER_URL',       'http://193.169.1.246:8080'),
        'rag_url'           => env('RAG_URL',           'http://193.169.1.246:8081'),
        'ollama_url'        => env('OLLAMA_URL',        'http://193.169.1.246:11434'),
        'ollama_model'      => env('OLLAMA_MODEL',      'llama3.2:3b'),
        'call_analysis_url' => env('CALL_ANALYSIS_URL', 'http://193.169.1.246:8082'),
        'transcription_url' => env('TRANSCRIPTION_API_URL', 'https://gvs9j8cd3a.execute-api.us-east-1.amazonaws.com/prod'),
    ],

    'tareas' => [
        'aviso_emails' => env('TAREAS_AVISO_EMAILS'),
    ],

    'camaras' => [
        'user' => env('CAMARA_USER'),
        'pass' => env('CAMARA_PASS'),
    ],

    'google' => [
        'api_key' => env('API_GOOGLE', ''),

        // Interruptor de costos de geocodificación. En false (por defecto) NO se
        // realiza ninguna llamada paga a la API de Google Maps Geocoding: se sirve
        // desde el caché en base y, para lo que falte, se usa Nominatim (gratis).
        'geocoding_enabled' => env('GEOCODING_GOOGLE_ENABLED', false),
    ],

    // Motor gratuito de geocodificación (OpenStreetMap / Nominatim). Sin API key.
    // Por defecto apunta a la instancia self-hosted en el servidor (extract de
    // Entre Ríos, ver README_NOMINATIM.md). Al ser propia NO rige la política de
    // 1 request/seg del servidor público, por eso el delay por defecto es bajo.
    // Para volver al servidor público de OSM: NOMINATIM_BASE_URL=https://nominatim.openstreetmap.org
    // y NOMINATIM_DELAY_MS=1100 (su política exige máximo 1 req/seg).
    'nominatim' => [
        'base_url'  => env('NOMINATIM_BASE_URL', 'http://193.169.1.246:8088'),
        'delay_ms'  => (int) env('NOMINATIM_DELAY_MS', 100),
        // Sufijo de contexto que se agrega a la dirección antes de buscar.
        // La instancia self-hosted es un extract de Paraná — no resuelve con
        // ", Entre Ríos, Argentina". Para otras ciudades de la provincia
        // el geocoder local (tabla calles) es el motor principal.
        'contexto'  => env('NOMINATIM_CONTEXTO', ', Paraná'),
        // Tope de llamadas nuevas por request en el batch inverso, para no exceder
        // el timeout de Cloudflare (100s). El resto se resuelve en consultas
        // posteriores a medida que el caché en base se va llenando.
        'reverse_batch_max' => (int) env('NOMINATIM_REVERSE_BATCH_MAX', 50),
    ],

    'open_route_service' => [
        'api_key' => env('API_ROUTE_SERVICE', ''),
    ],

    'thunderforest' => [
        'api_key' => env('API_KEY_THUNDER_FOREST_MAP', ''),
    ],

    // Ticketera HESK (help desk) — lado admin.
    'ticketera' => [
        'url'               => env('TICKETERA_URL'),
        'usuario'           => env('TICKETERA_USUARIO'),
        'password'          => env('TICKETERA_PASSWORD'),
        'timeout'           => (int) env('TICKETERA_TIMEOUT', 30),
        'login_path'        => env('TICKETERA_LOGIN_PATH', 'admin/index.php'),
        'admin_path'        => env('TICKETERA_ADMIN_PATH', 'admin/admin_main.php'),
        'nuevo_ticket_path' => env('TICKETERA_NUEVO_TICKET_PATH', 'admin/new_ticket.php'),
        'submit_path'       => env('TICKETERA_SUBMIT_PATH', 'admin/admin_submit_ticket.php'),
        // Cliente (obligatorio en HESK) y staff asignado; se obtienen una vez del panel.
        'customer_id'       => env('TICKETERA_CUSTOMER_ID'),
        'owner_id'          => env('TICKETERA_OWNER_ID'),
        'status'            => env('TICKETERA_STATUS'),
        'nombre'            => env('TICKETERA_NOMBRE', 'Tecnica 911'),
        'email'             => env('TICKETERA_EMAIL', env('TICKETERA_USUARIO')),
        // Seguro: en true NO se envía nada a la ticketera (solo simula y loguea).
        // Por defecto true para evitar envíos accidentales; poner false para enviar de verdad.
        'dry_run'           => filter_var(env('TICKETERA_DRY_RUN', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
