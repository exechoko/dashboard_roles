@push('scripts')
<script>
    (function () {
        var formularios = document.querySelectorAll('form[data-evitar-doble-envio]');

        formularios.forEach(function (formulario) {
            formulario.addEventListener('submit', function (evento) {
                if (formulario.dataset.enviando === '1') {
                    evento.preventDefault();
                    return;
                }

                formulario.dataset.enviando = '1';
                formulario.querySelectorAll('button[type="submit"]').forEach(function (boton) {
                    boton.disabled = true;
                    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                });
            });
        });

        window.addEventListener('pageshow', function (evento) {
            if (!evento.persisted) {
                return;
            }
            window.location.reload();
        });
    })();
</script>
@endpush
