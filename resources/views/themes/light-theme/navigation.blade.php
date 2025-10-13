<style>
    .main-navbar {
        background-color: var(--nav-bg) !important;
        border-bottom: 1px solid var(--border-color);
    }

    .navbar-bg {
        background-color: var(--nav-bg);
    }

    .main-sidebar,
    #sidebar-wrapper {
        background-color: var(--sidebar-bg) !important;
        border-right: 1px solid var(--border-color) !important;
    }

    .sidebar-brand {
        background-color: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
        padding: 1rem !important;
    }

    .sidebar-brand-sm {
        background-color: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
    }

    .sidebar-brand a,
    .sidebar-brand-sm a {
        color: var(--text-primary) !important;
        text-decoration: none !important;
    }

    .sidebar-menu {
        background-color: var(--sidebar-bg) !important;
    }

    .sidebar-menu li {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .sidebar-menu li a {
        color: rgba(0, 0, 0, 0.7) !important;
        padding: 12px 20px !important;
        transition: all 0.1s ease !important;
    }

    .sidebar-menu li a:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #000000 !important;
        transform: translateX(2px);
    }

    .sidebar-menu li.active > a,
    .sidebar-menu li.menu-open > a {
        background-color: #007bff !important;
        color: #ffffff !important;
    }

    .sidebar-menu .dropdown-menu {
        background-color: rgba(0, 0, 0, 0.02) !important;
        border: none !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .sidebar-menu .dropdown-menu li {
        background-color: transparent !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03) !important;
    }

    .sidebar-menu .dropdown-menu li:last-child {
        border-bottom: none !important;
    }

    .sidebar-menu .dropdown-menu li a {
        padding: 10px 15px 10px 45px !important;
        color: rgba(0, 0, 0, 0.6) !important;
        font-size: 13px !important;
        background-color: transparent !important;
        transition: all 0.1s ease !important;
    }

    .sidebar-menu .dropdown-menu li a:hover {
        color: #000000 !important;
        background-color: rgba(0, 0, 0, 0.08) !important;
        transform: translateX(3px);
    }

    .sidebar-menu .dropdown-menu li.active > a {
        color: #ffffff !important;
        background-color: rgba(0, 123, 255, 0.8) !important;
    }

    .sidebar-menu .treeview-menu {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    .sidebar-menu .treeview-menu li a {
        padding-left: 40px !important;
        color: rgba(0, 0, 0, 0.6) !important;
    }

    .sidebar-menu .treeview-menu li a:hover {
        color: #000000 !important;
        background-color: rgba(0, 0, 0, 0.05) !important;
    }

    .sidebar-menu i {
        color: rgba(0, 0, 0, 0.5) !important;
        margin-right: 8px !important;
        width: 20px !important;
        text-align: center !important;
    }

    .sidebar-menu li a:hover i,
    .sidebar-menu li.active > a i {
        color: #ffffff !important;
    }

    .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .navbar-nav .nav-link:hover {
        color: #ffffff !important;
    }

    .breadcrumb {
        background-color: var(--bg-secondary) !important;
    }

    .breadcrumb-item a {
        color: #007bff !important;
    }

    .breadcrumb-item.active {
        color: var(--text-secondary) !important;
    }

    .dropdown-menu {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
    }

    .dropdown-item {
        color: var(--text-primary) !important;
    }

    .dropdown-item:hover {
        background-color: var(--bg-secondary) !important;
    }

    .sidebar-toggle {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .sidebar-toggle:hover {
        color: #ffffff !important;
    }

    .main-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .main-sidebar::-webkit-scrollbar-track {
        background: var(--sidebar-bg);
    }

    .main-sidebar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }

    .main-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }
</style>
