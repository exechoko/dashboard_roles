# Solución: Error "Data truncated for column 'estado'"

## Error Completo
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'estado' at row 1
(SQL: insert into `importaciones` (`nombre_archivo`, `estado`, ...) values (..., pendiente, ...))
```

## Causa
La columna `estado` en la tabla `importaciones` está definida como ENUM con solo 3 valores:
- `'procesando'`
- `'completado'`
- `'error'`

Pero el sistema de colas intenta insertar `'pendiente'`, que no está permitido.

---

## Solución Rápida (Ejecutar AHORA)

Abre CMD o PowerShell en el directorio del proyecto y ejecuta:

```cmd
cd C:\Apache24\htdocs\dashboard_roles
php artisan migrate
```

Esto ejecutará la migración que agrega `'pendiente'` al ENUM.

---

## Solución Manual (Si la migración no funciona)

### Opción A: Usando phpMyAdmin o MySQL Workbench

1. Abre tu gestor de base de datos
2. Selecciona la base de datos del proyecto
3. Ejecuta este SQL:

```sql
ALTER TABLE `importaciones` 
MODIFY COLUMN `estado` ENUM('pendiente', 'procesando', 'completado', 'error') 
DEFAULT 'procesando';
```

### Opción B: Usando línea de comandos MySQL

```cmd
mysql -u root -p nombre_base_datos
```

Luego ejecuta:
```sql
ALTER TABLE `importaciones` 
MODIFY COLUMN `estado` ENUM('pendiente', 'procesando', 'completado', 'error') 
DEFAULT 'procesando';
```

---

## Verificar que se Solucionó

```cmd
php artisan tinker
```

Luego ejecuta:
```php
\App\Models\Importacion::create(['nombre_archivo' => 'test.xls', 'estado' => 'pendiente']);
```

Si no da error, está solucionado. Limpia el registro de prueba:
```php
\App\Models\Importacion::where('nombre_archivo', 'test.xls')->delete();
exit
```

---

## Estados Disponibles Ahora

Después de aplicar la solución, la columna `estado` acepta estos valores:

1. **`pendiente`** - Archivo en cola, esperando procesamiento
2. **`procesando`** - Archivo siendo procesado actualmente
3. **`completado`** - Archivo procesado exitosamente
4. **`error`** - Archivo con errores durante el procesamiento

---

## Flujo de Estados en el Sistema de Colas

```
Usuario sube archivos
    ↓
estado = 'pendiente' (se agrega a la cola)
    ↓
Worker toma el job
    ↓
estado = 'procesando' (comienza a procesar)
    ↓
    ├─→ Éxito → estado = 'completado'
    └─→ Error → estado = 'error'
```

---

## Prevención Futura

La migración ya está corregida en:
- `database/migrations/2024_01_01_000001_create_importaciones_table.php`
- `database/migrations/2024_03_12_164000_add_pendiente_to_importaciones_estado.php`

Si reinstalas la base de datos o la creas en otro servidor, ejecuta:
```cmd
php artisan migrate:fresh
```

---

## Troubleshooting

### Error: "Nothing to migrate"

Si ya ejecutaste las migraciones anteriormente, usa:
```cmd
php artisan migrate:refresh
```

⚠️ **CUIDADO**: Esto borrará todos los datos. Solo úsalo en desarrollo.

### Para producción (sin perder datos):

```cmd
php artisan migrate --force
```

Si dice "Nothing to migrate", ejecuta el SQL manualmente (Opción A o B de arriba).

---

## Resumen

**Solución en 1 línea:**
```cmd
php artisan migrate
```

Si no funciona, ejecuta este SQL directamente en la base de datos:
```sql
ALTER TABLE `importaciones` MODIFY COLUMN `estado` ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'procesando';
```
