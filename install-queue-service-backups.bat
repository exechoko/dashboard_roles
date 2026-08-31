@echo off
setlocal EnableDelayedExpansion
REM Script para instalar el worker de la cola 'backups' como servicio de Windows
REM (backups/restore de la BD desde Configuracion del Sistema > Backups). Va
REM como servicio aparte de LaravelQueueWorker porque un backup de una base
REM grande puede tardar varios minutos y no debe frenar el resto de la cola
REM 'default' (chat, geocodificacion, etc), igual que LaravelQueueWorkerMbox.
REM Requiere NSSM (Non-Sucking Service Manager)
REM Ejecutar como Administrador

echo ========================================
echo Instalador de Servicio Laravel Queue (backups)
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
set SERVICE_NAME=LaravelQueueWorkerBackups
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
REM connection "backups": sin esto, queue:work usa la conexion default de
REM                    config/queue.php (retry_after=90s) aunque se le diga
REM                    --queue=backups: el job queda "reservado" con esa
REM                    conexion y a los 90s MySQL lo vuelve a ofrecer como
REM                    disponible aunque mysqldump siga corriendo, asi que un
REM                    segundo intento lo encuentra con attempts=1 (=tries) y
REM                    lo marca failed con MaxAttemptsExceededException sin
REM                    llegar a correr. La conexion 'backups' (config/queue.php)
REM                    tiene su propio retry_after=86400 para evitar justo esto.
REM --queue=backups  : SOLO procesa la cola dedicada a backups/restore de BD
REM --tries=1        : un backup de varios GB no tiene sentido reintentarlo solo
REM --timeout=0      : sin limite por el worker; el Job ya trae su propio
REM                    timeout (1800s, ver app/Jobs/GenerarBackupBaseDatos.php)
REM --sleep=5        : espera 5 segundos entre polls cuando la cola está vacía
REM --max-time=3600  : reinicia el proceso cada 1 hora para liberar memoria
REM                    (no interrumpe un job en curso, solo el loop ocioso)
"%NSSM_PATH%" install "%SERVICE_NAME%" "%PHP_PATH%" "artisan" "queue:work" "backups" "--queue=backups" "--sleep=5" "--tries=1" "--timeout=0" "--max-time=3600"
if !errorlevel! neq 0 (
    echo [ERROR] Fallo al instalar el servicio con NSSM. Codigo: !errorlevel!
    pause
    exit /b 1
)
echo [OK] Servicio creado.

REM Configurar el directorio de trabajo
"%NSSM_PATH%" set "%SERVICE_NAME%" AppDirectory "%PROJECT_PATH%"

REM Configurar descripción del servicio
"%NSSM_PATH%" set "%SERVICE_NAME%" Description "Laravel Queue Worker dedicado a la cola 'backups' (backups/restore de la base de datos, Configuracion del Sistema)"

REM Configurar inicio automático
"%NSSM_PATH%" set "%SERVICE_NAME%" Start SERVICE_AUTO_START

REM Configurar reinicio en caso de fallo
"%NSSM_PATH%" set "%SERVICE_NAME%" AppExit Default Restart
"%NSSM_PATH%" set "%SERVICE_NAME%" AppRestartDelay 5000

REM Configurar salida de logs
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStdout "%PROJECT_PATH%\storage\logs\queue-worker-backups.log"
"%NSSM_PATH%" set "%SERVICE_NAME%" AppStderr "%PROJECT_PATH%\storage\logs\queue-worker-backups-error.log"

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
echo   - Ver logs:      type "%PROJECT_PATH%\storage\logs\queue-worker-backups.log"
echo.
echo El servicio se iniciará automáticamente con Windows.
echo.
pause
