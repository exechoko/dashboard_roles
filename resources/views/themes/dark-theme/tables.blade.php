<style>
    [data-theme="dark"] .table {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        margin-bottom: 0 !important;
        min-width: 800px; /* Ancho mínimo para forzar scroll si es necesario */
    }

    [data-theme="dark"] .table thead th {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
        font-weight: 600 !important;
        border-bottom: 2px solid var(--border-color) !important;
        vertical-align: middle !important;
    }

    [data-theme="dark"] .table tbody tr {
        background-color: var(--card-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
        transition: background-color 0.2s ease !important;
    }

    [data-theme="dark"] .table tbody tr:hover {
        background-color: var(--bg-secondary) !important;
    }

    [data-theme="dark"] .table tbody td {
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
        vertical-align: middle !important;
    }

    [data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255, 255, 255, 0.02) !important;
    }

    [data-theme="dark"] .table-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .table-success:hover {
        background-color: rgba(40, 167, 69, 0.25) !important;
    }

    [data-theme="dark"] .table-success td {
        color: var(--bg-primary) !important;
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .table-warning {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .table-warning:hover {
        background-color: rgba(255, 193, 7, 0.25) !important;
    }

    [data-theme="dark"] .table-warning td {
        color: var(--bg-primary) !important;
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .table-danger {
        background-color: rgba(220, 53, 69, 0.15) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .table-danger:hover {
        background-color: rgba(220, 53, 69, 0.25) !important;
    }

    [data-theme="dark"] .table-danger td {
        color: var(--bg-primary) !important;
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .table-info {
        background-color: rgba(23, 162, 184, 0.15) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .table-info:hover {
        background-color: rgba(23, 162, 184, 0.25) !important;
    }

    [data-theme="dark"] .table-info td {
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .badge-success {
        background-color: #28a745 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .badge-warning {
        background-color: #ffc107 !important;
        color: #000000 !important;
    }

    [data-theme="dark"] .badge-danger {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .badge-info {
        background-color: #17a2b8 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .badge-secondary {
        background-color: #6c757d !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .table small {
        color: var(--text-secondary) !important;
    }

    [data-theme="dark"] .table strong {
        color: #007bff !important;
    }

    [data-theme="dark"] .table .text-muted {
        color: var(--text-secondary) !important;
    }

    [data-theme="dark"] .small-box {
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
    }

    [data-theme="dark"] .small-box.bg-light {
        background-color: var(--bg-secondary) !important;
    }

    [data-theme="dark"] .small-box-content {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .small-box-content div {
        color: var(--text-primary) !important;
        font-size: 0.875rem !important;
        margin-bottom: 0.25rem !important;
    }

    [data-theme="dark"] .small-box-content strong {
        color: #007bff !important;
    }

    [data-theme="dark"] .table-responsive {
        background-color: var(--card-bg) !important;
        overflow-x: auto !important; /* Asegurar scroll horizontal */
        -webkit-overflow-scrolling: touch !important; /* Scroll suave en móviles */
    }

    [data-theme="dark"] .timeline {
        position: relative !important;
    }

    [data-theme="dark"] .timeline-item {
        display: flex !important;
        margin-bottom: 1.5rem !important;
    }

    [data-theme="dark"] .timeline-marker {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        margin-right: 1rem !important;
        margin-top: 0.25rem !important;
    }

    [data-theme="dark"] .timeline-marker.bg-success {
        background-color: #28a745 !important;
    }

    [data-theme="dark"] .timeline-content {
        flex-grow: 1 !important;
        padding: 1rem !important;
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 6px !important;
    }

    [data-theme="dark"] .timeline-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 0.75rem !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }

    [data-theme="dark"] .timeline-title {
        color: var(--text-primary) !important;
        margin: 0 !important;
        font-weight: 600 !important;
    }

    [data-theme="dark"] .timeline-body {
        color: var(--text-primary) !important;
        margin-bottom: 0.75rem !important;
    }

    [data-theme="dark"] .timeline-body p {
        color: var(--text-primary) !important;
        margin-bottom: 0.5rem !important;
    }

    [data-theme="dark"] .timeline-body strong {
        color: #007bff !important;
    }

    [data-theme="dark"] .timeline-footer {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-top: 1px solid var(--border-color) !important;
        padding-top: 0.75rem !important;
        margin-top: 0.75rem !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }

    [data-theme="dark"] .timeline-footer {
        border-top-color: var(--border-color) !important;
    }

    [data-theme="dark"] .timeline-footer small {
        color: var(--text-secondary) !important;
    }

    [data-theme="dark"] .btn-group {
        display: flex !important;
        gap: 0.25rem !important;
    }

    [data-theme="dark"] .btn-group.btn-group-sm .btn {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
    }

    [data-theme="dark"] .btn-group .btn {
        border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .btn-info {
        background-color: #17a2b8 !important;
        border-color: #138496 !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    [data-theme="dark"] .dataTables_wrapper {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .dataTables_info,
    [data-theme="dark"] .dataTables_length label,
    [data-theme="dark"] .dataTables_filter label {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .dataTables_paginate .paginate_button {
        color: var(--text-primary) !important;
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .dataTables_paginate .paginate_button:hover {
        background-color: var(--bg-tertiary) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] .dataTables_paginate .paginate_button.current {
        background-color: #007bff !important;
        border: 1px solid #007bff !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .bootstrap-table .fixed-table-container {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
    }

    [data-theme="dark"] .table-responsive::-webkit-scrollbar {
        height: 8px !important;
    }

    [data-theme="dark"] .table-responsive::-webkit-scrollbar-track {
        background: var(--bg-secondary) !important;
    }

    [data-theme="dark"] .table-responsive::-webkit-scrollbar-thumb {
        background: var(--border-color) !important;
        border-radius: 4px !important;
    }

    [data-theme="dark"] .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555555 !important;
    }

    @media (max-width: 768px) {
        [data-theme="dark"] .table {
            font-size: 0.875rem !important;
        }

        [data-theme="dark"] .table thead th {
            padding: 0.5rem !important;
        }

        [data-theme="dark"] .table tbody td {
            padding: 0.5rem !important;
        }

        [data-theme="dark"] .timeline-item {
            margin-bottom: 1rem !important;
        }

        [data-theme="dark"] .timeline-marker {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.875rem !important;
        }

        [data-theme="dark"] .timeline-content {
            padding: 0.75rem !important;
        }
    }
</style>
