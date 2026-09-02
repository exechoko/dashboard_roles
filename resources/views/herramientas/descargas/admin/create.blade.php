@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-upload mr-2"></i>Subir archivos</h3>
        <a href="{{ route('descargas.admin.archivos') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('descargas.admin.store') }}" method="POST" enctype="multipart/form-data" id="formUpload">
                    @csrf

                    <div class="form-group">
                        <label>Archivos *</label>
                        <div class="dropzone-custom" id="dropzoneArea">
                            <div class="dropzone-content text-center py-5">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2"><strong>Arrastra archivos aquí</strong></p>
                                <p class="text-muted mb-3">o haz clic para seleccionar</p>
                                <input type="file" name="archivos[]" id="fileInput" multiple class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-folder-open"></i> Seleccionar archivos
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Se aceptan todos los tipos de archivos.
                            Tamaño máximo: {{ number_format(config('descargas.tamano_maximo_kb') / 1024) }} MB por archivo.
                        </small>
                    </div>

                    <div id="archivosConfig" class="mt-4"></div>

                    <div id="accionesGlobales" class="mt-3" style="display: none;">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAplicarTodos">
                            <i class="fas fa-copy"></i> Aplicar configuración del 1er archivo a todos
                        </button>
                    </div>

                    <hr>

                    <div id="uploadErrorAlert" class="alert alert-danger" style="display:none;"></div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success btn-lg" id="btnSubmit" disabled>
                            <i class="fas fa-upload"></i> Subir archivos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Template para cada archivo --}}
<template id="tplArchivoConfig">
    <div class="card mb-3 archivo-config-card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div class="flex-grow-1 mr-3">
                <div>
                    <i class="fas fa-file text-muted mr-2"></i>
                    <strong class="archivo-nombre"></strong>
                    <small class="text-muted ml-2 archivo-tamano"></small>
                </div>
                <div class="archivo-progress-wrap mt-2" style="display:none;">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary archivo-progress-bar" role="progressbar" style="width:0%;"></div>
                    </div>
                    <small class="text-muted archivo-progress-label"></small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remover">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Categoría *</label>
                        <select class="form-control form-control-sm config-categoria" required>
                            <option value="">Seleccionar...</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Expiración (días)</label>
                        <input type="number" class="form-control form-control-sm config-expira" min="1" placeholder="Sin expiración">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Descripción</label>
                        <input type="text" class="form-control form-control-sm config-descripcion" placeholder="Opcional">
                    </div>
                </div>
            </div>
            <div class="form-group mb-2">
                <label class="small font-weight-bold">Roles que pueden descargar</label>
                <div class="row">
                    @foreach($roles as $rol)
                        <div class="col-md-4 col-sm-6">
                            <div class="custom-control custom-checkbox custom-control-sm">
                                <input type="checkbox" value="{{ $rol->id }}" class="custom-control-input config-rol" id="rol_{{ $rol->id }}__INDEX__">
                                <label class="custom-control-label small" for="rol_{{ $rol->id }}__INDEX__">{{ $rol->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="form-group mb-2">
                <label class="small font-weight-bold">Usuarios específicos</label>
                <select class="form-control form-control-sm config-usuarios" multiple data-placeholder="Seleccionar usuarios...">
                    <option value="">Cargando usuarios...</option>
                </select>
                <small class="form-text text-muted">Se puede compartir por rol, por usuarios específicos, o ambos — pero hace falta elegir al menos uno.</small>
            </div>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input config-destacado" id="destacado__INDEX__">
                <label class="custom-control-label small" for="destacado__INDEX__">Destacado</label>
            </div>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input config-notificar" id="notificar__INDEX__" checked>
                <label class="custom-control-label small" for="notificar__INDEX__">Notificar por email a los usuarios/roles con acceso</label>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('fileInput');
const dropzoneArea = document.getElementById('dropzoneArea');
const archivosConfig = document.getElementById('archivosConfig');
const accionesGlobales = document.getElementById('accionesGlobales');
const btnSubmit = document.getElementById('btnSubmit');
const tplArchivoConfig = document.getElementById('tplArchivoConfig');
let archivos = [];
let usuariosDisponibles = [];

// Cargar usuarios al iniciar
fetch('{{ route("usuarios.json") }}')
    .then(response => response.json())
    .then(data => {
        usuariosDisponibles = data;
    })
    .catch(error => {
        console.error('Error cargando usuarios:', error);
    });

// Event listeners para drag & drop
dropzoneArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzoneArea.classList.add('dragover');
});

dropzoneArea.addEventListener('dragleave', () => {
    dropzoneArea.classList.remove('dragover');
});

dropzoneArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzoneArea.classList.remove('dragover');
    agregarArchivos(e.dataTransfer.files);
});

dropzoneArea.addEventListener('click', (e) => {
    if (e.target === dropzoneArea || e.target.closest('.dropzone-content')) {
        fileInput.click();
    }
});

fileInput.addEventListener('change', (e) => {
    agregarArchivos(e.target.files);
});

function agregarArchivos(files) {
    for (let file of files) {
        archivos.push(file);
    }
    actualizarLista();
}

function actualizarLista() {
    archivosConfig.innerHTML = '';

    if (archivos.length === 0) {
        btnSubmit.disabled = true;
        accionesGlobales.style.display = 'none';
        return;
    }

    btnSubmit.disabled = false;
    accionesGlobales.style.display = archivos.length > 1 ? 'block' : 'none';

    archivos.forEach((file, index) => {
        const clone = tplArchivoConfig.content.cloneNode(true);
        const card = clone.querySelector('.archivo-config-card');
        
        card.querySelector('.archivo-nombre').textContent = file.name;
        card.querySelector('.archivo-tamano').textContent = '(' + formatSize(file.size) + ')';
        
        // Actualizar IDs de checkboxes de roles
        card.querySelectorAll('.config-rol').forEach((checkbox, i) => {
            checkbox.id = checkbox.id.replace('__INDEX__', index);
            const label = card.querySelectorAll('.custom-control-label')[i];
            if (label) label.setAttribute('for', checkbox.id);
        });
        
        // Actualizar ID de checkbox destacado
        card.querySelector('.config-destacado').id = card.querySelector('.config-destacado').id.replace('__INDEX__', index);
        card.querySelector('.config-destacado').nextElementSibling.setAttribute('for',
            card.querySelector('.config-destacado').id
        );

        // Actualizar ID de checkbox notificar
        card.querySelector('.config-notificar').id = card.querySelector('.config-notificar').id.replace('__INDEX__', index);
        card.querySelector('.config-notificar').nextElementSibling.setAttribute('for',
            card.querySelector('.config-notificar').id
        );

        // Inicializar selector de usuarios
        const selectUsuarios = card.querySelector('.config-usuarios');
        selectUsuarios.innerHTML = '<option value="">Seleccionar usuarios...</option>';
        usuariosDisponibles.forEach(usuario => {
            const option = document.createElement('option');
            option.value = usuario.id;
            option.textContent = usuario.name + ' (' + usuario.email + ')';
            selectUsuarios.appendChild(option);
        });
        
        // Inicializar Select2 si está disponible
        if ($.fn.select2) {
            $(selectUsuarios).select2({
                placeholder: 'Seleccionar usuarios...',
                allowClear: true,
                width: '100%'
            });
        }
        
        card.querySelector('.btn-remover').addEventListener('click', () => {
            archivos.splice(index, 1);
            actualizarLista();
        });
        
        card.dataset.index = index;
        archivosConfig.appendChild(clone);
    });

    const dataTransfer = new DataTransfer();
    archivos.forEach(file => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
}

// Botón "Aplicar a todos"
document.getElementById('btnAplicarTodos').addEventListener('click', () => {
    const primera = archivosConfig.querySelector('.archivo-config-card');
    if (!primera) return;

    const categoria = primera.querySelector('.config-categoria').value;
    const expira = primera.querySelector('.config-expira').value;
    const descripcion = primera.querySelector('.config-descripcion').value;
    const destacado = primera.querySelector('.config-destacado').checked;
    const notificar = primera.querySelector('.config-notificar').checked;
    const rolesCheckeados = [];
    primera.querySelectorAll('.config-rol:checked').forEach(cb => rolesCheckeados.push(cb.value));
    const usuariosSeleccionados = Array.from(primera.querySelector('.config-usuarios').selectedOptions)
        .map(opt => opt.value)
        .filter(v => v !== '');

    const cards = archivosConfig.querySelectorAll('.archivo-config-card');
    cards.forEach((card, i) => {
        if (i === 0) return;
        card.querySelector('.config-categoria').value = categoria;
        card.querySelector('.config-expira').value = expira;
        card.querySelector('.config-descripcion').value = descripcion;
        card.querySelector('.config-destacado').checked = destacado;
        card.querySelector('.config-notificar').checked = notificar;
        card.querySelectorAll('.config-rol').forEach(cb => {
            cb.checked = rolesCheckeados.includes(cb.value);
        });
        
        // Actualizar usuarios
        const selectUsuarios = card.querySelector('.config-usuarios');
        Array.from(selectUsuarios.options).forEach(opt => {
            opt.selected = usuariosSeleccionados.includes(opt.value);
        });
        if ($.fn.select2) {
            $(selectUsuarios).trigger('change');
        }
    });
});

// Submit del formulario: se envía por XHR (no submit nativo) para poder
// mostrar progreso de subida. Como todos los archivos van en un único
// request multipart, el progreso por archivo se aproxima repartiendo los
// bytes totales enviados según el tamaño de cada archivo, en el mismo
// orden en que se agregan al FormData (los navegadores arman el body
// multipart respetando ese orden, así que la aproximación es muy precisa;
// el único margen de error es el pequeño overhead de cada parte del
// multipart, despreciable frente al tamaño real de los archivos).
document.getElementById('formUpload').addEventListener('submit', function(e) {
    e.preventDefault();

    const cards = Array.from(archivosConfig.querySelectorAll('.archivo-config-card'));
    const formData = new FormData();
    formData.append('_token', this.querySelector('input[name="_token"]').value);

    cards.forEach((card, index) => {
        const prefix = 'archivos_config[' + index + ']';
        formData.append(prefix + '[categoria_id]', card.querySelector('.config-categoria').value);
        formData.append(prefix + '[expira_dias]', card.querySelector('.config-expira').value);
        formData.append(prefix + '[descripcion]', card.querySelector('.config-descripcion').value);
        formData.append(prefix + '[destacado]', card.querySelector('.config-destacado').checked ? '1' : '0');
        formData.append(prefix + '[notificar]', card.querySelector('.config-notificar').checked ? '1' : '0');

        card.querySelectorAll('.config-rol:checked').forEach(cb => {
            formData.append(prefix + '[roles][]', cb.value);
        });

        Array.from(card.querySelector('.config-usuarios').selectedOptions)
            .map(opt => opt.value)
            .filter(v => v !== '')
            .forEach(userId => formData.append(prefix + '[usuarios][]', userId));
    });

    // Límites acumulados de bytes (mismo orden que "archivos", que es el
    // orden en que se agregan al FormData a continuación).
    const acumulados = [0];
    archivos.forEach(file => acumulados.push(acumulados[acumulados.length - 1] + file.size));
    archivos.forEach(file => formData.append('archivos[]', file));

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    document.getElementById('uploadErrorAlert').style.display = 'none';
    cards.forEach(card => {
        card.querySelector('.archivo-progress-wrap').style.display = 'block';
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', this.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', function (evt) {
        if (!evt.lengthComputable) return;

        const totalBytesArchivos = acumulados[acumulados.length - 1];
        if (totalBytesArchivos === 0) return;
        // evt.total incluye el overhead del multipart (config, tokens, etc.);
        // escalamos los límites acumulados a esa magnitud real.
        const escala = evt.total / totalBytesArchivos;

        cards.forEach((card, i) => {
            const inicio = acumulados[i] * escala;
            const fin = acumulados[i + 1] * escala;
            let pct;
            if (evt.loaded >= fin) pct = 100;
            else if (evt.loaded <= inicio) pct = 0;
            else pct = Math.round(((evt.loaded - inicio) / (fin - inicio)) * 100);

            const bar = card.querySelector('.archivo-progress-bar');
            const label = card.querySelector('.archivo-progress-label');
            bar.style.width = pct + '%';

            if (pct >= 100) {
                bar.classList.remove('bg-primary');
                bar.classList.add('bg-success');
                label.textContent = 'Subido ✓';
            } else if (pct > 0) {
                label.textContent = 'Subiendo... ' + pct + '%';
            } else {
                label.textContent = 'En espera...';
            }
        });
    });

    xhr.onload = function () {
        let data = null;
        try { data = JSON.parse(xhr.responseText); } catch (err) { /* respuesta no-JSON */ }

        if (xhr.status >= 200 && xhr.status < 300 && data && data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        let mensaje = 'Ocurrió un error al subir los archivos.';
        if (xhr.status === 422 && data && data.errors) {
            mensaje = Object.values(data.errors).flat().join(' ');
        } else if (data && data.message) {
            mensaje = data.message;
        }
        mostrarErrorUpload(mensaje);
    };

    xhr.onerror = function () {
        mostrarErrorUpload('No se pudo conectar con el servidor. Verificá tu conexión e intentá de nuevo.');
    };

    xhr.send(formData);
});

function mostrarErrorUpload(mensaje) {
    const alerta = document.getElementById('uploadErrorAlert');
    alerta.textContent = mensaje;
    alerta.style.display = 'block';
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = '<i class="fas fa-upload"></i> Subir archivos';
}

function formatSize(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
}
</script>
@endpush

@push('styles')
<style>
.dropzone-custom {
    border: 2px dashed #ccc;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
}
.dropzone-custom:hover,
.dropzone-custom.dragover {
    border-color: #007bff;
    background-color: #f8f9fa;
}
.archivo-config-card .card-header {
    background-color: #f8f9fa;
}
</style>
@endpush
