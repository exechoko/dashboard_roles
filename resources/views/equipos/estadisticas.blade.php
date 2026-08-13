@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="section-title"><i class="fas fa-chart-pie"></i> Equipamientos - Estadísticas</h1>
        <a href="{{ route('equipos.estadisticas.export') }}" class="btn btn-success">
            <i class="fas fa-file-excel mr-1"></i> Exportar a Excel
        </a>
    </div>
    <div class="section-body">

        {{-- KPIs generales --}}
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Total de Equipos" data-equipos-filtro="{}">
                    <div class="card-icon bg-primary"><i class="fas fa-microchip"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Total Equipos</h4></div>
                    <div class="card-body">{{ $resumen['total'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Operativos (Nuevo/Usado/Reparado)" data-equipos-filtro='{"condicion":"operativo"}'>
                    <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Operativos</h4></div>
                    <div class="card-body">{{ $resumen['operativos'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="No Operativos (Baja/No funciona/Perdido/Degradado)" data-equipos-filtro='{"condicion":"no_operativo"}'>
                    <div class="card-icon bg-danger"><i class="fas fa-times-circle"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>No Operativos</h4></div>
                    <div class="card-body">{{ $resumen['no_operativos'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="En Revisión Técnica (último movimiento histórico)" data-equipos-filtro='{"ultimo_movimiento":"Revisión"}'>
                    <div class="card-icon bg-info"><i class="fas fa-tools"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>En Revisión Técnica</h4></div>
                    <div class="card-body">{{ $resumen['en_revision_tecnica'] }}</div></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Instalados (Móvil/Base)" data-equipos-filtro='{"condicion":"operativo","situacion":"instalado","excluir_portatil":1}'>
                    <div class="card-icon bg-success"><i class="fas fa-plug"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Instalados (Móvil/Base)</h4></div>
                    <div class="card-body">{{ $resumen['instalados'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Asignados (Portátiles TETRA, sin HTT500 ni VX-261)" data-equipos-filtro='{"condicion":"operativo","situacion":"instalado","uso":"Portatil","excluir_modelos":"Teltronic:HTT500","excluir_marcas":"Motorola\/Vertex"}'>
                    <div class="card-icon bg-primary"><i class="fas fa-walkie-talkie"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Asignados (Portátiles TETRA)</h4></div>
                    <div class="card-body">{{ $resumen['asignados_portatiles'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="HTT500 Asignados (sin Accesorios)" data-equipos-filtro='{"condicion":"operativo","situacion":"instalado","marca":"Teltronic","modelo":"HTT500"}'>
                    <div class="card-icon bg-danger"><i class="fas fa-battery-empty"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>HTT500 Asignados (sin Accesorios)</h4></div>
                    <div class="card-body">{{ $resumen['htt500_asignados'] }}</div></div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="Desinstalados (MDT400/DT410/SRG3900 en Stock 911)" data-equipos-filtro='{"situacion":"en_stock","modelos":"Teltronic:MDT400,Teltronic:DT410,Sepura:SRG3900"}'>
                    <div class="card-icon bg-warning"><i class="fas fa-warehouse"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Desinstalados</h4></div>
                    <div class="card-body">{{ $resumen['desinstalados'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="En Depósito (Portátiles/Otros en Stock 911)" data-equipos-filtro='{"situacion":"en_stock","excluir_modelos":"Teltronic:MDT400,Teltronic:DT410,Sepura:SRG3900"}'>
                    <div class="card-icon bg-secondary"><i class="fas fa-box"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>En Depósito (Portátiles/Otros)</h4></div>
                    <div class="card-body">{{ $resumen['en_deposito_otros'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="VX-261 Asignados (no TETRA)" data-equipos-filtro='{"condicion":"operativo","situacion":"instalado","marca":"Motorola\/Vertex"}'>
                    <div class="card-icon bg-dark"><i class="fas fa-satellite-dish"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>VX-261 Asignados (no TETRA)</h4></div>
                    <div class="card-body">{{ $resumen['vertex_asignados'] }}</div></div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    "Asignados (Portátiles TETRA)" excluye dos casos que se muestran aparte: el HTT500 (aunque su
                    estado diga Usado/Nuevo/Reparado, ya no hay baterías ni antenas para equiparlos) y el VX-261 de
                    Motorola/Vertex (no es un equipo TETRA, es otra red).
                </small>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="HTT-500 sin Movimiento en los Últimos 3 Años" data-equipos-filtro='{"marca":"Teltronic","modelo":"HTT500","estado_in":"Usado,Nuevo,Reparado,Degradado - Sin Accesorios","sin_movimiento_3y":1}'>
                    <div class="card-icon bg-danger"><i class="fas fa-history"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>HTT-500 sin Movimiento 3+ Años</h4></div>
                    <div class="card-body">{{ $resumen['htt500_sin_movimiento'] }}</div></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-statistic-1 equipos-clicable" data-equipos-titulo="No Operativos en Terreno (asignados, fuera de Stock 911)" data-equipos-filtro='{"condicion":"no_operativo","situacion":"instalado"}'>
                    <div class="card-icon bg-danger"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>No Operativos en Terreno</h4></div>
                    <div class="card-body">{{ $resumen['no_operativos_en_terreno'] }}</div></div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    "Instalados" y "Asignados" solo cuentan equipos operativos (Nuevo/Usado/Reparado). Los equipos no
                    operativos (Baja/No funciona/Perdido/Degradado) que todavía figuran con un recurso o dependencia
                    asignada —no en Stock 911— se muestran aparte en "No Operativos en Terreno": son los que habría
                    que retirar/reemplazar en el lugar.
                </small>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Solo se considera "instalado" o "desinstalado" a lo que se monta de forma fija en un móvil o base
                    (uso Móvil/Base, p. ej. MDT400, DT410, SRG3900). Los portátiles no se "instalan": se "asignan" a
                    una persona, o quedan "en depósito" si no tienen asignación.
                </small>
            </div>
        </div>

        {{-- Barra de progreso operativo / no operativo --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6>Condición general de la flota:
                            <strong class="text-success">{{ $resumen['pct_operativo'] }}% operativo</strong> /
                            <strong class="text-danger">{{ $resumen['pct_no_operativo'] }}% no operativo</strong>
                        </h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: {{ $resumen['pct_operativo'] }}%">
                                @if($resumen['pct_operativo'] >= 12)
                                    {{ $resumen['pct_operativo'] }}%
                                @endif
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $resumen['pct_no_operativo'] }}%">
                                @if($resumen['pct_no_operativo'] >= 12)
                                    {{ $resumen['pct_no_operativo'] }}%
                                @endif
                            </div>
                            @php $pctOtros = $resumen['total'] > 0 ? round($resumen['otros_estados'] / $resumen['total'] * 100, 1) : 0; @endphp
                            <div class="progress-bar bg-secondary" style="width: {{ $pctOtros }}%">
                                @if($pctOtros >= 12)
                                    {{ $pctOtros }}%
                                @endif
                            </div>
                        </div>
                        <div class="progress-legend mt-2">
                            <span><i class="fas fa-square text-success"></i> {{ $resumen['operativos'] }} operativos (Nuevo/Usado/Reparado)</span>
                            <span><i class="fas fa-square text-danger"></i> {{ $resumen['no_operativos'] }} no operativos (Baja/No funciona/Perdido/Degradado)</span>
                            <span><i class="fas fa-square text-secondary"></i> {{ $resumen['otros_estados'] }} otros estados (Recambio/Temporal/En revisión)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reconciliación completa por tipo de uso: siempre suma al total --}}
        @php
            $usoFiltroBase = [
                'Base' => ['uso' => 'Base'],
                'Movil' => ['uso' => 'Movil'],
                'Portatil' => ['uso' => 'Portatil', 'excluir_modelos' => 'Teltronic:HTT500', 'excluir_marcas' => 'Motorola/Vertex'],
                'HTT500 (sin accesorios)' => ['marca' => 'Teltronic', 'modelo' => 'HTT500'],
                'VX-261 (no TETRA)' => ['marca' => 'Motorola/Vertex'],
            ];
        @endphp
        <div class="row">
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

        <div class="row">
            {{-- Instalados por tipo de uso --}}
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-broadcast-tower"></i> Instalados por Tipo de Uso</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr class="bg-light"><th>Uso</th><th class="text-center">Cantidad</th></tr></thead>
                                <tbody>
                                @forelse($porTipoUso as $fila)
                                    @php $baseUso = $usoFiltroBase[$fila->uso] ?? []; @endphp
                                    <tr class="equipos-clicable" data-equipos-titulo="{{ $fila->uso }} — Instalados (operativo, en terreno)" data-equipos-filtro='{{ json_encode($baseUso + ["condicion" => "operativo", "situacion" => "instalado"]) }}'>
                                        <td>{{ $fila->uso }}</td>
                                        <td class="text-center"><span class="badge badge-primary">{{ $fila->cantidad }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">Sin datos</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Solo equipos operativos con asignación activa fuera de Stock 911 (móviles/patrulleros, bases y portátiles).</small>
                    </div>
                </div>
            </div>

            {{-- Por estado --}}
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-list"></i> Por Estado</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr class="bg-light"><th>Estado</th><th class="text-center">Cantidad</th></tr></thead>
                                <tbody>
                                @foreach($porEstado as $fila)
                                    <tr class="equipos-clicable" data-equipos-titulo="Estado: {{ $fila->estado }}" data-equipos-filtro='{{ json_encode(["estado_in" => $fila->estado]) }}'>
                                        <td>{{ $fila->estado }}</td>
                                        <td class="text-center"><span class="badge badge-secondary">{{ $fila->cantidad }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Móviles y bases instalados, por marca y modelo --}}
        <div class="row">
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
        <div class="row">
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
        <div class="row">
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
                                        <td><span class="tei-badge">{{ $eq->tei }}</span></td>
                                        <td><small class="text-muted">{{ $eq->issi ?? '—' }}</small></td>
                                        <td><span class="badge badge-danger">{{ $eq->estado }}</span></td>
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

{{-- Modal genérico de detalle: se reutiliza para cualquier contador/fila clicable --}}
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
    .equipos-clicable{cursor:pointer}
    .equipos-clicable:hover{filter:brightness(0.92)}
    tr.equipos-clicable:hover{background-color:rgba(0,0,0,.035)}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let equiposActuales = [];

    function escapeHtml(valor) {
        return (valor ?? '').toString()
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderTabla(equipos) {
        const tbody = document.querySelector('#modalDetalleEquiposTabla tbody');
        tbody.innerHTML = equipos.map(function (eq) {
            return '<tr>'
                + '<td>' + escapeHtml(eq.tei) + '</td>'
                + '<td><small class="text-muted">' + escapeHtml(eq.issi ?? '—') + '</small></td>'
                + '<td>' + escapeHtml(eq.marca) + ' ' + escapeHtml(eq.modelo) + '</td>'
                + '<td><span class="badge badge-secondary">' + escapeHtml(eq.estado) + '</span></td>'
                + '<td>' + escapeHtml(eq.recurso ?? '—') + '</td>'
                + '<td>' + escapeHtml(eq.dependencia ?? '—') + '</td>'
                + '<td>' + escapeHtml(eq.fecha_estado_fmt ?? '—') + '</td>'
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

    document.querySelectorAll('[data-equipos-filtro]').forEach(function (el) {
        el.addEventListener('click', function () {
            let filtro = {};
            try { filtro = JSON.parse(el.getAttribute('data-equipos-filtro') || '{}'); } catch (e) { filtro = {}; }
            const titulo = el.getAttribute('data-equipos-titulo') || 'Detalle de equipos';

            document.getElementById('modalDetalleEquiposTitulo').textContent = titulo;
            document.getElementById('modalDetalleEquiposBuscar').value = '';
            equiposActuales = [];
            renderTabla([]);
            document.getElementById('modalDetalleEquiposLoading').style.display = 'block';
            $('#modalDetalleEquipos').modal('show');

            const params = new URLSearchParams(filtro).toString();
            fetch("{{ route('equipos.estadisticas.detalle') }}" + (params ? '?' + params : ''))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    equiposActuales = Array.isArray(data) ? data : [];
                    poblarFiltroEstado(equiposActuales);
                    renderTabla(equiposActuales);
                })
                .catch(function () {
                    equiposActuales = [];
                    renderTabla([]);
                })
                .finally(function () {
                    document.getElementById('modalDetalleEquiposLoading').style.display = 'none';
                });
        });
    });
});
</script>
@endpush
