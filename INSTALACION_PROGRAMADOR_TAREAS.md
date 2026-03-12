# Instalación con Programador de Tareas de Windows

## ✅ Método Recomendado para Windows Server 2012 R2

El **Programador de Tareas de Windows** es una herramienta nativa que no requiere software adicional. Es ideal para Windows Server 2012 R2 y versiones posteriores.

---

## Ventajas vs NSSM

| Característica | Programador de Tareas | NSSM |
|----------------|----------------------|------|
| Software adicional | ❌ No requiere | ✅ Requiere descarga |
| Nativo de Windows | ✅ Sí | ❌ No |
| Interfaz gráfica | ✅ Sí (`taskschd.msc`) | ❌ Solo línea de comandos |
| Reinicio automático | ✅ Configurable | ✅ Configurable |
| Logs integrados | ✅ Event Viewer | ❌ Archivos personalizados |
| Complejidad | 🟢 Baja | 🟡 Media |

---

## Instalación Paso a Paso

### Paso 1: Configurar Base de Datos

```cmd
cd C:\Apache24\htdocs\dashboard_roles
php artisan migrate
```

Edita `.env` y verifica/agrega:
```env
QUEUE_CONNECTION=database
```

### Paso 2: Ajustar Rutas

Edita `install-queue-taskscheduler.bat` y ajusta estas variables:

```batch
set PHP_PATH=C:\php\php.exe
set PROJECT_PATH=C:\Apache24\htdocs\dashboard_roles
```

**Encontrar la ruta de PHP:**
```cmd
where php
```

### Paso 3: Instalar la Tarea Programada

1. Haz clic derecho en `install-queue-taskscheduler.bat`
2. Selecciona **"Ejecutar como administrador"**
3. El script configurará e iniciará la tarea automáticamente

### Paso 4: Verificar Instalación

**Opción A: Línea de comandos**
```cmd
schtasks /query /tn LaravelQueueWorker
```

**Opción B: Interfaz gráfica**
1. Presiona `Win + R`
2. Escribe `taskschd.msc` y presiona Enter
3. Busca "LaravelQueueWorker" en la lista
4. Verifica que el estado sea "En ejecución"

---

## Configuración de la Tarea

La tarea está configurada con:

- **Inicio**: Al arrancar Windows
- **Usuario**: SYSTEM (máximos privilegios)
- **Reintentos**: 3 intentos con 1 minuto de espera
- **Tiempo límite**: Sin límite (ejecución continua)
- **Prioridad**: Normal (7)

### Parámetros del Worker

```cmd
php artisan queue:work --sleep=3 --tries=3 --timeout=600
```

- `--sleep=3`: Espera 3 segundos entre verificaciones
- `--tries=3`: 3 reintentos por job fallido
- `--timeout=600`: 10 minutos máximo por job

---

## Comandos Útiles

### Gestión de la Tarea

```cmd
# Ver estado detallado
schtasks /query /tn LaravelQueueWorker /v /fo list

# Iniciar manualmente
schtasks /run /tn LaravelQueueWorker

# Detener
schtasks /end /tn LaravelQueueWorker

# Deshabilitar (no se ejecutará al iniciar Windows)
schtasks /change /tn LaravelQueueWorker /disable

# Habilitar
schtasks /change /tn LaravelQueueWorker /enable

# Ver historial de ejecución
Get-WinEvent -LogName Microsoft-Windows-TaskScheduler/Operational | Where-Object {$_.Message -like "*LaravelQueueWorker*"} | Select-Object -First 10
```

### Modificar Configuración

**Opción A: Interfaz gráfica**
1. Abre `taskschd.msc`
2. Busca "LaravelQueueWorker"
3. Clic derecho → Propiedades
4. Modifica según necesites

**Opción B: Editar XML y reinstalar**
1. Edita `LaravelQueueWorker.xml`
2. Ejecuta `install-queue-taskscheduler.bat` de nuevo

---

## Personalización Avanzada

### Cambiar Parámetros del Worker

Edita `LaravelQueueWorker.xml`, busca la sección `<Arguments>`:

```xml
<Arguments>artisan queue:work --sleep=5 --tries=5 --timeout=900</Arguments>
```

Luego reinstala:
```cmd
install-queue-taskscheduler.bat
```

### Ejecutar con Usuario Específico

Por defecto usa SYSTEM. Para usar otro usuario:

1. Abre `taskschd.msc`
2. Busca "LaravelQueueWorker" → Propiedades
3. Pestaña "General"
4. Cambia "Al ejecutar la tarea, usar la cuenta de usuario siguiente"
5. Ingresa credenciales del usuario

### Múltiples Workers

Para procesar más archivos simultáneamente:

```cmd
# Copiar el XML
copy LaravelQueueWorker.xml LaravelQueueWorker2.xml

# Editar el nombre en el XML
# Luego crear la segunda tarea
schtasks /create /tn LaravelQueueWorker2 /xml LaravelQueueWorker2.xml /f
schtasks /run /tn LaravelQueueWorker2
```

---

## Monitoreo

### Ver si está Ejecutándose

```cmd
# Verificar proceso PHP
tasklist | findstr php.exe

# Ver detalles de la tarea
schtasks /query /tn LaravelQueueWorker /v /fo list
```

### Logs de Laravel

```cmd
# Ver últimas líneas
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log -Tail 50

# Monitorear en tiempo real
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log -Wait -Tail 20
```

### Event Viewer (Visor de Eventos)

1. Presiona `Win + R` → `eventvwr.msc`
2. Ve a: **Registros de Windows → Sistema**
3. Busca eventos relacionados con "Task Scheduler"
4. Filtra por "LaravelQueueWorker"

---

## Troubleshooting

### La tarea no inicia

**Verificar:**
```cmd
# Ver último resultado de ejecución
schtasks /query /tn LaravelQueueWorker /v /fo list | findstr "Resultado"
```

**Códigos de resultado comunes:**
- `0x0`: Éxito
- `0x1`: Error general
- `0x41301`: Tarea en ejecución
- `0x41303`: Tarea no ejecutada aún

**Soluciones:**
1. Verifica que las rutas en `LaravelQueueWorker.xml` sean correctas
2. Verifica que PHP funcione:
   ```cmd
   C:\php\php.exe -v
   ```
3. Revisa Event Viewer para errores detallados

### La tarea se detiene sola

**Causa común**: Configuración de tiempo límite

**Solución**: Edita `LaravelQueueWorker.xml`:
```xml
<ExecutionTimeLimit>PT0S</ExecutionTimeLimit>
```
`PT0S` = sin límite de tiempo

### Jobs no se procesan

**Verificar que la tarea esté ejecutándose:**
```cmd
tasklist | findstr php.exe
```

Deberías ver un proceso `php.exe` ejecutando `queue:work`

**Si no hay proceso:**
```cmd
schtasks /run /tn LaravelQueueWorker
```

**Verificar logs:**
```cmd
type C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log
```

### Error "Access Denied"

**Causa**: Permisos insuficientes

**Solución**:
1. Ejecuta `install-queue-taskscheduler.bat` como Administrador
2. O configura la tarea para ejecutarse con SYSTEM:
   - Abre `taskschd.msc`
   - Propiedades de la tarea
   - General → "Ejecutar con los privilegios más altos"

---

## Desinstalación

### Método 1: Script automático

1. Clic derecho en `uninstall-queue-taskscheduler.bat`
2. "Ejecutar como administrador"

### Método 2: Manual

```cmd
schtasks /end /tn LaravelQueueWorker
schtasks /delete /tn LaravelQueueWorker /f
```

### Método 3: Interfaz gráfica

1. Abre `taskschd.msc`
2. Busca "LaravelQueueWorker"
3. Clic derecho → Eliminar

---

## Comparación con Ejecución Manual

| Aspecto | Tarea Programada | Ejecución Manual |
|---------|------------------|------------------|
| Inicio automático | ✅ Sí | ❌ No |
| Reinicio tras fallo | ✅ Automático | ❌ Manual |
| Requiere sesión activa | ❌ No | ✅ Sí |
| Ventana visible | ❌ No | ✅ Sí |
| Ideal para | 🟢 Producción | 🟡 Desarrollo |

---

## Mejores Prácticas

### 1. Monitoreo Regular

Crea un script de verificación diaria:

```batch
@echo off
echo === Estado Laravel Queue Worker ===
schtasks /query /tn LaravelQueueWorker
echo.
echo === Procesos PHP ===
tasklist | findstr php.exe
echo.
echo === Ultimos 10 logs ===
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log -Tail 10
```

### 2. Rotación de Logs

Laravel rota logs automáticamente, pero puedes configurar:

En `config/logging.php`:
```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'debug',
    'days' => 14, // Mantener 14 días
],
```

### 3. Alertas de Fallo

Configura el Programador de Tareas para enviar email en caso de fallo:

1. `taskschd.msc` → LaravelQueueWorker → Propiedades
2. Pestaña "Acciones" → Nueva acción
3. Acción: "Enviar un correo electrónico"
4. Configura SMTP y destinatarios

---

## Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `LaravelQueueWorker.xml` | Definición de la tarea programada |
| `install-queue-taskscheduler.bat` | Instalador automático |
| `uninstall-queue-taskscheduler.bat` | Desinstalador automático |

---

## Checklist de Instalación

- [ ] Ejecutar `php artisan migrate`
- [ ] Configurar `QUEUE_CONNECTION=database` en `.env`
- [ ] Ajustar rutas en `install-queue-taskscheduler.bat`
- [ ] Ejecutar `install-queue-taskscheduler.bat` como Administrador
- [ ] Verificar en `taskschd.msc` que la tarea esté en ejecución
- [ ] Probar importando un archivo de prueba
- [ ] Verificar logs en `storage/logs/laravel.log`
- [ ] Confirmar que el estado cambie de "pendiente" a "completado"

---

## Ventajas de Este Método

✅ **Sin dependencias externas**: No requiere NSSM ni otros programas  
✅ **Nativo de Windows**: Usa herramientas incluidas en el sistema  
✅ **Interfaz gráfica**: Fácil de administrar visualmente  
✅ **Logs integrados**: Event Viewer registra todo  
✅ **Robusto**: Reintentos automáticos configurables  
✅ **Inicio automático**: Se ejecuta al arrancar Windows  
✅ **Fácil mantenimiento**: Modificaciones desde la GUI  

---

## Soporte

Si tienes problemas:

1. Verifica Event Viewer: `eventvwr.msc`
2. Revisa logs de Laravel: `storage/logs/laravel.log`
3. Ejecuta manualmente para ver errores:
   ```cmd
   cd C:\Apache24\htdocs\dashboard_roles
   php artisan queue:work --verbose
   ```
4. Verifica que la tarea esté configurada correctamente en `taskschd.msc`

¡Listo! El sistema de colas está funcionando con el Programador de Tareas de Windows.
