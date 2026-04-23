<style>
.drop-zone {
    border: 2px dashed #adb5bd;
    border-radius: 8px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    position: relative;
}
.drop-zone:hover,
.drop-zone--over {
    border-color: #007bff;
    background: #f0f7ff;
}
.drop-zone-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

/* ── Visor MD / DOCX ── */
#visor-md,
#visor-docx {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    background: #fff;
    color: #212529;
}
#visor-md h1, #visor-md h2, #visor-md h3,
#visor-docx h1, #visor-docx h2, #visor-docx h3 {
    margin-top: 1.2rem;
    margin-bottom: 0.5rem;
    color: inherit;
}
#visor-md p, #visor-md li, #visor-md span,
#visor-docx p, #visor-docx li, #visor-docx span {
    color: inherit;
}
#visor-md pre, #visor-docx pre {
    background: #f4f4f4;
    color: #212529;
    padding: 1rem;
    border-radius: 4px;
    overflow-x: auto;
}
#visor-md code {
    background: #f4f4f4;
    color: #c7254e;
    padding: 0.1em 0.3em;
    border-radius: 3px;
}
#visor-md table, #visor-docx table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 1rem;
}
#visor-md th, #visor-md td,
#visor-docx th, #visor-docx td {
    border: 1px solid #dee2e6;
    padding: 0.4rem 0.8rem;
    color: inherit;
}
#visor-md thead th, #visor-docx thead th {
    background: #f8f9fa;
}
#visor-md blockquote {
    border-left: 4px solid #dee2e6;
    padding-left: 1rem;
    color: #6c757d;
    margin: 1rem 0;
}

/* ── Tema oscuro ── */
[data-theme="dark"] .drop-zone {
    border-color: var(--border-color);
    background: var(--bg-secondary);
}
[data-theme="dark"] .drop-zone:hover,
[data-theme="dark"] .drop-zone--over {
    border-color: #007bff;
    background: #1a2a3a;
}
[data-theme="dark"] #visor-md,
[data-theme="dark"] #visor-docx {
    background: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
}
[data-theme="dark"] #visor-md pre,
[data-theme="dark"] #visor-docx pre {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color);
}
[data-theme="dark"] #visor-md code {
    background: var(--bg-tertiary) !important;
    color: #f08080 !important;
}
[data-theme="dark"] #visor-md th,
[data-theme="dark"] #visor-md td,
[data-theme="dark"] #visor-docx th,
[data-theme="dark"] #visor-docx td {
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}
[data-theme="dark"] #visor-md thead th,
[data-theme="dark"] #visor-docx thead th {
    background: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}
[data-theme="dark"] #visor-md blockquote {
    border-left-color: var(--border-color);
    color: var(--text-secondary) !important;
}
[data-theme="dark"] #visor-fallback {
    background: var(--bg-secondary);
}
[data-theme="dark"] .modal-content {
    background-color: var(--card-bg) !important;
    color: var(--text-primary) !important;
}
[data-theme="dark"] .modal-header {
    border-bottom-color: var(--border-color) !important;
    background-color: var(--bg-secondary) !important;
}
[data-theme="dark"] .modal-title,
[data-theme="dark"] .modal-header span {
    color: var(--text-primary) !important;
}
</style>
