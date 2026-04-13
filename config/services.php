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
    ],

];
