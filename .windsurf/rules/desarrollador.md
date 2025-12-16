---
trigger: manual
---

# Reglas de Proyecto Laravel – Windsurf

## 1. Principios Generales

1. **Usa las mejores prácticas de programación**
   - Código limpio, PSR-12, desacoplado y siguiendo SOLID.
   - Optimización de queries, Jobs, caché y memoria.
   - Seguridad garantizada: validaciones, CSRF, XSS, SQL Injection.

2. **Siempre haz un plan antes de ejecutar**
   - Analiza requisitos, memorias y documentación.
   - Define arquitectura, estructura de carpetas, servicios y rutas.
   - Registra decisiones importantes en la memoria de Windsurf.

3. **Usa comentarios breves y claros**
   - Explica lo que hace cada función o método.
   - Evita comentarios redundantes o triviales; solo lo necesario para entender la lógica.

4. **Si no sabes algo, dilo**
   - Nunca inventes respuestas.
   - Indica claramente la incertidumbre o la necesidad de revisar documentación o memorias.

---

## 2. Arquitectura y Flujo de Trabajo

- Seguir MVC, Eloquent, Service Container, Jobs/Queues.
- Aplicar DI y patrones definidos en `/docs/technical/patterns.md`.
- Ciclo de trabajo: Analizar → Planificar → Aprobar → Ejecutar → Verificar → Informar.

## 3. Código y Estándares

- PSR-12, `laravel Pint`.
- Validaciones con Requests.
- Manejo de errores con `try/catch` y logging (`Log`, Sentry, Bugsnag).
- Uso de Resources/Transformers para API JSON.
- Evitar código duplicado y mantener funciones/métodos concisos.
- Optimizar rendimiento: queries, loops y Jobs en background.

## 4. Memoria de Windsurf

- Registrar patrones, decisiones y snippets:
  - `pattern:<area>:<name>`
  - `decision:<scope>:<description>`
  - `snippet:<purpose>:<name>`
- Consultar memorias antes de implementar para reutilizar soluciones existentes.
- Actualizar memorias tras cambios relevantes.

## 5. Checklist Rápido

- [ ] Código limpio, PSR-12 y lint pasado.
- [ ] Plan definido y aprobado antes de ejecutar.
- [ ] Comentarios breves explicando funciones.
- [ ] Seguridad aplicada y queries optimizadas.
- [ ] Decisiones y patrones registrados en memoria.
- [ ] Indicar claramente dudas o desconocimiento.
