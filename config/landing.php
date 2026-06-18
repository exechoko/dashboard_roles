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

    /*
    | Rutas relativas (dentro de 'path') para el módulo de Noticias:
    | - noticias_json: archivo de datos que consume la web estática.
    | - noticias_img_dir: carpeta donde se guardan las imágenes subidas.
    */
    'noticias_json'    => 'data/noticias.json',
    'noticias_img_dir' => 'images/noticias',

];
