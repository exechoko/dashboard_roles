<?php

/*
|--------------------------------------------------------------------------
| Catálogo de textos editables de la web pública
|--------------------------------------------------------------------------
|
| Cada bloque editable se marca en el HTML con data-edit="<clave>" y se
| declara acá con su etiqueta amigable, tipo de campo y valor por defecto.
| Cada grupo representa una página ('pagina' = archivo .html) para que en el
| panel se vea claramente qué página se está editando.
| Para sumar un texto editable: marcá el elemento en el HTML y agregá una
| entrada en este catálogo. Solo texto plano (sin HTML interno).
|
*/

return [

    'home' => [
        'label'  => 'Inicio',
        'pagina' => 'index.html',
        'textos' => [
            'home.hero_subtitulo' => [
                'label'   => 'Subtítulo principal (hero)',
                'tipo'    => 'textarea',
                'default' => 'Cada segundo cuenta. Llamadas de emergencia, eventos en tiempo real, móviles geolocalizados y cámaras monitoreadas las 24 horas para proteger a la comunidad.',
            ],
            'home.command_titulo' => [
                'label'   => 'Centro de comando · título',
                'tipo'    => 'text',
                'default' => 'Una red operativa entre móviles, cámaras y ciudadanía.',
            ],
            'home.command_texto' => [
                'label'   => 'Centro de comando · texto',
                'tipo'    => 'textarea',
                'default' => 'Cuando ingresa una llamada al 911, cada segundo cuenta. Los operadores reciben el alerta, el despacho coordina por radio al móvil más cercano y las cámaras de videovigilancia siguen el evento en tiempo real. Todo el sistema trabaja en simultáneo para que la respuesta llegue rápido, donde se la necesita.',
            ],
            'home.comunidad_texto' => [
                'label'   => 'Compromiso con la Comunidad · texto',
                'tipo'    => 'textarea',
                'default' => 'La División 911 y Videovigilancia trabaja de manera permanente para mejorar la seguridad ciudadana, incorporando tecnología, optimizando procesos y fortaleciendo la articulación con otras instituciones. El compromiso es brindar una respuesta rápida, eficiente y profesional ante cada emergencia, contribuyendo al bienestar de toda la comunidad.',
            ],
        ],
    ],

    'estadisticas' => [
        'label'  => 'Estadísticas',
        'pagina' => 'estadisticas.html',
        'textos' => [
            'estadisticas.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'ESTADÍSTICAS',
            ],
            'estadisticas.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'text',
                'default' => 'Métricas y análisis de eventos · 16 may – 16 jun 2026',
            ],
        ],
    ],

    'historia' => [
        'label'  => 'Historia',
        'pagina' => 'historia.html',
        'textos' => [
            'historia.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Nuestra historia',
            ],
            'historia.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Más de una década protegiendo a la comunidad con tecnología y compromiso',
            ],
        ],
    ],

    'tecnologia' => [
        'label'  => 'Tecnología',
        'pagina' => 'tecnologia.html',
        'textos' => [
            'tecnologia.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Tecnología aplicada',
            ],
            'tecnologia.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Sistemas y operaciones de la División 911 y V.V',
            ],
        ],
    ],

    'dependencias' => [
        'label'  => 'Dependencias',
        'pagina' => 'dependencias.html',
        'textos' => [
            'dependencias.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Dependencias y contactos',
            ],
        ],
    ],

    'violencia_genero' => [
        'label'  => 'Violencia de Género',
        'pagina' => 'violencia-genero.html',
        'textos' => [
            'vg.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Sección Violencia de Género',
            ],
            'vg.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Atención exclusiva y monitoreo 24/7 para la protección de víctimas',
            ],
        ],
    ],

    'educacion' => [
        'label'  => 'Educación',
        'pagina' => 'educacion.html',
        'textos' => [
            'educacion.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Educación y Prevención',
            ],
            'educacion.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Recursos para la comunidad · Información que salva vidas',
            ],
        ],
    ],

    'faq' => [
        'label'  => 'Preguntas frecuentes',
        'pagina' => 'faq.html',
        'textos' => [
            'faq.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Preguntas frecuentes',
            ],
            'faq.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Resolvé tus dudas sobre el servicio de emergencias 911',
            ],
        ],
    ],

    'galeria' => [
        'label'  => 'Galería',
        'pagina' => 'galeria.html',
        'textos' => [
            'galeria.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Galería de imágenes',
            ],
            'galeria.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Conoce nuestras instalaciones y equipamiento',
            ],
        ],
    ],

    'totems' => [
        'label'  => 'Tótems',
        'pagina' => 'totems.html',
        'textos' => [
            'totems.hero_titulo' => [
                'label'   => 'Título de la página',
                'tipo'    => 'text',
                'default' => 'Tótems de Seguridad',
            ],
            'totems.hero_subtitulo' => [
                'label'   => 'Subtítulo de la página',
                'tipo'    => 'textarea',
                'default' => 'Puntos de alerta y comunicación directa con la División 911',
            ],
        ],
    ],

];
