@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-ticket-alt"></i> {{ $ticket->codigo_interno }}</h1>
        @if($ticket->esDePg())
            <span class="badge badge-info">Solicitud de PG</span>
        @endif
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Ticket</h4>
                        <div>
                            <a href="{{ route('incidencias.tickets-pg.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            @can('editar-ticket-pg')
                            @if(!$ticket->estaEnviado())
                                <a href="{{ route('incidencias.tickets-pg.edit', $ticket) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            @endif
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Código interno</dt>
                            <dd class="col-sm-9"><strong>{{ $ticket->codigo_interno }}</strong></dd>
                            <dt class="col-sm-3">Ticketera</dt>
                            <dd class="col-sm-9">{{ $ticket->codigo_ticketera ?: '-' }}</dd>
                            <dt class="col-sm-3">ID de Ref.</dt>
                            <dd class="col-sm-9">{{ $ticket->referencia_ticketera ?: '-' }}</dd>
                            <dt class="col-sm-3">Estado ticket</dt>
                            <dd class="col-sm-9">
                                <span class="badge badge-{{ $ticket->colorEstadoTicketera() }}">{{ $ticket->estadoTicketeraLegible() }}</span>
                            </dd>
                            <dt class="col-sm-3">Estado envío</dt>
                            <dd class="col-sm-9">{{ strtoupper($ticket->estado_envio) }}</dd>
                            <dt class="col-sm-3">Categoría</dt>
                            <dd class="col-sm-9">{{ $ticket->tipo_equipo }} <span class="text-muted">/ {{ $ticket->prioridad }}</span></dd>
                            <dt class="col-sm-3">Asunto</dt>
                            <dd class="col-sm-9">{{ $ticket->asunto }}</dd>
                            <dt class="col-sm-3">Subsistema</dt>
                            <dd class="col-sm-9">{{ $ticket->subsistema ?: '-' }}</dd>

                            @if($ticket->movil || $ticket->equipo)
                                <dt class="col-sm-3">Móvil / TEI</dt>
                                <dd class="col-sm-9">{{ $ticket->movil ?: '-' }}@if($ticket->equipo) <span class="text-muted">— TEI {{ $ticket->equipo->tei }}</span>@endif</dd>
                            @endif
                            @if($ticket->modelo_equipo)
                                <dt class="col-sm-3">Equipo (Modelo)</dt>
                                <dd class="col-sm-9">{{ $ticket->modelo_equipo }}</dd>
                            @endif
                            @if($ticket->oficina)
                                <dt class="col-sm-3">Oficina</dt>
                                <dd class="col-sm-9">{{ $ticket->oficina }}</dd>
                            @endif
                            @if($ticket->camaras_afectadas)
                                <dt class="col-sm-3">Cámaras ({{ $ticket->cantidad_items }})</dt>
                                <dd class="col-sm-9">
                                    <ul class="mb-0 pl-3">
                                        @foreach($ticket->camaras_afectadas as $camara)
                                            <li>
                                                {{ $camara['nombre'] ?? '-' }}
                                                @if(!empty($camara['tipo']))<span class="badge badge-light">{{ $camara['tipo'] }}</span>@endif
                                                @if(!empty($camara['ip']))<span class="text-muted">({{ $camara['ip'] }})</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </dd>
                            @endif
                            <dt class="col-sm-3">Falla</dt>
                            <dd class="col-sm-9">
                                {{ optional($ticket->fecha_inicio_falla)->format('d/m/Y H:i') ?: 'sin registrar' }}
                                →
                                {{ optional($ticket->fecha_fin_falla)->format('d/m/Y H:i') ?: 'sin resolución' }}
                            </dd>

                            <dt class="col-sm-3">URL seguimiento</dt>
                            <dd class="col-sm-9">
                                @if($ticket->url_seguimiento)
                                    <a href="{{ $ticket->url_seguimiento }}" target="_blank" rel="noopener">{{ $ticket->url_seguimiento }}</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </dl>

                        <label>{{ $ticket->esDePg() ? 'Solicitud recibida de PG' : 'Texto enviado / a enviar' }}</label>
                        <div class="form-control h-auto" style="white-space: pre-line;">{{ $ticket->texto_enviado }}</div>

                        @php($respuestas = $ticket->respuestas())
                        @if(count($respuestas) > 0)
                            <label class="mt-3">Respuestas ({{ count($respuestas) }})</label>
                            <div class="timeline-respuestas">
                                @foreach($respuestas as $respuesta)
                                    <div class="border-left border-primary pl-3 mb-3">
                                        <div class="text-muted small font-weight-bold">
                                            {{ $respuesta['fecha']?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                        </div>
                                        <div style="white-space: pre-line;">{{ $respuesta['texto'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($ticket->ultimo_error)
                            <div class="alert alert-danger mt-3 mb-0">
                                <strong>Último error:</strong> {{ $ticket->ultimo_error }}
                            </div>
                        @endif
                    </div>
                    @canany(['editar-ticket-pg', 'enviar-ticket-pg'])
                    @if(!$ticket->estaEnviado())
                        <div class="card-footer text-right">
                            @can('editar-ticket-pg')
                            <form method="POST" action="{{ route('incidencias.tickets-pg.mejorar-redaccion', $ticket) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-magic"></i> Mejorar con IA
                                </button>
                            </form>
                            @endcan
                            @can('enviar-ticket-pg')
                            @if(!$ticket->yaEstaEnTicketera())
                            <form method="POST" action="{{ route('incidencias.tickets-pg.enviar', $ticket) }}" class="d-inline" onsubmit="return confirm('¿Enviar este ticket a la ticketera?');">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Enviar a ticketera
                                </button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    @endif
                    @endcanany
                    @can('editar-ticket-pg')
                    @if($ticket->referencia_ticketera)
                        <div class="card-footer text-right">
                            <form method="POST" action="{{ route('incidencias.tickets-pg.sincronizar-respuestas', $ticket) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-sync"></i> Consultar respuestas en HESK
                                </button>
                            </form>
                        </div>
                    @endif
                    @endcan
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Datos para Excel</h4>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarDatosExcel()">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                    <div class="card-body">
                        <textarea id="datos_excel" class="form-control" rows="18" readonly>Inc.: {{ $ticket->codigo_interno }}
Ticket (Nro.): {{ $ticket->codigo_ticketera }}
ID de Ref.: {{ $ticket->referencia_ticketera }}
Fecha Plat. Web: {{ optional($ticket->enviado_en ?? $ticket->created_at)->format('d/m/Y H:i') }}
Fecha Inicio Inc./Prest.: {{ optional($ticket->fecha_inicio_falla ?? $ticket->created_at)->format('d/m/Y H:i') }}
F. FIN falla: {{ optional($ticket->fecha_fin_falla)->format('d/m/Y H:i') }}
Categoría: {{ $ticket->tipo_equipo }}
Prior. Ticket: {{ $ticket->prioridad }}
Apl.: {{ $ticket->aplica_calculo ? 'SI' : 'NO' }}
Per. Fact.: {{ $ticket->periodo_facturado }}
Estado: {{ $ticket->estado_ticketera ?: 'Nuevo' }}
Incidencia / Prestación: {{ $ticket->texto_enviado }}
Subsist. Donde se produjo inc.: {{ $ticket->subsistema }}
Cant. Items: {{ $ticket->cantidad_items }}
Equipo (Modelo): {{ $ticket->modelo_equipo }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    function copiarDatosExcel() {
        const datos = document.getElementById('datos_excel');
        datos.select();
        document.execCommand('copy');
    }
</script>
@endpush
