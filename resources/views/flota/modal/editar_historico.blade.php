<form id="form-historico-{{ $h->id }}" action="{{ route('flota.update_historico', $h->id) }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="ModalEditar{{ $h->id }}" tabindex="-1" data-backdrop="false" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar Histórico</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container col-xs-12 col-sm-12 col-md-12">
                        <div class="row">
                            <!-- Input para agregar nuevos archivos -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archivo">Archivo adjunto</label>
                                    <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="imagen1">Imagen 1</label>
                                    <input type="file" id="imagen1-{{ $h->id }}" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="imagen2">Imagen 2</label>
                                    <input type="file" id="imagen2-{{ $h->id }}" class="form-control" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <!-- Previsualización de imágenes ya cargadas -->
                        <div class="row">
                            <div class="col-md-12">
                                <label>Imágenes existentes:</label>
                                <div id="imagenes-actuales-{{ $h->id }}" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    @if (is_array($h->rutas_imagenes))
                                        @foreach ($h->rutas_imagenes as $ruta)
                                            <div style="position: relative;" data-ruta="{{ $ruta }}">
                                                <a href="{{ asset($ruta) }}" target="_blank">
                                                    <img src="{{ asset($ruta) }}" alt="Imagen existente" style="width: 100px; height: auto;">
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 0; right: 0;"
                                                    onclick="eliminarImagen('{{ $ruta }}', '{{ $h->id }}')">X</button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 mt-3">
                            <div class="form-group">
                                <strong>Observaciones</strong>
                                <textarea name="observaciones" id="observaciones{{ $h->id }}" class="form-control" style="min-height: 200px;">{{ $h->observaciones }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right mt-3">
                        <button type="button" class="btn btn-success" onclick="guardar('{{ $h->id }}')">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Script específico para este modal
document.addEventListener('DOMContentLoaded', function() {
    const modalId = '{{ $h->id }}';

    // Inicializar arrays para este modal
    window.imagenesActualesMap[modalId] = @json($h->rutas_imagenes ?? []);
    initializeNuevasImagenes(modalId);

    console.log('Modal inicializado:', modalId);
    console.log('Imágenes actuales:', window.imagenesActualesMap[modalId]);

    // Configurar event listeners para los inputs de archivo
    const imagen1Input = document.getElementById('imagen1-{{ $h->id }}');
    const imagen2Input = document.getElementById('imagen2-{{ $h->id }}');

    if (imagen1Input) {
        imagen1Input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                previsualizarImagen(file, modalId);
                event.target.value = ''; // Limpiar el input
            }
        });
    }

    if (imagen2Input) {
        imagen2Input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                previsualizarImagen(file, modalId);
                event.target.value = ''; // Limpiar el input
            }
        });
    }

    // Limpiar cuando se cierre el modal
    $('#ModalEditar{{ $h->id }}').on('hidden.bs.modal', function () {
        // No limpiar los arrays para permitir guardar, pero puedes resetear si es necesario
        console.log('Modal cerrado:', modalId);
    });
});
</script>
