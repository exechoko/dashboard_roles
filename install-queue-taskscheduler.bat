@echo off
REM Script para instalar Laravel Queue Worker usando el Programador de Tareas de Windows
REM No requiere software adicional - usa herramientas nativas de Windows
REM Ejecutar como Administrador

echo ========================================
echo Instalador Laravel Queue Worker
echo Usando Programador de Tareas de Windows
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

REM Configuración - AJUSTA ESTAS RUTAS SEGUN TU SERVIDOR
set TASK_NAME=LaravelQueueWorker
set PROJECT_PATH=C:\Apache24\htdocs\dashboard_roles
set PHP_PATH=C:\php\php.exe
set XML_FILE=%PROJECT_PATH%\LaravelQueueWorker.xml

echo Verificando requisitos...
echo.

REM Verificar si PHP existe
if not exist "%PHP_PATH%" (
    echo ERROR: PHP no encontrado en %PHP_PATH%
    echo.
    echo Por favor, edita este script y ajusta la variable PHP_PATH
    echo para que apunte a tu instalacion de PHP
    echo.
    echo Ejemplos comunes:
    echo   - C:\php\php.exe
    echo   - C:\xampp\php\php.exe
    echo   - C:\wamp\bin\php\php7.x.x\php.exe
    echo.
    pause
    exit /b 1
)

REM Verificar si el proyecto existe
if not exist "%PROJECT_PATH%\artisan" (
    echo ERROR: Proyecto Laravel no encontrado en %PROJECT_PATH%
    echo.
    echo Por favor, edita este script y ajusta la variable PROJECT_PATH
    echo.
    pause
    exit /b 1
)

REM Verificar si el archivo XML existe
if not exist "%XML_FILE%" (
    echo ERROR: Archivo de configuracion no encontrado: %XML_FILE%
    echo.
    echo Asegurate de que LaravelQueueWorker.xml este en el directorio del proyecto
    echo.
    pause
    exit /b 1
)

echo [OK] PHP encontrado: %PHP_PATH%
echo [OK] Proyecto Laravel encontrado: %PROJECT_PATH%
echo [OK] Archivo XML encontrado
echo.

REM Actualizar rutas en el archivo XML
echo Configurando rutas en el archivo XML...
powershell -Command "(Get-Content '%XML_FILE%') -replace 'C:\\php\\php.exe', '%PHP_PATH%' -replace 'C:\\Apache24\\htdocs\\dashboard_roles', '%PROJECT_PATH%' | Set-Content '%XML_FILE%.temp'"
move /y "%XML_FILE%.temp" "%XML_FILE%" >nul

REM Verificar si la tarea ya existe
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorlevel% equ 0 (
    echo.
    echo La tarea programada "%TASK_NAME%" ya existe.
    echo Deseas eliminarla y reinstalarla? (S/N)
    set /p REINSTALL=
    if /i "%REINSTALL%" neq "S" (
        echo Instalacion cancelada.
        pause
        exit /b 0
    )
    echo.
    echo Eliminando tarea existente...
    schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1
    timeout /t 2 /nobreak >nul
)

echo.
echo Creando tarea programada "%TASK_NAME%"...
echo.

REM Crear la tarea usando el archivo XML
schtasks /create /tn "%TASK_NAME%" /xml "%XML_FILE%" /f

if %errorlevel% neq 0 (
    echo.
    echo ERROR: No se pudo crear la tarea programada
    echo.
    pause
    exit /b 1
)

echo.
echo Iniciando tarea...
schtasks /run /tn "%TASK_NAME%"

timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo Instalacion completada exitosamente
echo ========================================
echo.
echo Tarea: %TASK_NAME%
echo Estado: Ejecutandose
echo.
echo La tarea se iniciara automaticamente:
echo   - Al iniciar Windows
echo   - Si falla, se reintentara 3 veces (1 minuto entre intentos)
echo.
echo Comandos utiles:
echo   - Ver estado:    schtasks /query /tn %TASK_NAME%
echo   - Iniciar:       schtasks /run /tn %TASK_NAME%
echo   - Detener:       schtasks /end /tn %TASK_NAME%
echo   - Ver detalles:  taskschd.msc (buscar %TASK_NAME%)
echo.
echo Para ver logs de Laravel:
echo   type %PROJECT_PATH%\storage\logs\laravel.log
echo.
pause
