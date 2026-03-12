@echo off
REM Script para ejecutar el worker de Laravel Queue en Windows
REM Este script debe ejecutarse continuamente para procesar los jobs en cola

cd /d C:\Apache24\htdocs\dashboard_roles

:loop
php artisan queue:work --sleep=3 --tries=3 --timeout=600
if %errorlevel% neq 0 (
    echo Worker detenido con error. Reiniciando en 5 segundos...
    timeout /t 5 /nobreak
)
goto loop
