<style>
    .table {
        background-color: var(--card-bg) !important;
        color: var(--text-primary) !important;
        margin-bottom: 0 !important;
    }

    .table thead th {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
        font-weight: 600 !important;
        border-bottom: 2px solid var(--border-color) !important;
        vertical-align: middle !important;
    }

    .table tbody tr {
        background-color: var(--card-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
        transition: background-color 0.2s ease !important;
    }

    .table tbody tr:hover {
        background-color: var(--bg-secondary) !important;
    }

    .table tbody td {
        border-color: var(--border-color) !important;
        color: var(--text-primary) !important;
        vertical-align: middle !important;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    .table-success {
        background-color: #d4edda !important;
        color: #155724 !important;
    }

    .table-success td {
        border-color: #c3e6cb !important;
    }

    .table-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }

    .table-warning td {
        border-color: #ffeeba !important;
    }

    .table-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }

    .table-danger td {
        border-color: #f5c6cb !important;
    }

    .table-info {
        background-color: #d1ecf1 !important;
        color: #0c5460 !important;
    }

    .table-info td {
        border-color: #bee5eb !important;
    }

    .badge {
        font-weight: 600 !important;
        padding: 0.35em 0.65em !important;
    }

    .badge-success {
        background-color: #28a745 !important;
        color: #ffffff !important;
    }

    .badge-warning {
        background-color: #ffc107 !important;
        color: #000000 !important;
    }

    .badge-danger {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }

    .badge-info {
        background-color: #17a2b8 !important;
        color: #ffffff !important;
    }

    .badge-secondary {
        background-color: #6c757d !important;
        color: #ffffff !important;
    }

    .table small {
        font-size: 0.85rem !important;
        color: var(--text-secondary) !important;
    }

    .table strong {
        font-weight: 600 !important;
        color: #007bff !important;
    }

    .table .text-muted {
        font-size: 0.875rem !important;
        color: var(--text-secondary) !important;
    }

    .small-box {
        border-radius: 6px !important;
        overflow: hidden !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12) !important;
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
    }

    .small-box-content {
        padding: 0.75rem !important;
        color: var(--text-primary) !important;
    }

    .small-box-content div {
        color: var(--text-primary) !important;
        font-size: 0.875rem !important;
        margin-bottom: 0.25rem !important;
    }

    .small-box-content strong {
        color: #007bff !important;
    }

    .table-responsive {
        border-radius: 6px !important;
        overflow: hidden !important;
        background-color: var(--card-bg) !important;
    }

    .card-header h4 {
        color: var(--text-primary) !important;
        margin: 0 !important;
    }

    .card-header-action {
        display: flex !important;
        gap: 0.5rem !important;
    }

    .timeline {
        position: relative !important;
    }

    .timeline-item {
        display: flex !important;
        margin-bottom: 1.5rem !important;
    }

    .timeline-marker {
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

    .timeline-marker.bg-success {
        background-color: #28a745 !important;
    }

    .timeline-content {
        flex-grow: 1 !important;
        padding: 1rem !important;
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 6px !important;
    }

    .timeline-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 0.75rem !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }

    .timeline-title {
        color: var(--text-primary) !important;
        margin: 0 !important;
        font-weight: 600 !important;
    }

    .timeline-body {
        color: var(--text-primary) !important;
        margin-bottom: 0.75rem !important;
    }

    .timeline-body p {
        color: var(--text-primary) !important;
        margin-bottom: 0.5rem !important;
    }

    .timeline-body strong {
        color: #007bff !important;
    }

    .timeline-footer {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-top: 1px solid var(--border-color) !important;
        padding-top: 0.75rem !important;
        margin-top: 0.75rem !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }

    .timeline-footer small {
        color: var(--text-secondary) !important;
    }

    .btn-group {
        display: flex !important;
        gap: 0.25rem !important;
    }

    .btn-group.btn-group-sm .btn {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
    }

    .btn-group .btn {
        border-color: var(--border-color) !important;
    }

    .btn-info {
        background-color: #17a2b8 !important;
        border-color: #138496 !important;
        color: #ffffff !important;
    }

    .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
    }

    .dataTables_wrapper {
        color: var(--text-primary) !important;
    }

    .dataTables_info,
    .dataTables_length label,
    .dataTables_filter label {
        color: var(--text-primary) !important;
    }

    .dataTables_paginate .paginate_button {
        color: var(--text-primary) !important;
        background-color: var(--bg-secondary) !important;
        border: 1px solid var(--border-color) !important;
    }

    .dataTables_paginate .paginate_button:hover {
        background-color: var(--bg-tertiary) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #007bff !important;
        border: 1px solid #007bff !important;
        color: #ffffff !important;
    }

    .table-responsive::-webkit-scrollbar {
        height: 8px !important;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: var(--bg-secondary) !important;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--border-color) !important;
        border-radius: 4px !important;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #999999 !important;
    }

    @media (max-width: 768px) {
        .table {
            font-size: 0.875rem !important;
        }

        .table thead th {
            padding: 0.5rem !important;
        }

        .table tbody td {
            padding: 0.5rem !important;
        }

        .timeline-item {
            margin-bottom: 1rem !important;
        }

        .timeline-marker {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.875rem !important;
        }

        .timeline-content {
            padding: 0.75rem !important;
        }
    }
</style>
