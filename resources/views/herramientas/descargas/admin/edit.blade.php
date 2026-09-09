@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading">
            <i class="fas fa-edit mr-2"></i>
            Editar: {{ Str::limit($archivo->nombre_original, 40) }}
        </h3>
        <a href="{{ route('descargas.admin.archivos') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('descargas.admin.update', $archivo) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label>Nombre del archivo</label>
                                <input type="text" class="form-control" value="{{ $archivo->nombre_original }}" readonly>
                                <small class="form-text text-muted">El nombre no se puede modificar. Sube un nuevo archivo para reemplazarlo.</small>
                            </div>

                            <div class="form-group">
                                <label>Categoría *</label>
                                <select name="categoria_id" class="form-control" required>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}" {{ $archivo->categoria_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3">{{ $archivo->descripcion }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Roles que pueden descargar *</label>
                                <div class="row">
                                    @foreach($roles as $rol)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="roles[]" value="{{ $rol->id }}"
                                                       class="custom-control-input" id="rol_{{ $rol->id }}"
                                                       {{ $archivo->roles->contains($rol->id) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="rol_{{ $rol->id }}">{{ $rol->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Fecha de expiración</label>
                                <input type="datetime-local" name="expira_at" class="form-control"
                                       value="{{ $archivo->expira_at ? $archivo->expira_at->format('Y-m-d\TH:i') : '' }}">
                                <small class="form-text text-muted">Dejar vacío para sin expiración</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="destacado" value="0">
                                    <input type="checkbox" name="destacado" class="custom-control-input" id="destacado" value="1"
                                           {{ $archivo->destacado ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="destacado">Marcar como destacado</label>
                                </div>
                            </div>

                            <hr>

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Info del archivo --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Información</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li><strong>Tamaño:</strong> {{ $archivo->tamano_humano }}</li>
                            <li><strong>Tipo:</strong> .{{ strtoupper($archivo->extension) }}</li>
                            <li><strong>Subido por:</strong> {{ $archivo->user->name ?? 'Sistema' }}</li>
                            <li><strong>Fecha:</strong> {{ $archivo->created_at->format('d/m/Y H:i') }}</li>
                            <li><strong>Descargas:</strong> {{ $archivo->descargas_count }}</li>
                        </ul>
                    </div>
                </div>

                {{-- Versiones --}}
                @if($archivo->versiones->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Historial de versiones</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($archivo->versiones as $version)
                                    <div class="list-group-item">
                                        <small class="text-muted">Versión {{ $version->version_numero }} - {{ $version->created_at->format('d/m/Y H:i') }}</small>
                                        <br>
                                        <small>Por: {{ $version->user->name ?? 'Sistema' }}</small>
                                        @if($version->motivo)
                                            <br><small class="text-muted">{{ $version->motivo }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
