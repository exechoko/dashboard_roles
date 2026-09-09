<?php

return [
    /*
     | Monitoreo de infraestructura de red propia (PCs, servidores, cámaras
     | internas, routers/switches) vía ping + SNMP, reusando el inventario de
     | la tabla dispositivos_edificio. A diferencia de LibreNMS (que sólo ve
     | 172.40.30.x), esto cubre las subredes propias de la dependencia.
     */

    // Community string SNMP de solo lectura. Configurado en cada equipo por
    // scripts/habilitar-snmp.bat.
    'community' => env('INFRAESTRUCTURA_SNMP_COMMUNITY', 'public'),

    // Timeout de una consulta SNMP individual, en microsegundos.
    'snmp_timeout_us' => (int) env('INFRAESTRUCTURA_SNMP_TIMEOUT_US', 1500000),

    // Reintentos SNMP ante timeout.
    'snmp_reintentos' => (int) env('INFRAESTRUCTURA_SNMP_REINTENTOS', 1),

    // Timeout de ping, en milisegundos (equivalente a `ping -w`).
    'ping_timeout_ms' => (int) env('INFRAESTRUCTURA_PING_TIMEOUT_MS', 700),

    // Pausa entre consultas SNMP sucesivas al mismo equipo, en milisegundos.
    // El agente SNMP de Windows tiene rate-limit: 4 consultas seguidas sin
    // pausa hacen fallar alguna (verificado contra el Dell R420).
    'pausa_entre_oids_ms' => (int) env('INFRAESTRUCTURA_PAUSA_MS', 300),

    // Umbrales (%) que disparan alerta por Telegram.
    'umbral_cpu' => (int) env('INFRAESTRUCTURA_UMBRAL_CPU', 85),
    'umbral_ram' => (int) env('INFRAESTRUCTURA_UMBRAL_RAM', 90),
    'umbral_disco' => (int) env('INFRAESTRUCTURA_UMBRAL_DISCO', 90),

    // Histéresis: el equipo se considera recuperado recién cuando baja de
    // (umbral - histeresis), para que no oscile alerta/recuperado en el borde.
    'histeresis' => (int) env('INFRAESTRUCTURA_HISTERESIS', 5),

    // Minutos entre re-avisos de un mismo equipo que sigue en alerta.
    'cooldown_minutos' => (int) env('INFRAESTRUCTURA_COOLDOWN_MINUTOS', 30),

    // Chat IDs de Telegram para las alertas (separados por coma). Vacío = usa
    // el TELEGRAM_CHAT_ID por defecto del bot.
    'telegram_chat_ids' => env('INFRAESTRUCTURA_TELEGRAM_CHAT_IDS', ''),
];
