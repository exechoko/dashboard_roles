<style>
    [data-theme="dark"] body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.1s ease, color 0.1s ease;
    }

    [data-theme="dark"] .main-wrapper {
        background-color: var(--bg-primary);
    }

    [data-theme="dark"] .card {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        box-shadow: 0 2px 4px var(--shadow) !important;
    }

    [data-theme="dark"] .card-header {
        background-color: var(--bg-secondary) !important;
        border-bottom: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .card-body {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .main-content {
        background-color: var(--bg-primary);
    }

    [data-theme="dark"] .section-header {
        background-color: var(--bg-primary) !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .section-header h1,
    [data-theme="dark"] .section-header h2,
    [data-theme="dark"] .section-header h3,
    [data-theme="dark"] .section-header h4,
    [data-theme="dark"] .section-header h5,
    [data-theme="dark"] .section-header h6 {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .page__heading {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] label {
        color: var(--text-primary) !important;
        font-weight: 500;
    }

    [data-theme="dark"] .form-check-label {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .btn-secondary {
        background-color: var(--bg-tertiary) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .modal-content {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.7) !important;
    }

    [data-theme="dark"] .modal-header {
        background-color: var(--bg-secondary) !important;
        border-bottom: 2px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        padding: 1.5rem;
    }

    [data-theme="dark"] .modal-header .modal-title {
        color: var(--text-primary) !important;
        font-weight: 700;
        font-size: 1.25rem;
    }

    [data-theme="dark"] .modal-header .close {
        color: var(--text-primary) !important;
        opacity: 0.7;
        text-shadow: none;
        font-size: 1.5rem;
    }

    [data-theme="dark"] .modal-header .close:hover,
    [data-theme="dark"] .modal-header .close:focus {
        opacity: 1;
        color: var(--text-primary) !important;
        outline: none;
    }

    [data-theme="dark"] .modal-body {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        padding: 1.5rem;
        max-height: calc(85vh - 180px);
        overflow-y: auto;
    }

    [data-theme="dark"] .modal-dialog {
        max-height: 90vh !important;
    }

    [data-theme="dark"] .modal.show {
        overflow-y: auto !important;
        display: flex !important;
        align-items: center !important;
    }

    [data-theme="dark"] .modal-dialog {
        margin-top: 50px !important;
    }

    [data-theme="dark"] .modal-footer {
        background-color: var(--bg-secondary) !important;
        border-top: 2px solid var(--border-color) !important;
        padding: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    [data-theme="dark"] .alert {
        border: 1px solid var(--border-color) !important;
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

    [data-theme="dark"] .main-footer {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-top: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .sidebar-brand .navbar-brand-full {
        display: block !important;
        width: 45px !important;
        height: 45px !important;
        object-fit: contain !important;
        margin: 0 auto !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link {
        color: var(--text-primary) !important;
        background-color: transparent !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link:hover {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link.active {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-left: 3px solid var(--nav-bg) !important;
        padding-left: calc(1rem - 3px) !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link i {
        color: var(--text-primary) !important;
        font-size: 1rem !important;
        min-width: 20px !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link:hover i {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link span {
        color: var(--text-primary) !important;
        font-weight: 500 !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu {
        background-color: var(--bg-secondary) !important;
        border: none !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu .nav-link {
        padding-left: 2rem !important;
        color: #cccccc !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu .nav-link:hover {
        background-color: rgba(0, 123, 255, 0.432) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu .nav-link.active {
        color: var(--nav-bg) !important;
        border-left: none !important;
        padding-left: 2rem !important;
    }

    /* Quitar línea debajo del logo del sidebar en tema oscuro */
    [data-theme="dark"] #sidebar-wrapper .sidebar-brand {
        border-bottom: none !important;
        box-shadow: none !important;
    }

    /* A veces Stisla agrega una línea con pseudo-elemento */
    [data-theme="dark"] #sidebar-wrapper .sidebar-brand::after {
        display: none !important;
        border: none !important;
        content: none !important;
    }

    /* También eliminar posibles bordes globales del contenedor */
    [data-theme="dark"] #sidebar-wrapper {
        border-right: none !important;
    }

    [data-theme="dark"] .list-group {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .list-group-item {
        background-color: var(--card-bg) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
        padding: 0.75rem 1.25rem !important;
        transition: all 0.2s ease !important;
    }

    [data-theme="dark"] .list-group-item:hover {
        background-color: var(--bg-secondary) !important;
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .list-group-item small {
        color: var(--text-secondary) !important;
        display: block !important;
        margin-top: 0.25rem !important;
    }

    [data-theme="dark"] .list-group-item strong {
        color: #007bff !important;
        font-weight: 600 !important;
    }

    [data-theme="dark"] .list-group-item.active {
        background-color: #007bff !important;
        border-color: #0056b3 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item.active small {
        color: #e7f3ff !important;
    }

    [data-theme="dark"] .list-group-item.active strong {
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item.d-flex {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
    }

    [data-theme="dark"] .list-group-item>div {
        flex: 1 !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .list-group-item .btn {
        flex-shrink: 0 !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
    }

    [data-theme="dark"] .list-group-item .btn-danger {
        background-color: #dc3545 !important;
        border-color: #c82333 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item .btn-danger:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
    }

    [data-theme="dark"] .list-group-item .btn-info {
        background-color: #17a2b8 !important;
        border-color: #138496 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    [data-theme="dark"] .list-group-item .btn-success {
        background-color: #28a745 !important;
        border-color: #1e7e34 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item .btn-success:hover {
        background-color: #1e7e34 !important;
        border-color: #1c7430 !important;
    }

    [data-theme="dark"] .list-group-item .btn i {
        margin-right: 0.25rem !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .list-group-item span {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .list-group-item span strong {
        color: #007bff !important;
        font-weight: 600 !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
        border-color: #28a745 !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-danger {
        background-color: rgba(220, 53, 69, 0.15) !important;
        border-color: #dc3545 !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        border-color: #ffc107 !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-info {
        background-color: rgba(23, 162, 184, 0.15) !important;
        border-color: #17a2b8 !important;
        color: var(--text-primary) !important;
    }

    /* Menú contextual del Geocoder en modo oscuro */
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.7) !important;
    }

    /* Cada elemento de la lista */
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar li {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border-color) !important;
        padding: 6px 10px !important;
        transition: background-color 0.2s ease, color 0.2s ease !important;
    }

    /* Hover (al pasar el mouse sobre una sugerencia) */
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar li:hover {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
    }

    /* Asegurar texto legible dentro de <a> o <span> si los hubiera */
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar li a,
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar li span {
        color: var(--text-primary) !important;
    }

    /* Bordes redondeados y sombra del contenedor */
    [data-theme="dark"] .geocoder-control-suggestions.leaflet-bar {
        border-radius: 6px !important;
        overflow: hidden !important;
    }

    @media (max-width: 768px) {
        [data-theme="dark"] .list-group-item {
            padding: 0.5rem 0.75rem !important;
        }

        [data-theme="dark"] .list-group-item.d-flex {
            flex-wrap: wrap !important;
        }

        [data-theme="dark"] .list-group-item .btn {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
        }
    }
</style>
