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

                    {{-- Dropzone --}}
                    <div class="form-group">
                        <label>Archivos *</label>
                        <div class="dropzone-custom" id="dropzoneArea">
                            <div class="dropzone-content text-center py-5">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2"><strong>Arrastra archivos aquí</strong></p>
                                <p class="text-muted mb-3">o haz clic para seleccionar</p>
                                <input type="file" name="archivos[]" id="fileInput" multiple class="d-none"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.txt,.csv">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-folder-open"></i> Seleccionar archivos
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Extensiones permitidas: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, 7Z, JPG, PNG, GIF, TXT, CSV.
                            Tamaño máximo: {{ number_format(config('descargas.tamano_maximo_kb') / 1024) }} MB por archivo.
                        </small>
                    </div>

                    {{-- Lista de archivos seleccionados --}}
                    <div id="archivosSeleccionados" class="mb-4" style="display: none;">
                        <h6>Archivos seleccionados:</h6>
                        <ul class="list-group" id="listaArchivos"></ul>
                    </div>

                    <hr>

                    {{-- Configuración común --}}
                    <h5 class="mb-3">Configuración</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Categoría *</label>
                                <select name="categoria_id" class="form-control" required>
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ $cat->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expiración (días)</label>
                                <input type="number" name="expira_dias" class="form-control" min="1" placeholder="Sin expiración">
                                <small class="form-text text-muted">Dejar vacío para sin expiración</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción opcional del archivo..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Roles que pueden descargar *</label>
                        <div class="row">
                            @foreach($roles as $rol)
                                <div class="col-md-4 col-sm-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="roles[]" value="{{ $rol->id }}" class="custom-control-input" id="rol_{{ $rol->id }}">
                                        <label class="custom-control-label" for="rol_{{ $rol->id }}">{{ $rol->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <small class="form-text text-muted">Selecciona al menos un rol que pueda descargar estos archivos.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="destacado" value="0">
                            <input type="checkbox" name="destacado" class="custom-control-input" id="destacado" value="1">
                            <label class="custom-control-label" for="destacado">Marcar como destacado</label>
                        </div>
                        <small class="form-text text-muted">Los archivos destacados aparecen primero en la lista.</small>
                    </div>

                    <hr>

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
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('fileInput');
const dropzoneArea = document.getElementById('dropzoneArea');
const archivosSeleccionados = document.getElementById('archivosSeleccionados');
const listaArchivos = document.getElementById('listaArchivos');
const btnSubmit = document.getElementById('btnSubmit');
let archivos = [];

// Drag and drop
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
    if (archivos.length === 0) {
        archivosSeleccionados.style.display = 'none';
        btnSubmit.disabled = true;
        return;
    }

    archivosSeleccionados.style.display = 'block';
    btnSubmit.disabled = false;
    listaArchivos.innerHTML = '';

    archivos.forEach((file, index) => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `
            <div>
                <i class="fas fa-file text-muted mr-2"></i>
                <strong>${file.name}</strong>
                <small class="text-muted ml-2">(${formatSize(file.size)})</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerArchivo(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        listaArchivos.appendChild(li);
    });

    // Actualizar input file
    const dataTransfer = new DataTransfer();
    archivos.forEach(file => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
}

function removerArchivo(index) {
    archivos.splice(index, 1);
    actualizarLista();
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
</style>
@endpush
