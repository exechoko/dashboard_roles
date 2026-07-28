---
name: documentar-sistema
description: Documenta automáticamente C.A.R. 911 desde rutas, controladores, permisos, vistas y manuales. Usar cuando se solicite actualizar la documentación del sistema o el conocimiento del chatbot.
---

# Documentar C.A.R. 911

Actualiza la documentación segura para usuarios ubicada en `docs/sistema/`.

## Flujo obligatorio

1. Inspecciona `routes/web.php`, los controladores, Form Requests, vistas del menú, modelos y seeders de permisos.
2. Contrasta el comportamiento con los manuales Markdown existentes, sin asumir que estén actualizados.
3. Identifica módulos, tareas habituales, rutas internas, permisos y restricciones visibles para el usuario.
4. Actualiza los documentos por módulo con lenguaje funcional, pasos concretos y enlaces internos relativos.
5. Comprueba las rutas documentadas con `php artisan route:list`.
6. Resume en la respuesta qué documentos cambiaron y qué áreas siguen sin información suficiente.

## Seguridad documental

- No copies secretos, credenciales, tokens, contraseñas, direcciones de servicios internos ni contenido de `.env`.
- No documentes procedimientos que permitan eludir permisos o controles de acceso.
- No incluyas datos personales, expedientes, audios ni registros reales.
- No uses como fuente para el chatbot manuales de infraestructura como `MANUAL_SERVER_IA.md` o `README_NOMINATIM.md`.
- Describe únicamente comportamiento confirmado por código o documentación vigente.
- Señala explícitamente las funciones restringidas por permisos.

## Formato

- Escribe en español.
- Mantén `docs/sistema/README.md` como índice.
- Usa un archivo Markdown por dominio funcional cuando el contenido crezca.
- Usa enlaces internos con el formato `[Abrir pantalla](/ruta)`.
- Evita detalles de implementación que no ayuden al usuario final.
