<style>
    /* Card "Estado de procesos y Base de datos" */
    .estado-procesos-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .estado-procesos-header {
        background: linear-gradient(135deg, #1a3a2a, #166534);
        color: #fff;
    }

    .estado-procesos-grid {
        gap: 1.5rem;
    }

    /* Bloque que envuelve un grupo (Geocodificación, Tamaño BD, etc.) */
    .estado-procesos-bloque {
        padding-left: 0.75rem;
        margin-left: 0.25rem;
        border-left: 1px solid #dee2e6;
    }

    .estado-procesos-titulo {
        color: #6c757d;
    }

    .restauraciones-icono {
        font-size: 1rem;
        line-height: 1;
    }

    .inventario-conflictos-btn:not(:disabled) {
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.12);
    }

    .conflicto-funcionario {
        padding: 0.4rem 0.55rem;
        margin-bottom: 0.35rem;
        border-left: 3px solid #ef4444;
        background: #f8fafc;
        border-radius: 0 4px 4px 0;
    }

    [data-theme="dark"] .conflicto-funcionario {
        color: #e2e8f0;
        background: #1e293b;
    }

    /* ── Botón refresh restauraciones: animaciones por estado ───────────── */
    .btn-refresh-restauraciones {
        transition: background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease, transform 0.15s ease;
    }
    .btn-refresh-restauraciones.estado-consultando {
        color: #fff;
        background-color: #3b82f6;
        border-color: #3b82f6;
        animation: refresh-halo-azul 1.4s ease-in-out infinite;
    }
    .btn-refresh-restauraciones.estado-success {
        color: #fff;
        background-color: #22c55e;
        border-color: #22c55e;
        animation: refresh-flash-verde 1.5s ease-out 1;
    }
    .btn-refresh-restauraciones.estado-error {
        color: #fff;
        background-color: #ef4444;
        border-color: #ef4444;
        animation: refresh-shake-rojo 0.5s cubic-bezier(.36,.07,.19,.97) 1;
    }
    @keyframes refresh-halo-azul {
        0%   { box-shadow: 0 0 0 0   rgba(59,130,246,0.6); }
        70%  { box-shadow: 0 0 0 8px rgba(59,130,246,0);   }
        100% { box-shadow: 0 0 0 0   rgba(59,130,246,0);   }
    }
    @keyframes refresh-flash-verde {
        0%   { box-shadow: 0 0 0 0   rgba(34,197,94,0.7); transform: scale(1); }
        40%  { box-shadow: 0 0 0 12px rgba(34,197,94,0);  transform: scale(1.08); }
        100% { box-shadow: 0 0 0 0   rgba(34,197,94,0);   transform: scale(1); }
    }
    @keyframes refresh-shake-rojo {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-3px); }
        40%, 60% { transform: translateX(3px); }
    }
    /* Respeta a usuarios con prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .btn-refresh-restauraciones.estado-consultando,
        .btn-refresh-restauraciones.estado-success,
        .btn-refresh-restauraciones.estado-error {
            animation: none;
        }
    }

    /* Dark mode */
    [data-theme="dark"] .estado-procesos-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .estado-procesos-card .card-body {
        color: #e2e8f0;
    }

    [data-theme="dark"] .estado-procesos-bloque {
        border-left-color: rgba(255, 255, 255, 0.15);
    }

    [data-theme="dark"] .estado-procesos-titulo {
        color: #cbd5e1;
    }

    [data-theme="dark"] .estado-procesos-card .badge.badge-secondary {
        background-color: #475569;
        color: #e2e8f0;
    }

    /* Mobile: bloques apilados sin border-left a la izquierda (queda mal en una sola columna) */
    @media (max-width: 767px) {
        .estado-procesos-grid {
            gap: 0.85rem;
        }
        .estado-procesos-bloque {
            border-left: none;
            border-top: 1px solid #dee2e6;
            padding-left: 0;
            padding-top: 0.5rem;
            margin-left: 0;
            width: 100%;
        }
        [data-theme="dark"] .estado-procesos-bloque {
            border-top-color: rgba(255, 255, 255, 0.15);
        }
    }
</style>
