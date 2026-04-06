@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul

REM ── Habilitar ANSI en la terminal
for /f %%a in ('echo prompt $E^| cmd') do set "ESC=%%a"

set "C_RESET=%ESC%[0m"
set "C_BOLD=%ESC%[1m"
set "C_CYAN=%ESC%[96m"
set "C_YELLOW=%ESC%[93m"
set "C_GREEN=%ESC%[92m"
set "C_RED=%ESC%[91m"
set "C_WHITE=%ESC%[97m"
set "C_DIM=%ESC%[2m"
set "C_MAGENTA=%ESC%[95m"
set "C_BLUE=%ESC%[94m"

set PROJECT_PATH=C:\Apache24\htdocs\dashboard_roles
set PHP_PATH=C:\php\php.exe
set LOG_PATH=%PROJECT_PATH%\storage\logs

REM Verificar PHP
if not exist "%PHP_PATH%" (
    echo %C_RED%[ERROR]%C_RESET% PHP no encontrado en %PHP_PATH%
    pause
    exit /b 1
)

REM Verificar proyecto
if not exist "%PROJECT_PATH%\artisan" (
    echo %C_RED%[ERROR]%C_RESET% Proyecto no encontrado en %PROJECT_PATH%
    pause
    exit /b 1
)

cd /d "%PROJECT_PATH%"

:menu
cls
echo.
echo %C_CYAN%%C_BOLD%  ╔══════════════════════════════════════════════════════╗%C_RESET%
echo %C_CYAN%%C_BOLD%  ║       EJECUTOR DE TAREAS  —  Dashboard Roles         ║%C_RESET%
echo %C_CYAN%%C_BOLD%  ╚══════════════════════════════════════════════════════╝%C_RESET%
echo.
echo %C_DIM%  Proyecto : %PROJECT_PATH%%C_RESET%
echo %C_DIM%  PHP      : %PHP_PATH%%C_RESET%
echo.
echo %C_YELLOW%  ┌─  TAREAS DISPONIBLES  ─────────────────────────────────┐%C_RESET%
echo %C_YELLOW%  │%C_RESET%                                                        %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_WHITE%%C_BOLD%[1]%C_RESET% tareas:generar              %C_DIM%Genera recurrentes  (01:00)%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_WHITE%%C_BOLD%[2]%C_RESET% tareas:avisar               %C_DIM%Avisa tareas del dia (08:00)%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_WHITE%%C_BOLD%[3]%C_RESET% cecoco:importar-dia-anterior %C_DIM%Importa eventos     (06:00)%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_WHITE%%C_BOLD%[4]%C_RESET% cecoco:geocodificar          %C_DIM%Geocodifica eventos (06:30)%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_WHITE%%C_BOLD%[5]%C_RESET% telegram:tareas-diarias      %C_DIM%Resumen por Telegram (07:00)%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%                                                        %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_MAGENTA%%C_BOLD%[6]%C_RESET% telegram:polling             %C_DIM%Bot Telegram  (Ctrl+C detiene)%C_RESET% %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_MAGENTA%%C_BOLD%[7]%C_RESET% queue:worker                 %C_DIM%Worker de colas (Ctrl+C detiene)%C_RESET%%C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_BLUE%%C_BOLD%[8]%C_RESET% schedule:run                 %C_DIM%Todas las tareas activas ahora%C_RESET%  %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%                                                        %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_GREEN%%C_BOLD%[9]%C_RESET% TODAS %C_DIM%(opciones 1-5 en secuencia)%C_RESET%               %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%  %C_RED%%C_BOLD%[0]%C_RESET% Salir                                               %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  │%C_RESET%                                                        %C_YELLOW%│%C_RESET%
echo %C_YELLOW%  └────────────────────────────────────────────────────────┘%C_RESET%
echo.
set /p "OPCION=%C_WHITE%%C_BOLD%  > Opcion: %C_RESET%"

if "%OPCION%"=="0" goto :salir

echo.
echo %C_DIM%  ────────────────────────────────────────────────────────%C_RESET%
echo.

if "%OPCION%"=="1" (
    echo %C_CYAN%  >> Ejecutando:%C_RESET% %C_BOLD%tareas:generar%C_RESET%
    echo.
    "%PHP_PATH%" artisan tareas:generar
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="2" (
    echo %C_CYAN%  >> Ejecutando:%C_RESET% %C_BOLD%tareas:avisar%C_RESET%
    echo.
    "%PHP_PATH%" artisan tareas:avisar
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="3" (
    echo %C_CYAN%  >> Ejecutando:%C_RESET% %C_BOLD%cecoco:importar-dia-anterior%C_RESET%
    echo %C_DIM%     Log: %LOG_PATH%\cecoco_importacion.log%C_RESET%
    echo.
    "%PHP_PATH%" artisan cecoco:importar-dia-anterior
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="4" (
    echo %C_CYAN%  >> Ejecutando:%C_RESET% %C_BOLD%cecoco:geocodificar-dia-anterior%C_RESET%
    echo %C_DIM%     Log: %LOG_PATH%\cecoco_geocodificacion.log%C_RESET%
    echo.
    "%PHP_PATH%" artisan cecoco:geocodificar-dia-anterior
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="5" (
    echo %C_CYAN%  >> Ejecutando:%C_RESET% %C_BOLD%telegram:tareas-diarias%C_RESET%
    echo.
    "%PHP_PATH%" artisan telegram:tareas-diarias
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="6" (
    echo %C_MAGENTA%  >> Ejecutando:%C_RESET% %C_BOLD%telegram:polling%C_RESET%
    echo %C_YELLOW%     Presiona Ctrl+C para detener.%C_RESET%
    echo.
    "%PHP_PATH%" artisan telegram:polling
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="7" (
    echo %C_MAGENTA%  >> Iniciando:%C_RESET% %C_BOLD%queue:worker%C_RESET%
    echo %C_YELLOW%     Presiona Ctrl+C para detener.%C_RESET%
    echo.
    :queue_loop
    "%PHP_PATH%" artisan queue:work --sleep=3 --tries=3 --timeout=600
    if !errorlevel! neq 0 (
        echo.
        echo %C_YELLOW%  [!] Worker detenido con error. Reiniciando en 5 segundos...%C_RESET%
        timeout /t 5 /nobreak >nul
        goto :queue_loop
    )
    set "EXITCODE=0"
    goto :resultado
)

if "%OPCION%"=="8" (
    echo %C_BLUE%  >> Ejecutando:%C_RESET% %C_BOLD%schedule:run%C_RESET%
    echo.
    "%PHP_PATH%" artisan schedule:run --verbose
    set "EXITCODE=!errorlevel!"
    goto :resultado
)

if "%OPCION%"=="9" (
    echo %C_GREEN%  >> Ejecutando TODAS las tareas en secuencia...%C_RESET%
    echo.
    set "ERRORES=0"

    echo %C_CYAN%  [1/5]%C_RESET% tareas:generar
    "%PHP_PATH%" artisan tareas:generar
    if !errorlevel! neq 0 (
        echo   %C_RED%[FALLO]%C_RESET% tareas:generar
        set /a ERRORES+=1
    ) else (
        echo   %C_GREEN%[OK]%C_RESET% tareas:generar
    )
    echo.

    echo %C_CYAN%  [2/5]%C_RESET% cecoco:importar-dia-anterior
    "%PHP_PATH%" artisan cecoco:importar-dia-anterior
    if !errorlevel! neq 0 (
        echo   %C_RED%[FALLO]%C_RESET% cecoco:importar-dia-anterior
        set /a ERRORES+=1
    ) else (
        echo   %C_GREEN%[OK]%C_RESET% cecoco:importar-dia-anterior
    )
    echo.

    echo %C_CYAN%  [3/5]%C_RESET% cecoco:geocodificar-dia-anterior
    "%PHP_PATH%" artisan cecoco:geocodificar-dia-anterior
    if !errorlevel! neq 0 (
        echo   %C_RED%[FALLO]%C_RESET% cecoco:geocodificar-dia-anterior
        set /a ERRORES+=1
    ) else (
        echo   %C_GREEN%[OK]%C_RESET% cecoco:geocodificar-dia-anterior
    )
    echo.

    echo %C_CYAN%  [4/5]%C_RESET% telegram:tareas-diarias
    "%PHP_PATH%" artisan telegram:tareas-diarias
    if !errorlevel! neq 0 (
        echo   %C_RED%[FALLO]%C_RESET% telegram:tareas-diarias
        set /a ERRORES+=1
    ) else (
        echo   %C_GREEN%[OK]%C_RESET% telegram:tareas-diarias
    )
    echo.

    echo %C_CYAN%  [5/5]%C_RESET% tareas:avisar
    "%PHP_PATH%" artisan tareas:avisar
    if !errorlevel! neq 0 (
        echo   %C_RED%[FALLO]%C_RESET% tareas:avisar
        set /a ERRORES+=1
    ) else (
        echo   %C_GREEN%[OK]%C_RESET% tareas:avisar
    )
    echo.

    if !ERRORES! equ 0 (
        set "EXITCODE=0"
    ) else (
        set "EXITCODE=1"
    )
    goto :resultado
)

echo %C_RED%  [ERROR]%C_RESET% Opcion invalida: %OPCION%
goto :post_tarea

:resultado
echo.
echo %C_DIM%  ────────────────────────────────────────────────────────%C_RESET%
echo.
if "%OPCION%"=="9" (
    if !ERRORES! equ 0 (
        echo %C_GREEN%  Secuencia completada sin errores.%C_RESET%
    ) else (
        echo %C_RED%  Secuencia finalizada con !ERRORES! fallo(s).%C_RESET%
    )
) else (
    if "!EXITCODE!"=="0" (
        echo %C_GREEN%  Tarea finalizada correctamente.%C_RESET%
    ) else (
        echo %C_RED%  Tarea finalizada con errores.  Codigo: !EXITCODE!%C_RESET%
    )
)
echo.
echo %C_DIM%  Logs disponibles en:%C_RESET%
echo %C_DIM%    %LOG_PATH%\laravel.log%C_RESET%
echo %C_DIM%    %LOG_PATH%\cecoco_importacion.log%C_RESET%
echo %C_DIM%    %LOG_PATH%\cecoco_geocodificacion.log%C_RESET%

:post_tarea
echo.
echo %C_DIM%  ────────────────────────────────────────────────────────%C_RESET%
echo.
echo %C_WHITE%  Que deseas hacer?%C_RESET%
echo.
echo   %C_WHITE%%C_BOLD%[M]%C_RESET%  Volver al menu principal
echo   %C_WHITE%%C_BOLD%[0]%C_RESET%  Salir
echo.
set /p "SIGUIENTE=%C_WHITE%%C_BOLD%  > Opcion: %C_RESET%"

if /i "%SIGUIENTE%"=="M" goto :menu
if "%SIGUIENTE%"=="0" goto :salir

REM Cualquier otra tecla vuelve al menu
goto :menu

:salir
echo.
echo %C_CYAN%  Hasta luego!%C_RESET%
echo.
exit /b 0
