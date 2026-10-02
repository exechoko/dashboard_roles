# Validación de la búsqueda recuperable

Estado: prueba funcional en la web local de prueba realizada el 02/10/2026 con Playwright y Chrome, usando una cuenta autorizada por el usuario. Las suites aisladas de PHPUnit y Playwright siguen pendientes.

## Resultado de la prueba funcional

ID interno **3654695**, expediente **4143012**, del **01/09/2026 a las 00:07:35**:

- La búsqueda recuperó **47 modulaciones** y terminó con `completa`; mostró el mensaje correcto, barra al 100 %, listado y filtro habilitados.
- Mientras buscaba o estaba pausada, el listado y el estado vacío permanecieron ocultos y el filtro deshabilitado.
- Cerrar y reabrir recuperó el estado, con máximo **una consulta activa**. Se corrigió el cierre para cancelar consultas y reproducción.
- No se solicitaron audios al listar. La reproducción real devolvió HTTP 200 y generó la marca de escuchado. La descarga real devolvió HTTP 200, `audio/mpeg`, 89 900 bytes.
- Se probó una respuesta simulada de `limite_alcanzado` sobre la pantalla real: aviso persistente al filtrar, barra al 35 % y ausencia del mensaje de búsqueda completa. Esta simulación no demuestra la recuperación real de 2000 elementos.
- La recuperación del snapshot completo tardó 3,749 segundos en la medición registrada; no es una prueba de todos los escenarios de presupuesto.

Expediente **3654695**, ID interno **1058601**, del **09/09/2024 a las 14:00:00**: la prueba real terminó con HTTP 200 y `Búsqueda completa: 0 modulaciones` en 1,789 segundos. El estado vacío apareció únicamente al finalizar, sin reproductores, descargas ni solicitudes de audio. El ejecutor comprueba ahora expediente y fecha visibles para evitar confundirlos con el ID interno de la URL.

El servidor iniciado dentro del sandbox no podía conectarse al grabador. Se reinició, con aprobación, fuera del sandbox y limitado a `127.0.0.1:8000`; después completó la búsqueda. No se modificó el timeout general de streaming.

El ejecutor `probar-web-local.cjs` acepta credenciales de prueba mediante variables temporales de proceso o entrada estándar. No contiene credenciales guardadas y elimina su perfil temporal de Chrome al terminar. Las opciones `--audio` y `--estados` habilitan reproducción/descarga real y la comprobación simulada del límite.

## Entorno desechable

`run-isolated.ps1` prepara una copia sin `.env`, credenciales, uploads, logs ni cachés de producción. Monta el código y las dependencias en solo lectura. El proceso tiene red externa deshabilitada, entorno explícito, usuario sin privilegios, límites de CPU, memoria, procesos, archivos y 90 segundos de duración. Solo `/scratch` permite escrituras (256 MiB). El padre elimina la copia al terminar; no conserva archivos producidos por las pruebas.

Requiere Docker Engine disponible y una imagen Linux **ya instalada**, identificada por su ID `sha256:…`, con PHP 8.1+, extensiones necesarias (incluida PDO SQLite), Node, GNU timeout y Chromium compatible con la versión de Playwright instalada, en `/opt/playwright`. No descarga imágenes ni dependencias. No monta el directorio personal ni el socket de Docker dentro del contenedor.

Desde PowerShell:

```powershell
./tests/modulaciones/run-isolated.ps1 -Image sha256:ID_LOCAL_DE_64_CARACTERES -Suite php
./tests/modulaciones/run-isolated.ps1 -Image sha256:ID_LOCAL_DE_64_CARACTERES -Suite browser
```

`phpunit.modulaciones.xml` fuerza SQLite en memoria, reloj simulado en los tests del servicio, caché temporal y clientes de grabador simulados. La suite incluye las pruebas existentes de emparejamiento y validación de paths. Los tests HTTP usan usuarios y eventos en memoria, un Gate de prueba y CSRF habilitado; no consultan tablas reales. Playwright intercepta todas las peticiones y utiliza el HTML y JavaScript reales del modal, con respuestas y recursos sintéticos.

## Cobertura preparada

- 0, 999, 1000, 1001, 1999, 2000 y 2501 resultados; páginas conservadas, duplicados y reinicio del cursor.
- Timeout, respuesta perdida, revisión repetida, reapertura, cambios de ventana, caducidad, tokens ajenos y lock ocupado.
- Operaciones del grabador separadas, respuestas inválidas y ajuste de timeouts sin modificar reproducción.
- Checkpoints del disco y terminación de un proceso bloqueado.
- Permiso en cada operación, CSRF, períodos manipulados y frecuencia de inicios.
- Modal pausado sin vacío ni listado, advertencia persistente del tope, HTML escapado, audio diferido, botón de reintento, descarga y marcas de escuchado.

## Validaciones que aún faltan para aceptar/desplegar

Ejecutar las suites anteriores y revisar cualquier fallo. Completar la matriz aislada de reproducción/descarga con WAV/MP3 sintéticos; la prueba funcional anterior verificó una reproducción y descarga reales.

Ejecutar `EventoCecocoShowTest` e `InfraestructuraTest` en una base MySQL **desechable**, dentro de una red interna aislada y con límites equivalentes. Estas suites usan `DatabaseTransactions` y precisan un esquema existente. No deben correrse con `phpunit.xml` del proyecto contra la configuración local: contiene migraciones específicas de MySQL y no establece una base separada. Preparar allí el esquema a partir de migraciones y fixtures ficticios, nunca copiar datos de producción.

La skill `.agents/skills/security-audit/SKILL.md` exige todos los controles de aislamiento antes de ejecutar código del proyecto. En esta sesión Docker CLI estaba instalado, pero el motor no estaba disponible. Por eso las suites aisladas siguen sin ejecutarse y no se considera verificada la matriz completa de tiempos/recuperación. La prueba funcional con Playwright sobre la base local de prueba fue autorizada posteriormente por el usuario y está documentada arriba. Backend y vista deben entregarse juntos; no hay migraciones ni cambios al timeout de streaming.
