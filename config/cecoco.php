<?php

return [
    'url'     => env('CECOCO_URL', 'http://172.26.100.34:8080'),
    'gis_url'        => env('CECOCO_GIS_URL',        'http://172.26.100.52'),
    'geoserver_url'  => env('CECOCO_GEOSERVER_URL',  'http://172.26.100.51'),
    'user'    => env('CECOCO_USER', ''),
    'password' => env('CECOCO_PASSWORD', ''),
    'timeout' => env('CECOCO_TIMEOUT', 60),
    'recordings_path' => env('CECOCO_RECORDINGS_PATH', 'G:\\Audios Cecoco'),
];
