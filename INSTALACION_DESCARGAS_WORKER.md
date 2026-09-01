# Instalación del Worker para Plataforma de Descargas

## Descripción

La Plataforma de Descargas utiliza el sistema de colas (queues) de Laravel para procesar archivos de forma asíncrona. Esto es necesario porque:

1. Los archivos pueden ser muy grandes (hasta 10GB)
2. El sistema está detrás de Cloudflare, que tiene límites de timeout
3. Permite mostrar progreso en tiempo real al usuario
4. Evita bloquear la interfaz durante la carga

## Configuración del Worker

### Opción 1: Usar el Worker General (Recomendado)

El worker general ya está configurado para procesar todas las colas del sistema, incluyendo la cola `descargas`.

**Verificar que el worker está corriendo:**

```bash
# En Windows, verificar el proceso
tasklist | findstr php

# Deberías ver algo como:
# php.exe  1234  Console  1  123,456 K  Running  0:05:23  php artisan queue:work
```

**Si el worker no está corriendo:**

```bash
# Iniciar el worker manualmente
php artisan queue:work --queue=default,descargas,mbox --sleep=3 --tries=2 --timeout=7200
```

### Opción 2: Crear un Servicio en Windows (Recomendado para Producción)

Para que el worker se inicie automáticamente con Windows, puedes usar NSSM (Non-Sucking Service Manager):

1. **Descargar NSSM:**
   - Ir a https://nssm.cc/download
   - Descargar la versión win32 o win64 según tu sistema

2. **Instalar el servicio:**

```bash
# Abrir CMD como administrador
nssm install LaravelQueueWorkerDescargas

# En la interfaz gráfica:
# Path: C:\php\php.exe (o donde tengas PHP)
# Arguments: artisan queue:work --queue=default,descargas,mbox --sleep=3 --tries=2 --timeout=7200
# Working directory: C:\Apache24\htdocs\dashboard_roles
```

3. **Iniciar el servicio:**

```bash
nssm start LaravelQueueWorkerDescargas
```

4. **Verificar el estado:**

```bash
nssm status LaravelQueueWorkerDescargas
```

### Opción 3: Usar el Programador de Tareas de Windows

Si prefieres usar el Programador de Tareas:

1. Abrir el Programador de Tareas de Windows
2. Crear una nueva tarea básica
3. Nombre: "Laravel Queue Worker Descargas"
4. Trigger: "When the computer starts"
5. Action: "Start a program"
   - Program: `C:\php\php.exe`
   - Arguments: `artisan queue:work --queue=default,descargas,mbox --sleep=3 --tries=2 --timeout=7200`
   - Start in: `C:\Apache24\htdocs\dashboard_roles`

## Colas Configuradas

El sistema tiene las siguientes colas configuradas:

| Cola | Timeout | Descripción |
|------|---------|-------------|
| `default` | 90s | Tareas rápidas y generales |
| `descargas` | 7200s (2h) | Procesamiento de archivos de descarga |
| `mbox` | 86400s (24h) | Indexación de backups de correo |

## Monitoreo del Worker

### Ver trabajos en cola

```bash
# Ver trabajos pendientes
php artisan queue:monitor descargas

# Ver trabajos fallidos
php artisan queue:failed
```

### Reiniciar trabajos fallidos

```bash
# Reintentar todos los trabajos fallidos
php artisan queue:retry all

# Reintentar un trabajo específico
php artisan queue:retry {id}
```

### Limpiar trabajos fallidos

```bash
# Eliminar todos los trabajos fallidos
php artisan queue:flush
```

## Logs

Los logs de los trabajos se encuentran en:

- **Logs generales:** `storage/logs/laravel.log`
- **Logs de limpieza de ZIPs:** `storage/logs/descargas_limpiar_zips.log`

### Ver logs en tiempo real

```bash
# PowerShell
Get-Content storage/logs/laravel.log -Wait

# CMD
type storage/logs/laravel.log | findstr /C:"descargas"
```

## Notificaciones por Telegram

El sistema envía notificaciones automáticas por Telegram cuando:

1. Un job de descarga falla después de todos los reintentos
2. El comando de limpieza de ZIPs expirados falla

Para configurar las notificaciones, verifica que el bot de Telegram esté configurado en `.env`:

```env
TELEGRAM_BOT_TOKEN=tu_token_aqui
TELEGRAM_CHAT_ID=tu_chat_id_aqui
```

## Limpieza Automática de ZIPs

El sistema limpia automáticamente los archivos ZIP temporales expirados cada hora.

**Ejecutar manualmente:**

```bash
php artisan descargas:limpiar-zips
```

**Configuración:**

Los ZIPs expiran después de 24 horas por defecto. Puedes cambiar esto en `config/descargas.php`:

```php
'zip_temp_expiracion_horas' => 24,
```

## Solución de Problemas

### El worker no está procesando trabajos

1. Verificar que el worker está corriendo:
   ```bash
   tasklist | findstr php
   ```

2. Verificar que la cola `descargas` esté configurada:
   ```bash
   php artisan queue:monitor descargas
   ```

3. Reiniciar el worker:
   ```bash
   # Detener el worker actual (Ctrl+C si está en primer plano)
   # O detener el servicio
   nssm stop LaravelQueueWorkerDescargas
   
   # Iniciar nuevamente
   php artisan queue:work --queue=default,descargas,mbox --sleep=3 --tries=2 --timeout=7200
   ```

### Los trabajos fallan repetidamente

1. Ver los trabajos fallidos:
   ```bash
   php artisan queue:failed
   ```

2. Ver el error específico:
   ```bash
   php artisan queue:failed {id}
   ```

3. Revisar los logs:
   ```bash
   Get-Content storage/logs/laravel.log -Tail 100
   ```

### El progreso no se actualiza en la interfaz

1. Verificar que la ruta `/descargas/admin/job-status/{jobId}` esté accesible
2. Abrir la consola del navegador (F12) y verificar que no haya errores JavaScript
3. Verificar que el worker esté procesando el trabajo

### Los archivos no se mueven a la ubicación final

1. Verificar permisos de escritura en la carpeta `storage/app/descargas/`
2. Verificar que el disco `descargas` esté configurado en `config/filesystems.php`
3. Revisar los logs para ver el error específico

## Comandos Útiles

```bash
# Ver estado de todas las colas
php artisan queue:monitor default,descargas,mbox

# Pausar el worker (después de terminar el trabajo actual)
# Presionar Ctrl+C en la ventana del worker

# Reiniciar el worker después de cambios en el código
php artisan queue:restart

# Ver trabajos en tiempo real
php artisan queue:work --queue=descargas --verbose

# Limpiar la cola (¡cuidado! elimina todos los trabajos pendientes)
php artisan queue:clear --queue=descargas
```

## Soporte

Si tienes problemas con el worker o los jobs de descarga:

1. Revisar los logs en `storage/logs/laravel.log`
2. Verificar la configuración en `config/queue.php` y `config/descargas.php`
3. Contactar al administrador del sistema

---

**Última actualización:** 2026-08-28
**Versión:** 1.0
