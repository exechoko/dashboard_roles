@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar {{ ucfirst($dependencia->tipo) }}</h3>
            <div class="alert alert-info ml-3">
                Editando: {{ $dependencia->nombre }}
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-dark alert-dismissible fade show" role="alert">
                                    <strong>¡Revise los campos!</strong>
                                    @foreach ($errors->all() as $error)
                                        <span class="badge badge-danger">{{ $error }}</span>
                                    @endforeach
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('dependencias.update', $dependencia->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="tipo">Tipo de Dependencia</label>
                                            <input type="text" class="form-control" value="{{ ucfirst($dependencia->tipo) }}" readonly>
                                            <small class="form-text text-muted">El tipo de dependencia no se puede modificar.</small>
                                        </div>
                                    </div>

                                    @if($posiblesPadres->count() > 0)
                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="parent_id">Depende de</label>
                                            <select name="parent_id" id="parent_id" class="form-control select2">
                                                <option value="">Sin dependencia padre</option>
                                                @foreach($posiblesPadres as $padre)
                                                    <option value="{{ $padre->id }}"
                                                        {{ old('parent_id', $dependencia->parent_id) == $padre->id ? 'selected' : '' }}>
                                                        {{ $padre->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                Actualmente depende de: {{ $dependencia->dependeDe() }}
                                            </small>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="nombre">Nombre de la Dependencia *</label>
                                            @php
                                                // Remover el prefijo para mostrar solo el nombre base
                                                $prefijos = [
                                                    'direccion' => 'Dirección ',
                                                    'departamental' => 'Departamental ',
                                                    'division' => 'División ',
                                                    'comisaria' => 'Comisaría ',
                                                    'seccion' => 'Sección ',
                                                    'destacamento' => 'Destacamento '
                                                ];
                                                $prefijo = $prefijos[$dependencia->tipo] ?? '';
                                                $nombreBase = $prefijo && stripos($dependencia->nombre, $prefijo) === 0
                                                    ? substr($dependencia->nombre, strlen($prefijo))
                                                    : $dependencia->nombre;
                                            @endphp
                                            <input type="text" name="nombre" id="nombre" class="form-control"
                                                   value="{{ old('nombre', $nombreBase) }}" required>
                                            <small class="form-text text-muted">
                                                El prefijo "{{ rtrim($prefijo) }}" se agregará automáticamente.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono</label>
                                            <input type="text" name="telefono" id="telefono" class="form-control"
                                                   value="{{ old('telefono', $dependencia->telefono) }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-6">
                                        <div class="form-group">
                                            <label for="ubicacion">Ubicación</label>
                                            <input type="text" name="ubicacion" id="ubicacion" class="form-control"
                                                   value="{{ old('ubicacion', $dependencia->ubicacion) }}">
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="observaciones">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" id="observaciones"
                                                      style="height: 100px">{{ old('observaciones', $dependencia->observaciones) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar
                                        </button>
                                        <a href="{{ route('dependencias.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        @if($dependencia->id)
                                            <a href="{{ route('dependencias.show', $dependencia->id) }}" class="btn btn-info">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </form>

                            <!-- Información adicional -->
                            @if($dependencia->hijos->count() > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-sitemap"></i> Dependencias Subordinadas
                                                <span class="badge badge-info">{{ $dependencia->hijos->count() }}</span>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Atención:</strong> Esta dependencia tiene {{ $dependencia->hijos->count() }}
                                                dependencia(s) subordinada(s). Los cambios en la jerarquía pueden afectar estas dependencias.
                                            </div>
                                            <div class="row">
                                                @foreach($dependencia->hijos as $hijo)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="border rounded p-2">
                                                            <strong>{{ $hijo->nombre }}</strong><br>
                                                            <small class="text-muted">{{ ucfirst($hijo->tipo) }}</small>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Seleccionar dependencia padre'
            });
        });
    </script>
@endsection
