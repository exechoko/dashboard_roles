<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        // Cola dedicada a la indexación de backups .mbox: son jobs que pueden
        // tardar horas, así que necesitan un retry_after propio (si usaran el
        // de 'database' de 90s, el worker los re-entregaría en paralelo antes
        // de que terminen) y no deben competir con los jobs cortos de 'default'.
        'mbox' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'mbox',
            'retry_after' => 86400,
            'after_commit' => false,
        ],

<<<<<<< HEAD
        // Cola para la Plataforma de Descargas: procesamiento de archivos,
        // compresión ZIP, generación de QR, envío de notificaciones.
        // Timeout de 2 horas para soportar archivos grandes.
        'descargas' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'descargas',
            'retry_after' => 7200,
=======
        // Cola dedicada a backups/restore de la BD (Configuración del Sistema >
        // Backups): mysqldump/mysql de una base grande puede tardar varios
        // minutos, igual que 'mbox' arriba — mismo motivo, mismo retry_after.
        'backups' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'backups',
            'retry_after' => 86400,
>>>>>>> origin/master
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
