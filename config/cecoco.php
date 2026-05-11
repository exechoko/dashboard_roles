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
    'gps_url' => env('CECOCO_GPS_URL', env('CECOCO_URL', 'http://172.26.100.34:8080')),
    'gps_login_url' => env(
        'CECOCO_GPS_LOGIN_URL',
        rtrim(env('CECOCO_GPS_URL', env('CECOCO_URL', 'http://172.26.100.34:8080')), '/') . '/CECOCO_webapp/app/login/IndexLogin.faces'
    ),
    'gps_user_monitor'     => env('CECOCO_GPS_USER_MONITOR', ''),
    'gps_password_monitor' => env('CECOCO_GPS_PASSWORD_MONITOR', ''),
    'timeout' => env('CECOCO_TIMEOUT', 60),
    'recordings_path' => env('CECOCO_RECORDINGS_PATH', 'G:\\Audios Cecoco'),
];
