# CECOCO, eventos, expedientes y recorridos

## Buscar eventos

1. Abrí [Analizador de eventos CECOCO](/cecoco).
2. Aplicá al menos un filtro: texto, año/mes, operador, tipificación o rango de fecha y hora.
3. Pulsá **Buscar**.
4. Revisá los resultados ordenados desde el más reciente.
5. Abrí **Ver detalle** en el evento correspondiente.

Los filtros se conservan al regresar al listado. La exportación TXT usa los mismos criterios de búsqueda.

## Consultar un expediente

Disponible con permiso `ver-expediente-cecoco`.

1. Buscá y abrí el evento.
2. Pulsá **Ver expediente**.
3. Revisá el detalle almacenado localmente.
4. Usá **Actualizar desde CECOCO** solamente si necesitás refrescar la información desde el sistema fuente.
5. Si el resumen IA está habilitado, esperá a que pase de pendiente o procesando a completado.

## Escuchar grabaciones telefónicas

1. Abrí el detalle del evento.
2. Ingresá a Grabaciones si contás con el permiso correspondiente.
3. Revisá la fecha y el contexto antes de reproducir.
4. Usá los controles de audio del visor.

No compartas ni descargues material fuera de los procedimientos autorizados.

## Escuchar modulaciones de radio

Las modulaciones son las comunicaciones de radio TETRA, distintas de las grabaciones
telefónicas. Se listan desde el grabador, que es la fuente autoritativa: devuelve una
fila por modulación real.

Disponible con permiso `escuchar-modulaciones-cecoco`.

1. Abrí el detalle del evento.
2. Pulsá **Modulaciones**.
3. Esperá a que termine la búsqueda: muestra el avance y puede tardar varios segundos.
4. Reproducí con los controles de cada tarjeta o descargá con el botón de descarga.

### Qué ventana de tiempo busca

La ventana se calcula sola a partir del evento y se muestra arriba de la lista:

- **Desde:** 10 minutos antes de la fecha y hora del evento. Ese margen sirve para
  alcanzar las modulaciones previas al alta del evento, cuando el hecho ya se estaba
  comunicando por radio.
- **Hasta:** la fecha de cierre del evento.
- **Si el evento no tiene fecha de cierre:** hasta 60 minutos después del inicio.

No hay filtro por canal: trae **todo** el tráfico de radio de esa ventana, no solamente
el de los recursos asignados al evento. Por eso una ventana de varias horas puede tener
cientos de modulaciones. El tope es de 1000 por búsqueda.

### Cómo leer la lista

- **Resaltadas en verde:** modulaciones de recursos que intervinieron en el evento. Son
  las que normalmente interesan primero.
- **Atenuadas:** las que ya escuchaste. Se guardan en tu navegador y por evento, así que
  no las ven los demás usuarios ni se conservan si cambiás de equipo.
- **Título de cada tarjeta:** quién moduló y, con una flecha, a quién o a qué grupo.
- **Etiquetas:** tipo de comunicación, símplex o dúplex, y cuántos operadores de CECOCO
  la registraron.
- **Buscador:** filtra por recurso, SSI, canal u hora (por ejemplo `M2231216`, `Cria 904`
  o `06:16`).

### De dónde sale el audio

Cada modulación se sirve del backup local de audios si existe la copia de ese día; si no,
se obtiene del grabador. Al descargar se entrega en MP3.

Puede aparecer **Audio no disponible** en una modulación que ningún operador escuchó y que
además no se puede traer del grabador en ese momento. El listado igual se muestra completo:
sirve para reconstruir quién moduló y cuándo, aunque el audio no esté.

No compartas ni descargues material fuera de los procedimientos autorizados.

## Importar eventos

Disponible con permiso `importar-eventos`.

1. Desde CECOCO, abrí la opción de importación.
2. Seleccioná uno o varios archivos XLS, XLSX o XML.
3. Verificá que cada archivo corresponda al formato esperado.
4. Enviá la importación.
5. Revisá el historial de procesamiento: pendiente, completado, duplicado, omitido o fallido.

Los archivos se procesan en segundo plano y pueden demorar.

## Analítica de eventos

1. Abrí [Analítica CECOCO](/cecoco/analitica).
2. Elegí rango de fechas y tipificaciones.
3. Seleccioná comparación semanal, mensual o anual.
4. Consultá total, promedio diario, distribución y tendencias.
5. Desde los resultados podés volver al analizador con filtros equivalentes.

## Mapa de calor

1. Abrí [Mapa de calor CECOCO](/cecoco/mapa-calor).
2. Definí fecha desde y hasta.
3. Elegí tipificación si necesitás limitar el conjunto.
4. Consultá el mapa.
5. Si una dirección no fue localizada y tenés autorización, corregí el texto o indicá manualmente la ubicación.

## Mapas de situación

- [Mapa CECOCO](/indexMapaCecocoEnVivo) muestra móviles y eventos en vivo sobre el mapa.
- [Mapa GIS CECOCO](/cecoco/mapa-gis) muestra la misma situación tomada de la fuente GIS, con el posicionamiento que reporta ese sistema.
- [GIS histórico CECOCO](/cecoco/mapa-gis-historico) permite revisar posiciones ya registradas en lugar de la situación en vivo.

Son pantallas de consulta y no guardan información.

## Listado de eventos

1. Abrí [Eventos](/get-eventos).
2. Consultá los eventos traídos de CECOCO.

Sirve para la consulta directa cuando no alcanza con los filtros del analizador.

## Histórico móvil desde Excel

1. Abrí [Histórico móvil](/cecoco/historico-movil).
2. Cargá un archivo XLS o XLSX.
3. Definí velocidad máxima y umbrales de detención.
4. Procesá el archivo.
5. Revisá recorrido, animación, detenciones y excesos.
6. Exportá el resultado si corresponde.

## Histórico móvil desde GIS

1. Abrí [Histórico móvil GIS](/cecoco/historico-movil-gis).
2. Indicá fecha y hora inicial y final.
3. Buscá y validá el recurso.
4. Ejecutá la consulta.
5. Revisá recorrido, velocidades y detenciones.
6. Guardá o exportá cuando corresponda.

## Alias de recursos

1. Abrí [Alias de recursos CECOCO](/cecoco/recursos-alias).
2. Buscá por alias y filtrá activos o inactivos.
3. Para crear, ingresá el alias y vinculalo con un recurso o equipo interno.
4. Definí estado y observaciones.
5. Guardá. El alias debe ser único.
