<?php

/*
|--------------------------------------------------------------------------
| Categorías y Subsistemas de Tickets PG
|--------------------------------------------------------------------------
|
| Listas controladas extraídas de "CONTROL DE INCIDENCIAS 911.xlsm"
| (hoja "Patagonia").
|
| - 'categorias': valores de la columna I ("Categoría"). Se guardan en
|   el campo tipo_equipo del ticket.
| - 'subsistemas': rango con nombre "Listado_Items" (hoja "Listado Items"
|   A2:A88), usado como validación de la columna Q ("Subsist. donde se
|   produjo inc."). Determina el % de falla / cálculo de multa. Se guarda
|   en el campo subsistema del ticket.
|
*/

return [

    'categorias' => [
        'Tetra',
        'Tetra: Instalación / Desinstalación',
        'Cámara',
        'CCTV',
        'Cecoco',
        'General',
        'Prestación',
        'Infraestructura',
        'Internet',
        'Aire Acondicionado',
        'Mantenimiento',
        'Telefonía',
        'Grabación',
        'Nebula',
        'Red',
        'Dependencias',
        'Informativo',
        'Solicitud de combustible',
    ],

    /*
    | Bloque de campos dinámicos que se habilita según la categoría elegida.
    | Valores posibles: 'camaras', 'tetra', 'oficina'. Cualquier categoría sin
    | entrada usa el bloque 'generico' (solo texto libre + fechas de falla).
    */
    'campos' => [
        'Cámara'                               => 'camaras',
        'CCTV'                                 => 'camaras',
        'Tetra'                                => 'tetra',
        'Tetra: Instalación / Desinstalación'  => 'tetra',
        'Aire Acondicionado'                   => 'oficina',
    ],

    /*
    | Mapeo categoría (label) => id de categoría en HESK (tabla del help desk).
    | Verificado desle /admin/new_ticket.php. Las categorías sin equivalente
    | directo se derivan a la más cercana.
    */
    'hesk_categorias' => [
        'General'                             => 1,
        'Grabación'                           => 3,
        'Cámara'                              => 4,
        'Cecoco'                              => 5,
        'Nebula'                              => 6,
        'CCTV'                                => 7,
        'Infraestructura'                     => 8,
        'Tetra'                               => 9,
        'Tetra: Instalación / Desinstalación' => 9,
        'Dependencias'                        => 10,
        'Telefonía'                           => 11,
        'Prestación'                          => 12,
        'Mantenimiento'                       => 16,
        'Internet'                            => 17,
        'Aire Acondicionado'                  => 18,
        'Red'                                 => 8,
        'Informativo'                         => 1,
        'Solicitud de combustible'            => 12,
    ],

    /*
    | Mapeo prioridad (label) => valor de prioridad en HESK. OJO: HESK invierte
    | la escala (0 = Crítica ... 3 = Baja).
    */
    'hesk_prioridades' => [
        'Critico' => 0,
        'Alto'    => 1,
        'Medio'   => 2,
        'Bajo'    => 3,
    ],

    /*
    | Subsistema preseleccionado al elegir una categoría (editable después).
    | Debe coincidir con un valor de la lista 'subsistemas'.
    */
    'subsistema_por_categoria' => [
        'Tetra'                               => 'Sist. TETRA - Comunicación - Por Terminales TETRA',
        'Tetra: Instalación / Desinstalación' => 'Sist. TETRA - Comunicación - Por Terminales TETRA',
        'Cámara'                              => 'Sist. CCTV - Cámaras - Por cámara',
        'CCTV'                                => 'Sist. CCTV - Mód. Monitoreo - Por cámara',
        'Cecoco'                             => 'Emerg. 911 - Mód Com. Emergencias - Puestos Emergencias 911',
        'Aire Acondicionado'                 => 'Infraest. - Climatización técnica - Total',
        'Infraestructura'                    => 'Infraest. - Total',
        'Internet'                           => 'Infraest. - Sist. de red de F.O. externo (conectividad) - Total',
        'Telefonía'                          => 'Infraest. - Sistemas de Telefonía - Total',
    ],

    'subsistemas' => [
        'Sist. CCTV - Mód. Monitoreo - Por cámara',
        'Sist. CCTV - Mód. Monitoreo - Por puesto',
        'Sist. CCTV - Mód. Monitoreo - Total',
        'Sist. CCTV - Mód. Monitoreo - Potencial',
        'Sist. CCTV - Mód. Grabación - Por cámara',
        'Sist. CCTV - Mód. Grabación - Total',
        'Sist. CCTV - Mód. Grabación - Potencial',
        'Sist. CCTV - Mód. Admin. y Extracción - Por cámara',
        'Sist. CCTV - Mód. Admin. y Extracción - Por puesto',
        'Sist. CCTV - Mód. Admin. y Extracción - Total',
        'Sist. CCTV - Mód. Admin. y Extracción - Potencial',
        'Sist. CCTV - Cámaras - Por cámara',
        'Sist. CCTV - Cámaras - Total',
        'Sist. CCTV - Cámaras - Potencial',
        'Sist. CCTV - Total',
        'Sist. CCTV - Potencial',
        'Sist. TETRA - Mód. Grabación - Por Terminales TETRA',
        'Sist. TETRA - Mód. Grabación - Total',
        'Sist. TETRA - Mód. Grabación - Potencial',
        'Sist. TETRA - Mód. Admin y extracción - Por Terminales TETRA',
        'Sist. TETRA - Mód. Admin y extracción - Por Radio Base',
        'Sist. TETRA - Mód. Admin y extracción - Centro de control TETRA',
        'Sist. TETRA - Mód. Admin y extracción - Total',
        'Sist. TETRA - Mód. Admin y extracción - Potencial',
        'Sist. TETRA - Comunicación - Por Terminales TETRA',
        'Sist. TETRA - Comunicación - Por Radio Base',
        'Sist. TETRA - Comunicación - Centro de control TETRA',
        'Sist. TETRA - Comunicación - Total',
        'Sist. TETRA - Comunicación - Potencial',
        'Sist. TETRA - Total',
        'Sist. TETRA - Potencial',
        'Emerg. 911 - Modulo Servicios - por Comunic Telefónicas (100%)',
        'Emerg. 911 - Modulo Servicios - por Puestos Emergencias 911',
        'Emerg. 911 - Modulo Servicios - Total',
        'Emerg. 911 - Modulo Servicios - Potencial',
        'Emerg. 911 - Mód Com. Emergencias - % Com. TETRA (ya calculado)',
        'Emerg. 911 - Mód Com. Emergencias - % Comunic Telef. a Nº Total',
        'Emerg. 911 - Mód Com. Emergencias - Puestos Emergencias 911',
        'Emerg. 911 - Mód Com. Emergencias - Total',
        'Emerg. 911 - Mód Com. Emergencias - Potencial',
        'Emerg. 911 - Modulo GIS - Por Term. TETRA con GPS (50%)',
        'Emerg. 911 - Modulo GIS - por Posici Comunic Telef (50%)',
        'Emerg. 911 - Modulo GIS - por Puestos Emergencias 911',
        'Emerg. 911 - Modulo GIS - Total',
        'Emerg. 911 - Modulo GIS - Potencial',
        'Emerg. 911 - Modulo CCTV - Por cámara',
        'Emerg. 911 - Modulo CCTV - Puestos Emergencias 911',
        'Emerg. 911 - Modulo CCTV - Total',
        'Emerg. 911 - Modulo CCTV - Potencial',
        'Emerg. 911 - Mod Administ Extracción - Puestos Emergencias 911',
        'Emerg. 911 - Mod Administ Extracción - Total',
        'Emerg. 911 - Mod Administ Extracción - Potencial',
        'Emerg. 911 - Puestos Emergencias 911 - por Puestos Emergencias 911',
        'Emerg. 911 - Puestos Emergencias 911 - Total',
        'Emerg. 911 - Puestos Emergencias 911 - Potencial',
        'Emerg. 911 - % Comunicac. TETRA (100%)',
        'Emerg. 911 - % de Comunic Telef. (100%)',
        'Emerg. 911 - Total',
        'Emerg. 911 - Potencial',
        'Infraest. - Energía Gral. Generadores - Total',
        'Infraest. - Energía Gral. Generadores - Potencial',
        'Infraest. - Energía segurizada UPS - Total',
        'Infraest. - Energía segurizada UPS - Potencial',
        'Infraest. - Sist. de red de F.O. externo (conectividad) - Total',
        'Infraest. - Sist. de red de F.O. externo (conectividad) - Potencial',
        'Infraest. - Cableado y sist. de red int. Ctro. Control - Total',
        'Infraest. - Cableado y sist. de red int. Ctro. Control - Potencial',
        'Infraest. - Sistemas de Telefonía - Total',
        'Infraest. - Sistemas de Telefonía - Potencial',
        'Infraest. - Terminales de telefonía - Total',
        'Infraest. - Terminales de telefonía - Potencial',
        'Infraest. - Climatización técnica - Total',
        'Infraest. - Climatización técnica - Potencial',
        'Infraest. - Total',
        'Infraest. - Potencial',
        'Prest. Serv. - Tareas de Reclamos y ajustes - % Demoras resolución',
        'Prest. Serv. - Tareas de Reclamos y ajustes - % Demoras Atención',
        'Prest. Serv. - Tareas de Reclamos y ajustes - Total',
        'Prest. Serv. - Tareas de Reclamos y ajustes - Potencial',
        'Prest. Serv. - Soporte - Total',
        'Prest. Serv. - Soporte - Potencial',
        'Prest. Serv. - Sist. administración reclamos - Total',
        'Prest. Serv. - Sist. administración reclamos - Potencial',
        'Prest. Serv. - Capacitación - Total',
        'Prest. Serv. - Capacitación - Potencial',
        'Prest. Serv. - Total',
        'Prest. Serv. - Potencial',
    ],

];
