@extends('layouts.app')

@push('styles')
<style>
    /* Filtros de estado con acento neón propio por botón.
       Tema claro: tonos más oscuros para contraste sobre fondo blanco.
       Tema oscuro ([data-theme="dark"]): neón brillante + glow, a tono con el resto del theme. */
    .btn-filtro-estado {
        --neon: #64748b;
        --neon-soft: rgba(100, 116, 139, 0.15);
        color: var(--neon);
        border: 1px solid var(--neon);
        background: transparent;
        font-weight: 600;
        transition: box-shadow .2s ease, background-color .2s ease, color .2s ease;
    }

    .btn-filtro-estado .badge {
        background: var(--neon-soft);
        color: var(--neon);
        transition: inherit;
    }

    .btn-filtro-estado:hover {
        color: var(--neon);
        box-shadow: 0 0 6px var(--neon-soft), 0 0 14px var(--neon-soft);
        background: var(--neon-soft);
        text-decoration: none;
    }

    .btn-filtro-estado.activo {
        background: var(--neon);
        border-color: var(--neon);
        color: #fff;
        box-shadow: 0 0 8px var(--neon-soft), 0 0 18px var(--neon-soft);
    }

    .btn-filtro-estado.activo .badge {
        background: rgba(255, 255, 255, 0.28);
        color: #fff;
    }

    .filtro-todos      { --neon: #0891b2; --neon-soft: rgba(8, 145, 178, 0.16); }
    .filtro-nuevos     { --neon: #7c3aed; --neon-soft: rgba(124, 58, 237, 0.16); }
    .filtro-progreso   { --neon: #d97706; --neon-soft: rgba(217, 119, 6, 0.16); }
    .filtro-resueltos  { --neon: #059669; --neon-soft: rgba(5, 150, 105, 0.16); }

    [data-theme="dark"] .filtro-todos     { --neon: #00e5ff; --neon-soft: rgba(0, 229, 255, 0.22); }
    [data-theme="dark"] .filtro-nuevos    { --neon: #a78bfa; --neon-soft: rgba(167, 139, 250, 0.22); }
    [data-theme="dark"] .filtro-progreso  { --neon: #ffb020; --neon-soft: rgba(255, 176, 32, 0.22); }
    [data-theme="dark"] .filtro-resueltos { --neon: #00f2a6; --neon-soft: rgba(0, 242, 166, 0.22); }

    /* En oscuro, el botón activo lleva texto oscuro sobre el neón para que no encandile */
    [data-theme="dark"] .btn-filtro-estado.activo {
        color: #06101f;
        text-shadow: none;
    }

    [data-theme="dark"] .btn-filtro-estado.activo .badge {
        background: rgba(6, 16, 31, 0.35);
        color: #06101f;
    }
</style>
@endpush

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-ticket-alt"></i> Tickets PG</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Borradores y enviados</h4>
                @can('crear-ticket-pg')
                    <div>
                        <a href="{{ route('incidencias.tickets-pg.importar') }}" class="btn btn-outline-info">
                            <i class="fas fa-file-import"></i> Importar Excel
                        </a>
                        <a href="{{ route('incidencias.tickets-pg.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Ticket PG
                        </a>
                    </div>
                @endcan
            </div>
            <div class="card-body border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: .5rem;">
                    <form method="GET" action="{{ route('incidencias.tickets-pg.index') }}" class="flex-grow-1" style="max-width: 760px;">
                        @if($estadoFiltro !== '')
                            <input type="hidden" name="estado" value="{{ $estadoFiltro }}">
                        @endif
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" value="{{ $busqueda }}"
                                   placeholder="Buscar por texto, código PG, nro. o ID de ref. de la ticketera...">
                            <div class="input-group-prepend" id="icono_rango_fechas" style="cursor: pointer;" title="Filtrar por fecha del ticket">
                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" id="rango_fechas" class="form-control" autocomplete="off" readonly
                                   style="width: 205px; min-width: 205px; flex: 0 0 205px; cursor: pointer;"
                                   placeholder="Buscar por fecha" title="Filtrar por fecha del ticket"
                                   value="{{ ($fechaDesde !== '' && $fechaHasta !== '') ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : '' }}">
                            <input type="hidden" name="desde" id="fecha_desde" value="{{ $fechaDesde }}">
                            <input type="hidden" name="hasta" id="fecha_hasta" value="{{ $fechaHasta }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if($busqueda !== '' || $fechaDesde !== '' || $fechaHasta !== '')
                                    <a href="{{ route('incidencias.tickets-pg.index', array_filter(['estado' => $estadoFiltro])) }}" class="btn btn-light" title="Limpiar búsqueda">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        @error('desde')<small class="text-danger">{{ $message }}</small>@enderror
                        @error('hasta')<small class="text-danger">{{ $message }}</small>@enderror
                    </form>
                    @php
                        $filtrosEstado = [
                            ''            => ['Todos', 'filtro-todos'],
                            'nuevos'      => ['Nuevos', 'filtro-nuevos'],
                            'en_progreso' => ['En progreso', 'filtro-progreso'],
                            'resueltos'   => ['Resueltos', 'filtro-resueltos'],
                        ];
                    @endphp
                    <div class="btn-group filtros-estado-pg">
                        @foreach($filtrosEstado as $claveFiltro => [$etiquetaFiltro, $claseFiltro])
                            <a href="{{ route('incidencias.tickets-pg.index', array_filter(['q' => $busqueda, 'desde' => $fechaDesde, 'hasta' => $fechaHasta, 'estado' => $claveFiltro])) }}"
                               class="btn btn-sm btn-filtro-estado {{ $claseFiltro }} {{ $estadoFiltro === $claveFiltro ? 'activo' : '' }}">
                                {{ $etiquetaFiltro }}
                                <span class="badge ml-1">{{ $conteosPorEstado[$claveFiltro] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código interno</th>
                                <th>Ticketera</th>
                                <th>Asunto</th>
                                <th>Estado ticket</th>
                                <th>Envío</th>
                                <th>Creado</th>
                                <th class="text-center" style="width:130px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td><strong>{{ $ticket->codigo_interno }}</strong></td>
                                    <td>
                                        {{ $ticket->codigo_ticketera ?: '-' }}
                                        @if($ticket->referencia_ticketera)
                                            <small class="text-muted d-block">{{ $ticket->referencia_ticketera }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $ticket->asunto }}</td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->colorEstadoTicketera() }}">
                                            {{ $ticket->estadoTicketeraLegible() }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $colorEnvio = [
                                                'enviado'   => 'success',
                                                'error'     => 'danger',
                                                'importado' => 'info',
                                            ][$ticket->estado_envio] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $colorEnvio }}">
                                            {{ strtoupper($ticket->estado_envio) }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('incidencias.tickets-pg.show', $ticket) }}" class="btn btn-sm btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('editar-ticket-pg')
                                        @if(!$ticket->estaEnviado())
                                            <a href="{{ route('incidencias.tickets-pg.edit', $ticket) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        {{ ($busqueda !== '' || $fechaDesde !== '' || $fechaHasta !== '' || $estadoFiltro !== '') ? 'No se encontraron tickets con los filtros aplicados.' : 'No hay tickets PG cargados.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($tickets->hasPages())
                <div class="card-footer">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
    var desdeInicial = $('#fecha_desde').val();
    var hastaInicial = $('#fecha_hasta').val();

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
        $('#rango_fechas').val(opciones.startDate.format('DD/MM/YYYY') + ' - ' + opciones.endDate.format('DD/MM/YYYY'));
    }

    $('#rango_fechas').daterangepicker(opciones);

    $('#rango_fechas').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#fecha_desde').val(picker.startDate.format('YYYY-MM-DD'));
        $('#fecha_hasta').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).closest('form').submit();
    });

    $('#rango_fechas').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#fecha_desde').val('');
        $('#fecha_hasta').val('');
        $(this).closest('form').submit();
    });

    $('#icono_rango_fechas').on('click', function () {
        $('#rango_fechas').trigger('click');
    });
});
</script>
@endpush
