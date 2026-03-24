# Integración de Expedientes CECOCO

## Descripción

Este módulo permite visualizar el detalle completo de expedientes desde el sistema CECOCO mediante conexión HTTP directa en PHP nativo, sin necesidad de Python. El sistema se conecta al servidor CECOCO, descarga reportes BIRT en formato Excel y los procesa para mostrar una línea de tiempo completa del expediente.

## Características

- **PHP Nativo**: Sin dependencia de Python, compatible con Windows Server 2012 R2
- **Consulta en tiempo real**: Conexión HTTP directa al servidor CECOCO
- **Reportes BIRT**: Descarga y procesa reportes Excel generados por BIRT
- **Línea de tiempo organizada**: Muestra todos los eventos del expediente en orden cronológico
- **Permisos granulares**: Control de acceso mediante Spatie Permission
- **Interfaz responsive**: Vista optimizada para dispositivos móviles y escritorio
- **Manejo de errores**: Detección automática de expedientes que requieren restauración
- **Limpieza automática**: Archivos temporales se eliminan después de procesar

## Arquitectura

### Componentes Principales

1. **Servicio**: `App\Services\CecocoExpedienteService`
   - Inicia sesión en CECOCO mediante login AJAX
   - Inicializa reporte BIRT (report_history.rptdesign)
   - Descarga archivo Excel con datos del expediente
   - Parsea el Excel y estructura timeline cronológica
   - Limpia archivos temporales automáticamente

2. **Controlador**: `App\Http\Controllers\EventoCecocoController@verExpediente`
   - Autoriza acceso mediante permiso `ver-expediente-cecoco`
   - Invoca el servicio para obtener datos
   - Renderiza la vista con el detalle

3. **Vista**: `resources/views/eventos-cecoco/expediente.blade.php`
   - Muestra información general del expediente
   - Presenta línea de tiempo interactiva
   - Incluye estilos CSS personalizados

4. **Ruta**: `GET /cecoco/{eventoCecoco}/expediente`

### Flujo de Operación

```
1. Usuario → Click "Ver Detalle"
2. Controlador → Verifica permiso ver-expediente-cecoco
3. Servicio → GET http://172.26.100.34:8080/CECOCO_webapp
4. Servicio → POST /ajax/perfil/AjaxServletPerfil (Login)
5. Servicio → GET /run (Inicializar reporte BIRT)
6. Servicio → POST /frameset (Descargar Excel)
7. Servicio → Parsear Excel → Estructurar datos
8. Vista → Renderizar timeline
9. Servicio → Limpiar archivo temporal
```

## Configuración

### 1. Variables de Entorno

Agregar al archivo `.env`:

```env
# Configuración CECOCO Expedientes (PHP Nativo - Sin Python)
CECOCO_URL=http://172.26.100.34:8080
CECOCO_USER=
CECOCO_PASSWORD=
CECOCO_TIMEOUT=60
```

### 2. Archivo de Configuración

Ya creado en: `config/cecoco.php`

```php
return [
    'url' => env('CECOCO_URL', 'http://172.26.100.34:8080'),
    'user' => env('CECOCO_USER', ''),
    'password' => env('CECOCO_PASSWORD', ''),
    'timeout' => env('CECOCO_TIMEOUT', 60),
];
```

### 3. Requisitos del Sistema

- ✅ PHP 7.4 o superior
- ✅ Laravel HTTP Client (incluido en Laravel)
- ✅ PhpSpreadsheet (para parsear Excel)
- ✅ Acceso de red al servidor CECOCO (172.26.100.34:8080)
- ✅ Permisos de escritura en `storage/app/temp`
- ❌ **NO requiere Python** (compatible con Windows Server 2012 R2)

### 4. Permisos del Sistema

Ejecutar el seeder para crear el nuevo permiso:

```bash
php artisan db:seed --class=SeederTablaPermisos
```

Esto creará el permiso: `ver-expediente-cecoco`

### 5. Asignar Permisos a Roles

Desde el panel de administración, asignar el permiso `ver-expediente-cecoco` a los roles que deban tener acceso.

## Validación de Configuración

El servicio incluye un método de validación:

```php
use App\Services\CecocoExpedienteService;

$service = app(CecocoExpedienteService::class);
$validacion = $service->validarConfiguracion();

if (!$validacion['valido']) {
    foreach ($validacion['errores'] as $error) {
        echo "❌ $error\n";
    }
} else {
    echo "✅ Configuración válida\n";
}
```

## Uso

### Desde la Interfaz Web

1. Navegar a **CECOCO > Eventos**
2. Buscar eventos con filtros
3. En la tabla de resultados, hacer clic en el botón **"Detalle"** (verde)
4. Se mostrará la vista completa del expediente con línea de tiempo

### Desde la Vista de Detalle

1. Al ver un evento individual, hacer clic en **"Ver Detalle Completo"**
2. El sistema ejecutará el script Python y mostrará el expediente completo

## Estructura de Datos

### Timeline del Expediente

Cada evento en la línea de tiempo contiene:

```php
[
    'nro_expediente' => '123456',
    'fecha_hora' => '01/01/2024 10:30:00',
    'operador' => 'OPERADOR NOMBRE',
    'descripcion' => 'Descripción del evento',
    'tipo_servicio' => 'TIPO DE SERVICIO',
    'direccion' => 'Dirección del evento',
    'telefono' => '123456789',
    'estado' => 'Estado del evento',
    'recurso' => 'Móvil asignado'
]
```

## Manejo de Errores

### Expediente Requiere Restauración

Si el expediente está en backup, el script Python retorna `RESTORE_REQUIRED`:

```
Error al obtener el expediente: El expediente requiere restauración desde backup. 
Contacte al administrador del sistema CECOCO.
```

### Script Python No Encontrado

```
Error al obtener el expediente: Script Python no encontrado en: F:\Scripts_Eventos\scrapcoco_expediente.py
```

### Permisos Insuficientes

Si el usuario no tiene el permiso `ver-expediente-cecoco`, verá error 403.

## Logs

El servicio registra todas las ejecuciones en el log de Laravel:

```php
Log::info('Ejecución script Python expediente', [
    'expediente' => '123456',
    'comando' => 'python F:\Scripts_Eventos\scrapcopo_expediente.py 123456',
    'return_code' => 0,
    'output' => 'OK'
]);
```

## Seguridad

- ✅ Autorización mediante Spatie Permission
- ✅ Validación de entrada (número de expediente)
- ✅ Sanitización de comandos shell con `escapeshellarg()`
- ✅ Eliminación de archivos temporales después de procesar
- ✅ Control de permisos en vistas con `@can`

## Rendimiento

- Los archivos Excel temporales se eliminan automáticamente
- No se cachean los datos del expediente (siempre datos en tiempo real)
- Timeout configurable para evitar bloqueos prolongados

## Troubleshooting

### El botón "Detalle" no aparece

**Causa**: El usuario no tiene el permiso `ver-expediente-cecoco`

**Solución**: Asignar el permiso al rol del usuario

### Error al ejecutar Python

**Causa**: Python no está en el PATH o la ruta es incorrecta

**Solución**: 
1. Verificar que Python esté instalado: `python --version`
2. Ajustar `CECOCO_PYTHON_PATH` en `.env` con la ruta completa

### El archivo Excel no se genera

**Causa**: Permisos de escritura en `F:\Scripts_Eventos`

**Solución**: Verificar permisos del directorio para el usuario del servidor web

### Datos no se muestran correctamente

**Causa**: Formato del Excel no coincide con el parser

**Solución**: Revisar logs en `storage/logs/laravel.log` para ver detalles del parseo

## Mantenimiento

### Actualizar Script Python

1. Modificar el script en `F:\Scripts_Eventos\scrapcoco_expediente.py`
2. No requiere reiniciar el servidor Laravel
3. Los cambios se aplican inmediatamente

### Agregar Nuevos Campos al Timeline

1. Modificar `CecocoExpedienteService::mapearColumnasExpediente()`
2. Actualizar `CecocoExpedienteService::extraerEventoTimeline()`
3. Modificar la vista `expediente.blade.php` para mostrar los nuevos campos

## Archivos Modificados/Creados

### Nuevos Archivos
- `app/Services/CecocoExpedienteService.php`
- `config/cecoco.php`
- `resources/views/eventos-cecoco/expediente.blade.php`
- `INTEGRACION_CECOCO_EXPEDIENTES.md`

### Archivos Modificados
- `app/Http/Controllers/EventoCecocoController.php`
- `routes/web.php`
- `database/seeders/SeederTablaPermisos.php`
- `resources/views/eventos-cecoco/index.blade.php`
- `resources/views/eventos-cecoco/show.blade.php`

## Próximas Mejoras

- [ ] Cache de expedientes consultados recientemente
- [ ] Exportación del expediente completo a PDF
- [ ] Búsqueda de texto dentro del expediente
- [ ] Filtrado de eventos por tipo en la timeline
- [ ] Notificaciones cuando un expediente se actualiza

## Soporte

Para problemas o consultas, revisar:
1. Logs de Laravel: `storage/logs/laravel.log`
2. Validación de configuración mediante el método `validarConfiguracion()`
3. Verificar conectividad con servidor CECOCO (172.26.100.34:8080)
