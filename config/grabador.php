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

    'lang_id'          => env('GRABADOR_LANG_ID', 'es'),
    'timeout'          => (int) env('GRABADOR_TIMEOUT', 60),

    // Ventana de búsqueda alrededor del evento CECOCO: arranca N minutos antes
    // de la fecha/hora del evento y termina en la fecha de cierre del evento.
    'minutos_antes'    => (int) env('GRABADOR_MINUTOS_ANTES', 10),

    // Fallback de minutos después cuando el evento no tiene fecha de cierre.
    'minutos_despues_sin_cierre' => (int) env('GRABADOR_MINUTOS_DESPUES_SIN_CIERRE', 60),

    // Máximo de modulaciones a traer por búsqueda.
    'max_resultados'   => (int) env('GRABADOR_MAX_RESULTADOS', 500),

    // Audios de modulaciones en disco local (misma estructura que las grabaciones
    // telefónicas: {base}\YYYY\YYYY_MM\Operador\...). Se busca acá primero y, si no
    // hay resultados, se consulta la web del grabador como respaldo.
    'recordings_path'  => env('GRABADOR_RECORDINGS_PATH', env('CECOCO_RECORDINGS_PATH', 'G:\\Audios Cecoco')),

    // Tolerancia (en segundos) al emparejar una fila del grabador con un .mp3 del
    // backup local por hora de inicio (las copias de CECOCO arrancan con un pequeño
    // corrimiento respecto del grabador).
    'tolerancia_emparejado' => (int) env('GRABADOR_TOLERANCIA_EMPAREJADO', 5),

    // Las modulaciones de radio son todos los audios que NO son llamadas telefónicas.
    // Las telefónicas se identifican por este marcador en el nombre; el resto
    // (TETRA, Multiconferencia, Escucha, etc.) se considera modulación.
    'marcador_telefonia' => env('GRABADOR_MARCADOR_TELEFONIA', '(RDSI)'),
];
