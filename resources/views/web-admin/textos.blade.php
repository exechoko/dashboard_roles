@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Administrar Web — Textos</h3>
            <div>
                <a href="{{ route('web-historia.index') }}" class="btn btn-info">
                    <i class="fas fa-stream"></i> Cards de Historia
                </a>
                <a href="{{ route('web-admin.contadores.edit') }}" class="btn btn-light">
                    <i class="fas fa-sort-numeric-up"></i> Contadores
                </a>
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revise los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <span class="badge badge-light">{{ $error }}</span>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Editá los textos de la web. Al guardar, los cambios se publican automáticamente en
                <strong>div911.stper.com.ar</strong> (refrescá con Ctrl+F5).
            </div>

            <form action="{{ route('web-admin.textos.update') }}" method="POST">
                @csrf
                @method('PUT')

                @foreach ($catalogoPorGrupo as $grupo)
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">
                                <i class="fas fa-file-alt text-muted mr-1"></i> {{ $grupo['label'] }}
                            </h4>
                            @isset($grupo['pagina'])
                                <span class="badge badge-info" title="Archivo de la página">
                                    <i class="fas fa-link"></i> {{ $grupo['pagina'] }}
                                </span>
                            @endisset
                        </div>
                        <div class="card-body">
                            @foreach ($grupo['textos'] as $clave => $meta)
                                <div class="form-group">
                                    <label for="t_{{ $clave }}">{{ $meta['label'] }}</label>
                                    @php $valor = old('textos.' . $clave, $valores[$clave] ?? ($meta['default'] ?? '')); @endphp
                                    @if (($meta['tipo'] ?? 'text') === 'textarea')
                                        <textarea name="textos[{{ $clave }}]" id="t_{{ $clave }}" rows="4"
                                                  class="form-control @error('textos.' . $clave) is-invalid @enderror">{{ $valor }}</textarea>
                                    @else
                                        <input type="text" name="textos[{{ $clave }}]" id="t_{{ $clave }}"
                                               class="form-control @error('textos.' . $clave) is-invalid @enderror"
                                               value="{{ $valor }}">
                                    @endif
                                    @error('textos.' . $clave) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar y publicar
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
