@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="section-title mb-0">
            <i class="fas fa-chart-area"></i>
            Período {{ $periodo->label }}
            <small class="text-muted ml-2" style="font-size:0.65em">
                {{ $periodo->fecha_inicio->format('d/m/Y') }} — {{ $periodo->fecha_fin->format('d/m/Y') }}
                ({{ $periodo->dias }} días)
            </small>
        </h1>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <a href="{{ route('incidencias.periodos.index') }}" class="btn btn-light btn-sm mr-1">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            @can('editar-periodo-911')
            <a href="{{ route('incidencias.periodos.edit', $periodo->id) }}" class="btn btn-warning btn-sm mr-1">
                <i class="fas fa-edit"></i> Editar
            </a>
            @endcan
            <div class="btn-group mr-1">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-file-word"></i> Generar Informe
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('incidencias.periodos.informe', $periodo->id) }}">
                        <i class="fas fa-file-alt mr-1"></i> Informe Comisión Especial
                    </a>
                    <a class="dropdown-item" href="{{ route('incidencias.periodos.recibo', $periodo->id) }}">
                        <i class="fas fa-envelope mr-1"></i> Mensajes / Recibo Juárez
                    </a>
                </div>
            </div>
            @can('crear-incidencia-911')
            <a href="{{ route('incidencias.incidencia.create', $periodo->id) }}" class="btn btn-primary btn-sm mr-1">
                <i class="fas fa-plus"></i> Agregar Incidencia
            </a>
            <a href="{{ route('incidencias.periodos.importar', $periodo->id) }}" class="btn btn-info btn-sm mr-1">
                <i class="fas fa-file-import"></i> Importar Excel
            </a>
            @if($periodo->numero > 1)
            <form action="{{ route('incidencias.periodos.arrastrar', $periodo->id) }}" method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Traer las incidencias persistentes del período P{{ str_pad($periodo->numero - 1, 2, '0', STR_PAD_LEFT) }} que siguen sin resolverse? Se crearán nuevas incidencias en este período con minutos_fallo = {{ number_format($periodo->minutos_totales, 0, ',', '.') }} (período completo).');">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fas fa-angle-double-right"></i> Arrastrar persistentes de P{{ str_pad($periodo->numero - 1, 2, '0', STR_PAD_LEFT) }}
                </button>
            </form>
            @endif
            @endcan
        </div>
    </div>

    <div class="section-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        @endif

        {{-- ── Resumen visual ─────────────────────────────────────────────────── --}}
        <div class="row">
            {{-- Indicador principal --}}
            <div class="col-12 col-md-4 mb-3">
                <div class="card h-100 {{ $analisis['aplica_multa'] ? 'border-danger' : 'border-success' }}">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        @if($analisis['aplica_multa'])
                            <div class="text-danger mb-2"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                            <h2 class="text-danger font-weight-bold">MULTA</h2>
                            <p class="text-muted small">{{ $analisis['motivo_multa'] }}</p>
                        @else
                            <div class="text-success mb-2"><i class="fas fa-check-circle fa-3x"></i></div>
                            <h2 class="text-success font-weight-bold">Sin Multa</h2>
                            <p class="text-muted small">Deficiencia por debajo de los umbrales del pliego</p>
                        @endif
                        <hr>
                        <div class="display-5 font-weight-bold {{ $analisis['aplica_multa'] ? 'text-danger' : 'text-success' }}">
                            {{ number_format($analisis['total_ponderado'], 5) }}%
                        </div>
                        <small class="text-muted">Deficiencia ponderada del sistema</small>
                        @if($periodo->factura_monto)
                        <hr>
                        <small class="text-muted">
                            Factura: ${{ number_format($periodo->factura_monto, 2, ',', '.') }}<br>
                            @if($analisis['aplica_multa'])
                            Monto multa: <strong class="text-danger">${{ number_format($periodo->montoMulta(), 2, ',', '.') }}</strong>
                            @endif
                        </small>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Gráfico por sistema --}}
            <div class="col-12 col-md-5 mb-3">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">Deficiencia por Sistema (%)</h5></div>
                    <div class="card-body">
                        <div id="chartSistemas" style="height:220px;"></div>
                    </div>
                </div>
            </div>

            {{-- Info del período --}}
            <div class="col-12 col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">Info del Período</h5></div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">TETRA</td><td><strong>{{ $periodo->n_total_tetra }}</strong> terminales</td></tr>
                            <tr><td class="text-muted">CCTV</td><td><strong>{{ $periodo->n_total_camaras }}</strong> cámaras</td></tr>
                            <tr><td class="text-muted">Minutos</td><td>{{ number_format($periodo->minutos_totales, 0, ',', '.') }}</td></tr>
                            @if($periodo->factura_numero)
                            <tr><td class="text-muted">Factura</td><td>{{ $periodo->factura_numero }}</td></tr>
                            @endif
                            @if($periodo->expediente_numero)
                            <tr><td class="text-muted">Expediente</td><td>{{ $periodo->expediente_numero }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tabla resumen por módulos N1 ────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Tabla de Ponderación — Módulos de 1° Nivel (Anexo V)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Módulo de 1° Nivel</th>
                                <th class="text-center">% Def. N1</th>
                                <th class="text-center">Ponderación</th>
                                <th class="text-center">% Def. Total Sistema</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach(\App\Models\Incidencia911::MODULOS as $sis => $cfg)
                        @php
                            $datos   = $analisis['por_sistema'][$sis] ?? ['deficiencia_n1' => 0, 'ponderacion_n1' => $cfg['n1_peso'], 'contrib_total' => 0];
                            $defN1   = $datos['deficiencia_n1'];
                            $contT   = $datos['contrib_total'];
                            $excede  = $defN1 >= \App\Models\PeriodoFactura::UMBRAL_DEFICIENCIA_N1;
                        @endphp
                        <tr class="{{ $excede ? 'table-danger' : '' }}">
                            <td class="font-weight-bold">{{ $sis }}</td>
                            <td class="text-center {{ $excede ? 'text-danger font-weight-bold' : '' }}">{{ number_format($defN1, 5) }}%</td>
                            <td class="text-center text-muted">{{ $cfg['n1_peso'] }}%</td>
                            <td class="text-center">{{ number_format($contT, 5) }}%</td>
                            <td class="text-center">
                                @if($excede)
                                    <span class="badge badge-danger">Excede umbral (2%)</span>
                                @elseif($defN1 > 0)
                                    <span class="badge badge-warning">Con incidencias</span>
                                @else
                                    <span class="badge badge-light">Sin incidencias</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold {{ $analisis['aplica_multa'] ? 'table-danger' : 'table-success' }}">
                                <td colspan="3" class="text-right">Deficiencia ponderada del Sistema Completo:</td>
                                <td class="text-center">{{ number_format($analisis['total_ponderado'], 5) }}%</td>
                                <td class="text-center">
                                    @if($analisis['aplica_multa'])
                                        <span class="badge badge-danger">Excede umbral (1.5%)</span>
                                    @else
                                        <span class="badge badge-success">Dentro del límite</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Detalle de cálculo por sistema ─────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-calculator"></i> Detalle del cálculo (T = {{ number_format($periodo->minutos_totales, 0, ',', '.') }} min)</h5>
                <button class="btn btn-sm btn-light" type="button" data-toggle="collapse" data-target="#detalleCalculo">
                    <i class="fas fa-eye"></i> Ver / Ocultar
                </button>
            </div>
            <div class="collapse" id="detalleCalculo">
            @foreach(\App\Models\Incidencia911::MODULOS as $sis => $cfg)
            @php
                $det = $detalleCalculo[$sis] ?? ['aplica' => [], 'excluidas' => []];
                $sumaDefAplica = collect($det['aplica'])->sum('deficiencia');
            @endphp
            @if(count($det['aplica']) > 0 || count($det['excluidas']) > 0)
            <div class="card-body border-top pb-2">
                <strong>{{ $sis }}</strong>
                <span class="text-muted small ml-2">
                    {{ count($det['aplica']) }} incidencias en cálculo
                    @if(count($det['excluidas']) > 0)
                        · <span class="text-secondary">{{ count($det['excluidas']) }} excluidas</span>
                    @endif
                    · <span class="text-primary">Σ % Deficiencia = {{ number_format($sumaDefAplica, 5) }}%</span>
                </span>
                <div class="table-responsive mt-1">
                    <table class="table table-xs table-bordered mb-2" style="font-size:0.8em">
                        <thead class="thead-light">
                            <tr>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Módulo N2</th>
                                <th class="text-center">N afc / N tot</th>
                                <th class="text-center">Min. fallo</th>
                                <th class="text-center">% Indisp.</th>
                                <th class="text-center">% Defic.</th>
                                <th class="text-center">Aplica</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach(array_merge($det['aplica'], $det['excluidas']) as $row)
                        <tr class="{{ in_array($row, $det['excluidas']) ? 'text-muted bg-light' : '' }}">
                            <td><code>{{ $row['code'] }}</code></td>
                            <td>{{ $row['tipo'] }}</td>
                            <td>{{ $row['modulo_n2'] }} <small class="text-muted">({{ $row['pond_n2'] }}%)</small></td>
                            <td class="text-center">{{ $row['n_afect'] }}/{{ $row['n_total'] }}</td>
                            <td class="text-center">{{ number_format($row['min_fallo'], 1) }}</td>
                            <td class="text-center">{{ number_format($row['indisp'], 5) }}%</td>
                            <td class="text-center"><strong>{{ number_format($row['deficiencia'], 5) }}%</strong></td>
                            <td class="text-center">{{ in_array($row, $det['excluidas']) ? '✗' : '✓' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endforeach
            </div>
        </div>

        {{-- ── Equipos persistentes ────────────────────────────────────────────── --}}
        @php $labelAnterior = 'P' . str_pad($periodo->numero - 1, 2, '0', STR_PAD_LEFT); @endphp
        <div class="card mb-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-circle"></i>
                    Equipos con Falla Persistente
                    @if($persistentes->count() > 0)
                        <span class="badge badge-light text-danger ml-1">{{ $persistentes->count() }}</span>
                    @endif
                </h5>
                <div class="d-flex align-items-center mt-1 mt-md-0">
                    @can('crear-incidencia-911')
                    <form action="{{ route('incidencias.periodos.arrastrar', $periodo->id) }}" method="POST" class="d-inline mr-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light text-danger"
                            onclick="return confirm('¿Arrastrar los equipos persistentes del {{ $labelAnterior }} a este período?')"
                            title="Copia los equipos sin reparar del período anterior a este período (período completo)">
                            <i class="fas fa-arrow-circle-down mr-1"></i> Arrastrar del {{ $labelAnterior }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @if($persistentes->count() > 0)
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Código</th>
                                <th>Origen</th>
                                <th>Sistema / Módulo</th>
                                <th class="text-center">Min. falla</th>
                                <th class="text-center">% Deficiencia</th>
                                <th class="text-center">Aplica</th>
                                <th>Observaciones</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($persistentes as $inc)
                        @php
                            $T  = $periodo->minutos_totales;
                            $pI = ($T > 0 && $inc->n_total_unidades > 0)
                                ? ($inc->n_unidades_afectadas/$inc->n_total_unidades)*($inc->minutos_fallo/$T)*100 : 0;
                            $pD = $pI * 2;
                            $esArrastrado = $inc->hoja_origen === 'arrastrado';
                        @endphp
                        <tr class="{{ !$inc->aplica_calculo ? 'table-light text-muted' : ($esArrastrado ? 'table-warning' : '') }}">
                            <td>
                                <code>{{ $inc->incidencia_code ?? '—' }}</code>
                                @if($inc->tickets)
                                    <br><small class="text-muted">{{ $inc->tickets }}</small>
                                @endif
                            </td>
                            <td>
                                @if($esArrastrado)
                                    <span class="badge badge-warning" title="Arrastrado del período anterior — equipo sin reparar">
                                        <i class="fas fa-arrow-circle-down"></i> P.Anterior
                                    </span>
                                @else
                                    <span class="badge badge-secondary">{{ $inc->hoja_origen }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $inc->sistema }}</span>
                                <small>{{ $inc->modulo_n2 }}</small>
                            </td>
                            <td class="text-center">
                                {{ number_format($inc->minutos_fallo, 0) }}
                                @if($inc->minutos_fallo >= $periodo->minutos_totales)
                                    <br><small class="text-danger">período completo</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <strong class="{{ $inc->aplica_calculo ? 'text-danger' : 'text-muted' }}">
                                    {{ number_format($pD, 5) }}%
                                </strong>
                            </td>
                            <td class="text-center">
                                @if($inc->aplica_calculo)
                                    <span class="badge badge-danger">Sí — computa multa</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td><small>{{ Str::limit($inc->observaciones, 60) }}</small></td>
                            <td class="text-center text-nowrap">
                                @can('editar-incidencia-911')
                                <a href="{{ route('incidencias.incidencia.edit', [$periodo->id, $inc->id]) }}" class="btn btn-xs btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                @endcan
                                @can('borrar-incidencia-911')
                                <form action="{{ route('incidencias.incidencia.destroy', [$periodo->id, $inc->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta incidencia?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body text-center text-muted py-3">
                <i class="fas fa-check-circle text-success mr-1"></i>
                No hay equipos con falla persistente en este período.
                <span class="d-block mt-1 small">Usá el botón "Arrastrar del período anterior" si quedan equipos sin reparar del {{ $labelAnterior }}.</span>
            </div>
            @endif
        </div>

        {{-- ── Incidencias transitorias ─────────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Incidencias Transitorias ({{ $transitorias->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($transitorias->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código</th>
                                <th>Prioridad</th>
                                <th>Sistema / Módulo</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-center">Min. falla</th>
                                <th class="text-center">% Indisp.</th>
                                <th class="text-center">% Defic.</th>
                                <th class="text-center">Aplica</th>
                                <th>Observaciones</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($transitorias as $inc)
                        @php
                            $T  = $periodo->minutos_totales;
                            $pI = ($T > 0 && $inc->n_total_unidades > 0)
                                ? ($inc->n_unidades_afectadas/$inc->n_total_unidades)*($inc->minutos_fallo/$T)*100 : 0;
                            $pD = $pI * 2;
                        @endphp
                        <tr class="{{ !$inc->aplica_calculo ? 'text-muted bg-light' : '' }}">
                            <td><small><code>{{ $inc->incidencia_code ?? '—' }}</code></small></td>
                            <td>
                                <span class="badge badge-{{ $inc->color_prioridad }}">{{ ucfirst($inc->prioridad) }}</span>
                            </td>
                            <td><span class="badge badge-secondary">{{ $inc->sistema }}</span> <small>{{ $inc->modulo_n2 }}</small></td>
                            <td class="text-center">{{ $inc->n_unidades_afectadas }}/{{ $inc->n_total_unidades }}</td>
                            <td class="text-center">{{ number_format($inc->minutos_fallo, 0) }}</td>
                            <td class="text-center">{{ number_format($pI, 5) }}%</td>
                            <td class="text-center"><strong>{{ number_format($pD, 5) }}%</strong></td>
                            <td class="text-center">
                                @if($inc->aplica_calculo)
                                    <span class="badge badge-success">Sí</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td><small>{{ Str::limit($inc->observaciones, 70) }}</small></td>
                            <td class="text-center text-nowrap">
                                @can('editar-incidencia-911')
                                <a href="{{ route('incidencias.incidencia.edit', [$periodo->id, $inc->id]) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                @endcan
                                @can('borrar-incidencia-911')
                                <form action="{{ route('incidencias.incidencia.destroy', [$periodo->id, $inc->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i>No hay incidencias registradas.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/echarts.min.js') }}"></script>
<script>
(function() {
    var chart = echarts.init(document.getElementById('chartSistemas'));
    var labels = @json($chartLabels);
    var datos  = @json($chartData);
    var umbral = {{ \App\Models\PeriodoFactura::UMBRAL_DEFICIENCIA_N1 }};

    var colors = datos.map(v => v >= umbral ? '#e74a3b' : (v > 0.5 ? '#f6c23e' : '#1cc88a'));

    chart.setOption({
        tooltip: { trigger: 'axis', formatter: '{b}: {c}%' },
        grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
        xAxis: {
            type: 'value',
            axisLabel: { formatter: '{value}%' },
            max: Math.max(Math.ceil(Math.max(...datos) * 10) / 10, 2.5)
        },
        yAxis: { type: 'category', data: labels },
        series: [{
            type: 'bar',
            data: datos,
            itemStyle: {
                color: function(p) { return colors[p.dataIndex]; }
            },
            label: { show: true, position: 'right', formatter: '{c}%' },
            markLine: {
                data: [{ xAxis: umbral, name: 'Umbral 2%' }],
                lineStyle: { color: '#e74a3b', type: 'dashed' }
            }
        }]
    });
    window.addEventListener('resize', function() { chart.resize(); });
})();
</script>
@endpush
