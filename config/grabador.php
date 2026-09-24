<?php

return [
    /*
     | Grabador de modulaciones TETRA (Red Box Recorders "Quantify").
     | La búsqueda de llamadas se hace contra la web del grabador (puerto 80),
     | pero el audio lo sirve un "Replay Server" local instalado en la misma
     | máquina del dashboard (localhost:8880), que proxea hacia el grabador.
     */
    'url'              => env('GRABADOR_URL', 'http://172.20.123.1'),
    'user'             => env('GRABADOR_USER', ''),
    'password'         => env('GRABADOR_PASSWORD', ''),

    // Dirección del grabador tal como la espera el Replay Server (sin esquema).
    'recorder_address' => env('GRABADOR_RECORDER_ADDRESS', parse_url(env('GRABADOR_URL', 'http://172.20.123.1'), PHP_URL_HOST)),

    // Replay Server local (sirve el audio WAV). No es la IP del grabador.
    'replay_url'       => env('GRABADOR_REPLAY_URL', 'http://localhost:8880'),

    // Nombre del servicio de Windows del Replay Server (confirmado en
    // producción con `Get-CimInstance Win32_Service`), usado para reiniciarlo
    // desde Configuración del Sistema cuando queda colgado por uso concurrente.
    'replay_service_name' => env('GRABADOR_REPLAY_SERVICE_NAME', 'RedBoxReplayService'),

    'lang_id'          => env('GRABADOR_LANG_ID', 'es'),

    // Timeout por request HTTP al grabador.
    'timeout'          => (int) env('GRABADOR_TIMEOUT', 30),

    // Presupuesto total para UNA página (login + búsqueda/continuesearch + poll).
    // Debe quedar por debajo de los ~100 s en que Cloudflare corta la conexión en
    // producción; ventanas largas (varias horas) pueden tardar bastante en el
    // grabador, así que se deja poco margen (~10 s) antes de ese corte real.
    'timeout_total'    => (int) env('GRABADOR_TIMEOUT_TOTAL', 90),

    // Ventana de búsqueda alrededor del evento CECOCO: arranca N minutos antes
    // de la fecha/hora del evento y termina en la fecha de cierre del evento.
    'minutos_antes'    => (int) env('GRABADOR_MINUTOS_ANTES', 10),

    // Fallback de minutos después cuando el evento no tiene fecha de cierre.
    'minutos_despues_sin_cierre' => (int) env('GRABADOR_MINUTOS_DESPUES_SIN_CIERRE', 60),

    // Máximo de modulaciones a traer por búsqueda. 1000 es la capacidad natural
    // de una sola página del grabador (enum MaximumResults=7); por encima de eso
    // ya hacen falta páginas adicionales (continuesearch/nueva ventana), que el
    // frontend pide solas mientras "hayMas" siga en true.
    'max_resultados'   => (int) env('GRABADOR_MAX_RESULTADOS', 2000),

    // Al buscar, si la 1ª página de una ventana viene llena (densa), en vez de
    // esperar al continuesearch asíncrono del grabador (lento y, en ventanas
    // largas, la causa de búsquedas que se cortaban en silencio) se la parte al
    // medio y se busca cada mitad por separado con un startsearch propio (rápido
    // y síncrono). Esto se repite hasta que una ventana entra completa en su 1ª
    // página o llega a este piso (en cuyo caso sí se agota con continuesearch,
    // acotado porque la ventana ya es mínima).
    'bisect_minimo_segundos' => (int) env('GRABADOR_BISECT_MINIMO_SEGUNDOS', 60),

    // Audios de modulaciones en disco local (misma estructura que las grabaciones
    // telefónicas: {base}\YYYY\YYYY_MM\Operador\...). Se busca acá primero y, si no
    // hay resultados, se consulta la web del grabador como respaldo.
    'recordings_path'  => env('GRABADOR_RECORDINGS_PATH', env('CECOCO_RECORDINGS_PATH', 'G:\\Audios Cecoco')),

    // Conversor para pasar a MP3 los WAV del grabador al descargarlos: ffmpeg o
    // lame.exe (en servidores viejos tipo 2012 R2 usar lame, que es un único .exe
    // sin dependencias). Si no está disponible, la descarga cae al WAV original.
    'ffmpeg_path' => env('GRABADOR_FFMPEG_PATH', 'ffmpeg'),

    // Presupuesto (en segundos) para escanear el disco de audios al emparejar las
    // filas del grabador con su .mp3 local. Si se agota, el escaneo corta y esas
    // modulaciones se sirven por el Replay Server: vale más devolver el listado
    // que colgar el request esperando un disco de red lento.
    'escaneo_disco_timeout' => (int) env('GRABADOR_ESCANEO_DISCO_TIMEOUT', 25),

    // Tolerancia (en segundos) al emparejar una fila del grabador con un .mp3 del
    // backup local por hora de inicio (las copias de CECOCO arrancan con un pequeño
    // corrimiento respecto del grabador).
    'tolerancia_emparejado' => (int) env('GRABADOR_TOLERANCIA_EMPAREJADO', 5),

    // Las modulaciones de radio son todos los audios que NO son llamadas telefónicas.
    // Las telefónicas se identifican por este marcador en el nombre; el resto
    // (TETRA, Multiconferencia, Escucha, etc.) se considera modulación.
    'marcador_telefonia' => env('GRABADOR_MARCADOR_TELEFONIA', '(RDSI)'),

    // Apaga el watchdog programado (grabador:monitorear-replay, cada 5 min) que
    // reinicia solo el Replay Server cuando se cuelga (Configuración del Sistema
    // > Variables de Entorno > Infraestructura).
    'monitoreo_replay_enabled' => env('GRABADOR_MONITOREO_REPLAY_ENABLED', true),
];
