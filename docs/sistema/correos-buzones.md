# Visor de correos y buzones

El visor de correos muestra el contenido de backups `.mbox` (por ejemplo, exportaciones de Google Takeout) organizados por oficina. Cada oficina es un "buzón" y el acceso a sus mensajes depende del rol asignado a ese buzón.

## Consultar correos

Disponible con permiso `ver-visor-mails`.

1. Abrí [Visor de Correos](/herramientas/mails).
2. Elegí el buzón en el selector; el sistema solo lista los buzones habilitados para tus roles. Debajo se indica hasta qué fecha hay backup indexado.
3. Filtrá por texto (busca en asunto, cuerpo y nombres de adjuntos), remitente, destinatario/CC, asunto, nombre de adjunto, rango de fechas, carpeta (Recibidos, Enviados, Borradores, Spam, Papelera, Archivados), presencia de adjuntos o etiqueta de Gmail.
4. Pulsá **Buscar**. La lista muestra fecha, remitente, asunto, si tiene adjuntos y tamaño.
5. Abrí un mensaje para ver el detalle: encabezados, adjuntos descargables y el cuerpo completo.
6. Desde el detalle podés **Imprimir / PDF** (abre una vista lista para Ctrl+P), **Descargar .eml** (mensaje original) o descargar cada adjunto por separado.
7. Exportá el listado filtrado a Excel con **Exportar**.

Si un mensaje es muy grande, el cuerpo se muestra recortado; para verlo completo hay que descargar el `.eml`.

## Administrar buzones

Disponible con permiso `administrar-visor-mails`. Un usuario con este permiso ve todos los buzones, sin importar el rol.

1. Abrí [Buzones de Correo](/herramientas/mails/buzones) (accesible también desde **Administrar Buzones** en el visor).
2. Para dar de alta buzones en bloque, usá **Detectar Oficinas**: el sistema lista las subcarpetas encontradas en la ruta configurada de backups (`MBOX_PATH`) y marca cuáles ya son buzones. Tildá las nuevas y confirmá para crearlas con un nombre sugerido.
3. Para crear un buzón manualmente, pulsá **Nuevo Buzón** y completá nombre, carpeta (relativa a la ruta de backups), email real de la casilla (opcional), rol con acceso y descripción.
4. Asigná siempre un rol: los usuarios con ese rol podrán ver los mensajes de ese buzón desde el visor. Si el rol necesario no existe, creálo primero en Roles.
5. Un buzón puede quedar inactivo o eliminarse; al eliminarlo se borra el índice de mensajes pero los archivos `.mbox` del disco no se tocan.

## Indexar archivos .mbox de un buzón

1. Desde el listado de buzones, abrí **Archivos** del buzón correspondiente.
2. El sistema busca de forma recursiva los `.mbox` presentes en la carpeta del buzón. Los que ya están registrados muestran estado (pendiente, indexando, indexado o error) y cantidad de mensajes.
3. Pulsá **Registrar e indexar** en un archivo nuevo para encolar su procesamiento, o **Reindexar** si un archivo ya indexado cambió de tamaño o fecha (se marca como "Modificado").
4. Mientras se indexa, la fila muestra una barra de progreso que se actualiza sola.
5. **Borrar** el índice de un archivo quita sus mensajes de la búsqueda; el `.mbox` original no se modifica ni se borra.

La indexación corre en segundo plano; no hace falta quedarse en la pantalla, se puede volver más tarde a revisar el estado.
