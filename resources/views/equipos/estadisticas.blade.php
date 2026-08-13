@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
        <h1 class="section-title"><i class="fas fa-chart-pie"></i> Equipamientos - Estadísticas</h1>
        <div>
            <a href="{{ route('equipos.estadisticas.export') }}" class="btn btn-success">
                <i class="fas fa-file-excel mr-1"></i> Exportar a Excel
            </a>
            <button type="button" id="btnExportarPdf" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf mr-1"></i> Exportar a PDF
            </button>
        </div>
    </div>
    <div class="section-body">

        @php
            $usoFiltroBase = [
                'Base' => ['uso' => 'Base'],
                'Movil' => ['uso' => 'Movil'],
                'Portatil' => ['uso' => 'Portatil', 'excluir_modelos' => 'Teltronic:HTT500', 'excluir_marcas' => 'Motorola/Vertex'],
                'HTT500 (sin accesorios)' => ['marca' => 'Teltronic', 'modelo' => 'HTT500'],
                'VX-261 (no TETRA)' => ['marca' => 'Motorola/Vertex'],
            ];

            // Mismos colores que la vista de Terminales/Histórico: verde = bien, rojo = no funciona
            $estadoColores = [
                'Nuevo'                      => ['clase' => 'estado-nuevo',    'hex' => '#28a745'],
                'Usado'                      => ['clase' => 'estado-usado',    'hex' => '#6dbf67'],
                'Reparado'                   => ['clase' => 'estado-reparado', 'hex' => '#007bff'],
                'No funciona'                => ['clase' => 'estado-malo',     'hex' => '#dc3545'],
                'Baja'                       => ['clase' => 'estado-malo',     'hex' => '#dc3545'],
                'Perdido'                    => ['clase' => 'estado-malo',     'hex' => '#dc3545'],
                'Degradado - Sin Accesorios' => ['clase' => 'estado-malo',     'hex' => '#dc3545'],
                'Recambio'                   => ['clase' => 'estado-neutro',   'hex' => '#6c757d'],
                'Temporal'                   => ['clase' => 'estado-neutro',   'hex' => '#6c757d'],
                'En revision'                => ['clase' => 'estado-revision', 'hex' => '#17a2b8'],
            ];
            $estadoColorDefault = ['clase' => 'estado-neutro', 'hex' => '#6c757d'];

            $kpis = [
                ['titulo' => 'Total Equipos', 'valor' => $resumen['total'], 'icono' => 'fa-microchip', 'color' => 'bg-primary',
                    'filtro' => [], 'info' => 'Todos los equipos de la base, sin importar estado ni asignación.'],
                ['titulo' => 'Operativos', 'valor' => $resumen['operativos'], 'icono' => 'fa-check-circle', 'color' => 'bg-success',
                    'filtro' => ['condicion' => 'operativo'], 'info' => 'Equipos en estado Nuevo, Usado o Reparado, sin importar si están instalados, asignados o en stock.'],
                ['titulo' => 'No Operativos', 'valor' => $resumen['no_operativos'], 'icono' => 'fa-times-circle', 'color' => 'bg-danger',
                    'filtro' => ['condicion' => 'no_operativo'], 'info' => 'Equipos en estado Baja, No funciona, Perdido, Degradado - Sin Accesorios o Recambio (estos últimos ya no los tiene la Policía: fueron devueltos/cambiados).'],
                ['titulo' => 'En Revisión Técnica', 'valor' => $resumen['en_revision_tecnica'], 'icono' => 'fa-tools', 'color' => 'bg-info',
                    'filtro' => ['ultimo_movimiento' => 'Revisión'], 'info' => 'Equipos cuyo último movimiento histórico registrado es "Revisión" (soporte/sección técnica), sin importar el estado actual.'],

                ['titulo' => 'Instalados (Móvil/Base)', 'valor' => $resumen['instalados'], 'icono' => 'fa-plug', 'color' => 'bg-success',
                    'filtro' => ['condicion' => 'operativo', 'situacion' => 'instalado', 'excluir_portatil' => 1],
                    'info' => 'Equipos operativos con uso Móvil o Base (MDT400, DT410, SRG3900, etc.), asignados a un recurso real fuera de Stock 911.'],
                ['titulo' => 'Asignados (Portátiles TETRA)', 'valor' => $resumen['asignados_portatiles'], 'icono' => 'fa-walkie-talkie', 'color' => 'bg-primary',
                    'filtro' => ['condicion' => 'operativo', 'situacion' => 'instalado', 'uso' => 'Portatil', 'excluir_modelos' => 'Teltronic:HTT500', 'excluir_marcas' => 'Motorola/Vertex'],
                    'info' => 'Portátiles TETRA operativos (excluye HTT500 y VX-261), asignados a una persona/recurso real fuera de Stock 911.'],
                ['titulo' => 'HTT500 Asignados (sin Accesorios)', 'valor' => $resumen['htt500_asignados'], 'icono' => 'fa-battery-empty', 'color' => 'bg-danger',
                    'filtro' => ['condicion' => 'operativo', 'situacion' => 'instalado', 'marca' => 'Teltronic', 'modelo' => 'HTT500'],
                    'info' => 'HTT500 en estado Nuevo/Usado/Reparado, asignados fuera de Stock 911. Aunque el estado diga que están bien, no hay baterías ni antenas disponibles para equiparlos.'],
                ['titulo' => 'VX-261 Asignados (no TETRA)', 'valor' => $resumen['vertex_asignados'], 'icono' => 'fa-satellite-dish', 'color' => 'bg-dark',
                    'filtro' => ['condicion' => 'operativo', 'situacion' => 'instalado', 'marca' => 'Motorola/Vertex'],
                    'info' => 'Motorola/Vertex VX-261 operativos, asignados fuera de Stock 911. No es un equipo TETRA, es otra red.'],

                ['titulo' => 'No Operativos en Terreno', 'valor' => $resumen['no_operativos_en_terreno'], 'icono' => 'fa-map-marker-alt', 'color' => 'bg-danger',
                    'filtro' => ['condicion' => 'no_operativo', 'situacion' => 'instalado'],
                    'info' => 'Equipos no operativos (rotos, de baja, perdidos, degradados o en recambio) que todavía figuran asignados a un recurso real, fuera de Stock 911 — habría que retirarlos del lugar.'],
                ['titulo' => 'HTT-500 sin Movimiento 3+ Años', 'valor' => $resumen['htt500_sin_movimiento'], 'icono' => 'fa-history', 'color' => 'bg-danger',
                    'filtro' => ['marca' => 'Teltronic', 'modelo' => 'HTT500', 'estado_in' => 'Usado,Nuevo,Reparado,Degradado - Sin Accesorios', 'sin_movimiento_3y' => 1],
                    'info' => 'HTT500 en estados "vivos" (Usado/Nuevo/Reparado/Degradado) cuyo último movimiento histórico es de hace más de 3 años, o no tiene histórico registrado.'],
            ];
        @endphp

        {{-- KPIs generales: grilla pareja (5 columnas en escritorio, 2 en mobile), sin huecos --}}
        <div data-dashboard-section="kpis">
            <div class="kpi-grid mb-2">
                @foreach($kpis as $kpi)
                    <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="{{ $kpi['titulo'] }}" data-equipos-filtro='{{ json_encode($kpi['filtro']) }}'>
                        <div class="card-icon {{ $kpi['color'] }}"><i class="fas {{ $kpi['icono'] }}"></i></div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>{{ $kpi['titulo'] }} <i class="fas fa-info-circle info-icon" tabindex="0" data-info-texto="{{ $kpi['info'] }}"></i></h4>
                            </div>
                            <div class="card-body">{{ $kpi['valor'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <small class="text-muted d-block mb-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    "Asignados (Portátiles TETRA)" excluye el HTT500 (sin accesorios) y el VX-261 (no TETRA), que se muestran aparte.
                </small>
                <small class="text-muted d-block mb-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    "Instalados"/"Asignados" solo cuentan equipos operativos. Los no operativos que siguen con un recurso asignado (fuera de Stock 911) están en "No Operativos en Terreno".
                </small>
                <small class="text-muted d-block">
                    <i class="fas fa-info-circle mr-1"></i>
                    Todo lo que está en Sección Técnica (Stock 911, equipos sin reparación, reclamados, etc.) se muestra aparte, más abajo, en "Sección Técnica".
                </small>
            </div>
        </div>

        {{-- Barra de progreso operativo / no operativo / otros --}}
        <div class="row mb-4" data-dashboard-section="condicion">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-1">
                            Total de Equipos: <strong>{{ $resumen['total'] }}</strong>
                            <span class="text-muted mx-1">→</span>
                            Total Operativo: <strong class="text-success">{{ $resumen['operativos'] }}</strong> ({{ $resumen['pct_operativo'] }}%)
                        </h5>
                        <h6 class="mb-2 text-muted" style="font-size:.85rem;">Condición general de la flota:
                            <strong class="text-success">{{ $resumen['pct_operativo'] }}% operativo</strong> /
                            <strong class="text-danger">{{ $resumen['pct_no_operativo'] }}% no operativo</strong> /
                            <strong class="text-secondary">{{ $resumen['pct_otros'] }}% otros estados</strong>
                        </h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: {{ $resumen['pct_operativo'] }}%">
                                @if($resumen['pct_operativo'] >= 8)
                                    {{ $resumen['pct_operativo'] }}%
                                @endif
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $resumen['pct_no_operativo'] }}%">
                                @if($resumen['pct_no_operativo'] >= 8)
                                    {{ $resumen['pct_no_operativo'] }}%
                                @endif
                            </div>
                            <div class="progress-bar bg-secondary" style="width: {{ $resumen['pct_otros'] }}%">
                                @if($resumen['pct_otros'] >= 8)
                                    {{ $resumen['pct_otros'] }}%
                                @endif
                            </div>
                        </div>
                        <div class="progress-legend mt-2">
                            <span><i class="fas fa-square text-success"></i> {{ $resumen['operativos'] }} operativos ({{ $resumen['pct_operativo'] }}%) — Nuevo/Usado/Reparado</span>
                            <span><i class="fas fa-square text-danger"></i> {{ $resumen['no_operativos'] }} no operativos ({{ $resumen['pct_no_operativo'] }}%) — Baja/No funciona/Perdido/Degradado/Recambio</span>
                            <span><i class="fas fa-square text-secondary"></i> {{ $resumen['otros_estados'] }} otros estados ({{ $resumen['pct_otros'] }}%) — Temporal/En revisión</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráficos: vista rápida por tipo de uso, por estado, por marca/modelo y operativo vs no operativo --}}
        <div class="row">
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-instalados-uso">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-broadcast-tower mr-2 text-success"></i>Instalados por Tipo de Uso</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="Equipos operativos con asignación activa fuera de Stock 911, agrupados por tipo de uso. Hacé clic en un sector para ver el detalle."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:280px;">
                            <canvas id="chartInstaladosUso"></canvas>
                        </div>
                        <div id="chartInstaladosUsoValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-por-estado">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-list mr-2 text-info"></i>Por Estado</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="Todos los equipos agrupados por su estado actual. Hacé clic en un sector para ver el detalle."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:280px;">
                            <canvas id="chartPorEstado"></canvas>
                        </div>
                        <div id="chartPorEstadoValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-marca-uso">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-truck-pickup mr-2 text-primary"></i>Marca / Modelo por Tipo de Uso</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="Cantidad total de cada marca/modelo, agrupada por tipo de uso (todos los estados, instalados o no)."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:320px;">
                            <canvas id="chartMarcaUso"></canvas>
                        </div>
                        <div id="chartMarcaUsoValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-operativos">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-balance-scale-right mr-2 text-danger"></i>Operativos vs No Operativos por Marca / Modelo</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="De cada marca/modelo, cuántos están operativos (Nuevo/Usado/Reparado) y cuántos no (Baja/No funciona/Perdido/Degradado/Recambio)."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:320px;">
                            <canvas id="chartOperativos"></canvas>
                        </div>
                        <div id="chartOperativosValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reconciliación completa por tipo de uso: siempre suma al total --}}
        <div class="row" data-dashboard-section="tabla-reconciliacion">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-balance-scale"></i> Reconciliación por Tipo de Uso</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Uso</th>
                                        <th class="text-center">Instalados<br><small class="text-muted">(operativo, en terreno)</small></th>
                                        <th class="text-center">No Operativo<br><small class="text-muted">en terreno</small></th>
                                        <th class="text-center">Otros Estados<br><small class="text-muted">en terreno</small></th>
                                        <th class="text-center">En Stock 911</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($situacionPorTipoUso as $fila)
                                    @php
                                        $base = $usoFiltroBase[$fila->uso] ?? [];
                                        $filtroInstalados = $base + ['condicion' => 'operativo', 'situacion' => 'instalado'];
                                        $filtroNoOperativo = $base + ['condicion' => 'no_operativo', 'situacion' => 'instalado'];
                                        $filtroOtros = $base + ['condicion' => 'otros', 'situacion' => 'instalado'];
                                        $filtroStock = $base + ['situacion' => 'en_stock'];
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $fila->uso }}</strong></td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — Instalados (operativo, en terreno)" data-equipos-filtro='{{ json_encode($filtroInstalados) }}'>
                                            <span class="badge badge-success">{{ $fila->instalados }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — No Operativo en Terreno" data-equipos-filtro='{{ json_encode($filtroNoOperativo) }}'>
                                            <span class="badge badge-danger">{{ $fila->no_operativo_terreno }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — Otros Estados en Terreno" data-equipos-filtro='{{ json_encode($filtroOtros) }}'>
                                            <span class="badge badge-secondary">{{ $fila->otros_terreno }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — En Stock 911" data-equipos-filtro='{{ json_encode($filtroStock) }}'>
                                            <span class="badge badge-warning">{{ $fila->en_stock }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — Total" data-equipos-filtro='{{ json_encode($base) }}'>
                                            <strong>{{ $fila->total }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td>Total</td>
                                        <td class="text-center">{{ $situacionPorTipoUso->sum('instalados') }}</td>
                                        <td class="text-center">{{ $situacionPorTipoUso->sum('no_operativo_terreno') }}</td>
                                        <td class="text-center">{{ $situacionPorTipoUso->sum('otros_terreno') }}</td>
                                        <td class="text-center">{{ $situacionPorTipoUso->sum('en_stock') }}</td>
                                        <td class="text-center">{{ $situacionPorTipoUso->sum('total') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <small class="text-muted">Cada fila suma exactamente el total de esa categoría (Instalados + No Operativo + Otros + En Stock = Total): no hay equipos "perdidos" entre categorías.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Móviles y bases instalados, por marca y modelo --}}
        <div class="row" data-dashboard-section="tabla-moviles-bases">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-truck-pickup"></i> Móviles y Bases Instalados, por Marca y Modelo</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Uso</th>
                                        <th>Marca / Modelo</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($instaladosMovilBasePorMarca as $fila)
                                    <tr class="equipos-clicable" data-equipos-titulo="{{ $fila->marca }} {{ $fila->modelo }} — Instalados" data-equipos-filtro='{{ json_encode(["condicion" => "operativo", "situacion" => "instalado", "marca" => $fila->marca, "modelo" => $fila->modelo]) }}'>
                                        <td><span class="badge badge-info">{{ $fila->uso }}</span></td>
                                        <td>{{ $fila->marca }} {{ $fila->modelo }}</td>
                                        <td class="text-center"><strong>{{ $fila->cantidad }}</strong></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Solo equipos operativos con asignación activa a un móvil/base fuera de Stock 911.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Por tipo de equipo --}}
        <div class="row" data-dashboard-section="tabla-tipo-equipo">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-microchip"></i> Por Tipo de Equipo</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Marca / Modelo</th>
                                        <th>Uso</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Operativos</th>
                                        <th class="text-center">No Operativos</th>
                                        <th class="text-center">% Operativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($porTipoEquipo as $fila)
                                    @php $pctOp = $fila->total > 0 ? round($fila->operativos / $fila->total * 100, 1) : 0; @endphp
                                    <tr>
                                        <td>{{ $fila->marca }} {{ $fila->modelo }}</td>
                                        <td><small class="text-muted">{{ $fila->uso ?? '—' }}</small></td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->marca }} {{ $fila->modelo }} — Todos" data-equipos-filtro='{{ json_encode(["marca" => $fila->marca, "modelo" => $fila->modelo]) }}'>
                                            <strong>{{ $fila->total }}</strong>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->marca }} {{ $fila->modelo }} — Operativos" data-equipos-filtro='{{ json_encode(["marca" => $fila->marca, "modelo" => $fila->modelo, "condicion" => "operativo"]) }}'>
                                            <span class="badge badge-success">{{ $fila->operativos }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->marca }} {{ $fila->modelo }} — No Operativos" data-equipos-filtro='{{ json_encode(["marca" => $fila->marca, "modelo" => $fila->modelo, "condicion" => "no_operativo"]) }}'>
                                            <span class="badge badge-danger">{{ $fila->no_operativos }}</span>
                                        </td>
                                        <td class="text-center">{{ $pctOp }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Por dependencia --}}
        <div class="row" data-dashboard-section="tabla-dependencia">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-sitemap"></i> Por Dependencia (equipos actualmente instalados)</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Dependencia</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Operativos</th>
                                        <th class="text-center">No Operativos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($porDependencia as $fila)
                                    <tr>
                                        <td>{{ $fila->destino_nombre }} <small class="text-muted">({{ $fila->destino_tipo }})</small></td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->destino_nombre }} — Todos" data-equipos-filtro='{{ json_encode(["destino_id" => $fila->destino_id, "situacion" => "instalado"]) }}'>
                                            <strong>{{ $fila->total }}</strong>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->destino_nombre }} — Operativos" data-equipos-filtro='{{ json_encode(["destino_id" => $fila->destino_id, "situacion" => "instalado", "condicion" => "operativo"]) }}'>
                                            <span class="badge badge-success">{{ $fila->operativos }}</span>
                                        </td>
                                        <td class="text-center equipos-clicable" data-equipos-titulo="{{ $fila->destino_nombre }} — No Operativos" data-equipos-filtro='{{ json_encode(["destino_id" => $fila->destino_id, "situacion" => "instalado", "condicion" => "no_operativo"]) }}'>
                                            <span class="badge badge-danger">{{ $fila->no_operativos }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección Técnica: todo lo que hay ahí, no solo Stock 911 --}}
        <h3 class="mb-3 mt-2"><i class="fas fa-tools"></i> Sección Técnica</h3>
        <div class="row mb-3">
            <div class="col-12">
                <small class="text-muted d-block">
                    <i class="fas fa-info-circle mr-1"></i>
                    Todo lo que está físicamente en Sección Técnica: no es solo Stock 911, también incluye Equipos sin
                    reparación, Equipos reclamados, Custodia Blindados, Lote Temporal y otros recursos de esa dependencia.
                    Total: <strong>{{ $resumen['seccion_tecnica_total'] }}</strong> equipos.
                </small>
            </div>
        </div>

        {{-- Cantidades por recurso: misma grilla que las tarjetas KPI generales, sin huecos --}}
        <div data-dashboard-section="seccion-tecnica-kpis">
            <div class="kpi-grid kpi-grid-sm mb-4">
                @foreach($seccionTecnicaPorRecurso as $fila)
                    <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Sección Técnica — {{ $fila->recurso }}" data-equipos-filtro='{{ json_encode(["recurso_id" => $fila->recurso_id]) }}'>
                        <div class="card-icon bg-secondary"><i class="fas fa-box"></i></div>
                        <div class="card-wrap">
                            <div class="card-header"><h4>{{ $fila->recurso }}</h4></div>
                            <div class="card-body">{{ $fila->cantidad }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-seccion-tecnica-recurso">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-boxes mr-2 text-secondary"></i>Sección Técnica por Recurso</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="Distribución de los equipos de Sección Técnica según en qué recurso están cargados. Hacé clic en un sector para ver el detalle."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:280px;">
                            <canvas id="chartSeccionTecnicaRecurso"></canvas>
                        </div>
                        <div id="chartSeccionTecnicaRecursoValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6 mb-4">
                <div class="card chart-card h-100" data-dashboard-section="chart-seccion-tecnica-marca">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-microchip mr-2 text-primary"></i>Sección Técnica por Marca / Modelo</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="Cantidad de cada marca/modelo sumando todos los recursos de Sección Técnica."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:280px;">
                            <canvas id="chartSeccionTecnicaMarca"></canvas>
                        </div>
                        <div id="chartSeccionTecnicaMarcaValores" class="chart-values-list mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card chart-card" data-dashboard-section="chart-seccion-tecnica-operativo">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="chart-title mb-0"><i class="fas fa-heartbeat mr-2 text-danger"></i>Operativo vs No Operativo, por Recurso (dentro de Sección Técnica)</h4>
                        <i class="fas fa-info-circle info-icon-static" data-toggle="tooltip" title="De lo que hay en cada recurso de Sección Técnica, cuánto está en condición operativa (Nuevo/Usado/Reparado) y cuánto no."></i>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:320px;">
                            <canvas id="chartSeccionTecnicaOperativo"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" data-dashboard-section="tabla-seccion-tecnica">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-list"></i> Sección Técnica — Detalle por Recurso y Tipo de Equipo</h4></div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Recurso</th>
                                        <th>Marca / Modelo</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($seccionTecnicaPorRecursoYTipo as $fila)
                                    <tr class="equipos-clicable" data-equipos-titulo="{{ $fila->recurso }} — {{ $fila->marca }} {{ $fila->modelo }}" data-equipos-filtro='{{ json_encode(["recurso_id" => $fila->recurso_id, "marca" => $fila->marca, "modelo" => $fila->modelo]) }}'>
                                        <td><small class="text-muted">{{ $fila->recurso }}</small></td>
                                        <td>{{ $fila->marca }} {{ $fila->modelo }}</td>
                                        <td class="text-center"><strong>{{ $fila->cantidad }}</strong></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle HTT-500 sin movimiento reciente --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-history"></i> Detalle: HTT-500 sin Movimiento en los Últimos 3 Años</h4></div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr class="bg-light">
                                        <th>TEI</th>
                                        <th>ISSI</th>
                                        <th>Estado</th>
                                        <th>Recurso / Dependencia</th>
                                        <th>Último Movimiento</th>
                                        <th>Fecha Último Movimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($htt500SinMovimiento as $eq)
                                    <tr>
                                        <td><a class="tei-badge" href="{{ route('verHistoricoDesdeEquipo', $eq->id) }}" target="_blank" rel="noopener" title="Ver histórico">{{ $eq->tei }}</a></td>
                                        <td><small class="text-muted">{{ $eq->issi ?? '—' }}</small></td>
                                        @php $eColor = $estadoColores[$eq->estado] ?? $estadoColorDefault; @endphp
                                        <td><span class="estado-badge {{ $eColor['clase'] }}">{{ $eq->estado }}</span></td>
                                        <td>{{ $eq->recurso ?? '—' }} <small class="text-muted">{{ $eq->dependencia ? '('.$eq->dependencia.')' : '' }}</small></td>
                                        <td><small class="text-muted">{{ $eq->ultimo_movimiento_tipo ?? 'Sin histórico' }}</small></td>
                                        <td>{{ $eq->ultimo_movimiento_fecha ? \Carbon\Carbon::parse($eq->ultimo_movimiento_fecha)->format('d-m-Y') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('equipos.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Terminales</a>
    </div>
</section>

{{-- Modal genérico de detalle: se reutiliza para cualquier contador/fila/gráfico clicable --}}
<div class="modal fade" id="modalDetalleEquipos" tabindex="-1" role="dialog" aria-labelledby="modalDetalleEquiposTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleEquiposTitulo">Detalle de equipos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:.5rem;">
                    <input type="text" id="modalDetalleEquiposBuscar" class="form-control" style="max-width:320px;"
                        placeholder="Buscar por TEI, ISSI, recurso, dependencia...">
                    <div class="d-flex align-items-center" style="gap:.5rem;">
                        <select id="modalDetalleEquiposFiltroEstado" class="form-control form-control-sm">
                            <option value="">Todos los estados</option>
                        </select>
                        <span class="badge badge-primary" id="modalDetalleEquiposContador">0</span>
                    </div>
                </div>
                <div id="modalDetalleEquiposLoading" class="text-center py-5" style="display:none;">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
                <div class="table-responsive" style="max-height:55vh;overflow-y:auto;">
                    <table class="table table-hover table-sm" id="modalDetalleEquiposTabla">
                        <thead>
                            <tr class="bg-light">
                                <th>TEI</th>
                                <th>ISSI</th>
                                <th>Marca / Modelo</th>
                                <th>Estado</th>
                                <th>Recurso</th>
                                <th>Dependencia</th>
                                <th>Fecha Estado</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="modalDetalleEquiposVacio" class="text-center text-muted py-5" style="display:none;">
                    <i class="fas fa-search fa-2x mb-2 d-block"></i> Sin resultados
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card{border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
    .progress-legend{display:flex;gap:1rem;flex-wrap:wrap;font-size:.85rem;color:var(--text-secondary)}

    /* Grilla de tarjetas KPI: 5 por fila en escritorio, 2 en mobile (10 tarjetas ÷
       5 y ÷ 2 dan exacto, sin fila incompleta) — a diferencia de las columnas de
       Bootstrap, que dejan huecos si el total no es múltiplo de las columnas. */
    .kpi-grid{display:grid;grid-template-columns:repeat(5, 1fr);gap:1rem}
    .kpi-grid .card{margin-bottom:0}
    @media (max-width: 767px){.kpi-grid{grid-template-columns:repeat(2, 1fr)}}
    /* Variante para nombres de recurso largos (Sección Técnica): título más chico */
    .kpi-grid-sm .card-header h4{font-size:.72rem;line-height:1.15}
    .kpi-grid-sm .card-body{font-size:1.1rem}
    .equipos-clicable{cursor:pointer}
    .equipos-clicable:hover{filter:brightness(0.92)}
    tr.equipos-clicable:hover{background-color:rgba(0,0,0,.035)}

    /* El tema oscuro global le da fondo oscuro al <thead> pero no al <tfoot>;
       sin esto, el texto queda claro (forzado por .table) sobre el bg-light
       de Bootstrap, que sigue gris claro. */
    [data-theme="dark"] .table tfoot tr.bg-light,
    [data-theme="dark"] .table tfoot tr.bg-light td {
        background-color: var(--bg-secondary) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-color) !important;
    }

    /* Tarjetas KPI: el icono flotado (.card-icon) necesita que el card lo "contenga" */
    .card-statistic-1.equipos-clicable::after{content:"";display:table;clear:both}
    .info-icon, .info-icon-static{
        font-size:.8rem;
        color:rgba(0,0,0,.35);
        cursor:pointer;
        margin-left:4px;
        vertical-align:middle;
    }
    .info-icon:hover, .info-icon-static:hover{color:#007bff}
    [data-theme="dark"] .info-icon, [data-theme="dark"] .info-icon-static{color:rgba(255,255,255,.45)}
    [data-theme="dark"] .info-icon:hover, [data-theme="dark"] .info-icon-static:hover{color:#63b3ed}

    .popover{max-width:320px}
    .popover-body{max-height:260px;overflow-y:auto}

    /* Tarjetas de gráficos: mismo estilo visual que chart-card del resto del sistema */
    .chart-card{border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.1);overflow:hidden}
    .chart-card .card-header{border-bottom:none;background:transparent}
    .chart-card .chart-title{color:var(--text-primary) !important;font-weight:600;font-size:1rem;margin:0}
    [data-theme="dark"] .chart-card{background-color:#1e293b !important;border-color:rgba(255,255,255,.1) !important;box-shadow:0 4px 6px rgba(0,0,0,.3)}
    [data-theme="dark"] .chart-card .chart-title{color:#e2e8f0 !important}

    /* Lista de valores numéricos debajo de cada gráfico (para leerlos rápido y que salgan en el PDF) */
    .chart-values-list{display:flex;flex-wrap:wrap;gap:.35rem .75rem;font-size:.78rem;color:var(--text-secondary,#6c757d);border-top:1px solid rgba(0,0,0,.08);padding-top:.5rem}
    .chart-values-list .valor-item{max-width:100%;overflow-wrap:break-word}
    .chart-values-list .valor-item strong{color:var(--text-primary,#111)}
    [data-theme="dark"] .chart-values-list{border-top-color:rgba(255,255,255,.1)}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Datos para los gráficos (ya calculados por el backend) ─────────────
    const porTipoUsoData = @json($porTipoUso);
    const porEstadoData = @json($porEstado);
    const porTipoEquipoData = @json($porTipoEquipo);
    const usoFiltroBase = @json($usoFiltroBase);
    const seccionTecnicaPorRecurso = @json($seccionTecnicaPorRecurso);
    const seccionTecnicaPorRecursoYTipo = @json($seccionTecnicaPorRecursoYTipo);
    const estadoColores = @json(collect($estadoColores)->map(fn ($c) => $c['hex']));
    const estadoColorDefault = @json($estadoColorDefault['hex']);
    const detalleUrl = "{{ route('equipos.estadisticas.detalle') }}";

    function colorPorEstado(nombreEstado) {
        return estadoColores[nombreEstado] || estadoColorDefault;
    }

    // ── Modal genérico de detalle ───────────────────────────────────────────
    let equiposActuales = [];

    function escapeHtml(valor) {
        return (valor ?? '').toString()
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function urlHistorico(equipoId) {
        return "{{ route('verHistoricoDesdeEquipo', ['id' => '__ID__']) }}".replace('__ID__', equipoId);
    }

    function renderTabla(equipos) {
        const tbody = document.querySelector('#modalDetalleEquiposTabla tbody');
        tbody.innerHTML = equipos.map(function (eq) {
            return '<tr>'
                + '<td><a href="' + urlHistorico(eq.id) + '" target="_blank" rel="noopener" title="Ver histórico">' + escapeHtml(eq.tei) + '</a></td>'
                + '<td><small class="text-muted">' + escapeHtml(eq.issi ?? '—') + '</small></td>'
                + '<td>' + escapeHtml(eq.marca) + ' ' + escapeHtml(eq.modelo) + '</td>'
                + '<td><span class="estado-badge" style="background-color:' + colorPorEstado(eq.estado) + '">' + escapeHtml(eq.estado) + '</span></td>'
                + '<td>' + escapeHtml(eq.recurso ?? '—') + '</td>'
                + '<td>' + escapeHtml(eq.dependencia ?? '—') + '</td>'
                + '<td>' + escapeHtml(eq.fecha_estado_fmt ?? '—')
                    + (eq.fecha_estado_es_aproximada ? ' <small class="text-muted" title="No hay fecha de estado cargada; se usa la fecha del último movimiento del histórico como aproximación">(aprox.)</small>' : '')
                    + '</td>'
                + '</tr>';
        }).join('');
        document.getElementById('modalDetalleEquiposContador').textContent = equipos.length;
        document.getElementById('modalDetalleEquiposVacio').style.display = equipos.length ? 'none' : 'block';
        document.getElementById('modalDetalleEquiposTabla').style.display = equipos.length ? '' : 'none';
    }

    function poblarFiltroEstado(equipos) {
        const select = document.getElementById('modalDetalleEquiposFiltroEstado');
        const estados = [...new Set(equipos.map(function (e) { return e.estado; }).filter(Boolean))].sort();
        const actual = select.value;
        select.innerHTML = '<option value="">Todos los estados</option>'
            + estados.map(function (e) { return '<option value="' + escapeHtml(e) + '">' + escapeHtml(e) + '</option>'; }).join('');
        select.value = estados.includes(actual) ? actual : '';
    }

    function aplicarFiltros() {
        const texto = document.getElementById('modalDetalleEquiposBuscar').value.trim().toLowerCase();
        const estado = document.getElementById('modalDetalleEquiposFiltroEstado').value;
        const filtrados = equiposActuales.filter(function (eq) {
            if (estado && eq.estado !== estado) return false;
            if (!texto) return true;
            return ['tei', 'issi', 'marca', 'modelo', 'estado', 'recurso', 'dependencia'].some(function (campo) {
                return (eq[campo] ?? '').toString().toLowerCase().includes(texto);
            });
        });
        renderTabla(filtrados);
    }

    document.getElementById('modalDetalleEquiposBuscar').addEventListener('input', aplicarFiltros);
    document.getElementById('modalDetalleEquiposFiltroEstado').addEventListener('change', aplicarFiltros);

    function abrirModalConFiltro(titulo, filtro) {
        document.getElementById('modalDetalleEquiposTitulo').textContent = titulo;
        document.getElementById('modalDetalleEquiposBuscar').value = '';
        equiposActuales = [];
        renderTabla([]);
        document.getElementById('modalDetalleEquiposLoading').style.display = 'block';
        $('#modalDetalleEquipos').modal('show');

        const params = new URLSearchParams(filtro).toString();
        return fetch(detalleUrl + (params ? '?' + params : ''))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                equiposActuales = Array.isArray(data) ? data : [];
                poblarFiltroEstado(equiposActuales);
                renderTabla(equiposActuales);
                return equiposActuales;
            })
            .catch(function () {
                equiposActuales = [];
                renderTabla([]);
                return [];
            })
            .finally(function () {
                document.getElementById('modalDetalleEquiposLoading').style.display = 'none';
            });
    }

    document.querySelectorAll('[data-equipos-filtro]').forEach(function (el) {
        el.addEventListener('click', function () {
            let filtro = {};
            try { filtro = JSON.parse(el.getAttribute('data-equipos-filtro') || '{}'); } catch (e) { filtro = {}; }
            const titulo = el.getAttribute('data-equipos-titulo') || 'Detalle de equipos';
            abrirModalConFiltro(titulo, filtro);
        });
    });

    // ── Icono de info por tarjeta: explicación + desglose por marca/modelo ──
    document.querySelectorAll('.info-icon').forEach(function (icon) {
        icon.addEventListener('click', function (e) {
            e.stopPropagation();
            e.preventDefault();

            const yaAbierto = $(icon).data('popover-abierto');
            $('.info-icon').each(function () {
                $(this).popover('dispose');
                $(this).data('popover-abierto', false);
            });
            if (yaAbierto) {
                return;
            }

            const parentCard = icon.closest('[data-equipos-filtro]');
            let filtro = {};
            try { filtro = parentCard ? JSON.parse(parentCard.getAttribute('data-equipos-filtro') || '{}') : {}; } catch (err) { filtro = {}; }
            const texto = icon.getAttribute('data-info-texto') || '';

            $(icon).popover({
                html: true,
                trigger: 'manual',
                placement: 'bottom',
                title: 'Qué se cuenta acá',
                content: '<div>' + escapeHtml(texto) + '</div><div class="text-center py-2"><i class="fas fa-spinner fa-spin text-muted"></i></div>',
                container: 'body'
            }).popover('show');
            $(icon).data('popover-abierto', true);

            const params = new URLSearchParams(filtro).toString();
            fetch(detalleUrl + (params ? '?' + params : ''))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const conteo = {};
                    (Array.isArray(data) ? data : []).forEach(function (eq) {
                        const clave = ((eq.marca || '') + ' ' + (eq.modelo || '')).trim() || 'Sin modelo';
                        conteo[clave] = (conteo[clave] || 0) + 1;
                    });
                    const claves = Object.keys(conteo).sort();
                    const filas = claves.length
                        ? claves.map(function (k) {
                            return '<div class="d-flex justify-content-between" style="gap:1rem;"><span>' + escapeHtml(k) + '</span><strong>' + conteo[k] + '</strong></div>';
                        }).join('')
                        : '<div class="text-muted">Sin equipos</div>';
                    const total = Array.isArray(data) ? data.length : 0;

                    const contenido = '<div class="mb-2">' + escapeHtml(texto) + '</div>'
                        + '<hr class="my-2">'
                        + '<div class="small text-muted mb-1">Por marca / modelo (' + total + ' en total):</div>'
                        + filas;

                    $(icon).popover('dispose').popover({
                        html: true, trigger: 'manual', placement: 'bottom',
                        title: 'Qué se cuenta acá', content: contenido, container: 'body'
                    }).popover('show');
                    $(icon).data('popover-abierto', true);
                })
                .catch(function () {});
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('info-icon')) {
            $('.info-icon').each(function () {
                $(this).popover('dispose');
                $(this).data('popover-abierto', false);
            });
        }
    });

    $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover', delay: { show: 300, hide: 100 } });

    // ── Gráficos ─────────────────────────────────────────────────────────
    const paletteQual = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#a855f7', '#14b8a6', '#f43f5e', '#6366f1', '#84cc16', '#eab308'];

    function detectTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function renderValoresChart(containerId, items) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = items.map(function (it) {
            return '<span class="valor-item">' + escapeHtml(it.label) + ': <strong>' + it.value + '</strong></span>';
        }).join('');
    }

    let chartInstances = {};

    function createCharts() {
        Object.values(chartInstances).forEach(function (chart) { if (chart) chart.destroy(); });
        chartInstances = {};

        const isDark = detectTheme();
        const textColor = isDark ? '#e2e8f0' : '#111827';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(17,24,39,0.12)';
        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = gridColor;

        const tooltipStyle = {
            backgroundColor: isDark ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
            titleColor: isDark ? '#f1f5f9' : '#1e293b',
            bodyColor: isDark ? '#cbd5e1' : '#374151',
            borderColor: isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8
        };

        // 1) Instalados por Tipo de Uso (doughnut, clicable)
        const canvasInstaladosUso = document.getElementById('chartInstaladosUso');
        if (canvasInstaladosUso) {
            chartInstances.instaladosUso = new Chart(canvasInstaladosUso.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: porTipoUsoData.map(function (f) { return f.uso; }),
                    datasets: [{
                        data: porTipoUsoData.map(function (f) { return f.cantidad; }),
                        backgroundColor: paletteQual,
                        borderWidth: isDark ? 0 : 2,
                        borderColor: isDark ? 'transparent' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    onClick: function (evt, elements) {
                        if (!elements.length) return;
                        const uso = porTipoUsoData[elements[0].index].uso;
                        const base = usoFiltroBase[uso] || {};
                        abrirModalConFiltro(uso + ' — Instalados', Object.assign({}, base, { condicion: 'operativo', situacion: 'instalado' }));
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 10, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: tooltipStyle
                    }
                }
            });
            renderValoresChart('chartInstaladosUsoValores', porTipoUsoData.map(function (f) {
                return { label: f.uso, value: f.cantidad };
            }));
        }

        // 2) Por Estado (doughnut, clicable)
        const canvasPorEstado = document.getElementById('chartPorEstado');
        if (canvasPorEstado) {
            chartInstances.porEstado = new Chart(canvasPorEstado.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: porEstadoData.map(function (f) { return f.estado; }),
                    datasets: [{
                        data: porEstadoData.map(function (f) { return f.cantidad; }),
                        backgroundColor: porEstadoData.map(function (f) { return colorPorEstado(f.estado); }),
                        borderWidth: isDark ? 0 : 2,
                        borderColor: isDark ? 'transparent' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    onClick: function (evt, elements) {
                        if (!elements.length) return;
                        const estado = porEstadoData[elements[0].index].estado;
                        abrirModalConFiltro('Estado: ' + estado, { estado_in: estado });
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 10, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: tooltipStyle
                    }
                }
            });
            renderValoresChart('chartPorEstadoValores', porEstadoData.map(function (f) {
                return { label: f.estado, value: f.cantidad };
            }));
        }

        // 3) Marca / Modelo por Tipo de Uso (barras apiladas)
        const canvasMarcaUso = document.getElementById('chartMarcaUso');
        if (canvasMarcaUso) {
            const usos = [...new Set(porTipoEquipoData.map(function (f) { return f.uso || 'Sin uso'; }))];
            const modelos = [...new Set(porTipoEquipoData.map(function (f) { return f.marca + ' ' + f.modelo; }))];
            const datasets = modelos.map(function (modelo, idx) {
                return {
                    label: modelo,
                    data: usos.map(function (uso) {
                        const fila = porTipoEquipoData.find(function (f) {
                            return (f.marca + ' ' + f.modelo) === modelo && (f.uso || 'Sin uso') === uso;
                        });
                        return fila ? fila.total : 0;
                    }),
                    backgroundColor: paletteQual[idx % paletteQual.length]
                };
            });
            chartInstances.marcaUso = new Chart(canvasMarcaUso.getContext('2d'), {
                type: 'bar',
                data: { labels: usos, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, grid: { color: gridColor } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 8, font: { size: 10 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: tooltipStyle
                    }
                }
            });
            renderValoresChart('chartMarcaUsoValores', porTipoEquipoData.map(function (f) {
                return { label: (f.marca + ' ' + f.modelo).trim(), value: f.total };
            }));
        }

        // 4) Operativos vs No Operativos por Marca / Modelo (barras horizontales apiladas)
        const canvasOperativos = document.getElementById('chartOperativos');
        if (canvasOperativos) {
            chartInstances.operativos = new Chart(canvasOperativos.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: porTipoEquipoData.map(function (f) { return f.marca + ' ' + f.modelo; }),
                    datasets: [
                        { label: 'Operativos', data: porTipoEquipoData.map(function (f) { return f.operativos; }), backgroundColor: '#10b981' },
                        { label: 'No Operativos', data: porTipoEquipoData.map(function (f) { return f.no_operativos; }), backgroundColor: '#ef4444' }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, beginAtZero: true, grid: { color: gridColor } },
                        y: { stacked: true, grid: { display: false } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 8, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: tooltipStyle
                    }
                }
            });
            renderValoresChart('chartOperativosValores', porTipoEquipoData.map(function (f) {
                return { label: (f.marca + ' ' + f.modelo).trim(), value: f.operativos + ' / ' + f.no_operativos };
            }));
        }

        // 5) Sección Técnica por Recurso (doughnut, clicable)
        const canvasSecTecRecurso = document.getElementById('chartSeccionTecnicaRecurso');
        if (canvasSecTecRecurso) {
            chartInstances.secTecRecurso = new Chart(canvasSecTecRecurso.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: seccionTecnicaPorRecurso.map(function (f) { return f.recurso; }),
                    datasets: [{
                        data: seccionTecnicaPorRecurso.map(function (f) { return f.cantidad; }),
                        backgroundColor: paletteQual,
                        borderWidth: isDark ? 0 : 2,
                        borderColor: isDark ? 'transparent' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    onClick: function (evt, elements) {
                        if (!elements.length) return;
                        const fila = seccionTecnicaPorRecurso[elements[0].index];
                        abrirModalConFiltro('Sección Técnica — ' + fila.recurso, { recurso_id: fila.recurso_id });
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 8, font: { size: 10 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: Object.assign({}, tooltipStyle, {
                            callbacks: {
                                label: function (context) {
                                    return context.label + ': ' + context.parsed + ' equipos';
                                },
                                afterLabel: function (context) {
                                    const recurso = seccionTecnicaPorRecurso[context.dataIndex].recurso;
                                    return seccionTecnicaPorRecursoYTipo
                                        .filter(function (f) { return f.recurso === recurso; })
                                        .map(function (f) { return '  ' + f.marca + ' ' + f.modelo + ': ' + f.cantidad; });
                                }
                            }
                        })
                    }
                }
            });
            renderValoresChart('chartSeccionTecnicaRecursoValores', seccionTecnicaPorRecurso.map(function (f) {
                const detalle = seccionTecnicaPorRecursoYTipo
                    .filter(function (d) { return d.recurso === f.recurso; })
                    .map(function (d) { return d.marca + ' ' + d.modelo + ' x' + d.cantidad; })
                    .join(', ');
                return { label: f.recurso + (detalle ? ' (' + detalle + ')' : ''), value: f.cantidad };
            }));
        }

        // 6) Sección Técnica por Marca / Modelo (bar, sumando todos los recursos)
        const canvasSecTecMarca = document.getElementById('chartSeccionTecnicaMarca');
        if (canvasSecTecMarca) {
            const totalesPorModelo = {};
            seccionTecnicaPorRecursoYTipo.forEach(function (f) {
                const clave = (f.marca + ' ' + f.modelo).trim();
                totalesPorModelo[clave] = (totalesPorModelo[clave] || 0) + f.cantidad;
            });
            const modelosSecTec = Object.keys(totalesPorModelo).sort(function (a, b) { return totalesPorModelo[b] - totalesPorModelo[a]; });

            chartInstances.secTecMarca = new Chart(canvasSecTecMarca.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: modelosSecTec,
                    datasets: [{
                        label: 'Cantidad',
                        data: modelosSecTec.map(function (m) { return totalesPorModelo[m]; }),
                        backgroundColor: paletteQual[0]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: gridColor } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: tooltipStyle
                    }
                }
            });
            renderValoresChart('chartSeccionTecnicaMarcaValores', modelosSecTec.map(function (m) {
                return { label: m, value: totalesPorModelo[m] };
            }));
        }

        // 7) Sección Técnica: Operativo vs No Operativo, por recurso (horizontal apilado)
        const canvasSecTecOperativo = document.getElementById('chartSeccionTecnicaOperativo');
        if (canvasSecTecOperativo) {
            chartInstances.secTecOperativo = new Chart(canvasSecTecOperativo.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: seccionTecnicaPorRecurso.map(function (f) { return f.recurso; }),
                    datasets: [
                        { label: 'Operativo', data: seccionTecnicaPorRecurso.map(function (f) { return f.operativos; }), backgroundColor: colorPorEstado('Nuevo') },
                        { label: 'No Operativo', data: seccionTecnicaPorRecurso.map(function (f) { return f.no_operativos; }), backgroundColor: colorPorEstado('Baja') }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, beginAtZero: true, grid: { color: gridColor } },
                        y: { stacked: true, grid: { display: false } }
                    },
                    onClick: function (evt, elements) {
                        if (!elements.length) return;
                        const idx = elements[0].index;
                        const datasetIndex = elements[0].datasetIndex;
                        const fila = seccionTecnicaPorRecurso[idx];
                        const condicion = datasetIndex === 0 ? 'operativo' : 'no_operativo';
                        abrirModalConFiltro(fila.recurso + ' — ' + (datasetIndex === 0 ? 'Operativo' : 'No Operativo'), { recurso_id: fila.recurso_id, condicion: condicion });
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, padding: 8, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                        tooltip: tooltipStyle
                    }
                }
            });
        }
    }

    createCharts();

    const themeObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                createCharts();
            }
        });
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    // ── Exportar a PDF (título + KPIs + condición general + gráficos) ──────
    document.getElementById('btnExportarPdf').addEventListener('click', async function () {
        if (!window.jspdf || typeof html2canvas === 'undefined') {
            alert('No se encontraron las librerías necesarias para generar el PDF.');
            return;
        }

        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>Generando...';

        try {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const margin = 12;
            const contentWidth = pageWidth - margin * 2;
            let y = 14;

            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(15);
            pdf.text('Equipamientos - Estadísticas', margin, y);
            y += 7;
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            pdf.text('Generado: ' + new Date().toLocaleString('es-AR', { hour12: false }), margin, y);
            y += 5;
            pdf.text(
                'Total: {{ $resumen["total"] }} equipos | Operativo: {{ $resumen["pct_operativo"] }}% | No operativo: {{ $resumen["pct_no_operativo"] }}% | Otros: {{ $resumen["pct_otros"] }}%',
                margin, y
            );
            y += 8;

            const secciones = [
                'kpis', 'condicion',
                'chart-instalados-uso', 'chart-por-estado', 'chart-marca-uso', 'chart-operativos',
                'tabla-reconciliacion', 'tabla-moviles-bases', 'tabla-tipo-equipo', 'tabla-dependencia',
                'seccion-tecnica-kpis', 'chart-seccion-tecnica-recurso', 'chart-seccion-tecnica-marca',
                'chart-seccion-tecnica-operativo', 'tabla-seccion-tecnica'
            ];
            const etiquetas = {
                'kpis': 'Resumen general',
                'condicion': 'Condición general de la flota',
                'chart-instalados-uso': 'Instalados por Tipo de Uso',
                'chart-por-estado': 'Por Estado',
                'chart-marca-uso': 'Marca / Modelo por Tipo de Uso',
                'chart-operativos': 'Operativos vs No Operativos por Marca / Modelo',
                'tabla-reconciliacion': 'Reconciliación por Tipo de Uso',
                'tabla-moviles-bases': 'Móviles y Bases Instalados, por Marca y Modelo',
                'tabla-tipo-equipo': 'Por Tipo de Equipo',
                'tabla-dependencia': 'Por Dependencia (equipos actualmente instalados)',
                'seccion-tecnica-kpis': 'Sección Técnica — Cantidades por Recurso',
                'chart-seccion-tecnica-recurso': 'Sección Técnica por Recurso',
                'chart-seccion-tecnica-marca': 'Sección Técnica por Marca / Modelo',
                'chart-seccion-tecnica-operativo': 'Sección Técnica — Operativo vs No Operativo por Recurso',
                'tabla-seccion-tecnica': 'Sección Técnica — Detalle por Recurso y Tipo de Equipo'
            };

            // Alto máximo de imagen que entra en una página en blanco (deja lugar para el título)
            const maxHeightPorPagina = (pageHeight - margin * 2) - 8;

            for (const key of secciones) {
                const el = document.querySelector('[data-dashboard-section="' + key + '"]');
                if (!el) continue;

                const canvas = await html2canvas(el, { backgroundColor: '#ffffff', scale: 2 });
                const pxPorMm = canvas.width / contentWidth;
                const alturaTotalMm = canvas.height / pxPorMm;

                // Si entra completa en una página, no se corta en varias imágenes
                let slices;
                if (alturaTotalMm <= maxHeightPorPagina) {
                    slices = [{ dataUrl: canvas.toDataURL('image/png'), alturaMm: alturaTotalMm }];
                } else {
                    slices = [];
                    const altoSlicePx = Math.floor(maxHeightPorPagina * pxPorMm);
                    let offsetPx = 0;
                    while (offsetPx < canvas.height) {
                        const alturaPx = Math.min(altoSlicePx, canvas.height - offsetPx);
                        const sliceCanvas = document.createElement('canvas');
                        sliceCanvas.width = canvas.width;
                        sliceCanvas.height = alturaPx;
                        sliceCanvas.getContext('2d').drawImage(canvas, 0, offsetPx, canvas.width, alturaPx, 0, 0, canvas.width, alturaPx);
                        slices.push({ dataUrl: sliceCanvas.toDataURL('image/png'), alturaMm: alturaPx / pxPorMm });
                        offsetPx += alturaPx;
                    }
                }

                for (let i = 0; i < slices.length; i++) {
                    const slice = slices[i];

                    if (y + slice.alturaMm + 10 > pageHeight - margin) {
                        pdf.addPage();
                        y = margin;
                    }

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(10);
                    const titulo = (etiquetas[key] || key) + (slices.length > 1 ? ' (' + (i + 1) + '/' + slices.length + ')' : '');
                    pdf.text(titulo, margin, y);
                    y += 5;
                    pdf.addImage(slice.dataUrl, 'PNG', margin, y, contentWidth, slice.alturaMm);
                    y += slice.alturaMm + 8;
                }
            }

            pdf.save('estadisticas-equipamiento-' + new Date().toISOString().slice(0, 10) + '.pdf');
        } catch (error) {
            console.error(error);
            alert('No se pudo generar el PDF. Revisá la consola.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
});
</script>
@endpush
