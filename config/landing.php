<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ruta al código de la web pública (div911.stper.com.ar)
    |--------------------------------------------------------------------------
    |
    | Sitio estático servido por Apache, hermano de este proyecto. En DEV vive
    | en C:\Apache24\htdocs\LandingPage-911-y-VV; en producción puede diferir,
    | por eso se resuelve por entorno y nunca se hardcodea en el código.
    |
    */

    'path' => env('LANDING_PATH', base_path('../LandingPage-911-y-VV')),

    /*
    | Ruta relativa (dentro de 'path') del archivo de contadores generado.
    */
    'config_datos_js' => 'js/config-datos.js',

];
