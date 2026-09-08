# Pendiente Flota 911 — Soporte de guardias (turnos) en parte diario

## Contexto

El parte diario de Flota 911 no se realiza una vez por día calendario sino **una vez por guardia (turno)**. En la División 911 hay 4 guardias que rotan; cada guardia cubre un turno de ~12hs con cambios a las 07:00 y 19:00 (horarios flexibles). El funcionario que llena el parte puede variar y **un mismo funcionario puede aparecer en la dotación de un recurso en el turno mañana y de otro recurso en el turno noche del mismo día**.

Hoy el schema tiene unique `(recurso_id, fecha)` en `recurso_estado_diario` y `recurso_dotaciones`, así que el segundo parte del día pisa al primero. Y la validación de dotación única (implementada en el commit anterior) rechazaría por error casos legítimos de un mismo funcionario en dos turnos del día.

## Alcance

### 1. Migración de schema

**Agregar campos** a `recurso_estado_diario` y `recurso_dotaciones`:

- `guardia` enum(`guardia_1`, `guardia_2`, `guardia_3`, `guardia_4`) — identificatorio del grupo de guardia
- `horario` enum(`07_19`, `19_07`) — turno mañana vs noche
- `fecha_inicio` datetime — hora exacta de inicio del turno (editable)
- `fecha_fin` datetime — hora exacta de fin del turno (editable, cruza medianoche cuando es 19-07)

**Cambio de unique key** en ambas tablas:

- Actual: `unique(recurso_id, fecha)` (heredado del rename original)
- Nuevo: `unique(recurso_id, fecha_inicio)` — no puede haber dos partes que empiecen exactamente a la misma hora para el mismo recurso

`fecha` puede quedar como columna derivada (= `DATE(fecha_inicio)`) o eliminarse. **Recomendación**: eliminar `fecha`, dejar sólo `fecha_inicio`/`fecha_fin`.

**Datos existentes**: no hay registros críticos en dev; borrar filas antes de correr la migración (`TRUNCATE recurso_estado_diario; TRUNCATE recurso_dotaciones;`). No requiere backfill.

**Nueva migración**: `database/migrations/2026_09_08_200004_add_guardia_horario_to_flota911.php`.

### 2. Modelos

- `app/Models/RecursoEstadoDiario.php`:
  - Actualizar `$fillable` con los 4 campos nuevos, sacar `fecha`.
  - Agregar `$casts`: `fecha_inicio` y `fecha_fin` a `datetime`.
  - Agregar `public static array $guardias = ['guardia_1' => 'Guardia 1', ...]` y `$horarios = ['07_19' => '07:00 a 19:00', '19_07' => '19:00 a 07:00']`.

- `app/Models/RecursoDotacion.php`: idem.

### 3. Controller `VehiculoInformeController`

**`parteDiario(Request $request)`**:

- Recibir por query: `guardia`, `horario`, `fecha_inicio`, `fecha_fin`. Todos opcionales; si vienen todos, precarga el parte de ese turno específico.
- Si no vienen, defaults: `guardia = null`, `horario = 07_19`, `fecha_inicio = today 07:00`, `fecha_fin = today 19:00`.
- `getSecciones($destinoIds, $fechaInicio, $fechaFin)`: filtrar `estadoDiario` y `dotaciones` por `fecha_inicio = $fechaInicio` (o rango `[fecha_inicio, fecha_fin]`).

**`generarParteDiario(Request $request)`**:

- Validar: `guardia` in list, `horario` in list, `fecha_inicio` date, `fecha_fin` date, `fecha_fin > fecha_inicio`.
- La validación de dotación única sigue igual (`personal_id` duplicados dentro del mismo POST → ya representa un mismo turno).
- Persistir `RecursoEstadoDiario` con `updateOrCreate(['recurso_id' => X, 'fecha_inicio' => Y], [...])`.
- `RecursoDotacion::where('recurso_id', X)->where('fecha_inicio', Y)->delete()` antes de recrear.

### 4. Vista `resources/views/flota-911/informes/parte-diario.blade.php`

**Nueva sección arriba del formulario** (dentro del card "Configuración"):

```blade
<div class="row">
    <div class="col-md-3">
        <label>Guardia <span class="text-danger">*</span></label>
        <select name="guardia" class="form-control" required>
            @foreach(\App\Models\RecursoEstadoDiario::$guardias as $k => $label)
                <option value="{{ $k }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label>Horario <span class="text-danger">*</span></label>
        <select name="horario" class="form-control" id="inputHorario" required>
            <option value="07_19">07:00 a 19:00</option>
            <option value="19_07">19:00 a 07:00</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Fecha inicio</label>
        <input type="datetime-local" name="fecha_inicio" class="form-control" id="inputFechaInicio" required>
    </div>
    <div class="col-md-3">
        <label>Fecha fin</label>
        <input type="datetime-local" name="fecha_fin" class="form-control" id="inputFechaFin" required>
    </div>
</div>
```

**JS** — al cambiar fecha (date) u horario, autocompletar `fecha_inicio` y `fecha_fin`:

```js
function actualizarFechasPorHorario() {
    const fecha = document.getElementById('inputFecha').value; // date input existente
    const horario = document.getElementById('inputHorario').value;
    if (!fecha || !horario) return;
    if (horario === '07_19') {
        document.getElementById('inputFechaInicio').value = `${fecha}T07:00`;
        document.getElementById('inputFechaFin').value = `${fecha}T19:00`;
    } else {
        const fechaFin = new Date(fecha);
        fechaFin.setDate(fechaFin.getDate() + 1);
        const yyyy = fechaFin.getFullYear();
        const mm = String(fechaFin.getMonth() + 1).padStart(2, '0');
        const dd = String(fechaFin.getDate()).padStart(2, '0');
        document.getElementById('inputFechaInicio').value = `${fecha}T19:00`;
        document.getElementById('inputFechaFin').value = `${yyyy}-${mm}-${dd}T07:00`;
    }
}
document.getElementById('inputFecha').addEventListener('change', actualizarFechasPorHorario);
document.getElementById('inputHorario').addEventListener('change', actualizarFechasPorHorario);
```

El usuario puede editar `fecha_inicio` y `fecha_fin` manualmente si el turno se movió.

### 5. Servicio `FlotaInformeService::generarParteDiario`

- Cambiar firma a `generarParteDiario(Collection $secciones, string $guardia, string $horario, Carbon $fechaInicio, Carbon $fechaFin, ?string $novedadesGenerales = null)`.
- En el encabezado del .docx incluir: `"GUARDIA X — 07:00 a 19:00 del 08/09/2026"` (formatear con `RecursoEstadoDiario::$guardias[$guardia]` y `::$horarios[$horario]`).

## Archivos a modificar/crear

**Crear:**
- `database/migrations/2026_09_08_200004_add_guardia_horario_to_flota911.php`

**Modificar:**
- `app/Models/RecursoEstadoDiario.php` — `$fillable`, `$casts`, `$guardias`, `$horarios`.
- `app/Models/RecursoDotacion.php` — idem.
- `app/Http/Controllers/VehiculoInformeController.php` — `parteDiario()`, `generarParteDiario()`, `getSecciones()`.
- `app/Services/FlotaInformeService.php` — firma y encabezado del .docx.
- `resources/views/flota-911/informes/parte-diario.blade.php` — selectores + JS.

## Verificación end-to-end

1. **Migración**: `php artisan migrate` sin errores. Confirmar columnas y unique key con `DESCRIBE recurso_estado_diario` y `SHOW INDEX FROM recurso_estado_diario`.

2. **Parte diario turno mañana**: `/flota-911/informes/parte-diario` → Guardia 1, 07-19, fecha hoy → cargar dotación → generar. Verificar .docx y persistencia en `recurso_estado_diario` con `fecha_inicio = today 07:00`.

3. **Parte diario turno noche mismo día**: nuevo parte → Guardia 2, 19-07, fecha hoy → `fecha_fin` autocompleta al día siguiente 07:00 → cargar dotación (puede incluir funcionarios que estuvieron en el turno mañana en otros recursos) → generar. Verificar que no pisa al parte del turno mañana.

4. **Dotación única por turno**: en un mismo parte (mismo POST), un funcionario en 2 recursos → rechaza con error. Pero entre turnos distintos del mismo día, se permite.

5. **Horario editado manualmente**: cargar parte con `fecha_inicio = today 08:15` (moviendo 15 min el inicio) → guardar → recargar la vista con esos parámetros → precarga correcta.

6. **Informe .docx**: verifica que encabezado dice "GUARDIA 1 — 07:00 a 19:00 del 08/09/2026".

## Fuera de alcance

- Modelo `PartesDiarios` como entidad separada (con FK desde `recurso_estado_diario` y `recurso_dotaciones`). Es la solución más limpia pero requiere refactor más grande — se puede plantear después si aparece la necesidad de listar/auditar partes históricos.
- Campo dedicado "jefe de guardia" — el usuario decidió que va en las observaciones/novedades del parte de manera manual.
- UI para listar partes históricos por guardia/fecha.
- Ajuste equivalente para `recurso_estados_seccion` — sección tiene un estado global, no por turno.
