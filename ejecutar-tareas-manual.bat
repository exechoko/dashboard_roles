@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul

set PROJECT_PATH=C:\Apache24\htdocs\dashboard_roles
set PHP_PATH=C:\php\php.exe
set LOG_PATH=%PROJECT_PATH%\storage\logs

echo =========================================
echo  Ejecutor Manual de Tareas Programadas
echo  Dashboard Roles - Laravel
echo =========================================
echo.

REM Verificar PHP
if not exist "%PHP_PATH%" (
    echo [ERROR] PHP no encontrado en %PHP_PATH%
    pause
    exit /b 1
)

REM Verificar proyecto
if not exist "%PROJECT_PATH%\artisan" (
    echo [ERROR] Proyecto no encontrado en %PROJECT_PATH%
    pause
    exit /b 1
)

cd /d "%PROJECT_PATH%"

echo Selecciona la tarea a ejecutar:
echo.
echo  [1] tareas:generar         - Genera tareas recurrentes del dia (01:00)
echo  [2] tareas:avisar          - Envia aviso de tareas del dia (08:00)
echo  [3] cecoco:importar-dia-anterior    - Importa eventos CECOCO del dia anterior (06:00)
echo  [4] cecoco:geocodificar-dia-anterior - Geocodifica eventos CECOCO (06:30)
echo  [5] telegram:tareas-diarias         - Envia resumen diario por Telegram (07:00)
echo  [6] telegram:polling                - Ejecuta el bot de Telegram (cada minuto)
echo  [7] queue:worker           - Inicia el worker de colas
echo  [8] schedule:run           - Ejecuta TODAS las tareas que correspondan ahora
echo  [9] TODAS (1 al 5 en secuencia)
echo  [0] Salir
echo.
set /p OPCION="Opcion: "

if "%OPCION%"=="0" exit /b 0

echo.
echo ------------------------------------------

if "%OPCION%"=="1" (
    echo Ejecutando: tareas:generar
    echo.
    "%PHP_PATH%" artisan tareas:generar
    goto :fin
)

if "%OPCION%"=="2" (
    echo Ejecutando: tareas:avisar
    echo.
    "%PHP_PATH%" artisan tareas:avisar
    goto :fin
)

if "%OPCION%"=="3" (
    echo Ejecutando: cecoco:importar-dia-anterior
    echo Log: %LOG_PATH%\cecoco_importacion.log
    echo.
    "%PHP_PATH%" artisan cecoco:importar-dia-anterior
    goto :fin
)

if "%OPCION%"=="4" (
    echo Ejecutando: cecoco:geocodificar-dia-anterior
    echo Log: %LOG_PATH%\cecoco_geocodificacion.log
    echo.
    "%PHP_PATH%" artisan cecoco:geocodificar-dia-anterior
    goto :fin
)

if "%OPCION%"=="5" (
    echo Ejecutando: telegram:tareas-diarias
    echo.
    "%PHP_PATH%" artisan telegram:tareas-diarias
    goto :fin
)

if "%OPCION%"=="6" (
    echo Ejecutando: telegram:polling
    echo Presiona Ctrl+C para detener.
    echo.
    "%PHP_PATH%" artisan telegram:polling
    goto :fin
)

if "%OPCION%"=="7" (
    echo Iniciando queue:worker
    echo Presiona Ctrl+C para detener.
    echo.
    :queue_loop
    "%PHP_PATH%" artisan queue:work --sleep=3 --tries=3 --timeout=600
    if !errorlevel! neq 0 (
        echo Worker detenido con error. Reiniciando en 5 segundos...
        timeout /t 5 /nobreak >nul
    )
    goto :queue_loop
)

if "%OPCION%"=="8" (
    echo Ejecutando: schedule:run
    echo.
    "%PHP_PATH%" artisan schedule:run --verbose
    goto :fin
)

if "%OPCION%"=="9" (
    echo Ejecutando todas las tareas en secuencia...
    echo.

    echo [1/5] tareas:generar
    "%PHP_PATH%" artisan tareas:generar
    if !errorlevel! neq 0 (echo [FALLO] tareas:generar) else (echo [OK] tareas:generar)
    echo.

    echo [2/5] cecoco:importar-dia-anterior
    "%PHP_PATH%" artisan cecoco:importar-dia-anterior
    if !errorlevel! neq 0 (echo [FALLO] cecoco:importar-dia-anterior) else (echo [OK] cecoco:importar-dia-anterior)
    echo.

    echo [3/5] cecoco:geocodificar-dia-anterior
    "%PHP_PATH%" artisan cecoco:geocodificar-dia-anterior
    if !errorlevel! neq 0 (echo [FALLO] cecoco:geocodificar-dia-anterior) else (echo [OK] cecoco:geocodificar-dia-anterior)
    echo.

    echo [4/5] telegram:tareas-diarias
    "%PHP_PATH%" artisan telegram:tareas-diarias
    if !errorlevel! neq 0 (echo [FALLO] telegram:tareas-diarias) else (echo [OK] telegram:tareas-diarias)
    echo.

    echo [5/5] tareas:avisar
    "%PHP_PATH%" artisan tareas:avisar
    if !errorlevel! neq 0 (echo [FALLO] tareas:avisar) else (echo [OK] tareas:avisar)
    echo.

    goto :fin
)

echo [ERROR] Opcion invalida: %OPCION%

:fin
echo.
echo ------------------------------------------
if "%OPCION%"=="7" goto :eof
if "%OPCION%"=="6" goto :eof
echo Tarea finalizada. Codigo de salida: %errorlevel%
echo.
echo Logs disponibles en:
echo   %LOG_PATH%\laravel.log
echo   %LOG_PATH%\cecoco_importacion.log
echo   %LOG_PATH%\cecoco_geocodificacion.log
echo.
pause
