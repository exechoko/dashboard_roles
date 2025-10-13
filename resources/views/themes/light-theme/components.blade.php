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
</style>
