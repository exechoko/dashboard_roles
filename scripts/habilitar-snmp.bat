@echo off
REM Habilita el servicio SNMP y el ping entrante (ICMPv4) de Windows para el monitoreo desde el server Laravel.
REM Ejecutar como Administrador en cada PC (clic derecho > Ejecutar como administrador).

REM ==== CONFIGURAR ANTES DE DISTRIBUIR ====
set COMMUNITY=public
set SERVER_IP=193.169.1.247
set DEV_IP=193.169.1.164
REM ==========================================

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: este script debe ejecutarse como Administrador.
    pause
    exit /b 1
)

echo Instalando componente SNMP (detecta si es Windows Server o cliente)...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "if ((Get-CimInstance Win32_OperatingSystem).ProductType -ne 1) {" ^
    "    Install-WindowsFeature -Name SNMP-Service -IncludeManagementTools | Out-Null" ^
    "} else {" ^
    "    Add-WindowsCapability -Online -Name 'SNMP.Client~~~~0.0.1.0' | Out-Null" ^
    "}"

echo Configurando community string y restricciones...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$c='%COMMUNITY%'; $ip='%SERVER_IP%'; $dev='%DEV_IP%';" ^
    "New-Item -Path 'HKLM:\SYSTEM\CurrentControlSet\Services\SNMP\Parameters\ValidCommunities' -Force | Out-Null;" ^
    "New-ItemProperty -Path 'HKLM:\SYSTEM\CurrentControlSet\Services\SNMP\Parameters\ValidCommunities' -Name $c -PropertyType DWord -Value 4 -Force | Out-Null;" ^
    "New-Item -Path 'HKLM:\SYSTEM\CurrentControlSet\Services\SNMP\Parameters\PermittedManagers' -Force | Out-Null;" ^
    "New-ItemProperty -Path 'HKLM:\SYSTEM\CurrentControlSet\Services\SNMP\Parameters\PermittedManagers' -Name '1' -PropertyType String -Value $ip -Force | Out-Null;" ^
    "New-ItemProperty -Path 'HKLM:\SYSTEM\CurrentControlSet\Services\SNMP\Parameters\PermittedManagers' -Name '2' -PropertyType String -Value $dev -Force | Out-Null;"

echo Iniciando servicio SNMP...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Set-Service -Name SNMP -StartupType Automatic; Restart-Service -Name SNMP"

echo Abriendo puerto UDP 161 en el firewall para %SERVER_IP% y %DEV_IP%...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "if (-not (Get-NetFirewallRule -DisplayName 'SNMP desde server Laravel' -ErrorAction SilentlyContinue)) {" ^
    "New-NetFirewallRule -DisplayName 'SNMP desde server Laravel' -Direction Inbound -Protocol UDP -LocalPort 161 -RemoteAddress '%SERVER_IP%','%DEV_IP%' -Action Allow | Out-Null }"

echo Habilitando ping entrante (ICMPv4) para que el monitoreo detecte si el equipo esta caido...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "if (-not (Get-NetFirewallRule -DisplayName 'Permitir ping entrante' -ErrorAction SilentlyContinue)) {" ^
    "New-NetFirewallRule -DisplayName 'Permitir ping entrante' -Direction Inbound -Protocol ICMPv4 -IcmpType 8 -Action Allow | Out-Null }"

echo.
powershell -NoProfile -Command "Get-Service SNMP"
echo.
echo Listo. Verifica arriba que el servicio SNMP diga "Running".
pause
