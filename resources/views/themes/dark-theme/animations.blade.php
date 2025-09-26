<style>
    /* Animation for theme switching */
    * {
        transition: background-color 0.1s ease, color 0.1s ease, border-color 0.1s ease;
    }

    /* Custom scrollbar for dark theme */
    [data-theme="dark"] ::-webkit-scrollbar {
        width: 8px;
    }

    [data-theme="dark"] ::-webkit-scrollbar-track {
        background: var(--bg-secondary);
    }

    [data-theme="dark"] ::-webkit-scrollbar-thumb {
        background: var(--bg-tertiary);
        border-radius: 4px;
    }

    [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
        background: #555555;
    }

    /* Loading animations */
    [data-theme="dark"] .spinner-border {
        color: var(--text-primary);
    }

    /* Progress bars */
    [data-theme="dark"] .progress {
        background-color: var(--bg-tertiary);
    }
</style>
