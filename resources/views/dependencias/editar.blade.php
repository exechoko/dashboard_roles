@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Dependencia</h3>
            <div class="alert alert-info ml-3">
                Editando: {{ $dependencia->nombre }}
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información de la Dependencia</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('dependencias.update', $dependencia->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <!-- Tipo (Solo informativo, no editable) -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tipo"><strong>Tipo:</strong></label>
                                            <input type="text" class="form-control" value="{{ ucfirst($dependencia->tipo) }}" readonly>
                                            <small class="text-muted">El tipo de dependencia no se puede modificar</small>
                                        </div>
                                    </div>

                                    <!-- Parent (Dependencia Padre) -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_id">Depende de:</label>
                                            <select name="parent_id" id="parent_id" class="form-control select2">
                                                <option value="">Sin dependencia padre</option>
                                                @foreach($posiblesPadres as $padre)
                                                    <option value="{{ $padre->id }}"
                                                        {{ $dependencia->parent_id == $padre->id ? 'selected' : '' }}>
                                                        {{ $padre->nombre }} ({{ ucfirst($padre->tipo) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Nombre -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre: <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="nombre"
                                                   id="nombre"
                                                   class="form-control @error('nombre') is-invalid @enderror"
                                                   value="{{ old('nombre', $dependencia->nombre) }}"
                                                   required>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Teléfono -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono:</label>
                                            <input type="text"
                                                   name="telefono"
                                                   id="telefono"
                                                   class="form-control @error('telefono') is-invalid @enderror"
                                                   value="{{ old('telefono', $dependencia->telefono) }}">
                                            @error('telefono')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Ubicación -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ubicacion">Ubicación:</label>
                                            <input type="text"
                                                   name="ubicacion"
                                                   id="ubicacion"
                                                   class="form-control @error('ubicacion') is-invalid @enderror"
                                                   value="{{ old('ubicacion', $dependencia->ubicacion) }}">
                                            @error('ubicacion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Observaciones -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones:</label>
                                            <textarea name="observaciones"
                                                      id="observaciones"
                                                      class="form-control @error('observaciones') is-invalid @enderror"
                                                      rows="3">{{ old('observaciones', $dependencia->observaciones) }}</textarea>
                                            @error('observaciones')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Actualizar Dependencia
                                            </button>
                                            <a href="{{ route('dependencias.index') }}" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral con información -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Información Actual</h4>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><strong>Tipo:</strong></label>
                                <p>
                                    <span class="badge badge-{{ $dependencia->getBadgeClass() }}">
                                        {{ ucfirst($dependencia->tipo) }}
                                    </span>
                                </p>
                            </div>

                            @if($dependencia->padre)
                                <div class="form-group">
                                    <label><strong>Actualmente depende de:</strong></label>
                                    <p>
                                        <a href="{{ route('dependencias.show', $dependencia->padre->id) }}">
                                            {{ $dependencia->padre->nombre }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ ucfirst($dependencia->padre->tipo) }}</small>
                                    </p>
                                </div>
                            @endif

                            @if($dependencia->hijos->count() > 0)
                                <div class="form-group">
                                    <label><strong>Dependencias subordinadas:</strong></label>
                                    <p class="text-info">
                                        <i class="fas fa-info-circle"></i>
                                        {{ $dependencia->hijos->count() }} dependencia(s)
                                    </p>
                                    <small class="text-muted">
                                        Al cambiar la jerarquía, las dependencias subordinadas se mantendrán asociadas.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Ayuda -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Ayuda</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-info-circle text-info"></i> El tipo de dependencia no se puede cambiar</li>
                                <li><i class="fas fa-info-circle text-info"></i> Solo puedes asignar dependencias padre válidas según la jerarquía</li>
                                <li><i class="fas fa-info-circle text-info"></i> No se pueden crear referencias circulares</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });
            $(document).on('select2:open', () => {
                let select2Field = document.querySelector('.select2-search__field');
                if (select2Field) {
                    select2Field.focus();
                }
            });
            // Confirmación antes de enviar
            $('form').on('submit', function (e) {
                return confirm('¿Está seguro de actualizar esta dependencia?');
            });
        });
    </script>
@endsection
