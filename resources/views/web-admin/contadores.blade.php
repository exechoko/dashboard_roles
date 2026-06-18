@extends('layouts.app')

@php
    $escalares = [
        'Institucional' => [
            'anosServicio'  => 'Años de servicio',
            'funcionarios'  => 'Funcionarios',
        ],
        'Recursos / equipamiento' => [
            'camaras'            => 'Cámaras proyectadas',
            'moviles'            => 'Móviles policiales',
            'motopatrullas'      => 'Motopatrullas',
            'unidadesOperativas' => 'Unidades operativas',
        ],
        'Operativo' => [
            'llamadasPromedio' => 'Llamadas al 911 por día (promedio)',
        ],
        'Violencia de Género' => [
            'dispositivosDuales'  => 'Dispositivos duales (tobilleras)',
            'usuariosBotonPanico' => 'Usuarios del botón de pánico',
        ],
    ];

    $series = [
        'armasPorMes'     => 'Armas de fuego secuestradas',
        'vehiculosPorMes' => 'Vehículos recuperados',
        'motosPorMes'     => 'Motovehículos recuperados',
    ];

    $val = fn ($clave, $default = 0) => old($clave, $datos[$clave] ?? $default);
@endphp

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Administrar Web — Contadores</h3>
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalTutorial">
                <i class="fas fa-question-circle"></i> Tutorial
            </button>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>¡Revise los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <span class="badge badge-light">{{ $error }}</span>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Al guardar, los cambios se aplican automáticamente en la web
                <strong>div911.stper.com.ar</strong> (índice, estadísticas e historia). Los totales anuales se
                calculan solos a partir de las series mensuales.
            </div>

            <form action="{{ route('web-admin.contadores.update') }}" method="POST">
                @csrf
                @method('PUT')

                @foreach ($escalares as $grupo => $campos)
                    <div class="card">
                        <div class="card-header"><h4>{{ $grupo }}</h4></div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($campos as $clave => $etiqueta)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group">
                                            <label for="{{ $clave }}">{{ $etiqueta }}</label>
                                            <input type="number" min="0" step="1"
                                                   class="form-control @error($clave) is-invalid @enderror"
                                                   id="{{ $clave }}" name="{{ $clave }}"
                                                   value="{{ $val($clave) }}" required>
                                            @error($clave)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="card">
                    <div class="card-header"><h4>Resultados 2026 por mes</h4></div>
                    <div class="card-body">
                        <p class="text-muted">Cargá un valor por mes. Los totales del año se suman automáticamente en la web.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Indicador</th>
                                        @foreach ($meses as $mes)
                                            <th class="text-center">{{ $mes }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($series as $clave => $etiqueta)
                                        @php $valores = old($clave, $datos[$clave] ?? array_fill(0, count($meses), 0)); @endphp
                                        <tr>
                                            <td class="align-middle"><strong>{{ $etiqueta }}</strong></td>
                                            @foreach ($meses as $i => $mes)
                                                <td>
                                                    <input type="number" min="0" step="1"
                                                           class="form-control text-center @error($clave.'.'.$i) is-invalid @enderror"
                                                           name="{{ $clave }}[]"
                                                           value="{{ $valores[$i] ?? 0 }}" required>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar y publicar
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Modal Tutorial --}}
    <div class="modal fade" id="modalTutorial" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-graduation-cap"></i> Cómo administrar la web</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <h6 class="text-primary"><i class="fas fa-sort-numeric-up"></i> Modificar los contadores</h6>
                    <ol>
                        <li>Editá los números en cada sección (Institucional, Recursos, Operativo, Violencia de Género).</li>
                        <li>En <strong>Resultados 2026 por mes</strong>, cargá el valor de cada mes para armas, vehículos y motos. Los <em>totales del año</em> se calculan solos.</li>
                        <li>Tocá <strong>Guardar y publicar</strong>. Los cambios aparecen al instante en
                            <strong>div911.stper.com.ar</strong> (puede que tengas que refrescar con Ctrl+F5).</li>
                    </ol>

                    <hr>

                    <h6 class="text-primary"><i class="fas fa-font"></i> Editar textos <span class="badge badge-secondary">próximamente</span></h6>
                    <p class="mb-2 text-muted">Desde el panel vas a poder editar títulos y descripciones de las páginas. (En desarrollo.)</p>

                    <h6 class="text-primary"><i class="fas fa-newspaper"></i> Crear una noticia <span class="badge badge-secondary">próximamente</span></h6>
                    <ol class="text-muted">
                        <li>Entrar a <strong>Noticias → Nueva noticia</strong>.</li>
                        <li>Completar título, bajada y cuerpo del texto.</li>
                        <li>Subir una o más imágenes.</li>
                        <li>Marcar <strong>Publicada</strong> y guardar para que aparezca en la web.</li>
                    </ol>

                    <div class="alert alert-light border mb-0">
                        <i class="fas fa-shield-alt text-success"></i>
                        Solo los usuarios con los permisos correspondientes pueden ver y usar estas opciones.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>
@endsection
