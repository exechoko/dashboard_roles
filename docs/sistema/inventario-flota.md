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

## Dónde se ve que a un equipo le falta un accesorio

Un equipo con algún accesorio faltante muestra un aviso naranja **"Falta …"** en:

- el listado de **Flota**, debajo del modelo;
- el **detalle** del equipo, en la línea *Accesorios*;
- la cabecera de su **histórico**, al lado del estado.

Además, cada movimiento donde se relevó deja la constancia en sus observaciones.

## Relevar accesorios en un movimiento

Cuando el equipo se revisa físicamente, el formulario de movimiento muestra el bloque **Accesorios relevados**. Aparece en desinstalación completa y parcial, revisión, relevamiento, devolución, devolución a dependencia y devolver equipo temporal.

Es el caso habitual: la empresa de soporte hace una desinstalación completa y encuentra que el equipo no tiene la antena R.F. Se marca ahí mismo, sin tener que entrar después a editar el equipo.

Cada accesorio queda en **Sin cambios** salvo que se toque, así que un movimiento común no pisa lo que ya se sabía. Marcar **Le falta** deja el equipo como degradado.

Si el movimiento es **Revisión**, el equipo pasa a estado "En revisión" y queda fuera tanto de operativos como de degradados, hasta que se le cargue un estado definitivo.

Lo que se releva queda escrito en las observaciones de ese movimiento, con el formato `Accesorios relevados: Antena R.F.: FALTA | Kit de instalación: presente.` Esa línea del histórico no se vuelve a tocar nunca: el equipo guarda cómo está hoy, el histórico guarda lo que se constató en cada momento.

## Carga inicial del relevamiento

La primera vez, en vez de marcar equipo por equipo, se corre:

```
php artisan equipos:relevar-accesorios-inicial
```

Marca los MDT400 relevados sin antena R.F. y toda la flota HTT500 en estado operativo, que no tiene antenas disponibles. Con `--dry-run` muestra qué haría sin escribir nada.

Solo toca los equipos que todavía están **Sin relevar**, así que se puede correr más de una vez sin pisar lo que ya se cargó a mano. Si algún TEI de la lista no existe en la base, lo avisa.

## Reponer un accesorio

Cuando se consigue el repuesto, se carga como un movimiento más — normalmente **Instalación completa** o **Re instalación Parcial** — marcando el accesorio en **Lo tiene**.

No hay que corregir ni borrar nada anterior: el movimiento donde se detectó la falta queda como está, y el nuevo movimiento deja asentado cuándo se repuso. El equipo sale de Degradados y vuelve a Operativos solo.

## Accesorios de un equipo

Al editar un equipo, el bloque **Accesorios** releva antena R.F., frente remoto, GPS y kit de instalación. Cada uno tiene tres valores:

- **Sin relevar**: todavía no se revisó. No afecta las estadísticas.
- **Lo tiene**: el accesorio está.
- **Le falta**: el equipo queda marcado como **degradado**.

Un equipo degradado es distinto de uno roto: el transceptor funciona, pero sin ese accesorio no puede salir a la calle. Deja de contar como operativo y como instalado/asignado, y pasa al indicador **Degradados** hasta que se consiga el repuesto.

El campo de observación al lado de cada accesorio sirve para anotar el detalle (por ejemplo, de qué equipo se sacó la antena).

## Estadísticas

1. Abrí [Estadísticas](/equipos/estadisticas).
2. Consultá los indicadores del parque y el detalle por recurso.
3. Pulsá cualquier indicador para ver la lista de equipos que lo componen.

Es una pantalla de solo consulta. Sirve para ver totales y distribución antes de entrar a los listados.

La flota se reparte en indicadores que no se solapan y suman el total:

- **Operativos**: el estado dice que funcionan y no les falta ningún accesorio relevado.
- **Degradados (falta accesorio)**: el estado dice que funcionan pero les falta antena R.F., frente remoto, GPS o kit de instalación. Se recuperan comprando el repuesto.
  Acá entra toda la flota HTT500: está relevada sin antena, así que ya no necesita contarse aparte como antes.
- **No Operativos**: baja, no funciona, perdido, degradado sin accesorios o recambio.
- **Otros estados**: en revisión.

El bloque **Equipos a recuperar por repuesto** indica cuántos equipos volverían a servicio por cada accesorio que se consiga. Haciendo clic en una fila se abre la lista de cuáles son. Un equipo al que le falta más de un accesorio aparece en cada fila, así que esa suma puede superar el total de degradados.

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
