<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Evita que putenv() filtre variables de entorno entre requests
|--------------------------------------------------------------------------
|
| Apache en Windows corre PHP en un único proceso multihilo (MPM WinNT).
| putenv() escribe el entorno del proceso y PHP lo deshace al terminar
| cada request. Si el request A carga el .env y el B arranca en paralelo,
| B ve APP_KEY "ya definida" (immutable) y no la copia a $_ENV; cuando A
| termina y la borra, B queda sin APP_KEY/DB_*: 500 aleatorios. Sin
| putenv, cada request lee sólo sus propios $_ENV/$_SERVER.
|
*/

Env::disablePutenv();

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
