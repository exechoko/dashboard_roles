@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Alertas de Video &mdash; Dominios</h3>
            <div id="alertaDominiosAccionesWrap">
                <button type="button" class="btn btn-outline-info" id="btnVerTutorialAlertaDominios" onclick="iniciarTutorialPagina()">
                    <i class="fas fa-question-circle"></i> Ver tutorial
                </button>
                @can('crear-alerta-dominio')
                    <a href="{{ route('alertas-video.dominios.importar') }}" class="btn btn-info">
                        <i class="fas fa-upload"></i> Importar
                    </a>
                    <a href="{{ route('alertas-video.dominios.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Cargar Dominio
                    </a>
                @endcan
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

            @if ($ultimosCargados->isNotEmpty())
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="small text-muted mb-1"><i class="fas fa-history"></i> Últimos 5 dominios cargados</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 small">
                                <thead>
                                    <tr class="text-muted">
                                        <th class="py-1">Dominio</th>
                                        <th class="py-1">Marca / Modelo</th>
                                        <th class="py-1">Hecho relacionado</th>
                                        <th class="py-1 text-right">Cargado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ultimosCargados as $ultimo)
                                        <tr>
                                            <td class="py-1"><strong>{{ $ultimo->dominio }}</strong></td>
                                            <td class="py-1 text-muted">{{ trim(($ultimo->marca ?? '') . ' ' . ($ultimo->modelo ?? '')) ?: '-' }}</td>
                                            <td class="py-1 text-muted text-truncate" style="max-width: 260px;">{{ $ultimo->motivo ?? '-' }}</td>
                                            <td class="py-1 text-muted text-right">{{ $ultimo->created_at?->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @php $activoFiltro = request('activo', '1'); @endphp
            <div class="row" id="alertaDominiosStatsWrap">
                @php $filtrosVigentes = ['busqueda' => request('busqueda'), 'fecha_desde' => request('fecha_desde'), 'fecha_hasta' => request('fecha_hasta')]; @endphp
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.dominios.index', $filtrosVigentes + ['activo' => 'todos']) }}"
                       class="alerta-stat-card bg-slate {{ $activoFiltro === 'todos' ? 'active' : '' }}">
                        <div class="small">Total</div>
                        <div class="h3 mb-0">{{ $contadores['total'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.dominios.index', $filtrosVigentes + ['activo' => '1']) }}"
                       class="alerta-stat-card bg-green {{ $activoFiltro === '1' ? 'active' : '' }}">
                        <div class="small">Activos</div>
                        <div class="h3 mb-0">{{ $contadores['activos'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.dominios.index', $filtrosVigentes + ['activo' => '0']) }}"
                       class="alerta-stat-card bg-red {{ $activoFiltro === '0' ? 'active' : '' }}">
                        <div class="small">Inactivos</div>
                        <div class="h3 mb-0">{{ $contadores['inactivos'] }}</div>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('alertas-video.dominios.index') }}" class="mb-3" id="alertaDominiosBuscarForm">
                        <input type="hidden" name="activo" value="{{ $activoFiltro }}">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar por dominio, marca, modelo o motivo..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="rangoFechasDominios" class="form-control" autocomplete="off" readonly
                                           placeholder="Filtrar por fecha de carga"
                                           value="{{ (request('fecha_desde') && request('fecha_hasta')) ? \Carbon\Carbon::parse(request('fecha_desde'))->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse(request('fecha_hasta'))->format('d/m/Y') : '' }}">
                                    <input type="hidden" name="fecha_desde" id="fechaDesdeDominios" value="{{ request('fecha_desde') }}">
                                    <input type="hidden" name="fecha_hasta" id="fechaHastaDominios" value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <div class="col-md-3 mt-2 mt-md-0">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('alertas-video.dominios.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive" id="alertaDominiosTabla">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Dominio</th>
                                    <th>Marca / Modelo</th>
                                    <th>Motivo</th>
                                    <th>Fecha de carga</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dominios as $dominio)
                                    <tr>
                                        <td>
                                            <strong>{{ $dominio->dominio }}</strong>
                                            @if ($dominio->parcial)
                                                <span class="badge badge-alerta-no" title="Patente incompleta">Parcial</span>
                                            @endif
                                        </td>
                                        <td>{{ trim(($dominio->marca ?? '') . ' ' . ($dominio->modelo ?? '')) ?: '-' }}</td>
                                        <td class="text-truncate" style="max-width: 320px;">{{ $dominio->motivo ?? '-' }}</td>
                                        <td>{{ $dominio->fecha_carga?->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-alerta-{{ $dominio->activo ? 'activo' : 'inactivo' }}">
                                                {{ $dominio->estado_label }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('alertas-video.dominios.show', $dominio) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-alerta-dominio')
                                                <a href="{{ route('alertas-video.dominios.edit', $dominio) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No hay dominios registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $dominios->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('alertas-video._styles')
@endpush

@push('scripts')
<script>
$(function () {
    var desdeInicial = $('#fechaDesdeDominios').val();
    var hastaInicial = $('#fechaHastaDominios').val();

    var opciones = {
        autoUpdateInput: false,
        opens: 'right',
        drops: 'down',
        ranges: {
            'Hoy':             [moment(), moment()],
            'Últimos 7 días':  [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes':        [moment().startOf('month'), moment().endOf('month')],
            'Mes pasado':      [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Este año':        [moment().startOf('year'), moment().endOf('year')]
        },
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Aplicar',
            cancelLabel: 'Quitar',
            customRangeLabel: 'Rango personalizado',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        }
    };

    if (desdeInicial && hastaInicial) {
        opciones.startDate = moment(desdeInicial, 'YYYY-MM-DD');
        opciones.endDate = moment(hastaInicial, 'YYYY-MM-DD');
    }

    $('#rangoFechasDominios').daterangepicker(opciones);

    $('#rangoFechasDominios').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#fechaDesdeDominios').val(picker.startDate.format('YYYY-MM-DD'));
        $('#fechaHastaDominios').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).closest('form').submit();
    });

    $('#rangoFechasDominios').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#fechaDesdeDominios').val('');
        $('#fechaHastaDominios').val('');
        $(this).closest('form').submit();
    });
});
</script>
@endpush

@php
    $tutorialPasos = [
        [
            'id' => 'alertaDominiosStatsWrap',
            'titulo' => 'Total, Activos e Inactivos',
            'texto' => 'Hacé clic en cualquiera de las tres tarjetas para filtrar el listado por ese estado.',
        ],
        [
            'id' => 'alertaDominiosBuscarForm',
            'titulo' => 'Buscar un dominio',
            'texto' => 'Buscá por patente, marca, modelo o motivo de la alerta.',
        ],
        [
            'id' => 'alertaDominiosAccionesWrap',
            'titulo' => 'Cargar o importar',
            'texto' => '"Cargar Dominio" da de alta una alerta individual. "Importar" carga varias desde un archivo. Ambos requieren permiso de creación.',
        ],
        [
            'id' => 'alertaDominiosTabla',
            'titulo' => 'Ver y editar',
            'texto' => 'Desde cada fila podés ver el detalle de la alerta o, si tenés permiso, editarla. Una patente marcada "Parcial" significa que está incompleta.',
            'side' => 'top',
        ],
    ];
@endphp
@include('partials.tutorial', ['tutorialPasos' => $tutorialPasos, 'tutorialStorageKey' => 'tutorial_alertas_dominios_visto'])
