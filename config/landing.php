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

    /*
    | Optimización de imágenes de noticias: se convierten a WebP y se
    | redimensionan si superan el ancho máximo (en px). Calidad 0-100.
    */
    'noticias_img_max_ancho' => (int) env('LANDING_IMG_MAX_ANCHO', 1920),
    'noticias_img_calidad'   => (int) env('LANDING_IMG_CALIDAD', 82),

];
