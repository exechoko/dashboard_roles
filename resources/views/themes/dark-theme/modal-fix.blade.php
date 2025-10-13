<style>
    /* ========================================
       CORRECCIÓN CRÍTICA DE Z-INDEX PARA MODALES
       ======================================== */

    /* RESET z-index del navbar (MUY IMPORTANTE) */
    .main-navbar {
        z-index: 1029 !important;
    }

    /*.navbar-bg {
        z-index: 1028 !important;
    }

    .navbar {
        z-index: 1029 !important;
    }*/

    /* SIDEBAR */
    .main-sidebar,
    .main-sidebar-postion {
        z-index: 1020 !important;
    }

    /* *** MODALES ARRIBA DE TODO *** */
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

    /* Dropdowns y elementos flotantes */
    .dropdown-menu {
        z-index: 1110 !important;
    }

    .select2-container--open {
        z-index: 1120 !important;
    }

    .select2-dropdown {
        z-index: 1120 !important;
    }

    /* Alertas y notificaciones */
    .iziToast {
        z-index: 9999 !important;
    }

    .swal2-container {
        z-index: 10000 !important;
    }

    /* Date Range Picker */
    .daterangepicker {
        z-index: 1130 !important;
    }

    /* ========================================
       ESTILOS DARK THEME PARA MODALES
       ======================================== */

    .modal-content {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
        border: 1px solid var(--border-color, #333333) !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.7) !important;
    }

    .modal-header {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-bottom: 2px solid var(--border-color, #333333) !important;
        color: var(--text-primary, #ffffff) !important;
        padding: 1.5rem;
    }

    .modal-header .modal-title {
        color: var(--text-primary, #ffffff) !important;
        font-weight: 700;
        font-size: 1.25rem;
    }

    .modal-header .close {
        color: var(--text-primary, #ffffff) !important;
        opacity: 0.7;
        text-shadow: none;
        font-size: 1.5rem;
    }

    .modal-header .close:hover,
    .modal-header .close:focus {
        opacity: 1;
        color: var(--text-primary, #ffffff) !important;
        outline: none;
    }

    .modal-body {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
        padding: 1.5rem;
        max-height: calc(85vh - 180px);
        overflow-y: auto;
    }

    .modal-dialog {
        max-height: 90vh !important;
    }

    .modal.show {
        overflow-y: auto !important;
        display: flex !important;
        align-items: center !important;
        /*padding-top: 80px !important;*/
    }

    .modal-dialog {
        margin-top: 50px !important;
    }

    .modal-footer {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-top: 2px solid var(--border-color, #333333) !important;
        padding: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    /* ========================================
       FORMULARIOS EN MODALES
       ======================================== */

    .modal .form-group label {
        color: var(--text-primary, #ffffff) !important;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .modal .form-control {
        background-color: var(--input-bg, #2d2d2d) !important;
        border: 1px solid var(--input-border, #444444) !important;
        color: var(--text-primary, #ffffff) !important;
        transition: all 0.3s ease;
        padding: 0.5rem 0.75rem;
    }

    .modal .form-control:focus {
        background-color: var(--input-bg, #2d2d2d) !important;
        border-color: #007bff !important;
        color: var(--text-primary, #ffffff) !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        outline: none;
    }

    .modal .form-control::placeholder {
        color: var(--text-secondary, #888888) !important;
    }

    .modal textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    /* ========================================
       SELECT2 EN MODALES
       ======================================== */

    .modal .select2-container--default .select2-selection--single .select2-selection--multiple {
        background-color: var(--input-bg, #2d2d2d) !important;
        border: 1px solid var(--input-border, #444444) !important;
        color: var(--text-primary, #ffffff) !important;
        height: auto;
        min-height: 38px;
    }

    .modal .select2-container--default .select2-selection--single .select2-selection--multiple .select2-selection__rendered {
        color: var(--text-primary, #ffffff) !important;
        line-height: 28px;
    }

    .modal .select2-container--default.select2-container--focus .select2-selection--single .select2-selection--multiple {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    .select2-dropdown {
        background-color: var(--card-bg, #1e1e1e) !important;
        border: 1px solid var(--input-border, #444444) !important;
    }

    .select2-container--default .select2-results__option {
        color: var(--text-primary, #ffffff) !important;
        background-color: var(--card-bg, #1e1e1e) !important;
        padding: 10px;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
        color: white !important;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        color: var(--text-primary, #ffffff) !important;
    }

    /* ========================================
       TABLAS EN MODALES
       ======================================== */

    .modal table {
        color: var(--text-primary, #ffffff) !important;
    }

    .modal table thead {
        background-color: var(--bg-secondary, #2d2d2d) !important;
    }

    .modal table thead th {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
        font-weight: 700;
    }

    .modal table tbody tr {
        background-color: var(--card-bg, #1e1e1e) !important;
        border-color: var(--border-color, #333333) !important;
    }

    .modal table tbody tr:hover {
        background-color: var(--bg-secondary, #2d2d2d) !important;
    }

    .modal table tbody td {
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
    }

    /* ========================================
       BOTONES EN MODALES
       ======================================== */

    .modal .btn {
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .modal .btn-outline-secondary {
        color: var(--text-primary, #ffffff) !important;
        border-color: var(--border-color, #333333) !important;
    }

    .modal .btn-outline-secondary:hover {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-color: var(--text-primary, #ffffff) !important;
        color: var(--text-primary, #ffffff) !important;
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

    /* ========================================
       ALERTAS EN MODALES
       ======================================== */

    .modal .alert {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-color: var(--border-color, #333333) !important;
        color: var(--text-primary, #ffffff) !important;
    }

    .modal .alert-warning {
        background-color: #664d03 !important;
        border-color: #997404 !important;
        color: #ffecb5 !important;
    }

    .modal .alert-danger {
        background-color: #5a1e1e !important;
        border-color: #842029 !important;
        color: #f8d7da !important;
    }

    .modal .alert-success {
        background-color: #1e5631 !important;
        border-color: #2e7d32 !important;
        color: #d4edda !important;
    }

    .modal .alert-info {
        background-color: #0c3a52 !important;
        border-color: #084298 !important;
        color: #cfe2ff !important;
    }

    /* ========================================
       TEXTO ESPECIAL EN MODALES
       ======================================== */

    .modal h5,
    .modal h4,
    .modal h3,
    .modal h2,
    .modal h1 {
        color: var(--text-primary, #ffffff) !important;
        margin-bottom: 1rem;
    }

    .modal .text-muted {
        color: var(--text-secondary, #888888) !important;
    }

    .modal small {
        color: var(--text-secondary, #888888) !important;
    }

    .modal ul.list-unstyled li {
        color: var(--text-primary, #ffffff) !important;
        margin-bottom: 0.5rem;
    }

    .modal ul.list-unstyled li strong {
        color: var(--text-primary, #ffffff) !important;
        font-weight: 700;
    }

    /* ========================================
       IMAGEN EN MODALES
       ======================================== */

    .modal .img-thumbnail {
        background-color: var(--bg-secondary, #2d2d2d) !important;
        border-color: var(--border-color, #333333) !important;
    }

    /* ========================================
       RESPONSIVO
       ======================================== */

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
        // Asegurar que los modales siempre estén al frente
        $(document).on('shown.bs.modal', function(e) {
            const $modal = $(e.target);
            const $backdrop = $modal.next('.modal-backdrop');

            // Forzar z-index del modal
            $modal.css('z-index', 1100);

            // Forzar z-index del backdrop
            if ($backdrop.length) {
                $backdrop.css('z-index', 1099);
            }

            // Manejar múltiples modales
            const openModals = $('.modal.show').length;
            if (openModals > 1) {
                $modal.css('z-index', 1100 + (openModals * 5));
                $backdrop.css('z-index', 1099 + (openModals * 5));
            }
        });

        // Ajustar z-index cuando se cierra un modal
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
