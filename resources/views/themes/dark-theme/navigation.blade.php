<style>
    [data-theme="dark"] .main-navbar {
        background-color: var(--nav-bg) !important;
        border-bottom: 1px solid var(--border-color);
    }

    [data-theme="dark"] .navbar-bg {
        background-color: var(--nav-bg);
    }

    [data-theme="dark"] .main-sidebar,
    [data-theme="dark"] #sidebar-wrapper {
        background-color: var(--sidebar-bg) !important;
        border-right: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .sidebar-brand {
        background-color: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
        padding: 1rem !important;
    }

    [data-theme="dark"] .sidebar-brand-sm {
        background-color: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .sidebar-brand a,
    [data-theme="dark"] .sidebar-brand-sm a {
        color: var(--text-primary) !important;
        text-decoration: none !important;
    }

    [data-theme="dark"] .sidebar-menu {
        background-color: var(--sidebar-bg) !important;
    }

    [data-theme="dark"] .sidebar-menu li {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    [data-theme="dark"] .sidebar-menu li a {
        color: rgba(255, 255, 255, 0.8) !important;
        padding: 12px 20px !important;
        transition: all 0.1s ease !important;
    }

    [data-theme="dark"] .sidebar-menu li a:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
        transform: translateX(2px);
    }

    [data-theme="dark"] .sidebar-menu li.active > a,
    [data-theme="dark"] .sidebar-menu li.menu-open > a {
        background-color: #007bff !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu {
        background-color: rgba(0, 0, 0, 0.3) !important;
        border: none !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu li {
        background-color: transparent !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03) !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu li:last-child {
        border-bottom: none !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu li a {
        padding: 10px 15px 10px 45px !important;
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 13px !important;
        background-color: transparent !important;
        transition: all 0.1s ease !important;
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu li a:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.08) !important;
        transform: translateX(3px);
    }

    [data-theme="dark"] .sidebar-menu .dropdown-menu li.active > a {
        color: #ffffff !important;
        background-color: rgba(0, 123, 255, 0.8) !important;
    }

    [data-theme="dark"] .sidebar-menu .treeview-menu {
        background-color: rgba(0, 0, 0, 0.2) !important;
    }

    [data-theme="dark"] .sidebar-menu .treeview-menu li a {
        padding-left: 40px !important;
        color: rgba(255, 255, 255, 0.7) !important;
    }

    [data-theme="dark"] .sidebar-menu .treeview-menu li a:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }

    [data-theme="dark"] .sidebar-menu i {
        color: rgba(255, 255, 255, 0.6) !important;
        margin-right: 8px !important;
        width: 20px !important;
        text-align: center !important;
    }

    [data-theme="dark"] .sidebar-menu li a:hover i,
    [data-theme="dark"] .sidebar-menu li.active > a i {
        color: #ffffff !important;
    }

    [data-theme="dark"] .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    [data-theme="dark"] .navbar-nav .nav-link:hover {
        color: #ffffff !important;
    }

    [data-theme="dark"] .breadcrumb {
        background-color: var(--bg-secondary) !important;
    }

    [data-theme="dark"] .breadcrumb-item a {
        color: #007bff !important;
    }

    [data-theme="dark"] .breadcrumb-item.active {
        color: var(--text-secondary) !important;
    }

    [data-theme="dark"] .dropdown-menu {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .dropdown-item {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .dropdown-item:hover {
        background-color: var(--bg-tertiary) !important;
    }

    [data-theme="dark"] .sidebar-toggle {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    [data-theme="dark"] .sidebar-toggle:hover {
        color: #ffffff !important;
    }

    [data-theme="dark"] .main-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    [data-theme="dark"] .main-sidebar::-webkit-scrollbar-track {
        background: var(--sidebar-bg);
    }

    [data-theme="dark"] .main-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    [data-theme="dark"] .main-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
