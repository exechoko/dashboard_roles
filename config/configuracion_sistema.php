<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de claves del .env editables desde Configuración del Sistema
    |--------------------------------------------------------------------------
    |
    | Cada grupo se muestra como una pestaña en la pantalla "Variables de
    | entorno" (y el grupo 'ia' además alimenta la pantalla "IA y API Keys").
    | Las claves del .env que no estén listadas acá siguen editables desde la
    | pestaña "Avanzado", como texto plano.
    |
    */
    'grupos' => [

        'ia' => [
            'titulo' => 'Inteligencia Artificial',
            'icono'  => 'fas fa-brain',
            'claves' => [
                'IA_URL'         => ['label' => 'Servidor Ollama (resumen CECOCO)', 'tipo' => 'text', 'ayuda' => 'Servidor de inferencia usado para el resumen IA de eventos CECOCO.'],
                'IA_MODEL'       => ['label' => 'Modelo (resumen CECOCO)', 'tipo' => 'text'],
                'IA_TIMEOUT'     => ['label' => 'Timeout (segundos)', 'tipo' => 'number'],
                'IA_KEEP_ALIVE'  => ['label' => 'Keep-alive del modelo', 'tipo' => 'text', 'ayuda' => 'Ej: 30m, 1h. Cuánto se mantiene el modelo cargado en memoria entre pedidos.'],
                'IA_NUM_THREAD'  => ['label' => 'Hilos de CPU', 'tipo' => 'number'],
                'IA_THINK'       => ['label' => 'Modo "thinking"', 'tipo' => 'text', 'ayuda' => 'true / false / vacío, según si el modelo soporta razonamiento explícito.'],
                'IA_ENABLED'     => ['label' => 'Resumen IA habilitado', 'tipo' => 'bool'],
                'OLLAMA_URL'     => ['label' => 'Servidor Ollama (chat/otros)', 'tipo' => 'text'],
                'OLLAMA_MODEL'   => ['label' => 'Modelo Ollama (chat/otros)', 'tipo' => 'text'],
                'WHISPER_URL'    => ['label' => 'Servidor Whisper (transcripción)', 'tipo' => 'text'],
                'RAG_URL'        => ['label' => 'Servidor RAG (Base de Conocimiento)', 'tipo' => 'text'],
                'TRANSCRIPTION_API_URL' => ['label' => 'API de transcripción AWS', 'tipo' => 'text'],
                'OPENCODE_ENABLED'  => ['label' => 'Chatbot habilitado', 'tipo' => 'bool'],
                'OPENCODE_URL'      => ['label' => 'Servidor OpenCode (chatbot)', 'tipo' => 'text'],
                'OPENCODE_USERNAME' => ['label' => 'Usuario OpenCode', 'tipo' => 'text'],
                'OPENCODE_PASSWORD' => ['label' => 'Contraseña OpenCode', 'tipo' => 'password'],
                'OPENCODE_AGENT'    => ['label' => 'Agente OpenCode', 'tipo' => 'text'],
                'OPENCODE_MODEL'    => ['label' => 'Modelo principal', 'tipo' => 'text'],
                'OPENCODE_FALLBACK_MODEL'   => ['label' => 'Modelo de respaldo', 'tipo' => 'text'],
                'OPENCODE_CONNECT_TIMEOUT'  => ['label' => 'Timeout de conexión (seg)', 'tipo' => 'number'],
                'OPENCODE_RESPONSE_TIMEOUT' => ['label' => 'Timeout de respuesta (seg)', 'tipo' => 'number'],
                'OPENAI_API_KEY'    => ['label' => 'API Key OpenAI', 'tipo' => 'password'],
                'ELEVENS_API_KEY'   => ['label' => 'API Key ElevenLabs', 'tipo' => 'password'],
                'TINY_API_KEY'      => ['label' => 'API Key TinyMCE', 'tipo' => 'password'],
            ],
        ],

        'mapas' => [
            'titulo' => 'Mapas y Geocodificación',
            'icono'  => 'fas fa-map-marked-alt',
            'claves' => [
                'GEOCODING_GOOGLE_ENABLED'  => ['label' => 'Usar Google Geocoding (de pago)', 'tipo' => 'bool'],
                'API_GOOGLE'                => ['label' => 'API Key de Google Maps', 'tipo' => 'password'],
                'NOMINATIM_BASE_URL'        => ['label' => 'Servidor Nominatim (gratis)', 'tipo' => 'text'],
                'NOMINATIM_DELAY_MS'        => ['label' => 'Delay entre pedidos (ms)', 'tipo' => 'number'],
                'NOMINATIM_REVERSE_BATCH_MAX' => ['label' => 'Máx. pedidos por lote', 'tipo' => 'number'],
                'API_ROUTE_SERVICE'         => ['label' => 'API Key OpenRouteService', 'tipo' => 'password'],
                'API_KEY_THUNDER_FOREST_MAP' => ['label' => 'API Key Thunderforest', 'tipo' => 'password'],
                'API_KEY_MAP_TILER'         => ['label' => 'API Key MapTiler', 'tipo' => 'password'],
                'API_KEY_STADIA_MAPS'       => ['label' => 'API Key Stadia Maps', 'tipo' => 'password'],
            ],
        ],

        'cecoco' => [
            'titulo' => 'CECOCO',
            'icono'  => 'fas fa-life-ring',
            'claves' => [
                'CECOCO_URL'                 => ['label' => 'URL CECOCO', 'tipo' => 'text'],
                'CECOCO_GIS_URL'             => ['label' => 'URL GIS CECOCO', 'tipo' => 'text'],
                'CECOCO_USER'                => ['label' => 'Usuario', 'tipo' => 'text'],
                'CECOCO_PASSWORD'            => ['label' => 'Contraseña', 'tipo' => 'password'],
                'CECOCO_USER_MONITOR'        => ['label' => 'Usuario de monitoreo', 'tipo' => 'text'],
                'CECOCO_PASSWORD_MONITOR'    => ['label' => 'Contraseña de monitoreo', 'tipo' => 'password'],
                'CECOCO_GPS_URL'             => ['label' => 'URL GPS', 'tipo' => 'text'],
                'CECOCO_GPS_USER_MONITOR'    => ['label' => 'Usuario de monitoreo GPS', 'tipo' => 'text'],
                'CECOCO_GPS_PASSWORD_MONITOR' => ['label' => 'Contraseña de monitoreo GPS', 'tipo' => 'password'],
                'CECOCO_TIMEOUT'             => ['label' => 'Timeout (segundos)', 'tipo' => 'number'],
                'CECOCO_UMBRAL_RESTAURACIONES_MB' => ['label' => 'Umbral de alerta BD restauraciones (MB)', 'tipo' => 'number'],
            ],
        ],

        'infraestructura' => [
            'titulo' => 'Infraestructura',
            'icono'  => 'fas fa-network-wired',
            'claves' => [
                'GRABADOR_URL'         => ['label' => 'URL Grabador TETRA', 'tipo' => 'text'],
                'GRABADOR_USER'        => ['label' => 'Usuario Grabador', 'tipo' => 'text'],
                'GRABADOR_PASSWORD'    => ['label' => 'Contraseña Grabador', 'tipo' => 'password'],
                'GRABADOR_REPLAY_URL'  => ['label' => 'Replay Server (audio)', 'tipo' => 'text'],
                'LIBRENMS_URL'         => ['label' => 'URL LibreNMS', 'tipo' => 'text'],
                'LIBRENMS_USER'        => ['label' => 'Usuario LibreNMS', 'tipo' => 'text'],
                'LIBRENMS_PASSWORD'    => ['label' => 'Contraseña LibreNMS', 'tipo' => 'password'],
                'LIBRENMS_UMBRAL_CPU'  => ['label' => 'Umbral de alerta CPU (%)', 'tipo' => 'number'],
                'CENTRAL_TELEFONICA_URL'      => ['label' => 'URL Central Telefónica', 'tipo' => 'text'],
                'CENTRAL_TELEFONICA_USER'     => ['label' => 'Usuario Central Telefónica', 'tipo' => 'text'],
                'CENTRAL_TELEFONICA_PASSWORD' => ['label' => 'Contraseña Central Telefónica', 'tipo' => 'password'],
                'CAMARA_USER' => ['label' => 'Usuario de cámaras', 'tipo' => 'text'],
                'CAMARA_PASS' => ['label' => 'Contraseña de cámaras', 'tipo' => 'password'],
            ],
        ],

        'ticketera' => [
            'titulo' => 'Ticketera PG',
            'icono'  => 'fas fa-ticket-alt',
            'claves' => [
                'TICKETERA_URL'      => ['label' => 'URL Ticketera', 'tipo' => 'text'],
                'TICKETERA_USUARIO'  => ['label' => 'Usuario', 'tipo' => 'text'],
                'TICKETERA_PASSWORD' => ['label' => 'Contraseña', 'tipo' => 'password'],
                'TICKETERA_CUSTOMER_ID' => ['label' => 'ID de cliente', 'tipo' => 'text'],
                'TICKETERA_OWNER_ID'    => ['label' => 'ID de staff asignado', 'tipo' => 'text'],
                'TICKETERA_EMAIL'       => ['label' => 'Email de contacto', 'tipo' => 'text'],
                'TICKETERA_DRY_RUN'     => ['label' => 'Modo seguro (no envía de verdad)', 'tipo' => 'bool', 'ayuda' => 'En true, simula y loguea sin enviar tickets reales.'],
                'TICKET_PG_GENERAR_INCIDENCIA' => ['label' => 'Generar incidencia 911 automática', 'tipo' => 'bool'],
            ],
        ],

        'notificaciones' => [
            'titulo' => 'Notificaciones',
            'icono'  => 'fas fa-bell',
            'claves' => [
                'MAIL_HOST'          => ['label' => 'Servidor SMTP', 'tipo' => 'text'],
                'MAIL_PORT'          => ['label' => 'Puerto SMTP', 'tipo' => 'number'],
                'MAIL_USERNAME'      => ['label' => 'Usuario SMTP', 'tipo' => 'text'],
                'MAIL_PASSWORD'      => ['label' => 'Contraseña SMTP', 'tipo' => 'password'],
                'MAIL_ENCRYPTION'    => ['label' => 'Encriptación', 'tipo' => 'text'],
                'MAIL_FROM_ADDRESS'  => ['label' => 'Email remitente', 'tipo' => 'text'],
                'MAIL_FROM_NAME'     => ['label' => 'Nombre remitente', 'tipo' => 'text'],
                'TAREAS_AVISO_EMAILS' => ['label' => 'Emails de aviso de tareas', 'tipo' => 'text', 'ayuda' => 'Separados por coma.'],
                'TELEGRAM_BOT_TOKEN'  => ['label' => 'Token del bot de Telegram', 'tipo' => 'password'],
                'TELEGRAM_CHAT_ID'    => ['label' => 'Chat ID por defecto', 'tipo' => 'text'],
                'TELEGRAM_TAREAS_CHAT_IDS' => ['label' => 'Chat IDs de tareas', 'tipo' => 'text', 'ayuda' => 'Separados por coma.'],
            ],
        ],

        'workers' => [
            'titulo' => 'Workers y Colas',
            'icono'  => 'fas fa-cogs',
            'claves' => [
                'QUEUE_CONNECTION' => [
                    'label'   => 'Conexión de colas',
                    'tipo'    => 'select',
                    'opciones' => ['sync' => 'sync (sin worker, procesa en el request)', 'database' => 'database (requiere queue:work)'],
                    'ayuda'   => 'Con "database" hay que tener corriendo `php artisan queue:work` (worker por defecto), `php artisan queue:work mbox --queue=mbox` (worker de correos) y `php artisan queue:work backups --queue=backups` (worker de backups de BD).',
                ],
                'MBOX_AUTO_INDEXAR' => ['label' => 'Auto-indexar correos nuevos', 'tipo' => 'bool'],
            ],
        ],

        'general' => [
            'titulo' => 'General',
            'icono'  => 'fas fa-sliders-h',
            'claves' => [
                'APP_NAME' => ['label' => 'Nombre de la aplicación', 'tipo' => 'text'],
                'MYSQL_BIN_PATH' => ['label' => 'Carpeta de mysqldump/mysql', 'tipo' => 'text', 'ayuda' => 'Sólo si no están en el PATH del sistema. Ej: C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin'],
            ],
        ],

        'criticas' => [
            'titulo'  => 'Críticas (requiere permiso extra)',
            'icono'   => 'fas fa-exclamation-triangle',
            'ayuda'   => 'Cambiar estos valores puede dejar la aplicación sin conexión a la base de datos o inaccesible. Verificá dos veces antes de guardar.',
            'claves' => [
                'APP_ENV'      => ['label' => 'Entorno (local/production)', 'tipo' => 'text'],
                'APP_DEBUG'    => ['label' => 'Modo debug', 'tipo' => 'bool'],
                'APP_URL'      => ['label' => 'URL base de la aplicación', 'tipo' => 'text'],
                'DB_HOST'      => ['label' => 'Host de la base de datos', 'tipo' => 'text'],
                'DB_PORT'      => ['label' => 'Puerto de la base de datos', 'tipo' => 'number'],
                'DB_DATABASE'  => ['label' => 'Nombre de la base de datos', 'tipo' => 'text'],
                'DB_USERNAME'  => ['label' => 'Usuario de la base de datos', 'tipo' => 'text'],
                'DB_PASSWORD'  => ['label' => 'Contraseña de la base de datos', 'tipo' => 'password'],
            ],
        ],
    ],

    // Grupo cuyas claves solo se pueden editar con el permiso adicional
    // 'editar-configuracion-env-critico'.
    'grupo_critico' => 'criticas',

    // Claves que NUNCA se muestran ni se editan desde acá (ni siquiera con el
    // permiso crítico): tocarlas rompe la app de formas no recuperables desde
    // la propia web (APP_KEY invalida todo lo cifrado y las sesiones activas).
    'claves_bloqueadas' => ['APP_KEY'],

    // Patrón para detectar claves sensibles que no están en el catálogo (pestaña
    // "Avanzado"): se muestran enmascaradas y no se pisan si vuelven sin cambios.
    'patron_sensible' => '/PASSWORD|SECRET|TOKEN|API_KEY|_KEY$|_PASS$/i',

    'mysql' => [
        // Carpeta donde viven mysqldump / mysql si no están en el PATH del sistema
        // (en esta máquina de desarrollo, por ejemplo, sólo existen dentro de
        // "C:\Program Files\MySQL\MySQL Workbench 8.0").
        'bin_path' => env('MYSQL_BIN_PATH'),
    ],
];
