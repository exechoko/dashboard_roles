@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema — Variables de Entorno</h3>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-light border alert-dismissible fade show">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revisá los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <div class="small">{{ $error }}</div>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Los campos de contraseña / API key se muestran en blanco: si los dejás así, el valor actual
                <strong>no se modifica</strong>. Cada guardado hace un respaldo automático del archivo .env.
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                @foreach ($grupos as $grupoKey => $grupo)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#tab-{{ $grupoKey }}">
                            <i class="{{ $grupo['icono'] }}"></i> {{ $grupo['titulo'] }}
                        </a>
                    </li>
                @endforeach
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-avanzado">
                        <i class="fas fa-code"></i> Avanzado
                    </a>
                </li>
            </ul>

            <form action="{{ route('configuracion.env.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="tab-content">
                    @foreach ($grupos as $grupoKey => $grupo)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $grupoKey }}">
                            <div class="card">
                                <div class="card-body">
                                    @if ($grupoKey === $grupoCritico)
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{ $grupo['ayuda'] ?? 'Grupo crítico: verificá dos veces antes de guardar.' }}
                                            @if (!$puedeCritico)
                                                <strong>No tenés permiso para editar este grupo.</strong>
                                            @endif
                                        </div>
                                    @endif

                                    @foreach ($grupo['claves'] as $clave => $meta)
                                        @php $deshabilitado = $grupoKey === $grupoCritico && !$puedeCritico; @endphp
                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label" for="e_{{ $clave }}">
                                                {{ $meta['label'] }}
                                                <br><small class="text-muted">{{ $clave }}</small>
                                            </label>
                                            <div class="col-sm-8">
                                                @include('configuracion._campo', ['clave' => $clave, 'meta' => $meta, 'valor' => $valores[$clave] ?? '', 'idPrefijo' => 'e_', 'deshabilitado' => $deshabilitado])
                                                @isset($meta['ayuda'])
                                                    <small class="form-text text-muted">{{ $meta['ayuda'] }}</small>
                                                @endisset
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="tab-pane fade" id="tab-avanzado">
                        <div class="card">
                            <div class="card-body">
                                <p class="text-muted">
                                    Resto de las claves del archivo .env, sin catalogar. Los valores sensibles
                                    (contraseñas, tokens, API keys) se detectan automáticamente por el nombre y se
                                    muestran en blanco.
                                </p>
                                @forelse ($avanzado as $clave => $valor)
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label" for="a_{{ $clave }}">{{ $clave }}</label>
                                        <div class="col-sm-8">
                                            @php $meta = ['tipo' => \App\Support\ConfiguracionCatalogo::esSensible($clave) ? 'password' : 'text']; @endphp
                                            @include('configuracion._campo', ['clave' => $clave, 'meta' => $meta, 'valor' => $valor, 'idPrefijo' => 'a_', 'deshabilitado' => false])
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No hay claves adicionales.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
