<form id="form-historico-{{ $h->id }}" action="{{ route('flota.update_historico', $h->id) }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
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
                                    <input type="file" id="imagen1-{{ $h->id }}" class="form-control" accept="image/*;capture=camera*">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="imagen2">Imagen 2</label>
                                    <input type="file" id="imagen2-{{ $h->id }}" class="form-control" accept="image/*;capture=camera*">
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
                                            <div style="position: relative; margin-right: 10px;" data-ruta="{{ $ruta }}">
                                                <a href="{{ asset($ruta) }}" target="_blank">
                                                    <img src="{{ asset($ruta) }}" alt="Imagen existente" style="width: 100px; height: auto;">
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 0; right: 0;" onclick="eliminarImagen('{{ $ruta }}', '{{ $h->id }}')">X</button>
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
    let imagenesActuales_{{ $h->id }} = @json($h->rutas_imagenes ?? []);
    let nuevasImagenes = {}; // Objeto global para almacenar imágenes por ID

    function initializeNuevasImagenes(id) {
        console.log('initializeNuevasImagenes', id);

        // Inicializa el array de nuevas imágenes solo si no existe
        if (!nuevasImagenes[id]) {
            nuevasImagenes[id] = [];
        }
    }

    // Llama a esta función con el ID correspondiente en cada modal
    initializeNuevasImagenes('{{ $h->id }}');

    // Función para previsualizar la imagen
    function previsualizarImagen(file, id) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgElement = document.createElement('div');
            imgElement.style.position = 'relative';
            imgElement.style.marginRight = '10px';

            imgElement.innerHTML = `
                <img src="${e.target.result}" alt="Imagen nueva" style="width: 100px; height: auto;">
                <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 0; right: 0;"
                    onclick="eliminarNuevaImagen('${file.name}', '${id}')">X</button>
            `;

            imgElement.setAttribute('data-nueva', file.name); // Agregar atributo para eliminar
            document.getElementById(`imagenes-actuales-${id}`).appendChild(imgElement);
        };
        reader.readAsDataURL(file);
    }

    // Eliminar nueva imagen de memoria
    function eliminarNuevaImagen(nombreArchivo, id) {
        nuevasImagenes[id] = nuevasImagenes[id].filter(imagen => imagen.name !== nombreArchivo);
        const imgElement = document.querySelector(`[data-nueva="${nombreArchivo}"]`);
        if (imgElement) {
            imgElement.remove();
        }
    }

    // Agregar nuevas imágenes a memoria y previsualizarlas
    document.getElementById('imagen1-{{ $h->id }}').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            nuevasImagenes['{{ $h->id }}'].push(file);
            previsualizarImagen(file, '{{ $h->id }}');
            event.target.value = ''; // Limpiar el input después de agregar
        }
    });

    document.getElementById('imagen2-{{ $h->id }}').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            nuevasImagenes['{{ $h->id }}'].push(file);
            previsualizarImagen(file, '{{ $h->id }}');
            event.target.value = ''; // Limpiar el input después de agregar
        }
    });

    // Función para eliminar una imagen existente de memoria
    function eliminarImagen(ruta, id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
            imagenesActuales_{{ $h->id }} = imagenesActuales_{{ $h->id }}.filter(img => img !== ruta);
            document.querySelector(`[data-ruta="${ruta}"]`).remove();
        }
    }

    // Función para enviar el formulario usando jQuery
    function guardar(id) {
        let form = $(`#form-historico-${id}`)[0];
        let formData = new FormData(form);

        // Agregar nuevas imágenes
        nuevasImagenes[id].forEach((imagen) => {
            formData.append('nuevas_imagenes[]', imagen);
        });

        // Agregar rutas de imágenes existentes
        formData.append('imagenes_actuales', JSON.stringify(imagenesActuales_{{ $h->id }}));

        // Enviar el formulario usando jQuery
        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('Registro actualizado exitosamente.');
                location.reload(); // Recargar la página o actualizar la vista
            },
            error: function(xhr, status, error) {
                alert('Error al actualizar el registro: ' + xhr.responseText);
            }
        });
    }
</script>

