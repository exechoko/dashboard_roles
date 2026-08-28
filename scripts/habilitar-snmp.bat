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

echo Instalando componente SNMP (detecta si es Windows Server o cliente, y si es Windows 7)...
REM Get-WmiObject (en vez de Get-CimInstance) porque en Windows 7 el PowerShell
REM de fabrica es 2.0 y no tiene el modulo CIM. En Windows 7 tampoco existe
REM Add-WindowsCapability (es de Windows 10+), asi que ahi se instala por DISM.
REM Sin /all ni /quiet: en el DISM de Windows 7 esas opciones no son validas
REM para /Enable-Feature (da "Error: 87 - opcion no reconocida").
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$esServer = (Get-WmiObject Win32_OperatingSystem).ProductType -ne 1;" ^
    "$esWindows7 = [System.Environment]::OSVersion.Version.Major -lt 10;" ^
    "if ($esServer) {" ^
    "    Install-WindowsFeature -Name SNMP-Service -IncludeManagementTools | Out-Null" ^
    "} elseif ($esWindows7) {" ^
    "    Start-Process -FilePath dism.exe -ArgumentList '/online','/enable-feature','/featurename:SNMP','/norestart' -Wait -NoNewWindow" ^
    "} else {" ^
    "    Add-WindowsCapability -Online -Name 'SNMP.Client~~~~0.0.1.0' | Out-Null" ^
    "}"

powershell -NoProfile -Command "if (-not (Get-Service -Name SNMP -ErrorAction SilentlyContinue)) { exit 1 }"
if %errorlevel% neq 0 (
    echo.
    echo ERROR: no se pudo instalar el servicio SNMP. Revisa los mensajes de arriba.
    pause
    exit /b 1
)

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

REM Se usa "netsh advfirewall" (en vez de los cmdlets New-NetFirewallRule /
REM Get-NetFirewallRule) porque esos cmdlets pertenecen al modulo NetSecurity,
REM que no existe en Windows 7. netsh si funciona igual desde Windows 7 hasta
REM Windows 11 / Server 2022.
echo Abriendo puerto UDP 161 en el firewall para %SERVER_IP% y %DEV_IP%...
netsh advfirewall firewall show rule name="SNMP desde server Laravel" >nul 2>&1
if errorlevel 1 (
    netsh advfirewall firewall add rule name="SNMP desde server Laravel" dir=in action=allow protocol=UDP localport=161 remoteip=%SERVER_IP%,%DEV_IP%
)

echo Habilitando ping entrante (ICMPv4) para que el monitoreo detecte si el equipo esta caido...
netsh advfirewall firewall show rule name="Permitir ping entrante" >nul 2>&1
if errorlevel 1 (
    netsh advfirewall firewall add rule name="Permitir ping entrante" protocol=icmpv4:8,any dir=in action=allow
)

echo.
powershell -NoProfile -Command "Get-Service SNMP"
echo.
echo Listo. Verifica arriba que el servicio SNMP diga "Running".
pause
