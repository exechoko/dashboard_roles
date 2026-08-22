# Tareas, usuarios, roles, manuales y auditoría

## Crear una tarea

Disponible con permiso `crear-tarea`.

1. Abrí [Tareas](/tareas) y pulsá **Nueva Tarea**.
2. Completá nombre y descripción.
3. Elegí si será única, diaria, semanal o mensual.
4. Definí intervalo y día semanal o mensual cuando corresponda.
5. Indicá fechas de inicio y fin.
6. Marcá la tarea como activa y guardá.

El sistema genera instancias futuras de las tareas recurrentes.

## Completar una tarea

1. Abrí Tareas.
2. Filtrá por nombre, estado, fecha, usuario u observaciones.
3. Localizá la instancia correspondiente.
4. Cambiá el estado a **Realizada**.
5. Agregá observaciones y guardá.

El sistema registra automáticamente quién la completó y en qué fecha.

## Cambiar una recurrencia

1. Abrí **Editar** en la definición de la tarea.
2. Modificá recurrencia, intervalo o fechas.
3. Elegí si se cambia solo la definición o si deben regenerarse las instancias futuras pendientes.
4. Si regenerás, revisá la fecha de corte.
5. Guardá.

## Personal efectivo

Disponible con permiso `ver-personal`.

1. Abrí [Personal efectivo](/tareas/personal-efectivo).
2. Consultá o cargá nombre, apellido, LP y jerarquía.
3. El LP tiene cinco dígitos y no puede repetirse.

Es el padrón del que salen las personas al completar entregas, actas y retenciones. Conviene dar de alta a la persona acá antes de necesitarla en un acta.

## Activaciones de tótem

Disponible con permiso `ver-activacion-totem`.

1. Abrí [Activaciones Tótem](/tareas/activaciones-totem).
2. Revisá las activaciones de botón de emergencia detectadas en los eventos de CECOCO.
3. Registrá si el video ya fue descargado.
4. Atendé los avisos de vencimiento.

El plazo legal de conservación es de seis meses. Pasado el vencimiento la grabación ya no está disponible, así que los avisos deben atenderse antes de que llegue la fecha.

## Actas de credenciales

Disponible con permiso `ver-menu-constancias-credenciales`.

1. Abrí [Actas de credenciales](/constancias-credenciales).
2. Creá el acta con los datos del destinatario.
3. Descargá el documento generado.
4. Cargá el acta firmada cuando la recibas.
5. Enviala por correo al destinatario si corresponde.

## Administrar usuarios

Disponible según `ver-usuario`, `crear-usuario` y `editar-usuario`.

1. Abrí [Usuarios](/usuarios).
2. Creá o editá una cuenta.
3. Completá nombre, apellido, identificadores institucionales y correo.
4. Definí contraseña solo cuando corresponda.
5. Asigná uno o varios roles.
6. Habilitá acceso externo únicamente si está autorizado.
7. Guardá.

No se puede eliminar el usuario con el que está iniciada la sesión.

## Administrar roles y permisos

1. Abrí [Roles](/roles).
2. Creá o editá un rol.
3. Definí un nombre claro.
4. Seleccioná los permisos necesarios por módulo.
5. Evitá asignar permisos de creación, edición o borrado si el rol solo necesita consultar.
6. Guardá. El conjunto de permisos del rol se reemplaza por la selección actual.

## Consultar manuales

1. Abrí [Instructivos](/manuales/instructivos) o [Manuales CECOCO](/manuales/cecoco).
2. Buscá por título, temática o archivo.
3. Pulsá **Ver** para usar el visor.
4. Descargá el documento solo si tenés el permiso correspondiente.

La carga admite PDF, DOCX, Markdown y HTML. Para instructivos se solicita título y temática.

## Auditoría

1. Abrí [Auditoría](/auditoria).
2. Filtrá por acción, tabla, usuario o rango de fechas.
3. Revisá fecha, usuario, entidad, acción y resumen del cambio.
4. Usá la auditoría para verificar trazabilidad; no permite editar ni borrar eventos.

Los campos sensibles se ocultan en los registros de auditoría.
