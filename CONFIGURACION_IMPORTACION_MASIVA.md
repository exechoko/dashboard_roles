# Configuración para Importación Masiva de Archivos Excel

## Problema Resuelto

El error `PostTooLargeException` ocurre cuando el tamaño total de los archivos enviados excede los límites configurados en PHP.

```
Warning: POST Content-Length of 59504983 bytes exceeds the limit of 52428800 bytes
```

## Soluciones Implementadas

### 1. Configuración en .htaccess (✅ Ya configurado)

El archivo `public/.htaccess` ya incluye las directivas necesarias:

```apache
<IfModule mod_php7.c>
    php_value upload_max_filesize 512M
    php_value post_max_size 512M
    php_value max_execution_time 600
    php_value max_input_time 600
    php_value memory_limit 512M
</IfModule>
```

### 2. Validación en Frontend (✅ Ya implementado)

El formulario ahora valida:
- **Tamaño máximo por archivo**: 100 MB
- **Tamaño total máximo**: 400 MB
- Muestra advertencias visuales si se exceden los límites
- Deshabilita el botón de importar si hay errores

### 3. Configuración Adicional en php.ini (⚠️ Requiere acción manual)

Si el `.htaccess` no es suficiente, edita el archivo `php.ini` de Apache:

**Ubicación típica en Windows con Apache:**
```
C:\Apache24\php\php.ini
```

**Directivas a modificar:**
```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
max_file_uploads = 50
```

**Pasos:**
1. Abre `php.ini` con un editor de texto (como administrador)
2. Busca cada directiva y modifica su valor
3. Si la directiva tiene `;` al inicio, quítalo para descomentarla
4. Guarda el archivo
5. Reinicia Apache:
   ```cmd
   httpd -k restart
   ```
   O desde el panel de servicios de Windows

### 4. Verificar Configuración Actual

Crea un archivo temporal `info.php` en `public/`:

```php
<?php
phpinfo();
```

Accede a `http://tu-servidor/info.php` y busca:
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`
- `memory_limit`

**⚠️ IMPORTANTE:** Elimina este archivo después de verificar por seguridad.

## Límites Recomendados por Escenario

### Escenario 1: Pocos archivos grandes (3-5 archivos de 50-80 MB)
```ini
upload_max_filesize = 512M
post_max_size = 512M
```

### Escenario 2: Muchos archivos pequeños (20-30 archivos de 10-20 MB)
```ini
upload_max_filesize = 100M
post_max_size = 512M
max_file_uploads = 50
```

### Escenario 3: Configuración conservadora (si tienes limitaciones de servidor)
```ini
upload_max_filesize = 100M
post_max_size = 256M
max_file_uploads = 20
```

## Recomendaciones de Uso

1. **Divide en lotes**: Si tienes muchos archivos, impórtalos en grupos de 3-5 archivos
2. **Monitorea el tamaño**: El frontend muestra el tamaño total antes de enviar
3. **Tiempo de espera**: Archivos grandes pueden tardar varios minutos en procesarse
4. **No cierres el navegador**: Espera a que termine el proceso completo

## Troubleshooting

### El error persiste después de configurar .htaccess

**Causa:** Apache no está leyendo las directivas de `.htaccess`

**Solución:** Edita directamente `php.ini` (ver sección 3)

### Error "Maximum execution time exceeded"

**Causa:** El procesamiento tarda más de lo permitido

**Solución:** Aumenta `max_execution_time` en `php.ini`:
```ini
max_execution_time = 900
```

### Error de memoria "Allowed memory size exhausted"

**Causa:** PHP se queda sin memoria al procesar archivos grandes

**Solución:** Aumenta `memory_limit` en `php.ini`:
```ini
memory_limit = 1024M
```

### Los cambios no se aplican

1. Verifica que editaste el `php.ini` correcto (puede haber varios)
2. Reinicia Apache completamente
3. Limpia la caché del navegador
4. Verifica con `phpinfo()` que los cambios se aplicaron

## Contacto y Soporte

Si el problema persiste después de aplicar estas configuraciones, verifica:
- Logs de Apache: `C:\Apache24\logs\error.log`
- Logs de Laravel: `storage/logs/laravel.log`
- Configuración del servidor web (VirtualHost)
