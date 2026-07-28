# IA, transcripción y base de conocimiento

## Analizar una llamada

1. Abrí [Transcribir audio](/transcribir).
2. Seleccioná o arrastrá un archivo MP3, WAV, M4A u OGG.
3. Reproducí la vista previa para confirmar que sea el audio correcto.
4. Pulsá **Analizar llamada**.
5. Esperá mientras se procesa en segundo plano.
6. Revisá diálogo por hablantes, resumen y datos extraídos.
7. Corregí el diálogo si es necesario y guardá.
8. Exportá a TXT cuando corresponda.

No cierres la pantalla durante la carga inicial. El procesamiento puede demorar según la duración del audio.

## Consultar historial de transcripciones

1. En Transcribir audio, abrí la pestaña **Historial**.
2. Filtrá por nombre de archivo o teléfono cuando dispongas de ese dato.
3. Abrí el registro.
4. Revisá transcripción, resumen y datos asociados.
5. Guardá únicamente correcciones verificadas.

## Base de conocimiento RAG

El panel RAG es administrativo y está separado del asistente global.

1. Abrí [Base de conocimiento](/rag).
2. Creá una temática con nombre y descripción.
3. Seleccioná la temática.
4. Cargá hasta cinco documentos TXT, PDF, CSV o Markdown.
5. Elegí si necesitás resumen con IA.
6. Esperá el estado de carga y revisá el historial documental.
7. Escribí una pregunta sobre la temática seleccionada.
8. Esperá la respuesta asíncrona.

La eliminación de una temática local no necesariamente elimina sus documentos del servidor remoto. La reindexación debe usarse solo cuando se modificó el contenido fuente.

## Diferencia entre RAG y el asistente global

- El RAG permite que administradores carguen y consulten colecciones documentales.
- El asistente global usa la documentación aprobada del sistema para orientar a cualquier usuario autenticado.
- Ninguno de los dos debe recibir contraseñas ni datos sensibles innecesarios.
