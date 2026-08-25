<?php

return [
    // Login page del panel SSW (Softswitch Planex). El resto de las rutas se resuelven
    // contra el mismo host, quitando el archivo final de esta URL.
    'url' => env('CENTRAL_TELEFONICA_URL', 'http://172.40.20.65/ssw/index.php'),
    'user' => env('CENTRAL_TELEFONICA_USER', ''),
    'password' => env('CENTRAL_TELEFONICA_PASSWORD', ''),
    'timeout' => env('CENTRAL_TELEFONICA_TIMEOUT', 60),
];
