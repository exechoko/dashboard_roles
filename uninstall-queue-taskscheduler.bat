@echo off
REM Script para desinstalar Laravel Queue Worker del Programador de Tareas
REM Ejecutar como Administrador

echo ========================================
echo Desinstalador Laravel Queue Worker
echo Programador de Tareas de Windows
echo ========================================
echo.

REM Verificar si se ejecuta como administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Este script debe ejecutarse como Administrador
    pause
    exit /b 1
)

set TASK_NAME=LaravelQueueWorker

REM Verificar si la tarea existe
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorlevel% neq 0 (
    echo La tarea "%TASK_NAME%" no existe.
    echo No hay nada que desinstalar.
    pause
    exit /b 0
)

echo Deteniendo tarea "%TASK_NAME%"...
schtasks /end /tn "%TASK_NAME%" >nul 2>&1
timeout /t 2 /nobreak >nul

echo Eliminando tarea "%TASK_NAME%"...
schtasks /delete /tn "%TASK_NAME%" /f

if %errorlevel% equ 0 (
    echo.
    echo Tarea eliminada exitosamente.
) else (
    echo.
    echo ERROR: No se pudo eliminar la tarea.
)

echo.
pause
