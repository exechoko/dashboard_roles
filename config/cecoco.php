<?php

return [
    'url'     => env('CECOCO_URL', 'http://172.26.100.34:8080'),
    'gis_url'        => env('CECOCO_GIS_URL',        'http://172.26.100.52'),
    'geoserver_url'  => env('CECOCO_GEOSERVER_URL',  'http://172.26.100.51'),
    'user'    => env('CECOCO_USER', ''),
    'password' => env('CECOCO_PASSWORD', ''),
    // Usuario dedicado al monitoreo del dashboard (login JSF completo). CECOCO permite
    // sólo una sesión activa por usuario, así que este NO debe coincidir con CECOCO_USER.
    'user_monitor'     => env('CECOCO_USER_MONITOR', ''),
    'password_monitor' => env('CECOCO_PASSWORD_MONITOR', ''),
    // Cuenta dedicada a los lotes de consulta en vivo (cecoco:prefetch-detalles,
    // cecoco:crisis-moviles-911): no debe coincidir con 'user' ni 'user_monitor'
    // porque esas se usan para búsquedas manuales y monitoreo en cualquier momento
    // del día, y el lote puede tardar minutos/horas con la sesión abierta. Si no
    // está configurada, el servicio cae a la cuenta general.
    'user_prefetch'     => env('CECOCO_USER_PREFETCH_1', ''),
    'password_prefetch' => env('CECOCO_PASSWORD_PREFETCH_1', ''),
    'gps_url' => env('CECOCO_GPS_URL', env('CECOCO_URL', 'http://172.26.100.34:8080')),
    'gps_login_url' => env(
        'CECOCO_GPS_LOGIN_URL',
        rtrim(env('CECOCO_GPS_URL', env('CECOCO_URL', 'http://172.26.100.34:8080')), '/') . '/CECOCO_webapp/app/login/IndexLogin.faces'
    ),
    'gps_user_monitor'     => env('CECOCO_GPS_USER_MONITOR', ''),
    'gps_password_monitor' => env('CECOCO_GPS_PASSWORD_MONITOR', ''),
    'timeout' => env('CECOCO_TIMEOUT', 60),
    'recordings_path' => env('CECOCO_RECORDINGS_PATH', 'G:\\Audios Cecoco'),

    // Tamaño (MB) de la BD de restauraciones (CECOCO y GPS) a partir del cual
    // se dispara una notificación/alerta por Telegram.
    'umbral_restauraciones_mb' => (int) env('CECOCO_UMBRAL_RESTAURACIONES_MB', 4000),

    // Apaga la consulta horaria programada del tamaño de BD de restauraciones
    // de eventos CECOCO (Configuración del Sistema > Variables de Entorno >
    // Infraestructura). La de GPS se apaga por separado, ver abajo.
    'monitoreo_restauraciones_enabled' => env('CECOCO_MONITOREO_RESTAURACIONES_ENABLED', true),

    // Igual que la anterior, pero para la consulta horaria del tamaño de BD de
    // restauraciones GPS (fuente/servidor distinto vía gps_url).
    'monitoreo_restauraciones_gps_enabled' => env('CECOCO_GPS_MONITOREO_RESTAURACIONES_ENABLED', true),
];
