<style>
    .badge-alerta-activo { background-color: #16a34a; color: #fff; }
    .badge-alerta-inactivo { background-color: #475569; color: #fff; }
    .badge-alerta-si { background-color: #0d9488; color: #fff; }
    .badge-alerta-no { background-color: #94a3b8; color: #fff; }

    .alerta-stat-card,
    .alerta-stat-card:link,
    .alerta-stat-card:visited {
        display: block;
        border-radius: .5rem;
        color: #fff !important;
        padding: 1rem;
        text-decoration: none;
        border: 3px solid transparent;
        transition: transform .1s ease, border-color .1s ease;
    }
    .alerta-stat-card .small,
    .alerta-stat-card .h3 {
        color: #fff !important;
    }
    a.alerta-stat-card:hover {
        color: #fff !important;
        text-decoration: none;
        transform: translateY(-2px);
    }
    a.alerta-stat-card.active {
        border-color: #1f2937;
        box-shadow: 0 0 0 2px rgba(255,255,255,.6) inset;
    }
    .alerta-stat-card.bg-slate { background: linear-gradient(135deg, #475569, #334155); }
    .alerta-stat-card.bg-green { background: linear-gradient(135deg, #16a34a, #15803d); }
    .alerta-stat-card.bg-red { background: linear-gradient(135deg, #dc2626, #b91c1c); }

    .alerta-timeline {
        position: relative;
        padding-left: 34px;
    }
    .alerta-timeline::before {
        content: '';
        position: absolute;
        left: 14px;
        top: 4px;
        bottom: 4px;
        width: 2px;
        background: linear-gradient(180deg, #16a34a, #4f46e5, #d97706, #dc2626);
        opacity: .25;
        border-radius: 2px;
    }
    .alerta-timeline-item {
        position: relative;
        padding-bottom: 22px;
    }
    .alerta-timeline-item:last-child {
        padding-bottom: 0;
    }
    .alerta-timeline-marker {
        position: absolute;
        left: -34px;
        top: 2px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .8rem;
        box-shadow: 0 0 0 3px #fff, 0 1px 3px rgba(0,0,0,.15);
    }
    .alerta-timeline-content {
        background: #f8fafc;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-left-width: 3px;
        border-left-color: #64748b;
        border-radius: .375rem;
        padding: .6rem .9rem;
    }
    .alerta-timeline-content h6 {
        color: #1e293b;
    }
    .alerta-timeline-content .bg-white {
        background: #fff !important;
        color: #1e293b;
    }
    [data-theme="dark"] .alerta-timeline-content {
        background: var(--bg-secondary) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }
    [data-theme="dark"] .alerta-timeline-content h6 {
        color: var(--text-primary) !important;
    }
    [data-theme="dark"] .alerta-timeline-content .bg-white {
        background: var(--card-bg) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    .alerta-foto-preview {
        width: 100%;
        max-width: 160px;
        border-radius: .375rem;
        border: 1px solid #e2e8f0;
        object-fit: cover;
    }
</style>
