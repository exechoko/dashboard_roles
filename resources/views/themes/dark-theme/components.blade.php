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
</style>
