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
    // Cuentas dedicadas a cecoco:prefetch-detalles --workers=N: cada worker usa su
    // propia sesión CECOCO en paralelo, así que necesitan cuentas propias (CECOCO
    // permite una sola sesión activa por usuario). No deben coincidir con 'user' ni
    // 'user_monitor' porque esas se usan para búsquedas manuales y monitoreo en
    // cualquier momento del día. Solo se listan las que tengan usuario configurado.
    'prefetch_workers' => array_values(array_filter([
        ['user' => env('CECOCO_USER_PREFETCH_1', ''), 'password' => env('CECOCO_PASSWORD_PREFETCH_1', '')],
        ['user' => env('CECOCO_USER_PREFETCH_2', ''), 'password' => env('CECOCO_PASSWORD_PREFETCH_2', '')],
        ['user' => env('CECOCO_USER_PREFETCH_3', ''), 'password' => env('CECOCO_PASSWORD_PREFETCH_3', '')],
    ], fn (array $credencial): bool => $credencial['user'] !== '')),
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
];
