<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Partes diarios — textos fijos del oficio
    |--------------------------------------------------------------------------
    */

    'destinatario' => [
        env('FLOTA911_DESTINATARIO_L1', 'JEFE DE DIVISIÓN 911 Y VIDEO VIG.'),
        env('FLOTA911_DESTINATARIO_L2', 'LIC. CRIO. INSP. ZUNINO JUAN'),
    ],

    // Prefijo que la Sección Patrulla antepone al número de móvil ("MÓVIL JP 1007").
    'prefijo_movil_patrulla' => env('FLOTA911_PREFIJO_MOVIL', 'JP'),

    'membrete' => [
        'moviles' => [
            'POLICÍA DE ENTRE RÍOS',
            'DIRECCIÓN GRAL. DE OP. Y SEGURIDAD PÚBLICA',
            'DIVISIÓN 911 Y VIDEO VIGILANCIA',
        ],
        'motos' => [
            'MINISTERIO DE SEGURIDAD Y JUSTICIA',
            'POLICÍA DE ENTRE RÍOS 24/7',
        ],
        'novedades' => [
            'POLICÍA DE ENTRE RÍOS',
            'DIRECCIÓN GRAL. DE OP. Y SEGURIDAD PÚBLICA',
            'DIVISIÓN 911 Y VIDEO VIGILANCIA',
        ],
    ],

];
