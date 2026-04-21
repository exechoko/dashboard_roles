@echo off
setlocal EnableDelayedExpansion
REM Script para instalar el worker de Laravel Queue como servicio de Windows
REM Requiere NSSM (Non-Sucking Service Manager)
REM Ejecutar como Administrador

echo ========================================
echo Instalador de Servicio Laravel Queue
echo ========================================
echo.

REM Verificar si se ejecuta como administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Este script debe ejecutarse como Administrador
    echo Haz clic derecho en el archivo y selecciona "Ejecutar como administrador"
    pause
    exit /b 1
)

REM Configuración
set SERVICE_NAME=LaravelQueueWorker
set PROJECT_PATH=C:\Apache24\htdocs\equipamiento
set PHP_PATH=C:\php\php.exe
set NSSM_PATH=C:\nssm\nssm.exe

echo Verificando requisitos...
echo.

REM Verificar si NSSM existe
if not exist "%NSSM_PATH%" (
    echo ERROR: NSSM no encontrado en %NSSM_PATH%
    echo.
    echo Por favor, descarga NSSM desde: https://nssm.cc/download
    echo Extrae nssm.exe en C:\nssm\
    echo.
    pause
    exit /b 1
)

REM Verificar si PHP existe
if not exist "%PHP_PATH%" (
    echo ERROR: PHP no encontrado en %PHP_PATH%
    echo.
    echo Por favor, ajusta la variable PHP_PATH en este script
    echo para que apunte a tu instalación de PHP
    echo.
    pause
    exit /b 1
)

REM Verificar si el proyecto existe
if not exist "%PROJECT_PATH%\artisan" (
    echo ERROR: Proyecto Laravel no encontrado en %PROJECT_PATH%
    echo.
    echo Por favor, ajusta la variable PROJECT_PATH en este script
    echo.
    pause
    exit /b 1
)

echo [OK] NSSM encontrado
echo [OK] PHP encontrado
echo [OK] Proyecto Laravel encontrado
echo.

REM Verificar si el servicio ya existe
echo Verificando si el servicio ya existe...
sc query "%SERVICE_NAME%" >nul 2>&1
if !errorlevel! equ 0 (
    echo.
    echo [AVISO] El servicio %SERVICE_NAME% ya existe.
    echo.
    set /p "REINSTALL=¿Deseas reinstalarlo? (S para si, N para cancelar): "
    echo.
    if /i "!REINSTALL!" neq "S" (
        echo Instalacion cancelada.
        pause
        exit /b 0
    )
    echo Deteniendo servicio existente...
    net stop "%SERVICE_NAME%" >nul 2>&1
    timeout /t 3 /nobreak >nul
    echo Eliminando servicio existente...
    "%NSSM_PATH%" remove "%SERVICE_NAME%" confirm
    timeout /t 3 /nobreak >nul
    echo [OK] Servicio anterior eliminado.
    echo.
)

echo.
echo Instalando servicio %SERVICE_NAME%...
echo.

REM Instalar el servicio con NSSM
REM --max-time=3600  : reinicia el proceso cada 1 hora para liberar memoria
REM --tries=3        : reintenta jobs fallidos hasta 3 veces
REM --timeout=600    : mata jobs que tarden más de 10 minutos
REM --sleep=3        : espera 3 segundos entre polls cuando la cola está vacía
"%NSSM_PATH%" install "%SERVICE_NAME%" "%PHP_PATH%" "artisan" "queue:work" "--sleep=3" "--tries=3" "--timeout=600" "--max-time=3600"
if !errorlevel! neq 0 (
    echo [ERROR] Fallo al instalar el servicio con NSSM. Codigo: !errorlevel!
    pause
    exit /b 1
)
echo [OK] Servicio creado.

REM Configurar el directorio de trabajo
"%NSSM_PATH%" set "%SERVICE_NAME%" AppDirectory "%PROJECT_PATH%"

REM Configurar descripción del servicio
"%NSSM_PATH%" set "%SERVICE_NAME%" Description "Laravel Queue Worker para procesamiento de importaciones de eventos CECOCO"

REM Configurar inicio automático
"%NSSM_PATH%" set "%SERVICE_NAME%" Start SERVICE_AUTO_START

REM Configurar reinicio en caso de fallo
"%NSSM_PATH%" set "%SERVICE_NAME%" AppExit Default Restart
"%NSSM_PATH%" set "%SERVICE_NAME%" AppRestartDelay 5000

REM Configurar salida de logs
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStdout "%PROJECT_PATH%\storage\logs\queue-worker.log"
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStderr "%PROJECT_PATH%\storage\logs\queue-worker-error.log"

REM Rotar logs
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStdoutCreationDisposition 4
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStderrCreationDisposition 4

echo.
echo Iniciando servicio...
net start "%SERVICE_NAME%"
if !errorlevel! neq 0 (
    echo [ERROR] No se pudo iniciar el servicio. Codigo: !errorlevel!
    echo Verificar con: sc query %SERVICE_NAME%
    pause
    exit /b 1
)
echo [OK] Servicio iniciado correctamente.

echo.
echo ========================================
echo Instalación completada
echo ========================================
echo.
echo Servicio: %SERVICE_NAME%
echo Estado: Ejecutándose
echo.
echo Comandos útiles:
echo   - Ver estado:    sc query %SERVICE_NAME%
echo   - Iniciar:       net start %SERVICE_NAME%
echo   - Detener:       net stop %SERVICE_NAME%
echo   - Reiniciar:     net stop %SERVICE_NAME% ^&^& net start %SERVICE_NAME%
echo   - Ver logs:      type "%PROJECT_PATH%\storage\logs\queue-worker.log"
echo.
echo El servicio se iniciará automáticamente con Windows.
echo.
pause
