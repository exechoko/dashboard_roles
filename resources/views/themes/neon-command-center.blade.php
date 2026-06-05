<style>
    body {
        background:
            radial-gradient(circle at top right, var(--surface-glow), transparent 34rem),
            linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
        font-family: 'Lato', sans-serif;
    }

    .main-wrapper,
    .main-content {
        background: transparent !important;
    }

    .navbar-bg,
    .main-navbar {
        background:
            linear-gradient(90deg, var(--nav-bg), rgba(0, 153, 255, 0.18), var(--nav-bg)) !important;
        border-bottom: 1px solid var(--border-color) !important;
        box-shadow: 0 10px 32px var(--shadow) !important;
    }

    .main-sidebar,
    #sidebar-wrapper {
        background:
            linear-gradient(180deg, var(--sidebar-bg), var(--bg-secondary)) !important;
        border-right: 1px solid var(--border-color) !important;
        box-shadow: 10px 0 34px rgba(0, 0, 0, 0.08) !important;
    }

    .sidebar-menu li {
        border-bottom: 0 !important;
    }

    .sidebar-menu li a,
    .sidebar-menu .nav-link {
        border-radius: 12px !important;
        margin: 3px 12px !important;
        transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease !important;
    }

    .sidebar-menu li a:hover,
    .sidebar-menu .nav-link:hover,
    .sidebar-menu li.active > a,
    .sidebar-menu li.menu-open > a,
    .sidebar-menu .nav-link.active {
        background: var(--sidebar-active-bg) !important;
        color: var(--accent-primary) !important;
        box-shadow: inset 3px 0 0 var(--accent-primary), 0 0 18px var(--neon-primary) !important;
    }

    .sidebar-menu li a:hover i,
    .sidebar-menu li.active > a i,
    .sidebar-menu .nav-link:hover i,
    .sidebar-menu .nav-link.active i {
        color: var(--accent-primary) !important;
        text-shadow: 0 0 12px var(--neon-primary) !important;
    }

    .card,
    .modal-content,
    .section-header,
    .table-responsive,
    .small-box,
    .timeline-content,
    .list-group {
        border: 1px solid var(--border-color) !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 34px var(--shadow) !important;
        backdrop-filter: blur(10px);
    }

    .card {
        overflow: hidden;
    }

    .card-header,
    .modal-header {
        background:
            linear-gradient(90deg, var(--bg-secondary), var(--bg-tertiary)) !important;
        border-bottom: 1px solid var(--border-color) !important;
    }

    .card-header h4,
    .section-header h1,
    .page__heading {
        color: var(--text-primary) !important;
        letter-spacing: 0.01em;
    }

    .section-header {
        background:
            linear-gradient(135deg, var(--card-bg), var(--bg-tertiary)) !important;
        margin-bottom: 1.5rem !important;
    }

    .btn-primary,
    .badge-primary,
    .page-item.active .page-link {
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)) !important;
        border-color: transparent !important;
        color: #ffffff !important;
        box-shadow: 0 0 18px var(--neon-primary) !important;
    }

    .btn-success,
    .badge-success {
        background: linear-gradient(135deg, var(--accent-success), #00c2a8) !important;
        border-color: transparent !important;
    }

    .btn-danger,
    .badge-danger {
        background: linear-gradient(135deg, var(--accent-danger), #ff6b3d) !important;
        border-color: transparent !important;
    }

    .btn-info,
    .badge-info {
        background: linear-gradient(135deg, var(--accent-info), var(--accent-primary)) !important;
        border-color: transparent !important;
    }

    .btn-warning,
    .badge-warning {
        background: linear-gradient(135deg, var(--accent-warning), #ffd166) !important;
        border-color: transparent !important;
    }

    .btn-outline-primary {
        border-color: var(--accent-primary) !important;
        color: var(--accent-primary) !important;
    }

    .btn-outline-primary:hover {
        background-color: var(--accent-primary) !important;
        color: #ffffff !important;
        box-shadow: 0 0 18px var(--neon-primary) !important;
    }

    .form-control,
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple,
    .input-group-text {
        border-radius: 10px !important;
    }

    .form-control:focus,
    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default .select2-selection--single:focus {
        border-color: var(--accent-primary) !important;
        box-shadow: 0 0 0 0.18rem var(--neon-primary) !important;
    }

    .table thead,
    table.dataTable thead {
        background: linear-gradient(90deg, var(--bg-tertiary), var(--bg-secondary)) !important;
    }

    .table thead tr,
    table.dataTable thead tr {
        background: transparent !important;
    }

    .table thead th,
    table.dataTable thead th,
    table.dataTable thead td {
        background: transparent !important;
        color: var(--text-primary) !important;
        border-top: 0 !important;
        border-left: 0 !important;
        border-right: 0 !important;
        border-bottom: 1px solid var(--border-color) !important;
        text-transform: uppercase;
        letter-spacing: 0.045em;
        font-size: 0.74rem;
    }

    .table thead th:first-child {
        border-top-left-radius: 12px !important;
    }

    .table thead th:last-child {
        border-top-right-radius: 12px !important;
    }

    .table tbody tr:hover {
        background-color: var(--surface-glow) !important;
    }

    [data-theme="dark"] .table tbody tr.table-success td,
    [data-theme="dark"] .table tbody tr.table-warning td,
    [data-theme="dark"] .table tbody tr.table-danger td,
    [data-theme="dark"] .table tbody tr.table-info td,
    [data-theme="dark"] .table tbody tr.table-success td small,
    [data-theme="dark"] .table tbody tr.table-warning td small,
    [data-theme="dark"] .table tbody tr.table-danger td small,
    [data-theme="dark"] .table tbody tr.table-info td small,
    [data-theme="dark"] .table tbody tr.table-success td .text-muted,
    [data-theme="dark"] .table tbody tr.table-warning td .text-muted,
    [data-theme="dark"] .table tbody tr.table-danger td .text-muted,
    [data-theme="dark"] .table tbody tr.table-info td .text-muted {
        color: #102033 !important;
    }

    .table strong,
    .breadcrumb-item a,
    a:not(.btn):not(.nav-link):not(.dropdown-item):not(.banner-efemeride) {
        color: var(--accent-primary) !important;
    }

    .banner-efemeride {
        background: rgba(255, 255, 255, 0.92) !important;
        border-color: rgba(0, 153, 255, 0.3) !important;
        color: #102033 !important;
        box-shadow: 0 0 18px rgba(0, 153, 255, 0.18) !important;
    }

    .banner-efemeride:hover,
    .banner-efemeride:focus {
        background: #ffffff !important;
        color: #06101f !important;
        box-shadow: 0 0 22px rgba(0, 153, 255, 0.28) !important;
    }

    .banner-efemeride__anio {
        color: var(--accent-primary) !important;
    }

    .banner-efemeride__texto {
        color: inherit !important;
        opacity: 1 !important;
    }

    .dropdown-menu,
    .select2-dropdown,
    .daterangepicker {
        border-radius: 14px !important;
        box-shadow: 0 18px 38px var(--shadow) !important;
    }

    .alert {
        border-radius: 14px !important;
        box-shadow: 0 10px 24px var(--shadow) !important;
    }

    .main-footer {
        background: transparent !important;
        border-top: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .main-sidebar,
    [data-theme="dark"] #sidebar-wrapper {
        box-shadow: 10px 0 40px rgba(0, 229, 255, 0.08) !important;
    }

    [data-theme="dark"] .card,
    [data-theme="dark"] .modal-content,
    [data-theme="dark"] .section-header,
    [data-theme="dark"] .table-responsive,
    [data-theme="dark"] .small-box,
    [data-theme="dark"] .timeline-content,
    [data-theme="dark"] .list-group {
        box-shadow: 0 14px 38px rgba(0, 229, 255, 0.08) !important;
    }

    [data-theme="dark"] .banner-efemeride {
        background: rgba(0, 229, 255, 0.1) !important;
        border-color: rgba(0, 229, 255, 0.32) !important;
        color: #eaf6ff !important;
        box-shadow: 0 0 18px rgba(0, 229, 255, 0.18) !important;
    }

    [data-theme="dark"] .banner-efemeride:hover,
    [data-theme="dark"] .banner-efemeride:focus {
        background: rgba(0, 229, 255, 0.16) !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .table .badge-success,
    [data-theme="dark"] .table .badge-info,
    [data-theme="dark"] .table .badge-danger,
    [data-theme="dark"] .table .badge-secondary,
    [data-theme="dark"] .table .badge-primary,
    [data-theme="dark"] .table .badge-dark {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    [data-theme="dark"] .table .badge-warning {
        color: #06101f !important;
        text-shadow: none !important;
    }

    .modal.show,
    [data-theme="dark"] .modal.show {
        display: block !important;
        align-items: initial !important;
        overflow-y: auto !important;
    }

    .modal-dialog,
    [data-theme="dark"] .modal-dialog {
        margin: 1.75rem auto !important;
        max-height: none !important;
    }

    .modal-content,
    [data-theme="dark"] .modal-content {
        max-height: none !important;
        overflow: visible !important;
    }

    .modal-body,
    [data-theme="dark"] .modal-body {
        max-height: calc(100vh - 12rem) !important;
        overflow: auto !important;
    }

    .modal .table-responsive,
    [data-theme="dark"] .modal .table-responsive {
        max-width: 100% !important;
        overflow-x: auto !important;
        box-shadow: none !important;
    }

    .modal .table,
    [data-theme="dark"] .modal .table {
        min-width: max-content !important;
    }
</style>
