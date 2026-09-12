{{--
    Modal genérico de la PWA: se abre clonando el contenido de un <template>
    identificado por data-modal-tpl en cualquier botón/elemento con ese
    atributo. Pensado para listas largas (30-60 items) que no entran en una
    card del listado.
--}}
<div class="m-modal-backdrop" id="mModalBackdrop" hidden>
    <div class="m-modal">
        <div class="m-modal__header">
            <span id="mModalTitle">Detalle</span>
            <button type="button" class="m-modal__close" id="mModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="m-modal__body" id="mModalBody"></div>
    </div>
</div>

<script>
    (function () {
        var backdrop = document.getElementById('mModalBackdrop');
        var body = document.getElementById('mModalBody');
        var title = document.getElementById('mModalTitle');
        var closeBtn = document.getElementById('mModalClose');

        function abrir(trigger) {
            var tpl = document.getElementById(trigger.dataset.modalTpl);
            if (!tpl) {
                return;
            }

            body.innerHTML = '';
            body.appendChild(tpl.content.cloneNode(true));
            title.textContent = trigger.dataset.modalTitle || 'Detalle';
            backdrop.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function cerrar() {
            backdrop.hidden = true;
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-modal-tpl]');
            if (trigger) {
                abrir(trigger);
            }
        });

        closeBtn.addEventListener('click', cerrar);
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                cerrar();
            }
        });
    })();
</script>
