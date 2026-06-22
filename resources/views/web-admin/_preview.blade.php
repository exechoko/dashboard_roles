{{-- Modal de vista previa reutilizable. Dispará con un botón:
     <button class="btn btn-outline-primary js-web-preview" data-pagina="galeria.html" data-title="Galería">…</button>
     Incluí este partial una vez en la vista: @include('web-admin._preview') --}}
<div class="modal fade" id="modalWebPreview" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Vista previa — <span id="webPreviewTitulo"></span></h5>
                <div>
                    <a href="#" id="webPreviewAbrir" target="_blank" rel="noopener" class="btn btn-sm btn-light mr-1" title="Ver versión publicada (pestaña nueva)">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <button type="button" id="webPreviewRecargar" class="btn btn-sm btn-light mr-2" title="Recargar la vista previa">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0">
                <iframe id="webPreviewIframe" src="about:blank" title="Vista previa de la página"
                        style="width:100%;height:78vh;border:0;"></iframe>
            </div>
            <div class="modal-footer py-2">
                <small class="text-muted mr-auto">
                    <i class="fas fa-info-circle"></i> Refleja los datos <strong>ya guardados</strong> de esta sección, tal como se verán en la web.
                </small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var iframe = document.getElementById('webPreviewIframe');
            var titulo = document.getElementById('webPreviewTitulo');
            var abrir = document.getElementById('webPreviewAbrir');
            var recargar = document.getElementById('webPreviewRecargar');
            var baseUrl = @json(rtrim(config('landing.url'), '/') . '/');
            var previewUrl = @json(route('web-admin.preview'));
            var paginaActual = '';

            function generar() {
                if (!paginaActual) { return; }
                iframe.srcdoc = '<p style="font-family:sans-serif;padding:1rem;">Generando vista previa…</p>';
                fetch(previewUrl + '?pagina=' + encodeURIComponent(paginaActual), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.text(); })
                .then(function (html) { iframe.srcdoc = html; })
                .catch(function (e) {
                    iframe.srcdoc = '<p style="font-family:sans-serif;color:#c00;padding:1rem;">No se pudo generar la vista previa: ' + e.message + '</p>';
                });
            }

            document.querySelectorAll('.js-web-preview').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    paginaActual = boton.getAttribute('data-pagina') || '';
                    titulo.textContent = boton.getAttribute('data-title') || '';
                    if (abrir) { abrir.href = baseUrl + paginaActual; }
                    generar();
                    if (window.jQuery) { window.jQuery('#modalWebPreview').modal('show'); }
                });
            });

            if (recargar) { recargar.addEventListener('click', generar); }
            if (window.jQuery) {
                window.jQuery('#modalWebPreview').on('hidden.bs.modal', function () { iframe.srcdoc = ''; });
            }
        })();
    </script>
@endpush
