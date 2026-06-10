<?php

return [
    /*
     | Monitoreo de LibreNMS (http://172.40.20.113).
     | La API REST requiere un token que sólo un admin puede generar, por lo que
     | el servicio inicia sesión en la web con usuario/contraseña (igual que un
     | navegador) y consulta los endpoints internos de la UI.
     */
    'url'      => env('LIBRENMS_URL', 'http://172.40.20.113'),
    'user'     => env('LIBRENMS_USER', ''),
    'password' => env('LIBRENMS_PASSWORD', ''),

    // Timeout por request HTTP a LibreNMS.
    'timeout'  => (int) env('LIBRENMS_TIMEOUT', 30),

    // ID del device group de LibreNMS con las PCs de los operadores de video
    // (grupo "CCTV", el mismo del filtro filter[groups.id][eq]=9 en la web).
    'grupo_video' => (int) env('LIBRENMS_GRUPO_VIDEO', 9),

    // Umbral de uso de CPU (% promedio entre núcleos) que dispara la alerta.
    'umbral_cpu' => (int) env('LIBRENMS_UMBRAL_CPU', 60),

    // Histéresis: el equipo se considera recuperado recién cuando baja de
    // (umbral - histeresis), para que no oscile alerta/recuperado en el borde.
    'histeresis' => (int) env('LIBRENMS_HISTERESIS', 5),

    // Minutos entre re-avisos de un mismo equipo que sigue sobre el umbral.
    'cooldown_minutos' => (int) env('LIBRENMS_COOLDOWN_MINUTOS', 30),

    // Chat IDs de Telegram para las alertas (separados por coma). Vacío = usa
    // el TELEGRAM_CHAT_ID por defecto del bot.
    'telegram_chat_ids' => env('LIBRENMS_TELEGRAM_CHAT_IDS', ''),
];
