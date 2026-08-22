# Equipos, flota, vehículos y recursos

## Consultar equipos

1. Abrí [Equipos](/equipos).
2. Usá la búsqueda para localizar por TEI, ISSI, procedencia o propietario.
3. Revisá tipo, marca, modelo, estado y observaciones.
4. Pulsá el TEI para consultar los movimientos históricos del equipo.

## Crear un equipo

Disponible con permiso `crear-equipo`.

1. En Equipos, pulsá **Nuevo**.
2. Elegí tipo de terminal y estado.
3. Ingresá el TEI, que es obligatorio y no puede repetirse.
4. Completá ISSI, nombre ISSI, procedencia, propietario, garantía, baterías y observaciones cuando correspondan.
5. Guardá y verificá que aparezca en el listado.

La creación del equipo no lo asigna automáticamente a una dependencia o móvil. Esa asignación se realiza desde Flota.

## Editar un equipo

1. Buscá el equipo y abrí **Editar**.
2. Actualizá únicamente los datos necesarios.
3. Revisá especialmente TEI, ISSI, baterías y estado.
4. Guardá.

Eliminar un equipo también puede afectar sus relaciones de flota e histórico. Solo debe hacerse con autorización y después de verificar que sea el registro correcto.

## Alta inicial en flota

Flota relaciona un equipo con dependencia, recurso y vehículo.

1. Abrí [Flota](/flota) y pulsá **Nuevo equipo**.
2. Elegí un equipo que todavía no tenga situación de flota.
3. Seleccioná tipo de movimiento, dependencia y recurso.
4. Completá fecha, ticket y observaciones.
5. Adjuntá imágenes o documento cuando formen parte del respaldo.
6. Guardá. Se crea la situación actual y el primer registro histórico.

## Registrar un movimiento de flota

Disponible con permiso `editar-flota`.

1. Abrí [Flota](/flota).
2. Buscá el registro por TEI, ISSI, recurso o dependencia.
3. Pulsá **Editar** en la fila correcta.
4. Elegí el tipo de movimiento y su fecha.
5. Completá los campos que aparezcan según el movimiento: dependencia, recurso, equipo sustituto, estado final o nuevo ISSI.
6. Agregá ticket, observaciones y anexos si corresponden.
7. Revisá el resumen antes de guardar.
8. Guardá y comprobá el nuevo movimiento en el histórico.

Movimientos habituales:

- instalación o movimiento patrimonial;
- desinstalación completa;
- entrega provisoria, revisión o reprogramación;
- devolución o devolución a dependencia;
- reemplazo o recambio de equipos;
- baja, extravío y recuperación.

La opción **solo modificar histórico** registra el evento sin cambiar la situación actual. Debe usarse únicamente cuando se necesita corregir o completar la trazabilidad.

## Consultar el histórico de flota

1. Desde Flota, pulsá el TEI.
2. Revisá movimientos, destinos, recursos, fechas, tickets y anexos.
3. Usá la opción de impresión cuando necesites una copia del histórico.
4. La edición de observaciones o anexos requiere `editar-historico`.

## Búsqueda avanzada

1. Abrí [Búsqueda avanzada](/busqueda-avanzada).
2. Combiná filtros de texto, terminal, equipo, recurso, destino, estado, movimiento, fechas, ticket o patrimonio.
3. Pulsá **Buscar**.
4. Revisá el resultado y exportá a Excel si necesitás trabajar con el mismo conjunto filtrado.

## Vehículos

1. Abrí [Vehículos](/vehiculos).
2. Buscá por marca, modelo, dominio o propiedad.
3. Para crear, completá tipo, marca, modelo y dominio; el dominio no puede repetirse.
4. Agregá chasis, color, propiedad y observaciones cuando correspondan.
5. Guardá. Luego podrá asociarse a un recurso.

## Estadísticas

1. Abrí [Estadísticas](/equipos/estadisticas).
2. Consultá los indicadores del parque y el detalle por recurso.

Es una pantalla de solo consulta. Sirve para ver totales y distribución antes de entrar a los listados.

## Tipos de terminales

1. Abrí [Tipos de terminales](/terminales).
2. Consultá o cargá los tipos disponibles: portátil, móvil, base y base-móvil.

Es el catálogo que alimenta el desplegable al crear un equipo. Solo se modifica al incorporar una clase de equipo nueva.

## Recursos

1. Abrí [Recursos](/recursos).
2. Seleccioná dependencia y, opcionalmente, un vehículo.
3. Ingresá el nombre del recurso.
4. Activá **múltiples equipos** solamente si ese recurso admite más de una asignación simultánea.
5. Guardá.

Un recurso configurado para un único equipo deja de ofrecerse cuando ya está ocupado.
