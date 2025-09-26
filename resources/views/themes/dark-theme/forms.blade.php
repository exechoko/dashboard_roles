<style>
    /* Forms */
    .form-control {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
    }

    .form-control:focus {
        background-color: var(--input-bg) !important;
        border-color: #007bff !important;
        color: var(--text-primary) !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    /* Input groups */
    [data-theme="dark"] .input-group-text {
        background-color: var(--bg-tertiary) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
    }

    /* Select2 dark theme */
    [data-theme="dark"] .select2-container--default .select2-selection--single {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .select2-dropdown {
        background-color: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
    }

    [data-theme="dark"] .select2-container--default .select2-results__option {
        background-color: var(--input-bg) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
    }

    /* DateRangePicker */
    [data-theme="dark"] .daterangepicker {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .daterangepicker .calendar-table {
        background-color: var(--card-bg) !important;
    }
</style>
