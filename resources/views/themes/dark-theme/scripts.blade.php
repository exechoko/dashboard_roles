<script>
    function toggleTheme() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Actualizar UI inmediatamente
        if (newTheme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            themeIcon.className = 'fas fa-sun';
        } else {
            html.removeAttribute('data-theme');
            themeIcon.className = 'fas fa-moon';
        }

        // Guardar en localStorage
        localStorage.setItem('theme', newTheme);

        // Guardar en la base de datos si el usuario está autenticado
        @auth
        $.ajax({
            url: '{{ route("profile.updateTheme") }}',
            type: 'POST',
            data: {
                theme: newTheme,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Tema guardado en la base de datos');
            },
            error: function(xhr) {
                console.error('Error al guardar el tema:', xhr.responseJSON);
                // Si falla, al menos queda guardado en localStorage
            }
        });
        @endauth
    }

    document.addEventListener('DOMContentLoaded', function () {
        @auth
            // Usuario autenticado: usar tema de la BD
            const userTheme = @json(auth()->user()->theme ?? 'light');
            const themeIcon = document.getElementById('themeIcon');

            if (themeIcon) {
                themeIcon.className = userTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }

            // Sincronizar localStorage con BD
            localStorage.setItem('theme', userTheme);
        @else
            // Usuario no autenticado: usar localStorage
            const savedTheme = localStorage.getItem('theme') || 'light';
            const themeIcon = document.getElementById('themeIcon');

            if (themeIcon) {
                themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        @endauth
    });

    $(document).ready(function () {
        function updateSelect2Theme() {
            if ($.fn.select2) {
                $('.select2').each(function() {
                    const $element = $(this);
                    const config = $element.data('select2')?.options || {};
                    $element.select2('destroy').select2(config);
                });
            }
        }

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
