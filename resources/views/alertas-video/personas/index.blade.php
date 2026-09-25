@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Alertas de Video &mdash; Personas</h3>
            <div id="alertaPersonasAccionesWrap">
                <button type="button" class="btn btn-outline-info" id="btnVerTutorialAlertaPersonas" onclick="iniciarTutorialPagina()">
                    <i class="fas fa-question-circle"></i> Ver tutorial
                </button>
                @can('crear-alerta-persona')
                    <a href="{{ route('alertas-video.personas.importar') }}" class="btn btn-info">
                        <i class="fas fa-upload"></i> Importar
                    </a>
                    <a href="{{ route('alertas-video.personas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Cargar Persona
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
                        <div class="small text-muted mb-1"><i class="fas fa-history"></i> Últimas 5 personas cargadas</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 small">
                                <thead>
                                    <tr class="text-muted">
                                        <th class="py-1">Apellido y Nombre</th>
                                        <th class="py-1">D.N.I.</th>
                                        <th class="py-1">Hecho relacionado</th>
                                        <th class="py-1 text-right">Cargado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ultimosCargados as $ultimo)
                                        <tr>
                                            <td class="py-1"><strong>{{ $ultimo->apellido_nombre }}</strong></td>
                                            <td class="py-1 text-muted">{{ $ultimo->dni ?? '-' }}</td>
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
            <div class="row" id="alertaPersonasStatsWrap">
                @php $filtrosVigentes = ['busqueda' => request('busqueda'), 'fecha_desde' => request('fecha_desde'), 'fecha_hasta' => request('fecha_hasta')]; @endphp
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', $filtrosVigentes + ['activo' => 'todos']) }}"
                       class="alerta-stat-card bg-slate {{ $activoFiltro === 'todos' ? 'active' : '' }}">
                        <div class="small">Total</div>
                        <div class="h3 mb-0">{{ $contadores['total'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', $filtrosVigentes + ['activo' => '1']) }}"
                       class="alerta-stat-card bg-green {{ $activoFiltro === '1' ? 'active' : '' }}">
                        <div class="small">Activos</div>
                        <div class="h3 mb-0">{{ $contadores['activos'] }}</div>
                    </a>
                </div>
                <div class="col-md-4 col-6 mb-3">
                    <a href="{{ route('alertas-video.personas.index', $filtrosVigentes + ['activo' => '0']) }}"
                       class="alerta-stat-card bg-red {{ $activoFiltro === '0' ? 'active' : '' }}">
                        <div class="small">Inactivos</div>
                        <div class="h3 mb-0">{{ $contadores['inactivos'] }}</div>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('alertas-video.personas.index') }}" class="mb-3" id="alertaPersonasBuscarForm">
                        <input type="hidden" name="activo" value="{{ $activoFiltro }}">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar por D.N.I., apellido y nombre o motivo..."
                                       value="{{ request('busqueda') }}">
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="rangoFechasPersonas" class="form-control" autocomplete="off" readonly
                                           placeholder="Filtrar por fecha de carga"
                                           value="{{ (request('fecha_desde') && request('fecha_hasta')) ? \Carbon\Carbon::parse(request('fecha_desde'))->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse(request('fecha_hasta'))->format('d/m/Y') : '' }}">
                                    <input type="hidden" name="fecha_desde" id="fechaDesdePersonas" value="{{ request('fecha_desde') }}">
                                    <input type="hidden" name="fecha_hasta" id="fechaHastaPersonas" value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <div class="col-md-3 mt-2 mt-md-0">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('alertas-video.personas.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive" id="alertaPersonasTabla">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>D.N.I.</th>
                                    <th>Apellido y Nombre</th>
                                    <th>Motivo</th>
                                    <th>Fecha de carga</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($personas as $persona)
                                    <tr>
                                        <td>
                                            @if ($persona->foto_url)
                                                <img src="{{ $persona->foto_url }}" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td>{{ $persona->dni ?? '-' }}</td>
                                        <td><strong>{{ $persona->apellido_nombre }}</strong></td>
                                        <td class="text-truncate" style="max-width: 320px;">{{ $persona->motivo ?? '-' }}</td>
                                        <td>{{ $persona->fecha_carga?->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-alerta-{{ $persona->activo ? 'activo' : 'inactivo' }}">
                                                {{ $persona->estado_label }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('alertas-video.personas.show', $persona) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('editar-alerta-persona')
                                                <a href="{{ route('alertas-video.personas.edit', $persona) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay personas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $personas->links() }}
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
    var desdeInicial = $('#fechaDesdePersonas').val();
    var hastaInicial = $('#fechaHastaPersonas').val();

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

    $('#rangoFechasPersonas').daterangepicker(opciones);

    $('#rangoFechasPersonas').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#fechaDesdePersonas').val(picker.startDate.format('YYYY-MM-DD'));
        $('#fechaHastaPersonas').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).closest('form').submit();
    });

    $('#rangoFechasPersonas').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#fechaDesdePersonas').val('');
        $('#fechaHastaPersonas').val('');
        $(this).closest('form').submit();
    });
});
</script>
@endpush

@php
    $tutorialPasos = [
        [
            'id' => 'alertaPersonasStatsWrap',
            'titulo' => 'Total, Activos e Inactivos',
            'texto' => 'Hacé clic en cualquiera de las tres tarjetas para filtrar el listado por ese estado.',
        ],
        [
            'id' => 'alertaPersonasBuscarForm',
            'titulo' => 'Buscar una persona',
            'texto' => 'Buscá por D.N.I., apellido y nombre o motivo de la alerta.',
        ],
        [
            'id' => 'alertaPersonasAccionesWrap',
            'titulo' => 'Cargar o importar',
            'texto' => '"Cargar Persona" da de alta una alerta individual. "Importar" carga varias desde un archivo. Ambos requieren permiso de creación.',
        ],
        [
            'id' => 'alertaPersonasTabla',
            'titulo' => 'Ver y editar',
            'texto' => 'Desde cada fila podés ver el detalle de la alerta o, si tenés permiso, editarla.',
            'side' => 'top',
        ],
    ];
@endphp
@include('partials.tutorial', ['tutorialPasos' => $tutorialPasos, 'tutorialStorageKey' => 'tutorial_alertas_personas_visto'])
