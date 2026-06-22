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
    | URL pública del sitio, usada para la vista previa dentro del panel.
    | Muestra la versión publicada (los cambios aparecen al guardar).
    */
    'url' => env('LANDING_URL', 'https://div911.stper.com.ar'),

    /*
    | Ruta relativa (dentro de 'path') del archivo de contadores generado.
    */
    'config_datos_js' => 'js/config-datos.js',

    /*
    | Ruta relativa del archivo de textos editables generado.
    */
    'config_textos_js' => 'js/config-textos.js',

    /*
    | Línea de tiempo de la página Historia (cards generadas) y carpeta de
    | imágenes que se muestran solapadas sobre cada tarjeta del timeline.
    */
    'historia_js'      => 'js/historia-data.js',
    'historia_img_dir' => 'images/historia',

    /*
    | Cards de la página Tecnología: archivo JS generado, carpeta de imágenes
    | y paleta de colores (deben coincidir con las clases color-* del CSS).
    */
    'tecnologia_js'      => 'js/tecnologia-data.js',
    'tecnologia_img_dir' => 'images/tecnologia',
    'tecnologia_colores' => [
        'blue'   => 'Azul',
        'green'  => 'Verde',
        'purple' => 'Violeta',
        'pink'   => 'Rosa',
        'indigo' => 'Índigo',
        'orange' => 'Naranja',
        'red'    => 'Rojo',
    ],

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

    /*
    | Dependencias / comisarías: archivo JS generado y catálogo de categorías
    | (deben coincidir con los filtros definidos en js/dependencias.js).
    */
    'dependencias_js' => 'js/dependencias-data.js',
    'dependencias_categorias' => [
        'ciudad-parana'             => 'Comisarías de Paraná',
        'ciudad-colonia-avellaneda' => 'Colonia Avellaneda',
        'ciudad-oro-verde'          => 'Oro Verde',
        'ciudad-san-benito'         => 'San Benito',
        'departamental'             => 'Departamental',
        'divisiones'                => 'Divisiones',
    ],

];
