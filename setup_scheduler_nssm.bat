@echo off
echo Configurando LaravelScheduler...

nssm set LaravelScheduler Application C:\Windows\System32\cmd.exe
nssm set LaravelScheduler AppParameters /c C:\Apache24\htdocs\equipamiento\scheduler.bat
nssm set LaravelScheduler AppDirectory C:\Apache24\htdocs\equipamiento

echo Reiniciando servicio...
nssm restart LaravelScheduler

echo.
echo Verificando configuracion:
nssm get LaravelScheduler Application
nssm get LaravelScheduler AppParameters
nssm get LaravelScheduler AppDirectory
nssm status LaravelScheduler

echo.
echo Listo. Revisa que el status sea SERVICE_RUNNING.
pause
