# Instalación de Sistema de Colas en Windows Server 2012 R2

## Descripción General

Este sistema implementa **Laravel Queue** para procesar archivos Excel de forma asíncrona en segundo plano. Los archivos se agregan a una cola y se procesan automáticamente sin bloquear la interfaz web.

## Ventajas del Sistema de Colas

✅ **No bloquea la interfaz**: Los usuarios pueden seguir trabajando mientras se procesan los archivos  
✅ **Procesamiento en segundo plano**: Los archivos se procesan automáticamente  
✅ **Reintentos automáticos**: Si un archivo falla, se reintenta hasta 3 veces  
✅ **Escalable**: Puedes procesar cientos de archivos sin problemas  
✅ **Monitoreable**: Puedes ver el estado de cada importación en tiempo real  
✅ **Robusto**: Si el servidor se reinicia, los jobs pendientes se procesan automáticamente  

---

## Paso 1: Configurar Base de Datos

### 1.1 Ejecutar Migración

Abre PowerShell o CMD en el directorio del proyecto y ejecuta:

```cmd
cd C:\Apache24\htdocs\dashboard_roles
php artisan migrate
```

Esto creará la tabla `jobs` en la base de datos para almacenar los trabajos en cola.

### 1.2 Configurar .env

Edita el archivo `.env` y asegúrate de tener:

```env
QUEUE_CONNECTION=database
```

Si no existe, agrégalo. Esto le dice a Laravel que use la base de datos para las colas.

---

## Paso 2: Instalar NSSM (Recomendado para Producción)

NSSM (Non-Sucking Service Manager) permite ejecutar el worker de Laravel como un servicio de Windows que se inicia automáticamente.

### 2.1 Descargar NSSM

1. Ve a: https://nssm.cc/download
2. Descarga la versión más reciente (ejemplo: `nssm-2.24.zip`)
3. Extrae el archivo ZIP

### 2.2 Instalar NSSM

1. Copia `nssm.exe` (de la carpeta `win64` o `win32` según tu sistema) a `C:\nssm\`
2. Crea la carpeta si no existe:
   ```cmd
   mkdir C:\nssm
   ```

### 2.3 Verificar Instalación

```cmd
C:\nssm\nssm.exe version
```

Deberías ver la versión de NSSM.

---

## Paso 3: Configurar el Script de Instalación

### 3.1 Editar install-queue-service.bat

Abre el archivo `install-queue-service.bat` y ajusta estas variables según tu configuración:

```batch
set SERVICE_NAME=LaravelQueueWorker
set PROJECT_PATH=C:\Apache24\htdocs\dashboard_roles
set PHP_PATH=C:\php\php.exe
set NSSM_PATH=C:\nssm\nssm.exe
```

**Importante**: Asegúrate de que las rutas sean correctas para tu servidor.

### 3.2 Encontrar la Ruta de PHP

Si no sabes dónde está PHP, ejecuta:

```cmd
where php
```

O busca en:
- `C:\php\php.exe`
- `C:\xampp\php\php.exe`
- `C:\wamp\bin\php\php7.x.x\php.exe`

---

## Paso 4: Instalar el Servicio

### 4.1 Ejecutar como Administrador

1. Haz clic derecho en `install-queue-service.bat`
2. Selecciona **"Ejecutar como administrador"**
3. Sigue las instrucciones en pantalla

### 4.2 Verificar Instalación

El script instalará y arrancará el servicio automáticamente. Para verificar:

```cmd
sc query LaravelQueueWorker
```

O abre **Servicios de Windows**:
1. Presiona `Win + R`
2. Escribe `services.msc`
3. Busca "LaravelQueueWorker"
4. Verifica que el estado sea **"En ejecución"**

---

## Paso 5: Comandos Útiles

### Gestión del Servicio

```cmd
# Ver estado
sc query LaravelQueueWorker

# Iniciar servicio
net start LaravelQueueWorker

# Detener servicio
net stop LaravelQueueWorker

# Reiniciar servicio
net stop LaravelQueueWorker && net start LaravelQueueWorker
```

### Ver Logs

```cmd
# Ver log de salida
type C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker.log

# Ver log de errores
type C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker-error.log

# Ver últimas 20 líneas
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker.log -Tail 20
```

### Monitorear en Tiempo Real

```cmd
# PowerShell - Ver logs en tiempo real
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker.log -Wait -Tail 50
```

---

## Paso 6: Desinstalar el Servicio (Opcional)

Si necesitas desinstalar el servicio:

1. Haz clic derecho en `uninstall-queue-service.bat`
2. Selecciona **"Ejecutar como administrador"**

---

## Alternativa: Ejecutar Manualmente (Desarrollo/Pruebas)

Si no quieres instalar el servicio, puedes ejecutar el worker manualmente:

### Opción A: Usando el script BAT

Doble clic en `queue-worker.bat` (se ejecutará en una ventana de CMD)

### Opción B: Comando directo

```cmd
cd C:\Apache24\htdocs\dashboard_roles
php artisan queue:work --sleep=3 --tries=3 --timeout=600
```

**Nota**: La ventana debe permanecer abierta. Si la cierras, el worker se detiene.

---

## Configuración del Worker

El worker está configurado con estos parámetros:

- `--sleep=3`: Espera 3 segundos entre cada verificación de la cola
- `--tries=3`: Reintenta hasta 3 veces si un job falla
- `--timeout=600`: Tiempo máximo de 600 segundos (10 minutos) por job

### Modificar Configuración

Edita `install-queue-service.bat` o `queue-worker.bat` y ajusta los parámetros:

```batch
php artisan queue:work --sleep=5 --tries=5 --timeout=900
```

Luego reinstala el servicio.

---

## Troubleshooting

### El servicio no inicia

**Problema**: El servicio aparece como "Detenido"

**Soluciones**:
1. Verifica que las rutas en `install-queue-service.bat` sean correctas
2. Verifica que PHP esté instalado y funcione:
   ```cmd
   C:\php\php.exe -v
   ```
3. Verifica que Laravel funcione:
   ```cmd
   cd C:\Apache24\htdocs\dashboard_roles
   php artisan --version
   ```
4. Revisa los logs de error:
   ```cmd
   type C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker-error.log
   ```

### Los jobs no se procesan

**Problema**: Los archivos quedan en estado "pendiente"

**Soluciones**:
1. Verifica que el servicio esté ejecutándose:
   ```cmd
   sc query LaravelQueueWorker
   ```
2. Verifica la configuración en `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```
3. Verifica que la tabla `jobs` exista en la base de datos
4. Reinicia el servicio:
   ```cmd
   net stop LaravelQueueWorker && net start LaravelQueueWorker
   ```

### Error "Class not found"

**Problema**: El worker muestra error de clase no encontrada

**Soluciones**:
1. Regenera el autoload de Composer:
   ```cmd
   cd C:\Apache24\htdocs\dashboard_roles
   composer dump-autoload
   ```
2. Reinicia el servicio

### Jobs fallan constantemente

**Problema**: Todos los jobs terminan en estado "error"

**Soluciones**:
1. Revisa los logs de Laravel:
   ```cmd
   type C:\Apache24\htdocs\dashboard_roles\storage\logs\laravel.log
   ```
2. Verifica que la tabla `importaciones` exista
3. Verifica permisos de escritura en `storage/app/importaciones_temp/`
4. Aumenta el timeout si los archivos son muy grandes:
   ```batch
   --timeout=1200
   ```

### El servicio se detiene después de un tiempo

**Problema**: El servicio se detiene solo después de horas/días

**Soluciones**:
1. Verifica la configuración de reinicio automático en NSSM
2. Aumenta la memoria de PHP en `php.ini`:
   ```ini
   memory_limit = 512M
   ```
3. Revisa logs del sistema de Windows (Event Viewer)

---

## Monitoreo y Mantenimiento

### Verificación Diaria

```cmd
# Estado del servicio
sc query LaravelQueueWorker

# Jobs pendientes en la base de datos
php artisan queue:monitor

# Últimos logs
powershell Get-Content C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker.log -Tail 20
```

### Limpieza de Logs

Los logs pueden crecer mucho. Para limpiarlos:

```cmd
# Eliminar logs antiguos
del C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker.log
del C:\Apache24\htdocs\dashboard_roles\storage\logs\queue-worker-error.log

# Reiniciar servicio para crear nuevos logs
net stop LaravelQueueWorker && net start LaravelQueueWorker
```

### Limpieza de Jobs Completados

Los jobs completados se eliminan automáticamente de la tabla `jobs`, pero puedes limpiar jobs fallidos:

```cmd
php artisan queue:flush
```

---

## Configuración Avanzada

### Múltiples Workers

Para procesar más archivos simultáneamente, instala múltiples workers:

```cmd
# Instalar segundo worker
C:\nssm\nssm.exe install LaravelQueueWorker2 C:\php\php.exe artisan queue:work --sleep=3 --tries=3
```

### Prioridades de Cola

Puedes crear colas con diferentes prioridades:

```php
// En el controlador
ProcesarArchivoEventoCecoco::dispatch($path, $nombre, $id)->onQueue('high');
```

```cmd
# Worker que procesa primero la cola 'high'
php artisan queue:work --queue=high,default
```

---

## Resumen de Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `app/Jobs/ProcesarArchivoEventoCecoco.php` | Job que procesa cada archivo Excel |
| `database/migrations/2024_03_12_000000_create_jobs_table.php` | Migración para tabla de jobs |
| `queue-worker.bat` | Script para ejecutar worker manualmente |
| `install-queue-service.bat` | Script para instalar servicio de Windows |
| `uninstall-queue-service.bat` | Script para desinstalar servicio |

---

## Soporte

Si tienes problemas:

1. Revisa los logs en `storage/logs/`
2. Verifica el estado del servicio
3. Ejecuta el worker manualmente para ver errores en tiempo real:
   ```cmd
   php artisan queue:work --verbose
   ```

---

## Checklist de Instalación

- [ ] Ejecutar `php artisan migrate`
- [ ] Configurar `QUEUE_CONNECTION=database` en `.env`
- [ ] Descargar e instalar NSSM en `C:\nssm\`
- [ ] Ajustar rutas en `install-queue-service.bat`
- [ ] Ejecutar `install-queue-service.bat` como administrador
- [ ] Verificar que el servicio esté ejecutándose
- [ ] Probar importando un archivo de prueba
- [ ] Verificar logs en `storage/logs/queue-worker.log`

¡Listo! El sistema de colas está configurado y funcionando.
