<script>
    // Dark theme functionality
    function toggleTheme() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const currentTheme = html.getAttribute('data-theme');

        if (currentTheme === 'dark') {
            // Switch to light theme
            html.removeAttribute('data-theme');
            themeIcon.className = 'fas fa-moon';
            themeText.textContent = 'Oscuro';
            localStorage.setItem('theme', 'light');
        } else {
            // Switch to dark theme
            html.setAttribute('data-theme', 'dark');
            themeIcon.className = 'fas fa-sun';
            themeText.textContent = 'Claro';
            localStorage.setItem('theme', 'dark');
        }
    }

    // Load saved theme on page load
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme');
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');

        if (savedTheme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            themeIcon.className = 'fas fa-sun';
            themeText.textContent = 'Claro';
        } else {
            html.removeAttribute('data-theme');
            themeIcon.className = 'fas fa-moon';
            themeText.textContent = 'Oscuro';
        }
    });

    // Initialize Select2 with dark theme support
    $(document).ready(function () {
        // Re-initialize select2 when theme changes
        function updateSelect2Theme() {
            $('.select2').select2('destroy').select2();
        }

        // Monitor theme changes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    setTimeout(updateSelect2Theme, 100);
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    });
</script>
