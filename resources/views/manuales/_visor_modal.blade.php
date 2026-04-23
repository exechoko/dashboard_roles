{{-- Modal Visor de Documentos --}}
<div class="modal fade" id="modalVisor" tabindex="-1" role="dialog" aria-labelledby="modalVisorLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisorLabel">
                    <i class="fas fa-file mr-2" id="visor-icono"></i>
                    <span id="visor-nombre">Documento</span>
                </h5>
                <div class="ml-auto d-flex align-items-center">
                    <a href="#" id="visor-descargar" class="btn btn-sm btn-success mr-2">
                        <i class="fas fa-download mr-1"></i>Descargar
                    </a>
                    <button type="button" class="close ml-1" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0" style="min-height: 70vh;">
                <div id="visor-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Cargando documento...</p>
                </div>

                {{-- PDF: embed (Chrome no renderiza PDF en iframe con sandbox) --}}
                <embed id="visor-pdf"
                    src="about:blank"
                    type="application/pdf"
                    style="width:100%; height:75vh; border:none; display:none;">

                {{-- HTML: iframe sin sandbox (mismo origen, contenido propio) --}}
                <iframe id="visor-iframe"
                    src="about:blank"
                    style="width:100%; height:75vh; border:none; display:none;">
                </iframe>

                {{-- Markdown: div renderizado --}}
                <div id="visor-md"
                    style="display:none; padding:2rem; overflow-y:auto; max-height:75vh; background:#fff;">
                </div>

                {{-- DOCX: div renderizado con mammoth --}}
                <div id="visor-docx"
                    style="display:none; padding:2rem; overflow-y:auto; max-height:75vh; background:#fff;">
                </div>

                {{-- Fallback: tipo no previsualizable --}}
                <div id="visor-fallback" style="display:none;" class="text-center py-5">
                    <i class="fas fa-file fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Este tipo de archivo no tiene vista previa.<br>Podés descargarlo con el botón de arriba.</p>
                </div>
            </div>
        </div>
    </div>
</div>
