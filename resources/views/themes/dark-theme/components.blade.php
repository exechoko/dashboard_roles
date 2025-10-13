<style>
    /* Global dark theme styles */
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.1s ease, color 0.1s ease;
    }

    /* Main wrapper */
    .main-wrapper {
        background-color: var(--bg-primary);
    }

    /* Cards */
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

    /* Content area */
    .main-content {
        background-color: var(--bg-primary);
    }

    /* Section headers */
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

    /* Labels generales */
    label {
        color: var(--text-primary, #ffffff) !important;
        font-weight: 500;
    }

    .form-check-label {
        color: var(--text-primary, #ffffff) !important;
    }

    /* Buttons */
    .btn-secondary {
        background-color: var(--bg-tertiary) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    /* Modals */
    .modal-content {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color) !important;
    }

    .modal-header {
        border-bottom: 1px solid var(--border-color) !important;
    }

    .modal-footer {
        border-top: 1px solid var(--border-color) !important;
    }

    /* Alerts */
    .alert {
        border: 1px solid var(--border-color) !important;
    }

    /* Footer */
    .main-footer {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-top: 1px solid var(--border-color) !important;
    }

    /* ========================================
       SIDEBAR Y LOGO - CENTRADO
       ======================================== */
    /* Logo grande (vista normal) */
    .sidebar-brand .navbar-brand-full {
        display: block !important;
        width: 45px !important;
        height: 45px !important;
        object-fit: contain !important;
        margin: 0 auto !important;
    }

    /* ========================================
   NAV-LINK EN SIDEBAR - DARK THEME
   ======================================== */

    /* ========================================
   DARK THEME
   ======================================== */

    [data-theme="dark"] .sidebar-menu .nav-link {
        color: var(--text-primary) !important;
        background-color: transparent !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link:hover {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .sidebar-menu .nav-link.active {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        color: var(--text-primary) !important;
        border-left: 3px solid var(--nav-bg) !important;
        padding-left: calc(1rem - 3px) !important;
    }

    /* Iconos en nav-link - DARK THEME */
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

    /* Dropdown menu - DARK THEME */
    [data-theme="dark"] .sidebar-menu .dropdown-menu {
        background-color: var(--bg-secondary, #2d2d2d) !important;
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

    /* ========================================
   LIST GROUP - LIGHT THEME (Por defecto)
   ======================================== */

    .list-group {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
    }

    .list-group-item {
        background-color: #ffffff !important;
        border-color: #dee2e6 !important;
        color: #333333 !important;
    }

    .list-group-item:hover {
        background-color: #f8f9fa !important;
    }

    .list-group-item.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: #ffffff !important;
    }

    .list-group-item small {
        color: #6c757d !important;
    }

    .list-group-item strong {
        color: #212529 !important;
    }

    /* ========================================
   LIST GROUP - DARK THEME
   ======================================== */

    [data-theme="dark"] .list-group {
        background-color: var(--card-bg, #1e1e1e) !important;
        border: 1px solid var(--border-color, #333333) !important;
    }

    [data-theme="dark"] .list-group-item {
        background-color: var(--card-bg, #1e1e1e) !important;
        border-color: var(--border-color, #333333) !important;
        color: var(--text-primary, #ffffff) !important;
        padding: 0.75rem 1.25rem !important;
        transition: all 0.2s ease !important;
    }

    [data-theme="dark"] .list-group-item:hover {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-color: var(--border-color, #333333) !important;
    }

    [data-theme="dark"] .list-group-item small {
        color: var(--text-secondary, #b3b3b3) !important;
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

    /* ========================================
   FLEXBOX DENTRO DE LIST-GROUP
   ======================================== */

    .list-group-item.d-flex {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
    }

    .list-group-item>div {
        flex: 1 !important;
    }

    [data-theme="dark"] .list-group-item>div {
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
   BOTONES EN LIST-GROUP
   ======================================== */

    .list-group-item .btn {
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

    /* ========================================
   ICONO EN BOTONES
   ======================================== */

    .list-group-item .btn i {
        margin-right: 0.25rem !important;
    }

    [data-theme="dark"] .list-group-item .btn i {
        color: #ffffff !important;
    }

    /* ========================================
   ESPECIAL PARA TU COMPONENTE
   ======================================== */

    [data-theme="dark"] .list-group-item span {
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .list-group-item span strong {
        color: #007bff !important;
        font-weight: 600 !important;
    }

    /* ========================================
   ESTADOS DE LIST-GROUP
   ======================================== */

    [data-theme="dark"] .list-group-item.list-group-item-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
        border-color: #28a745 !important;
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-danger {
        background-color: rgba(220, 53, 69, 0.15) !important;
        border-color: #dc3545 !important;
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        border-color: #ffc107 !important;
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] .list-group-item.list-group-item-info {
        background-color: rgba(23, 162, 184, 0.15) !important;
        border-color: #17a2b8 !important;
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
   RESPONSIVE
   ======================================== */

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
</style>
