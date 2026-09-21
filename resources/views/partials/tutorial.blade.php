{{--
    Tour guiado genérico (Driver.js) para una pantalla de CECOCO.
    Variables esperadas:
      $tutorialPasos      array de ['id' => 'idDelElemento', 'titulo' => '...', 'texto' => '...', 'side' => opcional, 'align' => opcional]
      $tutorialStorageKey string única por pantalla (clave de localStorage para "ya lo vio")
--}}
@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.8.0/dist/driver.css">
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.8.0/dist/driver.js.iife.js"></script>
<script>
(function () {
    var storageKey = @json($tutorialStorageKey);
    var candidatos = @json($tutorialPasos);

    // Un paso por elemento relevante de la pantalla. Se filtran los que no
    // existen en el DOM (ej. botones ocultos según los permisos del usuario,
    // o secciones que todavía no se cargaron con resultados).
    function pasosDisponibles() {
        return candidatos
            .filter(function (p) { return document.getElementById(p.id); })
            .map(function (p) {
                return {
                    element: '#' + p.id,
                    popover: {
                        title: p.titulo,
                        description: p.texto,
                        side: p.side || 'bottom',
                        align: p.align || 'start'
                    }
                };
            });
    }

    window.iniciarTutorialPagina = function () {
        var pasos = pasosDisponibles();
        if (!pasos.length) { return; }

        window.driver.js.driver({
            showProgress: true,
            progressText: '@{{current}} de @{{total}}',
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: 'Listo',
            steps: pasos
        }).drive();

        try { localStorage.setItem(storageKey, '1'); } catch (e) {}
    };

    // Se muestra solo (auto) la primera vez que el navegador ve esta pantalla;
    // después queda disponible siempre desde el botón "Ver tutorial".
    document.addEventListener('DOMContentLoaded', function () {
        var yaVisto = false;
        try { yaVisto = localStorage.getItem(storageKey) === '1'; } catch (e) {}
        if (!yaVisto) {
            setTimeout(window.iniciarTutorialPagina, 600);
        }
    });
})();
</script>
<style>
.driver-popover {
    background: var(--bg-secondary, #fff);
    color: var(--text-primary, #1a2233);
}
.driver-popover-title { color: var(--text-primary, #1a2233) !important; }
.driver-popover-description { color: var(--text-secondary, #5b6472) !important; }
[data-theme="dark"] .driver-popover-arrow-side-bottom.driver-popover-arrow { border-bottom-color: var(--bg-secondary, #0b1b31) !important; }
[data-theme="dark"] .driver-popover-arrow-side-top.driver-popover-arrow { border-top-color: var(--bg-secondary, #0b1b31) !important; }
[data-theme="dark"] .driver-popover-arrow-side-left.driver-popover-arrow { border-left-color: var(--bg-secondary, #0b1b31) !important; }
[data-theme="dark"] .driver-popover-arrow-side-right.driver-popover-arrow { border-right-color: var(--bg-secondary, #0b1b31) !important; }
</style>
@endpush
