<form id="form-historico-{{ $h->id }}" action="{{ route('flota.update_historico', $h->id) }}" method="post"
    enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="modal fade" id="ModalEditar{{ $h->id }}" tabindex="-1" data-backdrop="false" role="dialog"
        aria-hidden="true">
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
                                    <input type="file" name="archivo" class="form-control"
                                        accept=".pdf,.doc,.docx,.xlsx,.zip,.rar">
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
                                <div id="imagenes-actuales-{{ $h->id }}" style="display: flex; flex-wrap: wrap;">
                                    @if (is_array($h->rutas_imagenes))
                                        @foreach ($h->rutas_imagenes as $ruta)
                                            <div style="position: relative; margin-right: 10px;"
                                                data-ruta="{{ $ruta }}">
                                                <a href="{{ asset($ruta) }}" target="_blank">
                                                    <img src="{{ asset($ruta) }}" alt="Imagen existente"
                                                        style="width: 100px; height: auto;">
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    style="position: absolute; top: 0; right: 0;"
                                                    onclick="eliminarImagen('{{ $ruta }}', '{{ $h->id }}')">X</button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12">
                            <div class="form-group">
                                <strong>Observaciones</strong>
                                <textarea name="observaciones" id="observaciones{{ $h->id }}" class="form-control" style="min-height: 200px;">{{ $h->observaciones }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <button type="button" class="btn btn-success" onclick="guardar('{{ $h->id }}')">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    let imagenesActuales = @json($h->rutas_imagenes ?? []);
    let nuevasImagenes = [];

    // Agregar nuevas imágenes a memoria
    document.getElementById('imagen1-{{ $h->id }}').addEventListener('change', function(event) {
        nuevasImagenes.push(event.target.files[0]);
        previsualizarImagen(event.target.files[0], '{{ $h->id }}'); // Función para previsualizar la imagen
    });

    document.getElementById('imagen2-{{ $h->id }}').addEventListener('change', function(event) {
        nuevasImagenes.push(event.target.files[0]);
        previsualizarImagen(event.target.files[0], '{{ $h->id }}');
    });

    // Función para previsualizar una imagen en el DOM
    function previsualizarImagen(imagen, id) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100px';
            img.style.height = 'auto';
            document.getElementById(`imagenes-actuales-${id}`).appendChild(img);
        }
        reader.readAsDataURL(imagen);
    }

    // Función para eliminar una imagen existente de memoria
    function eliminarImagen(ruta, id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
            imagenesActuales = imagenesActuales.filter(img => img !== ruta);
            document.querySelector(`[data-ruta="${ruta}"]`).remove();
        }
    }

    // Función para enviar el formulario sin usar jQuery
    function guardar(id) {
        let form = document.getElementById(`form-historico-${id}`);
        let formData = new FormData(form);

        // Agregar nuevas imágenes
        nuevasImagenes.forEach((imagen) => {
            formData.append('nuevas_imagenes[]', imagen);
        });

        // Agregar rutas de imágenes existentes
        formData.append('imagenes_actuales', JSON.stringify(imagenesActuales));

        console.log('formData', formData);

        // Enviar el formulario usando fetch
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(response => {
            if (response.ok) {
                alert('Histórico actualizado con éxito.');
                location.reload();
            } else {
                alert('Error al guardar el histórico.');
            }
        }).catch(error => {
            console.error('Error:', error);
        });
    }
</script>
