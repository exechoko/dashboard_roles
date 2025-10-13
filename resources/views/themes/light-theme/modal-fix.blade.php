<style>
    .main-navbar {
        z-index: 1029 !important;
    }

    .main-sidebar,
    .main-sidebar-postion {
        z-index: 1020 !important;
    }

    .modal.show {
        z-index: 1100 !important;
        display: block !important;
    }

    .modal {
        z-index: 1100 !important;
    }

    .modal-backdrop {
        z-index: 1099 !important;
    }

    .modal-backdrop.show {
        z-index: 1099 !important;
        opacity: 0.7 !important;
    }

    .dropdown-menu {
        z-index: 1110 !important;
    }

    .select2-container--open {
        z-index: 1120 !important;
    }

    .select2-dropdown {
        z-index: 1120 !important;
    }

    .iziToast {
        z-index: 9999 !important;
    }

    .swal2-container {
        z-index: 10000 !important;
    }

    .daterangepicker {
        z-index: 1130 !important;
    }

    .modal.show {
        overflow-y: auto !important;
        display: flex !important;
        align-items: center !important;
    }

    .modal .form-group label {
        color: var(--text-primary) !important;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .modal .form-control {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
        transition: all 0.3s ease;
        padding: 0.5rem 0.75rem;
    }

    .modal .form-control:focus {
        background-color: var(--input-bg) !important;
        border-color: #007bff !important;
        color: var(--text-primary) !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        outline: none;
    }

    .modal .form-control::placeholder {
        color: var(--text-secondary) !important;
    }

    .modal textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .modal .select2-container--default .select2-selection--single,
    .modal .select2-container--default .select2-selection--multiple {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
        height: auto;
        min-height: 38px;
    }

    .modal .select2-container--default .select2-selection--single .select2-selection__rendered,
    .modal .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        color: var(--text-primary) !important;
        line-height: 28px;
    }

    .modal .select2-container--default.select2-container--focus .select2-selection--single,
    .modal .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    .modal .select2-dropdown {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--input-border) !important;
    }

    .modal .select2-container--default .select2-results__option {
        color: var(--text-primary) !important;
        background-color: var(--card-bg) !important;
        padding: 10px;
    }

    .modal .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
        color: white !important;
    }

    .modal .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f8f9fa !important;
        color: var(--text-primary) !important;
    }

    .modal table {
        color: var(--text-primary) !important;
    }

    .modal table thead {
        background-color: var(--bg-secondary) !important;
    }

    .modal table thead th {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
        font-weight: 700;
    }

    .modal table tbody tr {
        background-color: var(--card-bg) !important;
        border-color: var(--border-color) !important;
    }

    .modal table tbody tr:hover {
        background-color: var(--bg-secondary) !important;
    }

    .modal table tbody td {
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }

    .modal .btn {
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .modal .btn-outline-secondary {
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }

    .modal .btn-outline-secondary:hover {
        background-color: var(--bg-secondary) !important;
        border-color: var(--text-primary) !important;
        color: var(--text-primary) !important;
    }

    .modal .btn-info {
        background-color: #17a2b8 !important;
        border-color: #17a2b8 !important;
        color: white !important;
    }

    .modal .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    .modal .btn-warning {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #000 !important;
    }

    .modal .btn-warning:hover {
        background-color: #e0a800 !important;
        border-color: #d39e00 !important;
    }

    .modal .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }

    .modal .btn-danger:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
    }

    .modal .alert {
        background-color: var(--bg-secondary) !important;
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    .modal .alert-warning {
        background-color: #fff3cd !important;
        border-color: #ffeeba !important;
        color: #856404 !important;
    }

    .modal .alert-danger {
        background-color: #f8d7da !important;
        border-color: #f5c6cb !important;
        color: #721c24 !important;
    }

    .modal .alert-success {
        background-color: #d4edda !important;
        border-color: #c3e6cb !important;
        color: #155724 !important;
    }

    .modal .alert-info {
        background-color: #d1ecf1 !important;
        border-color: #bee5eb !important;
        color: #0c5460 !important;
    }

    .modal h5,
    .modal h4,
    .modal h3,
    .modal h2,
    .modal h1 {
        color: var(--text-primary) !important;
        margin-bottom: 1rem;
    }

    .modal .text-muted {
        color: var(--text-secondary) !important;
    }

    .modal small {
        color: var(--text-secondary) !important;
    }

    .modal ul.list-unstyled li {
        color: var(--text-primary) !important;
        margin-bottom: 0.5rem;
    }

    .modal ul.list-unstyled li strong {
        color: var(--text-primary) !important;
        font-weight: 700;
    }

    .modal .img-thumbnail {
        background-color: var(--bg-secondary) !important;
        border-color: var(--border-color) !important;
    }

    @media (max-width: 768px) {
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header .modal-title {
            font-size: 1rem;
        }

        .modal-body {
            max-height: 70vh;
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .modal-dialog.modal-xl {
            max-width: 95vw !important;
        }

        .modal-body {
            padding: 0.75rem;
        }

        .modal-footer {
            flex-wrap: wrap;
        }

        .modal .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('shown.bs.modal', function(e) {
            const $modal = $(e.target);
            const $backdrop = $modal.next('.modal-backdrop');

            $modal.css('z-index', 1100);

            if ($backdrop.length) {
                $backdrop.css('z-index', 1099);
            }

            const openModals = $('.modal.show').length;
            if (openModals > 1) {
                $modal.css('z-index', 1100 + (openModals * 5));
                $backdrop.css('z-index', 1099 + (openModals * 5));
            }
        });

        $(document).on('hidden.bs.modal', function() {
            const openModals = $('.modal.show').length;
            if (openModals > 0) {
                $('.modal.show').each(function(index) {
                    $(this).css('z-index', 1100 + (index * 5));
                });
            }
        });
    });
</script>
