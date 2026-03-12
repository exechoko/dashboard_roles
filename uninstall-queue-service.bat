@echo off
REM Script para desinstalar el servicio de Laravel Queue Worker
REM Ejecutar como Administrador

echo ========================================
echo Desinstalador de Servicio Laravel Queue
echo ========================================
echo.

REM Verificar si se ejecuta como administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Este script debe ejecutarse como Administrador
    pause
    exit /b 1
)

set SERVICE_NAME=LaravelQueueWorker
set NSSM_PATH=C:\nssm\nssm.exe

REM Verificar si el servicio existe
sc query %SERVICE_NAME% >nul 2>&1
if %errorlevel% neq 0 (
    echo El servicio %SERVICE_NAME% no existe.
    pause
    exit /b 0
)

echo Deteniendo servicio %SERVICE_NAME%...
net stop %SERVICE_NAME%
timeout /t 2 /nobreak >nul

echo Eliminando servicio %SERVICE_NAME%...
"%NSSM_PATH%" remove %SERVICE_NAME% confirm

echo.
echo Servicio eliminado exitosamente.
echo.
pause
