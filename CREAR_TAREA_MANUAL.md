# Crear Tarea Programada Manualmente

## Método 1: Usando la Interfaz Gráfica (Recomendado)

### Paso 1: Abrir el Programador de Tareas

1. Presiona `Win + R`
2. Escribe `taskschd.msc`
3. Presiona Enter

### Paso 2: Crear Nueva Tarea

1. En el panel derecho, haz clic en **"Crear tarea..."** (NO "Crear tarea básica")
2. Se abrirá una ventana con varias pestañas

### Paso 3: Configurar Pestaña "General"

- **Nombre**: `LaravelQueueWorker`
- **Descripción**: `Laravel Queue Worker para procesamiento de importaciones de eventos CECOCO`
- **Opciones de seguridad**:
  - Selecciona: ☑ **"Ejecutar tanto si el usuario inició sesión como si no"**
  - Selecciona: ☑ **"Ejecutar con los privilegios más altos"**
  - **Configurar para**: Windows Server 2012 R2

### Paso 4: Configurar Pestaña "Desencadenadores"

1. Haz clic en **"Nuevo..."**
2. **Iniciar la tarea**: Selecciona **"Al iniciar"**
3. Configuración avanzada:
   - ☑ **Habilitado**
4. Haz clic en **"Aceptar"**

### Paso 5: Configurar Pestaña "Acciones"

1. Haz clic en **"Nueva..."**
2. **Acción**: Selecciona **"Iniciar un programa"**
3. **Programa o script**: 
   ```
   C:\php\php.exe
   ```
   ⚠️ **IMPORTANTE**: Ajusta esta ruta según tu instalación de PHP
   
4. **Agregar argumentos**:
   ```
   artisan queue:work --sleep=3 --tries=3 --timeout=600
   ```
   
5. **Comenzar en (opcional)**:
   ```
   C:\Apache24\htdocs\dashboard_roles
   ```
   ⚠️ **IMPORTANTE**: Ajusta esta ruta a tu proyecto
   
6. Haz clic en **"Aceptar"**

### Paso 6: Configurar Pestaña "Condiciones"

- **Energía**:
  - ☐ Desmarcar: "Iniciar la tarea solo si el equipo está conectado a la corriente alterna"
  - ☐ Desmarcar: "Detener si el equipo deja de estar conectado a la corriente alterna"

### Paso 7: Configurar Pestaña "Configuración"

- ☑ **Permitir que la tarea se ejecute a petición**
- ☑ **Ejecutar la tarea lo antes posible después de perder una ejecución programada**
- ☐ **Detener la tarea si se ejecuta más de**: DESMARCAR (queremos que se ejecute continuamente)
- **Si la tarea en ejecución no finaliza cuando se solicita, forzar su detención**: Desmarcar
- **Si la tarea ya se está ejecutando, se aplica la siguiente regla**: Selecciona **"No iniciar una nueva instancia"**

### Paso 8: Guardar

1. Haz clic en **"Aceptar"**
2. Si te pide credenciales, ingresa tu usuario y contraseña de Windows
3. La tarea se creará y comenzará a ejecutarse

---

## Método 2: Usando Línea de Comandos

Abre CMD o PowerShell **como Administrador** y ejecuta:

```cmd
schtasks /create /tn "LaravelQueueWorker" /tr "C:\php\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=600" /sc onstart /ru SYSTEM /rl HIGHEST /f
```

⚠️ **IMPORTANTE**: Esto NO establece el directorio de trabajo. Es mejor usar el método gráfico o el archivo XML.

### Comando Mejorado con PowerShell

```powershell
$action = New-ScheduledTaskAction -Execute "C:\php\php.exe" -Argument "artisan queue:work --sleep=3 --tries=3 --timeout=600" -WorkingDirectory "C:\Apache24\htdocs\dashboard_roles"
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit 0 -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
Register-ScheduledTask -TaskName "LaravelQueueWorker" -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description "Laravel Queue Worker para procesamiento de importaciones"
```

---

## ¿Cada Cuánto se Ejecuta?

### Respuesta Corta
**La tarea se ejecuta CONTINUAMENTE**, no cada X minutos.

### Explicación Detallada

1. **Inicio**: La tarea se inicia automáticamente cuando arranca Windows
2. **Ejecución continua**: El comando `queue:work` se mantiene ejecutándose permanentemente
3. **Verificación de cola**: Cada 3 segundos (`--sleep=3`) verifica si hay jobs nuevos en la cola
4. **Procesamiento**: Cuando encuentra un job, lo procesa inmediatamente
5. **Bucle infinito**: Después de procesar, vuelve a esperar 3 segundos y verifica de nuevo

### Diagrama de Flujo

```
Inicio de Windows
    ↓
Tarea inicia → php artisan queue:work
    ↓
┌─────────────────────────────────┐
│ Bucle Infinito:                 │
│ 1. Verificar si hay jobs        │
│ 2. Si hay → Procesar            │
│ 3. Si no hay → Esperar 3 seg    │
│ 4. Volver al paso 1             │
└─────────────────────────────────┘
```

### Parámetros Explicados

- `--sleep=3`: Espera 3 segundos entre cada verificación de la cola
- `--tries=3`: Si un job falla, lo reintenta hasta 3 veces
- `--timeout=600`: Cada job tiene máximo 600 segundos (10 minutos) para completarse

---

## Verificar que Funciona

### 1. Ver si la tarea existe
```cmd
schtasks /query /tn LaravelQueueWorker
```

### 2. Ver si el proceso está ejecutándose
```cmd
tasklist | findstr php.exe
```

Deberías ver algo como:
```
php.exe    1234  Console    1    45,678 K
```

### 3. Ver detalles de la tarea
```cmd
schtasks /query /tn LaravelQueueWorker /v /fo list
```

### 4. Iniciar manualmente (si no está ejecutándose)
```cmd
schtasks /run /tn LaravelQueueWorker
```

---

## Encontrar la Ruta de PHP

Si no sabes dónde está PHP instalado:

```cmd
where php
```

O busca en estas ubicaciones comunes:
- `C:\php\php.exe`
- `C:\xampp\php\php.exe`
- `C:\wamp\bin\php\php7.4.33\php.exe`
- `C:\Program Files\PHP\php.exe`

---

## Troubleshooting

### Error: "El sistema no puede encontrar el archivo especificado"

**Causa**: La tarea no existe aún.

**Solución**: Créala usando el Método 1 o 2 de arriba.

### La tarea existe pero no se ejecuta

**Verificar estado:**
```cmd
schtasks /query /tn LaravelQueueWorker /v /fo list
```

Busca la línea "Estado:" - debería decir "Ejecutándose"

**Si dice "Listo" o "Detenido":**
```cmd
schtasks /run /tn LaravelQueueWorker
```

### La tarea se ejecuta pero los jobs no se procesan

**Verificar que el proceso PHP esté activo:**
```cmd
tasklist | findstr php.exe
```

**Verificar logs de Laravel:**
```cmd
type C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log
```

**Verificar configuración de .env:**
```env
QUEUE_CONNECTION=database
```

---

## Diferencia con Tareas Programadas Tradicionales

| Tipo | Frecuencia | Uso |
|------|-----------|-----|
| **Tarea tradicional** | Cada X minutos/horas | Backups, limpiezas |
| **Queue Worker** | Continuo (siempre activo) | Procesamiento en tiempo real |

### Ejemplo de Tarea Tradicional (NO usar para queue)
```
Desencadenador: Cada 5 minutos
Acción: php artisan queue:work --max-jobs=10
```
❌ **Problema**: Se ejecuta, procesa 10 jobs y se detiene. Luego espera 5 minutos.

### Queue Worker Correcto (usar este)
```
Desencadenador: Al iniciar Windows
Acción: php artisan queue:work (sin --max-jobs)
```
✅ **Correcto**: Se ejecuta continuamente, procesa jobs inmediatamente cuando llegan.

---

## Comandos Útiles de Administración

```cmd
# Ver estado
schtasks /query /tn LaravelQueueWorker

# Iniciar
schtasks /run /tn LaravelQueueWorker

# Detener
schtasks /end /tn LaravelQueueWorker

# Eliminar
schtasks /delete /tn LaravelQueueWorker /f

# Deshabilitar (no se ejecutará al iniciar)
schtasks /change /tn LaravelQueueWorker /disable

# Habilitar
schtasks /change /tn LaravelQueueWorker /enable

# Ver historial
Get-WinEvent -LogName Microsoft-Windows-TaskScheduler/Operational | Where-Object {$_.Message -like "*LaravelQueueWorker*"} | Select-Object -First 10
```

---

## Resumen Rápido

1. **Crear tarea**: Usa `taskschd.msc` (interfaz gráfica)
2. **Desencadenador**: "Al iniciar" Windows
3. **Acción**: `C:\php\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=600`
4. **Directorio**: `C:\Apache24\htdocs\dashboard_roles`
5. **Frecuencia**: Continuo (verifica cola cada 3 segundos)
6. **Verificar**: `tasklist | findstr php.exe`

La tarea NO se ejecuta "cada X tiempo", sino que permanece activa continuamente esperando jobs.
