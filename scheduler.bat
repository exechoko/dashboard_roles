@echo off
:start
php artisan schedule:run
timeout /t 60 /nobreak > nul
goto start
