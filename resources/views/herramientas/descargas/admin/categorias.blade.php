@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-tags mr-2"></i>Categorías</h3>
        <div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalCrearCategoria">
                <i class="fas fa-plus"></i> Nueva categoría
            </button>
            <a href="{{ route('descargas.admin.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">
                @if($categorias->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">Orden</th>
                                    <th style="width: 60px;">Icono</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th style="width: 100px;">Archivos</th>
                                    <th style="width: 80px;">Estado</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorias as $categoria)
                                    <tr>
                                        <td class="text-center">{{ $categoria->orden }}</td>
                                        <td class="text-center">
                                            <i class="{{ $categoria->icono }} fa-lg" style="color: {{ $categoria->color }}"></i>
                                        </td>
                                        <td>
                                            <strong>{{ $categoria->nombre }}</strong>
                                            <br><small class="text-muted">Slug: {{ $categoria->slug }}</small>
                                        </td>
                                        <td>{{ Str::limit($categoria->descripcion, 50) ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $categoria->archivos_count }}</span>
                                        </td>
                                        <td>
                                            @if($categoria->activo)
                                                <span class="badge badge-success">Activa</span>
                                            @else
                                                <span class="badge badge-danger">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar"
                                                    data-id="{{ $categoria->id }}"
                                                    data-nombre="{{ $categoria->nombre }}"
                                                    data-descripcion="{{ $categoria->descripcion }}"
                                                    data-icono="{{ $categoria->icono }}"
                                                    data-color="{{ $categoria->color }}"
                                                    data-orden="{{ $categoria->orden }}"
                                                    data-activo="{{ $categoria->activo }}"
                                                    data-toggle="modal" data-target="#modalEditarCategoria">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if($categoria->archivos_count == 0)
                                                <form action="{{ route('descargas.admin.categorias.destroy', $categoria) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay categorías creadas.</p>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalCrearCategoria">
                            <i class="fas fa-plus"></i> Crear primera categoría
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Modal Crear Categoría --}}
<div class="modal fade" id="modalCrearCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('descargas.admin.categorias.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva categoría</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Icono (FontAwesome)</label>
                                <input type="text" name="icono" class="form-control" value="fas fa-folder" placeholder="fas fa-folder">
                                <small class="form-text text-muted">Ej: fas fa-file-pdf, fas fa-image</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Color</label>
                                <input type="color" name="color" class="form-control" value="#6c757d">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" name="orden" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Categoría --}}
<div class="modal fade" id="modalEditarCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditar" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar categoría</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Icono (FontAwesome)</label>
                                <input type="text" name="icono" id="edit_icono" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Color</label>
                                <input type="color" name="color" id="edit_color" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" name="orden" id="edit_orden" class="form-control">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" id="edit_activo" class="custom-control-input" value="1">
                            <label class="custom-control-label" for="edit_activo">Activa</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.getElementById('formEditar').action = `/descargas/admin/categorias/${id}`;
        document.getElementById('edit_nombre').value = this.dataset.nombre;
        document.getElementById('edit_descripcion').value = this.dataset.descripcion || '';
        document.getElementById('edit_icono').value = this.dataset.icono;
        document.getElementById('edit_color').value = this.dataset.color;
        document.getElementById('edit_orden').value = this.dataset.orden;
        document.getElementById('edit_activo').checked = this.dataset.activo === '1';
    });
});
</script>
@endpush
