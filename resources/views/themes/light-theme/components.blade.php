<style>
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.1s ease, color 0.1s ease;
    }

    .main-wrapper {
        background-color: var(--bg-primary);
    }

    .card {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        box-shadow: 0 2px 4px var(--shadow) !important;
    }

    .card-header {
        background-color: var(--bg-secondary) !important;
        border-bottom: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    .card-body {
        color: var(--text-primary) !important;
    }

    .main-content {
        background-color: var(--bg-primary);
    }

    .section-header {
        background-color: var(--bg-primary) !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border-color) !important;
    }

    .section-header h1,
    .section-header h2,
    .section-header h3,
    .section-header h4,
    .section-header h5,
    .section-header h6 {
        color: var(--text-primary) !important;
    }

    .page__heading {
        color: var(--text-primary) !important;
    }

    label {
        color: var(--text-primary) !important;
        font-weight: 500;
    }

    .form-check-label {
        color: var(--text-primary) !important;
    }

    .btn-secondary {
        background-color: var(--bg-tertiary) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    .modal-content {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
    }

    .modal-header {
        background-color: var(--bg-secondary) !important;
        border-bottom: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        padding: 1.5rem;
    }

    .modal-header .modal-title {
        color: var(--text-primary) !important;
        font-weight: 700;
        font-size: 1.25rem;
    }

    .modal-header .close {
        color: var(--text-primary) !important;
        opacity: 0.7;
        text-shadow: none;
        font-size: 1.5rem;
    }

    .modal-header .close:hover,
    .modal-header .close:focus {
        opacity: 1;
        color: var(--text-primary) !important;
        outline: none;
    }

    .modal-body {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        padding: 1.5rem;
        max-height: calc(85vh - 180px);
        overflow-y: auto;
    }

    .modal-footer {
        background-color: var(--bg-secondary) !important;
        border-top: 1px solid var(--border-color) !important;
        padding: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .alert {
        border: 1px solid var(--border-color) !important;
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
    }

    /* Excluir stats-labels de los estilos del tema */
    .stats-labels .alert {
        background-color: initial !important;
        border-color: initial !important;
        color: initial !important;
    }

    /* Mantener colores originales de Bootstrap para stats */
    .stats-labels .alert-dark {
        background-color: #343a40 !important;
        border-color: #343a40 !important;
        color: #fff !important;
    }

    .stats-labels .alert-info {
        background-color: #17a2b8 !important;
        border-color: #17a2b8 !important;
        color: #fff !important;
    }

    .stats-labels .alert-warning {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #212529 !important;
    }

    .stats-labels .alert-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
    }

    .stats-labels .alert-success {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: #fff !important;
    }

    .stats-labels .alert-primary {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    .main-footer {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-top: 1px solid var(--border-color) !important;
    }

    .sidebar-brand .navbar-brand-full {
        display: block !important;
        width: 45px !important;
        height: 45px !important;
        object-fit: contain !important;
        margin: 0 auto !important;
    }

    /* Quitar línea debajo del logo del sidebar en tema claro */
    #sidebar-wrapper .sidebar-brand {
        border-bottom: none !important;
        box-shadow: none !important;
    }

    /* A veces Stisla agrega una línea con pseudo-elemento. */
    #sidebar-wrapper .sidebar-brand::after {
        display: none !important;
        border: none !important;
        content: none !important;
    }

    /* También eliminar posibles bordes globales del contenedor */
    #sidebar-wrapper {
        border-right: none !important;
    }

    .list-group {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
    }

    .list-group-item {
        background-color: #ffffff !important;
        border-color: #dee2e6 !important;
        color: #333333 !important;
        padding: 0.75rem 1.25rem !important;
        transition: all 0.2s ease !important;
    }

    .list-group-item:hover {
        background-color: #f8f9fa !important;
    }

    .list-group-item small {
        color: #6c757d !important;
        display: block !important;
        margin-top: 0.25rem !important;
    }

    .list-group-item strong {
        color: #007bff !important;
        font-weight: 600 !important;
    }

    .list-group-item.active {
        background-color: #007bff !important;
        border-color: #0056b3 !important;
        color: #ffffff !important;
    }

    .list-group-item.active small {
        color: #e7f3ff !important;
    }

    .list-group-item.active strong {
        color: #ffffff !important;
    }

    .list-group-item.d-flex {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
    }

    .list-group-item>div {
        flex: 1 !important;
    }

    .list-group-item .btn {
        flex-shrink: 0 !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
    }

    .list-group-item .btn-danger {
        background-color: #dc3545 !important;
        border-color: #c82333 !important;
        color: #ffffff !important;
    }

    .list-group-item .btn-danger:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
    }

    .list-group-item .btn-info {
        background-color: #17a2b8 !important;
        border-color: #138496 !important;
        color: #ffffff !important;
    }

    .list-group-item .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    .list-group-item .btn-success {
        background-color: #28a745 !important;
        border-color: #1e7e34 !important;
        color: #ffffff !important;
    }

    .list-group-item .btn-success:hover {
        background-color: #1e7e34 !important;
        border-color: #1c7430 !important;
    }

    .list-group-item .btn i {
        margin-right: 0.25rem !important;
        color: #ffffff !important;
    }

    .list-group-item span {
        color: #333333 !important;
    }

    .list-group-item span strong {
        color: #007bff !important;
        font-weight: 600 !important;
    }

    @media (max-width: 768px) {
        .list-group-item {
            padding: 0.5rem 0.75rem !important;
        }

        .list-group-item.d-flex {
            flex-wrap: wrap !important;
        }

        .list-group-item .btn {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
        }
    }

    /* ══════════════════════════════════════════
       SHARED: Card header moderno
    ══════════════════════════════════════════ */
    .card-header-modern {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.1rem 1.5rem 0.8rem;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: .5rem;
    }
    .card-header-left { display: flex; align-items: center; gap: .8rem; }
    .header-icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, #6777ef, #35199a);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem; flex-shrink: 0;
    }
    .header-title { color: var(--text-primary); margin: 0; font-weight: 700; font-size: 1rem; }
    .badge-total {
        background: linear-gradient(135deg, #6777ef, #35199a);
        color: #fff; font-size: .85em;
        border-radius: 20px; padding: .2em .6em;
    }
    .btn-nuevo {
        background: linear-gradient(135deg, #6777ef, #35199a);
        border: none; border-radius: 8px;
        font-size: .875rem; padding: .45rem 1rem; color: #fff;
    }
    .btn-nuevo:hover { opacity: .9; color: #fff; }

    /* ══════════════════════════════════════════
       SHARED: Buscador
    ══════════════════════════════════════════ */
    .search-wrapper {
        display: flex; align-items: center;
        border: 2px solid var(--border-color);
        border-radius: 10px; overflow: hidden;
        background: var(--input-bg);
        transition: border-color .2s;
    }
    .search-wrapper:focus-within { border-color: #6777ef; }
    .search-icon-left { padding: 0 .75rem; color: var(--text-secondary); font-size: .95rem; }
    .search-input {
        border: none !important; box-shadow: none !important;
        flex: 1; padding: .55rem .5rem; font-size: .9rem;
        background: transparent !important; color: var(--text-primary) !important;
    }
    .search-clear { padding: 0 .6rem; color: var(--text-secondary); text-decoration: none; font-size: .85rem; }
    .search-clear:hover { color: #e74c3c; }
    .btn-search {
        background: linear-gradient(135deg, #6777ef, #35199a);
        color: #fff; border: none; border-radius: 0;
        padding: .55rem 1.2rem; font-size: .875rem; white-space: nowrap;
    }
    .btn-search:hover { opacity: .9; color: #fff; }

    /* ══════════════════════════════════════════
       SHARED: Tabla moderna
    ══════════════════════════════════════════ */
    .table-modern {
        border-collapse: separate; border-spacing: 0;
        width: 100%; font-size: .875rem;
    }
    .table-modern thead tr { background: linear-gradient(135deg, #6777ef, #35199a); }
    .table-modern thead th {
        color: #fff; font-weight: 600; padding: .85rem 1rem;
        border: none; white-space: nowrap; letter-spacing: .02em;
    }
    .table-modern thead th:first-child { border-radius: 8px 0 0 0; }
    .table-modern thead th:last-child  { border-radius: 0 8px 0 0; }
    .table-modern tbody tr { border-bottom: 1px solid var(--border-color); transition: background .15s; }
    .table-modern tbody tr:hover { background: var(--bg-secondary); }
    .table-modern tbody td { padding: .75rem 1rem; vertical-align: middle; border: none; color: var(--text-primary); }
    .table-modern tbody tr:last-child td { border-bottom: none; }

    /* TEI badge */
    .tei-badge {
        display: inline-flex; align-items: center;
        background: #2d3748; color: #fff;
        border-radius: 6px; padding: .3rem .65rem;
        font-size: .8rem; font-weight: 600;
        text-decoration: none; white-space: nowrap; transition: background .15s;
    }
    .tei-badge:hover { background: #1a202c; color: #fff; text-decoration: none; }

    /* Modelo cell */
    .modelo-cell { display: flex; align-items: center; gap: .6rem; }
    .modelo-img { border-radius: 6px; border: 1px solid var(--border-color); object-fit: contain; }
    .modelo-text { line-height: 1.3; color: var(--text-primary); }

    /* Badge recurso con colores por tipo de vehículo */
    .badge-recurso {
        display: inline-flex; align-items: center;
        border-radius: 20px; padding: .25em .75em;
        font-size: .8rem; font-weight: 600;
        white-space: nowrap; align-self: flex-start; color: #fff;
        background: linear-gradient(135deg, #6c757d, #495057);
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
    }
    .recurso-auto        { background: linear-gradient(135deg, #3a7bd5, #00d2ff) !important; box-shadow: 0 2px 6px rgba(58,123,213,.3) !important; }
    .recurso-camioneta   { background: linear-gradient(135deg, #f7971e, #ffd200) !important; color: #3d2c00 !important; box-shadow: 0 2px 6px rgba(247,151,30,.3) !important; }
    .recurso-camion      { background: linear-gradient(135deg, #c0392b, #e74c3c) !important; box-shadow: 0 2px 6px rgba(231,76,60,.3) !important; }
    .recurso-moto        { background: linear-gradient(135deg, #6a11cb, #a855f7) !important; box-shadow: 0 2px 6px rgba(106,17,203,.3) !important; }
    .recurso-helicoptero { background: linear-gradient(135deg, #11998e, #38ef7d) !important; box-shadow: 0 2px 6px rgba(17,153,142,.25) !important; }
    .recurso-sin-vehiculo { background: linear-gradient(135deg, #5c6672, #8d99a6) !important; box-shadow: 0 2px 6px rgba(0,0,0,.15) !important; }
    .recurso-cell { display: flex; flex-direction: column; gap: .3rem; }
    .recurso-veh-info { display: flex; flex-direction: column; gap: .1rem; padding-left: .2rem; }
    .recurso-veh-detalle { font-size: .78rem; color: var(--text-primary); font-weight: 500; }
    .recurso-veh-dominio { font-size: .75rem; color: var(--text-secondary); }

    /* Dependencia cell */
    .dep-cell { font-size: .78rem; }
    .dep-nombre { display: block; font-weight: 500; color: var(--text-primary); }
    .dep-padre  { display: block; color: var(--text-secondary); font-size: .72rem; }

    /* Observaciones cell */
    .obs-cell { max-width: 180px; }
    .obs-text {
        display: block; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
        max-width: 170px; cursor: default;
        color: var(--text-secondary); font-size: .78rem;
    }

    /* Botones de acción */
    .action-td { white-space: nowrap; }
    .action-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 7px;
        font-size: .85rem; margin: 0 2px; cursor: pointer;
        text-decoration: none; transition: transform .1s, opacity .15s;
    }
    .action-btn:hover { transform: scale(1.1); opacity: .85; text-decoration: none; }
    .btn-view { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    .btn-edit { background: #d1f2eb; color: #0e6655; border: 1px solid #28b463; }
    .btn-del  { background: #fde8e8; color: #922b21; border: 1px solid #e74c3c; }

    /* Estado badge */
    .estado-badge {
        display: inline-block; border-radius: 20px;
        padding: .2em .65em; font-size: .78rem; font-weight: 600; color: #fff;
        background: #6c757d;
    }
</style>
