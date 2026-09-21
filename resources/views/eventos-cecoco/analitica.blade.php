@extends('layouts.app')

@section('css')
    <style>
        /* stat cards */
        .stat-card {
            border-radius: 12px;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .stat-card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            color: #e2e8f0;
        }

        .stat-card .stat-icon {
            font-size: 2rem;
            width: 52px;
            text-align: center;
            flex-shrink: 0;
        }

        .stat-card .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-size: 0.78rem;
            opacity: .65;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* chart cards — mismo patrón que home */
        .chart-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: #fff;
            padding: 1.2rem;
        }

        [data-theme="dark"] .chart-card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .chart-card h6 {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 1rem;
        }

        [data-theme="dark"] .chart-card h6 {
            color: #e2e8f0 !important;
        }

        /* alertas */
        .alerta-refuerzo {
            border-left: 4px solid #dc3545;
            background: rgba(220, 53, 69, .07);
            border-radius: 0 8px 8px 0;
            padding: .8rem 1rem;
            margin-bottom: .5rem;
        }

        .alerta-refuerzo.warning {
            border-color: #fd7e14;
            background: rgba(253, 126, 20, .07);
        }

        [data-theme="dark"] .alerta-refuerzo {
            background: rgba(220, 53, 69, .13);
        }

        [data-theme="dark"] .alerta-refuerzo.warning {
            background: rgba(253, 126, 20, .13);
        }

        #loading-analitica {
            display: none;
        }

        .periodo-rapido .btn {
            font-size: .82rem;
        }

        .dashboard-config-card {
            border: 1px dashed rgba(13, 110, 253, .35);
            border-radius: 12px;
            background: rgba(13, 110, 253, .04);
        }

        [data-theme="dark"] .dashboard-config-card {
            background: rgba(56, 189, 248, .08);
            border-color: rgba(56, 189, 248, .28);
        }

        .dashboard-config-card .form-check-label {
            font-size: .86rem;
        }

        .tipificaciones-panel {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 10px;
            max-height: 185px;
            overflow-y: auto;
            padding: .75rem;
        }

        [data-theme="dark"] .tipificaciones-panel {
            border-color: rgba(255, 255, 255, .12);
            background: rgba(15, 23, 42, .35);
        }

        .tipificaciones-panel .form-check-label {
            font-size: .82rem;
        }

        .panel-toggle {
            cursor: pointer;
            user-select: none;
        }

        .panel-toggle .panel-chevron {
            display: inline-block;
            transition: transform .15s ease;
            font-size: .8rem;
        }

        .panel-toggle.is-collapsed .panel-chevron {
            transform: rotate(-90deg);
        }

        .cobertura-alerta {
            border-left: 4px solid #fd7e14;
            background: rgba(253, 126, 20, .07);
            border-radius: 0 8px 8px 0;
            padding: .8rem 1rem;
        }

        [data-theme="dark"] .cobertura-alerta {
            background: rgba(253, 126, 20, .13);
        }

        #tr-tabla-lentos th, #tr-tabla-lentos td {
            font-size: .85rem;
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    <div class="mb-3">
        <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
        <h4 class="mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Analítica de eventos CECOCO</h4>
        <button type="button" class="btn btn-outline-info btn-sm" id="btnVerTutorialAnalitica" onclick="iniciarTutorialPagina()">
            <i class="bi bi-question-circle"></i> Ver tutorial
        </button>
    </div>
    <p class="text-muted mb-3" style="font-size:.9rem">Analizá patrones temporales y geográficos para optimizar el
        patrullaje.</p>

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-auto">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">PERÍODO RÁPIDO</label>
                    <div class="d-flex gap-1 periodo-rapido flex-wrap">
                        <button class="btn btn-outline-primary btn-sm" data-dias="7">Última semana</button>
                        <button class="btn btn-outline-secondary btn-sm" data-dias="14">14 días</button>
                        <button class="btn btn-outline-secondary btn-sm" data-dias="30">Último mes</button>
                        <button class="btn btn-outline-secondary btn-sm" data-dias="90">3 meses</button>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">DESDE</label>
                    <input type="date" id="filtro-desde" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">HASTA</label>
                    <input type="date" id="filtro-hasta" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-lg-2 col-xl-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">COMPARAR CON</label>
                    <select id="filtro-comparar" class="form-select form-select-sm" style="width:100%">
                        <option value="mes" selected>Mes anterior</option>
                        <option value="semana">Semana anterior</option>
                        <option value="anio">Año anterior</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 col-xl-3" id="filtroDependenciaWrap">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">
                        DEPENDENCIA INTERVINIENTE
                        <i class="bi bi-info-circle text-muted" title="Cruza cada tipificación contra el móvil o moto que intervino según los Trámites de CECOCO. Solo cuenta expedientes con detalle ya consultado."></i>
                    </label>
                    <select id="filtro-dependencia" class="form-select form-select-sm" style="width:100%">
                        <option value="">Todas (sin cruzar por interviniente)</option>
                        @foreach($dependencias as $dependencia)
                            <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <label class="form-label fw-semibold mb-0" style="font-size:.82rem">TIPIFICACIONES A MOSTRAR</label>
                    <small class="text-muted">Marcá una o varias; con “Todas” no se aplica filtro por tipo.</small>
                </div>
                <div class="position-relative mb-2">
                    <i class="bi bi-search position-absolute text-muted" style="left:.6rem; top:50%; transform:translateY(-50%); font-size:.8rem"></i>
                    <input type="text" id="tipos-buscar" class="form-control form-control-sm" style="padding-left:1.8rem"
                        placeholder="Buscar tipificación... (ej. robo, hurto, violencia)" autocomplete="off">
                </div>
                <div class="tipificaciones-panel">
                    <div class="row g-2">
                        <div class="col-12 col-md-6 col-lg-4" data-tipo-fila="todas">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipos-todas" checked>
                                <label class="form-check-label fw-semibold" for="tipos-todas">Todas las tipificaciones</label>
                            </div>
                        </div>
                        @foreach($tipos as $index => $tipo)
                            @if($tipo)
                                <div class="col-12 col-md-6 col-lg-4" data-tipo-fila="item">
                                    <div class="form-check">
                                        <input class="form-check-input tipo-checkbox" type="checkbox" value="{{ $tipo }}" id="tipo-{{ $index }}">
                                        <label class="form-check-label" for="tipo-{{ $index }}">{{ $tipo }}</label>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p id="tipos-sin-resultados" class="text-muted text-center mb-0 py-2" hidden>
                        Ninguna tipificación coincide con la búsqueda.
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end flex-wrap gap-2 pt-2 border-top">
                <button id="btn-ver-analizador" class="btn btn-outline-primary btn-sm px-3" disabled>
                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver eventos en Analizador
                </button>
                <button id="btn-exportar-pdf" class="btn btn-outline-danger btn-sm px-3" disabled>
                    <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                </button>
                <div>
                    <button id="btn-analizar" class="btn btn-primary btn-sm px-4"
                        style="background: linear-gradient(135deg, #ec4899, #8b5cf6); border:none; box-shadow: 0 0 10px rgba(236, 72, 153, 0.5);">
                        <i class="bi bi-stars me-1"></i>Analizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-config-card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <div class="fw-semibold"><i class="bi bi-sliders me-1 text-primary"></i>Dashboard configurable</div>
                <small class="text-muted">Elegí qué gráficos y datos ver. La exportación incluye esta misma selección.</small>
            </div>
            <button id="btn-restaurar-dashboard" class="btn btn-outline-secondary btn-sm" type="button">Restaurar vista</button>
        </div>
        <div class="row g-2" id="dashboard-selector">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="fecha" id="toggle-fecha" checked>
                    <label class="form-check-label" for="toggle-fecha">Llamadas por día</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="comparativa" id="toggle-comparativa" checked>
                    <label class="form-check-label" for="toggle-comparativa">Comparativa</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="resultados" id="toggle-resultados" checked>
                    <label class="form-check-label" for="toggle-resultados">Resultados operativos</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="hora" id="toggle-hora" checked>
                    <label class="form-check-label" for="toggle-hora">Por hora</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="dia" id="toggle-dia" checked>
                    <label class="form-check-label" for="toggle-dia">Por día semanal</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="tipos" id="toggle-tipos" checked>
                    <label class="form-check-label" for="toggle-tipos">Top tipificaciones</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="calles" id="toggle-calles" checked>
                    <label class="form-check-label" for="toggle-calles">Top calles/sectores</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="incidencias" id="toggle-incidencias" checked>
                    <label class="form-check-label" for="toggle-incidencias">Ranking sectores</label>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input dashboard-toggle" type="checkbox" value="detencion" id="toggle-detencion" checked>
                    <label class="form-check-label" for="toggle-detencion">Tasa de detención</label>
                </div>
            </div>
            @can('ver-tiempos-respuesta-cecoco')
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="form-check">
                        <input class="form-check-input dashboard-toggle" type="checkbox" value="tiempos-respuesta" id="toggle-tiempos-respuesta" checked>
                        <label class="form-check-label" for="toggle-tiempos-respuesta">Tiempos de respuesta</label>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    {{-- Loading --}}
    <div id="loading-analitica" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Procesando datos...</p>
    </div>

    {{-- Contenido (oculto hasta cargar) --}}
    <div id="contenido-analitica" style="display:none">

        {{-- Tarjetas resumen --}}
        <div class="row g-3 mb-4" id="tarjetas-resumen">
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="stat-icon text-primary"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <div class="stat-value" id="stat-total">-</div>
                        <div class="stat-label">Total eventos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="stat-icon text-danger"><i class="bi bi-clock-fill"></i></div>
                    <div>
                        <div class="stat-value" id="stat-hora-pico" style="font-size:1.3rem">-</div>
                        <div class="stat-label">Franja horaria pico</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="stat-icon text-warning"><i class="bi bi-calendar-day-fill"></i></div>
                    <div>
                        <div class="stat-value" id="stat-dia-pico">-</div>
                        <div class="stat-label">Día con más eventos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="stat-icon text-success"><i class="bi bi-geo-alt-fill"></i></div>
                    <div>
                        <div class="stat-value" id="stat-calle-top" style="font-size:1rem; word-break:break-word">-</div>
                        <div class="stat-label">Calle/sector más afectado</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="stat-icon text-info"><i class="bi bi-telephone-fill"></i></div>
                    <div>
                        <div class="stat-value" id="stat-promedio-diario">-</div>
                        <div class="stat-label">Llamadas/día promedio</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resultados operativos (estimados desde el texto de las novedades) --}}
        <div class="mb-4" data-dashboard-section="resultados">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-2 panel-toggle" data-collapse-toggle="body-resultados">
                <span class="panel-chevron" aria-hidden="true">▾</span>
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2-check me-1 text-primary"></i>Resultados operativos del período</h6>
                <span class="badge bg-secondary-subtle text-secondary-emphasis border" style="font-size:.7rem">VALORES APROX.</span>
            </div>
            <div id="body-resultados">
                <p class="text-muted mb-3" style="font-size:.8rem">
                    <i class="bi bi-info-circle me-1"></i>Estimados a partir del texto de las novedades; cuentan eventos cuya
                    descripción coincide, no cantidades exactas. Pueden no reflejar la cifra oficial.
                </p>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon text-primary"><i class="bi bi-person-fill-lock"></i></div>
                            <div>
                                <div class="stat-value" id="stat-demorados">-</div>
                                <div class="stat-label">Demorados / detenidos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon text-danger"><i class="bi bi-bullseye"></i></div>
                            <div>
                                <div class="stat-value" id="stat-armas">-</div>
                                <div class="stat-label">Armas de fuego secuestradas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon text-warning"><i class="bi bi-bicycle"></i></div>
                            <div>
                                <div class="stat-value" id="stat-motos">-</div>
                                <div class="stat-label">Motovehículos recuperados</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon text-success"><i class="bi bi-car-front-fill"></i></div>
                            <div>
                                <div class="stat-value" id="stat-vehiculos">-</div>
                                <div class="stat-label">Vehículos recuperados</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tendencia diaria --}}
        <div class="row g-3 mb-4" data-dashboard-section="fecha">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-fecha">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-telephone me-1"></i>Llamadas al 911 por día
                    </h6>
                    <div id="body-fecha">
                        <canvas id="chart-fecha" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico Comparativo --}}
        <div class="row g-3 mb-4" data-dashboard-section="comparativa">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-comparativa">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-bar-chart-steps me-1 text-primary"></i>Comparativa de Hechos de Relevancia (Período
                        vs Mismo Periodo Mes Ant.)
                    </h6>
                    <div id="body-comparativa">
                        <canvas id="chart-comparativa" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7" data-dashboard-section="hora">
                <div class="chart-card h-100">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-hora">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-clock me-1"></i>Eventos por hora del día
                    </h6>
                    <div id="body-hora">
                        <canvas id="chart-hora" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5" data-dashboard-section="dia">
                <div class="chart-card h-100">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-dia">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-calendar-week me-1"></i>Eventos por día de semana
                    </h6>
                    <div id="body-dia">
                        <canvas id="chart-dia" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6" data-dashboard-section="tipos">
                <div class="chart-card">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-tipos">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-list-ul me-1"></i>Top tipificaciones
                    </h6>
                    <div id="body-tipos">
                        <canvas id="chart-tipos" height="280"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6" data-dashboard-section="calles">
                <div class="chart-card">
                    <h6 class="panel-toggle d-flex align-items-center" data-collapse-toggle="body-calles">
                        <span class="panel-chevron me-1" aria-hidden="true">▾</span><i class="bi bi-signpost-2 me-1"></i>Top calles / sectores con más incidentes
                    </h6>
                    <div id="body-calles">
                        <canvas id="chart-calles" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mayores incidencias en Paraná --}}
        <div class="card mb-4" id="seccion-incidencias" data-dashboard-section="incidencias">
            <div class="card-header fw-semibold d-flex align-items-center gap-2 panel-toggle" data-collapse-toggle="body-incidencias">
                <span class="panel-chevron" aria-hidden="true">▾</span>
                <i class="bi bi-geo-alt-fill text-danger"></i>
                Sectores con mayor concentración de incidencias — Paraná
            </div>
            <div class="card-body p-0" id="body-incidencias">
                <div id="tabla-incidencias-container" class="px-3 py-2"></div>
            </div>
        </div>

        {{-- Tasa de detención por tipificación --}}
        <div class="card mb-4" id="seccion-detencion" data-dashboard-section="detencion">
            <div class="card-header fw-semibold d-flex align-items-center flex-wrap gap-2 panel-toggle" data-collapse-toggle="body-detencion">
                <span class="panel-chevron" aria-hidden="true">▾</span>
                <i class="bi bi-person-fill-lock text-primary"></i>
                Tasa de detención por tipificación
                <span class="badge bg-secondary-subtle text-secondary-emphasis border" style="font-size:.7rem">VALORES APROX.</span>
            </div>
            <div class="card-body p-0" id="body-detencion">
                <p class="text-muted px-3 pt-2 mb-1" style="font-size:.8rem">
                    <i class="bi bi-info-circle me-1"></i>Estimado a partir del texto de las novedades: qué proporción de
                    cada tipo de intervención terminó con una persona detenida/demorada.
                </p>
                <div id="tabla-detencion-container" class="px-3 py-2"></div>
            </div>
        </div>

        @can('ver-tiempos-respuesta-cecoco')
            {{-- Tiempos de respuesta (fusionado desde cecoco.tiempos-respuesta) --}}
            <div class="card mb-4" id="seccion-tiempos-respuesta" data-dashboard-section="tiempos-respuesta">
                <div class="card-header fw-semibold d-flex align-items-center gap-2 panel-toggle" data-collapse-toggle="body-tiempos-respuesta">
                    <span class="panel-chevron" aria-hidden="true">▾</span>
                    <i class="bi bi-stopwatch-fill text-primary"></i>
                    Tiempos de respuesta
                </div>
                <div class="card-body" id="body-tiempos-respuesta">
                    <p class="text-muted mb-3" style="font-size:.8rem">
                        <i class="bi bi-info-circle me-1"></i>Minutos entre que un recurso pasa a "En desplazamiento" y llega
                        a "En atención", según el timeline de los expedientes ya consultados. Se toma el recurso móvil más
                        rápido de cada evento; los expedientes sin ese par de marcas para ningún móvil quedan fuera del cálculo.
                    </p>

                    <div class="cobertura-alerta mb-3" id="tr-alerta-cobertura">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="tr-texto-cobertura">-</span>
                    </div>

                    <div id="tr-sin-datos" class="text-center text-muted py-4" style="display:none">
                        <i class="bi bi-emoji-frown" style="font-size:2rem"></i>
                        <p class="mt-2 mb-0">Ningún expediente consultado en este período tiene datos de recurso asignados.</p>
                    </div>

                    <div id="tr-contenido-con-datos">
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-primary"><i class="bi bi-clock-history"></i></div>
                                    <div>
                                        <div class="stat-value" id="tr-stat-promedio">-</div>
                                        <div class="stat-label">Promedio (min)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-info"><i class="bi bi-bar-chart-steps"></i></div>
                                    <div>
                                        <div class="stat-value" id="tr-stat-mediana">-</div>
                                        <div class="stat-label">Mediana (min)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-success"><i class="bi bi-lightning-fill"></i></div>
                                    <div>
                                        <div class="stat-value" id="tr-stat-minimo">-</div>
                                        <div class="stat-label">Más rápido (min)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-danger"><i class="bi bi-hourglass-split"></i></div>
                                    <div>
                                        <div class="stat-value" id="tr-stat-maximo">-</div>
                                        <div class="stat-label">Más lento (min)</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="chart-card">
                                    <h6><i class="bi bi-graph-up me-1"></i>Tiempo de respuesta promedio por día</h6>
                                    <canvas id="tr-chart-fecha" height="90"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-lg-6">
                                <div class="chart-card h-100">
                                    <h6><i class="bi bi-clock me-1"></i>Promedio por hora de despacho</h6>
                                    <canvas id="tr-chart-hora" height="220"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="chart-card h-100">
                                    <h6><i class="bi bi-bar-chart me-1"></i>Distribución de tiempos</h6>
                                    <canvas id="tr-chart-distribucion" height="220"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <div class="chart-card h-100">
                                    <h6><i class="bi bi-list-ul me-1"></i>Tipificaciones más lentas (promedio, mín. 3 casos)</h6>
                                    <canvas id="tr-chart-tipos" height="260"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                        Eventos con mayor tiempo de respuesta
                                    </div>
                                    <div class="card-body p-0" style="overflow-x:auto">
                                        <table class="table table-sm mb-0" id="tr-tabla-lentos">
                                            <thead>
                                                <tr>
                                                    <th>Expediente</th>
                                                    <th>Fecha</th>
                                                    <th>Tipificación</th>
                                                    <th>Recurso</th>
                                                    <th class="text-end">Minutos</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tr-tabla-lentos-body"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

    </div>
@endsection

@php
    $tutorialPasos = [
        [
            'id' => 'filtro-desde',
            'titulo' => 'Elegí el período',
            'texto' => 'Definí desde/hasta (o usá un período rápido) y, si querés, las tipificaciones a incluir.',
        ],
        [
            'id' => 'filtroDependenciaWrap',
            'titulo' => 'Filtrar por dependencia interviniente',
            'texto' => 'Elegí una dependencia (hoy, División 911 y Videovigilancia) para cruzar cada tipificación contra el móvil o moto que efectivamente intervino, según los Trámites de CECOCO. Con "Todas" no se aplica ese cruce y los totales son solo por texto/tipificación, sin importar quién respondió.',
        ],
        [
            'id' => 'btn-analizar',
            'titulo' => 'Analizar',
            'texto' => 'Aplicá los filtros elegidos y esperá a que carguen los datos.',
        ],
        [
            'id' => 'seccion-detencion',
            'titulo' => 'Tasa de detención por tipificación',
            'texto' => 'Con una dependencia seleccionada, cada fila suma cuántos casos tuvieron un móvil/moto de esa dependencia y, de esos, cuántos terminaron con detenido/demorado/aprehendido. Solo cuenta expedientes con el detalle de trámites ya consultado a CECOCO: si faltan muchos, el número puede estar subestimado.',
            'side' => 'top',
        ],
    ];
@endphp
@include('partials.tutorial', ['tutorialPasos' => $tutorialPasos, 'tutorialStorageKey' => 'tutorial_analitica_cecoco_visto'])

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        // --- Neon Glow Plugin para Chart.js ---
        Chart.register({
            id: 'neonGlow',
            beforeDatasetsDraw: function (chart) {
                if (chart.config.options.neonGlow) {
                    let ctx = chart.ctx;
                    ctx.save();
                    ctx.shadowColor = chart.config.options.neonGlow.color || 'rgba(236,72,153, 0.8)';
                    ctx.shadowBlur = chart.config.options.neonGlow.blur || 18;
                    ctx.shadowOffsetX = 0;
                    ctx.shadowOffsetY = 0;
                }
            },
            afterDatasetsDraw: function (chart) {
                if (chart.config.options.neonGlow) {
                    chart.ctx.restore();
                }
            }
        });

        $(function () {
            // Select2 para filtro comparar
            $('#filtro-comparar').select2({
                width: '100%',
                minimumResultsForSearch: Infinity
            });

            // Select2 para filtro de dependencia interviniente
            $('#filtro-dependencia').select2({
                width: '100%',
                minimumResultsForSearch: Infinity
            });

            // Forzar el foco en el campo de búsqueda cuando se abre el Select2
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    let select2Field = document.querySelector('.select2-container--open .select2-search__field');
                    if (select2Field) {
                        select2Field.focus();
                    }
                }, 0);
            });
        });

        document.addEventListener('DOMContentLoaded', function () {

            // ---- Detección de tema (igual que home) ----
            function detectTheme() {
                const html = document.documentElement;
                const theme = html.getAttribute('data-theme') || html.getAttribute('data-bs-theme') || '';
                if (theme === 'dark') return true;
                const bg = getComputedStyle(document.body).backgroundColor;
                if (bg) {
                    const match = bg.match(/\d+/g);
                    if (match && match.length >= 3) {
                        return (parseInt(match[0]) + parseInt(match[1]) + parseInt(match[2])) / 3 < 128;
                    }
                }
                return false;
            }

            // ---- Helpers de fecha ----
            function hoy() { return new Date().toISOString().slice(0, 10); }
            function haceDias(n) {
                const d = new Date();
                d.setDate(d.getDate() - n + 1);
                return d.toISOString().slice(0, 10);
            }

            // ---- Estado ----
            let ultimosDatos = null;
            let ultimosDatosTiempoRespuesta = null;
            let chartHora = null, chartDia = null, chartTipos = null, chartCalles = null, chartFecha = null, chartComparativa = null;
            let trChartFecha = null, trChartHora = null, trChartDistribucion = null, trChartTipos = null;
            const dashboardStorageKey = 'cecoco-analitica-dashboard';
            const tieneTiemposRespuesta = document.getElementById('seccion-tiempos-respuesta') !== null;
            const chartInstances = () => [
                chartFecha, chartComparativa, chartHora, chartDia, chartTipos, chartCalles,
                trChartFecha, trChartHora, trChartDistribucion, trChartTipos,
            ].filter(Boolean);
            const dashboardDefaults = ['resultados', 'fecha', 'comparativa', 'hora', 'dia', 'tipos', 'calles', 'incidencias', 'detencion']
                .concat(tieneTiemposRespuesta ? ['tiempos-respuesta'] : []);
            const chartLabels = {
                resultados: 'Resultados operativos del período (aprox.)',
                fecha: 'Llamadas al 911 por día',
                comparativa: 'Comparativa de hechos de relevancia',
                hora: 'Eventos por hora del día',
                dia: 'Eventos por día de semana',
                tipos: 'Top tipificaciones',
                calles: 'Top calles / sectores',
                incidencias: 'Sectores con mayor concentración',
                detencion: 'Tasa de detención por tipificación',
                'tiempos-respuesta': 'Tiempos de respuesta'
            };

            function obtenerWidgetsSeleccionados() {
                return Array.from(document.querySelectorAll('.dashboard-toggle:checked')).map(input => input.value);
            }

            function aplicarDashboardSeleccionado(guardar = true) {
                const selected = obtenerWidgetsSeleccionados();
                document.querySelectorAll('[data-dashboard-section]').forEach(section => {
                    section.style.display = selected.includes(section.dataset.dashboardSection) ? '' : 'none';
                });

                if (guardar) {
                    localStorage.setItem(dashboardStorageKey, JSON.stringify(selected));
                }

                requestAnimationFrame(function () {
                    chartInstances().forEach(chart => chart.resize());
                });
            }

            function cargarDashboardSeleccionado() {
                let selected = dashboardDefaults;
                try {
                    const stored = JSON.parse(localStorage.getItem(dashboardStorageKey));
                    if (Array.isArray(stored) && stored.length > 0) {
                        selected = stored.filter(item => dashboardDefaults.includes(item));
                    }
                } catch (error) {
                    selected = dashboardDefaults;
                }

                document.querySelectorAll('.dashboard-toggle').forEach(input => {
                    input.checked = selected.includes(input.value);
                });
                aplicarDashboardSeleccionado(false);
            }

            function obtenerTiposSeleccionados() {
                if (document.getElementById('tipos-todas').checked) {
                    return [];
                }

                return Array.from(document.querySelectorAll('.tipo-checkbox:checked')).map(input => input.value);
            }

            function resumenTiposSeleccionados() {
                const tipos = obtenerTiposSeleccionados();
                return tipos.length > 0 ? tipos.join(', ') : 'Todas las tipificaciones';
            }

            function normalizarBusqueda(texto) {
                return (texto || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
            }

            function filtrarTipificaciones() {
                const consulta = normalizarBusqueda(document.getElementById('tipos-buscar').value);
                let visibles = 0;

                document.querySelectorAll('[data-tipo-fila="item"]').forEach(fila => {
                    const label = fila.querySelector('.form-check-label');
                    const coincide = !consulta || normalizarBusqueda(label ? label.textContent : '').includes(consulta);
                    fila.hidden = !coincide;
                    if (coincide) {
                        visibles++;
                    }
                });

                document.getElementById('tipos-sin-resultados').hidden = visibles > 0;
            }

            function sincronizarTipificaciones(event) {
                const todas = document.getElementById('tipos-todas');

                if (event && event.target === todas && todas.checked) {
                    document.querySelectorAll('.tipo-checkbox').forEach(input => input.checked = false);
                    return;
                }

                if (event && event.target.classList.contains('tipo-checkbox') && event.target.checked) {
                    todas.checked = false;
                }

                if (document.querySelectorAll('.tipo-checkbox:checked').length === 0) {
                    todas.checked = true;
                }
            }

            // Inicializar filtros
            document.getElementById('filtro-desde').value = haceDias(7);
            document.getElementById('filtro-hasta').value = hoy();
            cargarDashboardSeleccionado();

            // Botones período rápido
            document.querySelectorAll('.periodo-rapido [data-dias]').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.periodo-rapido .btn').forEach(b => {
                        b.classList.remove('btn-primary');
                        b.classList.add('btn-outline-secondary');
                    });
                    this.classList.remove('btn-outline-primary', 'btn-outline-secondary');
                    this.classList.add('btn-primary');
                    document.getElementById('filtro-desde').value = haceDias(parseInt(this.dataset.dias));
                    document.getElementById('filtro-hasta').value = hoy();
                    cargarDatos();
                });
            });
            document.querySelector('[data-dias="7"]').classList.replace('btn-outline-primary', 'btn-primary');
            document.getElementById('btn-analizar').addEventListener('click', cargarDatos);
            document.getElementById('btn-exportar-pdf').addEventListener('click', exportarPdf);
            document.getElementById('btn-ver-analizador').addEventListener('click', abrirAnalizadorEventos);
            document.getElementById('tipos-todas').addEventListener('change', sincronizarTipificaciones);
            document.querySelectorAll('.tipo-checkbox').forEach(input => {
                input.addEventListener('change', sincronizarTipificaciones);
            });
            document.getElementById('tipos-buscar').addEventListener('input', filtrarTipificaciones);
            document.querySelectorAll('.panel-toggle').forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const body = document.getElementById(this.dataset.collapseToggle);
                    if (!body) {
                        return;
                    }
                    body.hidden = !body.hidden;
                    this.classList.toggle('is-collapsed', body.hidden);
                });
            });
            document.getElementById('btn-restaurar-dashboard').addEventListener('click', function () {
                document.querySelectorAll('.dashboard-toggle').forEach(input => input.checked = true);
                aplicarDashboardSeleccionado();
            });
            document.querySelectorAll('.dashboard-toggle').forEach(input => {
                input.addEventListener('change', aplicarDashboardSeleccionado);
            });

            // ---- Renderizar gráficos con colores del tema actual ----
            function renderCharts(datos) {
                const isDark = detectTheme();
                const textColor = isDark ? '#e2e8f0' : '#0f172a';
                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.15)';
                const tooltipStyle = {
                    backgroundColor: isDark ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
                    titleColor: isDark ? '#f1f5f9' : '#1e293b',
                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                    borderColor: isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)',
                    borderWidth: 1, padding: 10, boxPadding: 4, cornerRadius: 8
                };

                // Tendencia diaria (llamadas por fecha)
                if (chartFecha) chartFecha.destroy();
                if (datos.por_fecha && Object.keys(datos.por_fecha).length > 0) {
                    const fechaLabels = Object.keys(datos.por_fecha);
                    const fechaVals = Object.values(datos.por_fecha);
                    const gradientStroke = isDark ? '#a855f7' : 'rgba(13,110,253,0.8)';
                    chartFecha = new Chart(document.getElementById('chart-fecha'), {
                        type: 'line',
                        data: {
                            labels: fechaLabels,
                            datasets: [{
                                label: 'Llamadas',
                                data: fechaVals,
                                borderColor: gradientStroke,
                                backgroundColor: function (context) {
                                    const chartArea = context.chart.chartArea;
                                    if (!chartArea) return null;
                                    const gradient = context.chart.ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                    gradient.addColorStop(0, isDark ? 'rgba(168,85,247,0.05)' : 'rgba(13,110,253,0.05)');
                                    gradient.addColorStop(1, isDark ? 'rgba(168,85,247,0.5)' : 'rgba(13,110,253,0.2)');
                                    return gradient;
                                },
                                fill: true,
                                tension: 0.35,
                                pointRadius: fechaLabels.length > 60 ? 0 : 3,
                                pointHoverRadius: 6,
                                borderWidth: isDark ? 3 : 2
                            }]
                        },
                        options: {
                            responsive: true,
                            neonGlow: isDark ? { color: 'rgba(168,85,247,0.8)', blur: 15 } : false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...tooltipStyle,
                                    callbacks: { title: items => items[0].label, label: item => ` ${item.raw} llamadas` }
                                }
                            },
                            scales: {
                                x: { grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, maxTicksLimit: 12, maxRotation: 30 } },
                                y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } }
                            }
                        }
                    });
                }

                // Gráfico Comparativo (Hechos de relevancia vs. mes anterior)
                if (chartComparativa) chartComparativa.destroy();
                if (datos.comparativa_actual) {
                    chartComparativa = new Chart(document.getElementById('chart-comparativa'), {
                        type: 'bar',
                        data: {
                            labels: ['Accidentes', 'Robos', 'Hurtos', 'Abuso Armas', 'Homicidios'],
                            datasets: [
                                {
                                    label: 'Período Seleccionado',
                                    data: datos.comparativa_actual,
                                    backgroundColor: function (context) {
                                        const chartArea = context.chart.chartArea;
                                        if (!chartArea) return null;
                                        const gradient = context.chart.ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                        gradient.addColorStop(0, isDark ? 'rgba(236,72,153,0.3)' : '#3b82f6');
                                        gradient.addColorStop(1, isDark ? '#ec4899' : '#3b82f6');
                                        return gradient;
                                    },
                                    borderRadius: 6,
                                    borderWidth: 0
                                },
                                {
                                    label: 'Mismo Período Ant.',
                                    data: datos.comparativa_anterior,
                                    backgroundColor: function (context) {
                                        const chartArea = context.chart.chartArea;
                                        if (!chartArea) return null;
                                        const gradient = context.chart.ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                        gradient.addColorStop(0, isDark ? 'rgba(56,189,248,0.2)' : '#94a3b8');
                                        gradient.addColorStop(1, isDark ? '#38bdf8' : '#94a3b8');
                                        return gradient;
                                    },
                                    borderRadius: 6,
                                    borderWidth: 0
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            neonGlow: isDark ? { color: 'rgba(236,72,153,0.5)', blur: 15 } : false,
                            plugins: {
                                legend: { display: true, position: 'top', labels: { color: textColor } },
                                tooltip: tooltipStyle
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: textColor } },
                                y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } }
                            }
                        }
                    });
                }

                // Gráfico por hora
                const horaLabels = Object.keys(datos.por_hora).map(h => String(h).padStart(2, '0') + 'h');
                const horaVals = Object.values(datos.por_hora);
                const maxHora = Math.max(...horaVals);
                const horaColors = horaVals.map(v => {
                    const r = maxHora > 0 ? v / maxHora : 0;
                    if (isDark) {
                        return r >= 0.8 ? '#f43f5e' : r >= 0.5 ? '#f97316' : r >= 0.25 ? '#eab308' : '#38bdf8';
                    }
                    if (r >= 0.8) return 'rgba(220,53,69,0.85)';
                    if (r >= 0.5) return 'rgba(253,126,20,0.75)';
                    if (r >= 0.25) return 'rgba(255,193,7,0.8)';
                    return 'rgba(13,110,253,0.5)';
                });
                if (chartHora) chartHora.destroy();
                chartHora = new Chart(document.getElementById('chart-hora'), {
                    type: 'bar',
                    data: { labels: horaLabels, datasets: [{ label: 'Eventos', data: horaVals, backgroundColor: horaColors, borderRadius: 4 }] },
                    options: {
                        responsive: true,
                        neonGlow: isDark ? { color: 'rgba(244,63,94,0.4)', blur: 10 } : false,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, font: { size: 10 } } },
                            y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } }
                        }
                    }
                });

                // Gráfico por día
                if (chartDia) chartDia.destroy();
                chartDia = new Chart(document.getElementById('chart-dia'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(datos.por_dia),
                        datasets: [{
                            label: 'Eventos', data: Object.values(datos.por_dia),
                            backgroundColor: function (context) {
                                const chartArea = context.chart.chartArea;
                                if (!chartArea) return null;
                                const gradient = context.chart.ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                gradient.addColorStop(0, isDark ? 'rgba(56,189,248,0.2)' : 'rgba(13,110,253,0.3)');
                                gradient.addColorStop(1, isDark ? '#38bdf8' : 'rgba(13,110,253,0.65)');
                                return gradient;
                            },
                            borderRadius: 6, borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        neonGlow: isDark ? { color: 'rgba(56,189,248,0.6)', blur: 15 } : false,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor } },
                            y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } }
                        }
                    }
                });

                // Top tipificaciones
                if (chartTipos) chartTipos.destroy();
                chartTipos = new Chart(document.getElementById('chart-tipos'), {
                    type: 'bar',
                    data: {
                        labels: datos.top_tipos.map(t => t.tipo || '(sin tipo)'),
                        datasets: [{
                            label: 'Eventos', data: datos.top_tipos.map(t => t.total),
                            backgroundColor: function (context) {
                                const chartArea = context.chart.chartArea;
                                if (!chartArea) return null;
                                const gradient = context.chart.ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                                gradient.addColorStop(0, isDark ? 'rgba(168,85,247,0.3)' : 'rgba(13,110,253,0.3)');
                                gradient.addColorStop(1, isDark ? '#a855f7' : 'rgba(13,110,253,0.7)');
                                return gradient;
                            },
                            borderRadius: 4, borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true,
                        neonGlow: isDark ? { color: 'rgba(168,85,247,0.5)', blur: 15 } : false,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } },
                            y: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
                        }
                    }
                });

                // Top calles
                if (chartCalles) chartCalles.destroy();
                chartCalles = new Chart(document.getElementById('chart-calles'), {
                    type: 'bar',
                    data: {
                        labels: datos.top_calles.map(c => c.calle || '(sin dirección)'),
                        datasets: [{
                            label: 'Eventos', data: datos.top_calles.map(c => c.total),
                            backgroundColor: function (context) {
                                const chartArea = context.chart.chartArea;
                                if (!chartArea) return null;
                                const gradient = context.chart.ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                                gradient.addColorStop(0, isDark ? 'rgba(236,72,153,0.3)' : 'rgba(220,53,69,0.3)');
                                gradient.addColorStop(1, isDark ? '#ec4899' : 'rgba(220,53,69,0.65)');
                                return gradient;
                            },
                            borderRadius: 4, borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true,
                        neonGlow: isDark ? { color: 'rgba(236,72,153,0.5)', blur: 15 } : false,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } },
                            y: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
                        }
                    }
                });
            }

            // ---- Sectores con mayor incidencia en Paraná ----
            function mostrarIncidencias(datos) {
                const { top_calles, total } = datos;
                const el = document.getElementById('tabla-incidencias-container');

                if (!top_calles || top_calles.length === 0) {
                    el.innerHTML = '<p class="text-muted py-2 mb-0">Sin datos suficientes para el período seleccionado.</p>';
                    return;
                }

                const maxVal = top_calles[0].total;
                const rows = top_calles.slice(0, 10).map((c, i) => {
                    const pct = total > 0 ? Math.round(c.total / total * 100) : 0;
                    const barPct = maxVal > 0 ? Math.round(c.total / maxVal * 100) : 0;
                    const badge = i === 0 ? 'bg-danger' : i === 1 ? 'bg-warning text-dark' : i === 2 ? 'bg-orange' : 'bg-secondary';
                    return `
                            <div class="d-flex align-items-center py-2 border-bottom gap-3">
                                <span class="badge ${badge} rounded-pill" style="min-width:24px;font-size:.75rem">${i + 1}</span>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="fw-semibold text-truncate" style="font-size:.9rem">${c.calle || '(sin dirección)'}</div>
                                    <div class="progress mt-1" style="height:5px;border-radius:3px">
                                        <div class="progress-bar bg-danger" style="width:${barPct}%"></div>
                                    </div>
                                </div>
                                <div class="text-end" style="min-width:80px">
                                    <span class="fw-bold">${c.total.toLocaleString('es-AR')}</span>
                                    <span class="text-muted ms-1" style="font-size:.8rem">(${pct}%)</span>
                                </div>
                            </div>`;
                }).join('');

                el.innerHTML = rows;
            }

            // ---- Tasa de detención por tipificación ----
            function mostrarTasaDetencion(datos) {
                const filas = datos.tasa_detencion_por_categoria;
                const el = document.getElementById('tabla-detencion-container');

                if (!filas || filas.length === 0) {
                    el.innerHTML = '<p class="text-muted py-2 mb-0">Sin datos suficientes para el período seleccionado.</p>';
                    return;
                }

                const tieneDependencia = datos.dependencia_seleccionada
                    && filas.some(f => f.con_dependencia !== undefined || f.cruce_omitido);

                const rows = filas.map(f => {
                    const barPct = f.total > 0 ? Math.round(f.con_detenido / f.total * 100) : 0;
                    let filaDependencia = '';
                    if (f.cruce_omitido) {
                        filaDependencia = `
                                <div class="text-end ms-3 text-muted" style="min-width:150px;font-size:.78rem">
                                    No calculado: acotá el período (demasiados eventos para cruzar en vivo).
                                </div>`;
                    } else if (f.con_dependencia !== undefined) {
                        filaDependencia = `
                                <div class="text-end ms-3" style="min-width:150px">
                                    <span class="fw-bold">${f.con_dependencia.toLocaleString('es-AR')}</span>
                                    <span class="text-muted ms-1" style="font-size:.8rem">con ${datos.dependencia_seleccionada}</span>
                                    <div class="text-muted" style="font-size:.78rem">
                                        de esos, ${f.con_dependencia_detenido.toLocaleString('es-AR')} con detenido (${f.porcentaje_dependencia}%)
                                    </div>
                                </div>`;
                    }
                    return `
                            <div class="d-flex align-items-center py-2 border-bottom gap-3">
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="fw-semibold text-truncate" style="font-size:.9rem">${f.categoria}</div>
                                    <div class="progress mt-1" style="height:5px;border-radius:3px">
                                        <div class="progress-bar bg-primary" style="width:${barPct}%"></div>
                                    </div>
                                </div>
                                <div class="text-end" style="min-width:130px">
                                    <span class="fw-bold">${f.con_detenido.toLocaleString('es-AR')}</span>
                                    <span class="text-muted ms-1" style="font-size:.8rem">/ ${f.total.toLocaleString('es-AR')} (${f.porcentaje}%)</span>
                                </div>
                                ${filaDependencia}
                            </div>`;
                }).join('');

                const encabezado = tieneDependencia
                    ? `<p class="text-muted mb-2" style="font-size:.8rem" id="tabla-detencion-dependencia-nota">
                            <i class="bi bi-signpost-split me-1"></i>Cruce contra <strong>${datos.dependencia_seleccionada}</strong>: solo cuenta expedientes con detalle de trámites ya consultado a CECOCO.
                       </p>`
                    : '';

                el.innerHTML = encabezado + rows;
            }

            function rangoAnalizado() {
                const desde = document.getElementById('filtro-desde').value || '-';
                const hasta = document.getElementById('filtro-hasta').value || '-';
                const tipo = resumenTiposSeleccionados();
                const comparar = document.getElementById('filtro-comparar');
                const compararTexto = comparar.options[comparar.selectedIndex] ? comparar.options[comparar.selectedIndex].text : '-';

                return { desde, hasta, tipo, compararTexto };
            }

            function construirParametrosAnalisis() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;
                const compararCon = document.getElementById('filtro-comparar').value;
                const dependenciaId = document.getElementById('filtro-dependencia').value;
                const params = new URLSearchParams({ desde, hasta, comparar_con: compararCon });

                if (dependenciaId) {
                    params.append('dependencia_id', dependenciaId);
                }

                obtenerTiposSeleccionados().forEach(tipo => {
                    params.append('tipos[]', tipo);
                });

                return params;
            }

            function abrirAnalizadorEventos() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;

                if (!desde || !hasta) {
                    alert('Seleccioná un período de fechas.');
                    return;
                }

                const url = new URL(`{{ route('cecoco.index') }}`, window.location.origin);
                url.searchParams.set('desde_datetime', `${desde}T00:00`);
                url.searchParams.set('hasta_datetime', `${hasta}T23:59`);

                obtenerTiposSeleccionados().forEach(tipo => {
                    url.searchParams.append('tipos[]', tipo);
                });

                window.location.href = url.toString();
            }

            async function exportarPdf() {
                if (!ultimosDatos) {
                    alert('Primero analizá un período para exportar.');
                    return;
                }

                if (!window.jspdf || typeof html2canvas === 'undefined') {
                    alert('No se encontraron las librerías necesarias para generar el PDF.');
                    return;
                }

                const selected = obtenerWidgetsSeleccionados();
                if (selected.length === 0) {
                    alert('Seleccioná al menos un gráfico o sección para exportar.');
                    return;
                }

                const button = document.getElementById('btn-exportar-pdf');
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando...';

                try {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const margin = 12;
                    const contentWidth = pageWidth - margin * 2;
                    const rango = rangoAnalizado();
                    let y = 14;

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(15);
                    pdf.text('Analítica de eventos CECOCO', margin, y);
                    y += 8;
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(9);
                    pdf.text(`Rango: ${rango.desde} a ${rango.hasta}`, margin, y);
                    y += 5;
                    pdf.text(`Tipificación: ${rango.tipo}`, margin, y);
                    y += 5;
                    pdf.text(`Comparación: ${rango.compararTexto}`, margin, y);
                    y += 5;
                    pdf.text(`Total eventos: ${ultimosDatos.total.toLocaleString('es-AR')} | Promedio diario: ${ultimosDatos.promedio_diario}`, margin, y);
                    y += 7;

                    for (const key of selected) {
                        const section = document.querySelector(`[data-dashboard-section="${key}"]`);
                        if (!section || section.offsetParent === null) {
                            continue;
                        }

                        const target = section.querySelector('.chart-card, .card') || section;
                        const canvas = await html2canvas(target, { backgroundColor: '#ffffff', scale: 2 });
                        const imageData = canvas.toDataURL('image/png');
                        const imageHeight = Math.min((canvas.height * contentWidth) / canvas.width, 92);

                        if (y + imageHeight + 10 > pageHeight - margin) {
                            pdf.addPage();
                            y = margin;
                        }

                        pdf.setFont('helvetica', 'bold');
                        pdf.setFontSize(10);
                        pdf.text(chartLabels[key] || key, margin, y);
                        y += 4;
                        pdf.addImage(imageData, 'PNG', margin, y, contentWidth, imageHeight);
                        y += imageHeight + 7;
                    }

                    pdf.save(`analitica-cecoco-${rango.desde}-${rango.hasta}.pdf`);
                } catch (error) {
                    console.error(error);
                    alert('No se pudo generar el PDF. Revisá la consola.');
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            }

            // ---- Carga de datos ----
            function fmtTiempoRespuesta(valor) {
                return valor === null || valor === undefined ? '-' : Number(valor).toLocaleString('es-AR');
            }

            function renderTablaLentosTiempoRespuesta(eventos) {
                const body = document.getElementById('tr-tabla-lentos-body');
                if (!eventos || eventos.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin datos.</td></tr>';
                    return;
                }

                body.innerHTML = eventos.map(function (ev) {
                    const url = `{{ url('/cecoco') }}/${ev.id}/expediente`;
                    return `<tr>
                        <td><a href="${url}" target="_blank">${ev.nro_expediente}</a></td>
                        <td>${ev.fecha_hora}</td>
                        <td>${ev.tipo_servicio}</td>
                        <td>${ev.recurso}</td>
                        <td class="text-end fw-semibold">${ev.minutos}</td>
                    </tr>`;
                }).join('');
            }

            function renderChartsTiempoRespuesta(datos) {
                const isDark = detectTheme();
                const textColor = isDark ? '#e2e8f0' : '#0f172a';
                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.15)';
                const tooltipStyle = {
                    backgroundColor: isDark ? 'rgba(30,41,59,0.95)' : 'rgba(255,255,255,0.95)',
                    titleColor: isDark ? '#f1f5f9' : '#1e293b',
                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                    borderColor: isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)',
                    borderWidth: 1, padding: 10, boxPadding: 4, cornerRadius: 8
                };

                if (trChartFecha) trChartFecha.destroy();
                const fechaLabels = Object.keys(datos.por_fecha || {});
                const fechaVals = Object.values(datos.por_fecha || {});
                trChartFecha = new Chart(document.getElementById('tr-chart-fecha'), {
                    type: 'line',
                    data: {
                        labels: fechaLabels,
                        datasets: [{
                            label: 'Minutos promedio',
                            data: fechaVals,
                            borderColor: isDark ? '#38bdf8' : 'rgba(13,110,253,0.8)',
                            backgroundColor: isDark ? 'rgba(56,189,248,0.15)' : 'rgba(13,110,253,0.1)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: fechaLabels.length > 60 ? 0 : 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, maxTicksLimit: 12, maxRotation: 30 } },
                            y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor } }
                        }
                    }
                });

                if (trChartHora) trChartHora.destroy();
                const horaLabels = Object.keys(datos.por_hora).map(h => String(h).padStart(2, '0') + 'h');
                const horaVals = Object.values(datos.por_hora).map(v => v === null ? 0 : v);
                trChartHora = new Chart(document.getElementById('tr-chart-hora'), {
                    type: 'bar',
                    data: { labels: horaLabels, datasets: [{ label: 'Minutos promedio', data: horaVals, backgroundColor: isDark ? '#a855f7' : 'rgba(13,110,253,0.6)', borderRadius: 4 }] },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, font: { size: 10 } } },
                            y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor } }
                        }
                    }
                });

                if (trChartDistribucion) trChartDistribucion.destroy();
                trChartDistribucion = new Chart(document.getElementById('tr-chart-distribucion'), {
                    type: 'bar',
                    data: {
                        labels: (datos.distribucion || []).map(d => d.banda),
                        datasets: [{ label: 'Eventos', data: (datos.distribucion || []).map(d => d.total), backgroundColor: isDark ? '#ec4899' : 'rgba(220,53,69,0.6)', borderRadius: 4 }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                            y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor, precision: 0 } }
                        }
                    }
                });

                if (trChartTipos) trChartTipos.destroy();
                trChartTipos = new Chart(document.getElementById('tr-chart-tipos'), {
                    type: 'bar',
                    data: {
                        labels: (datos.top_tipos || []).map(t => t.tipo),
                        datasets: [{ label: 'Promedio (min)', data: (datos.top_tipos || []).map(t => t.promedio), backgroundColor: isDark ? '#f97316' : 'rgba(253,126,20,0.7)', borderRadius: 4 }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { ...tooltipStyle, callbacks: { label: item => ` ${item.raw} min (${datos.top_tipos[item.dataIndex].cantidad} casos)` } }
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor } },
                            y: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
                        }
                    }
                });
            }

            function cargarTiemposRespuesta() {
                document.getElementById('tr-texto-cobertura').textContent = 'Calculando...';

                fetch(`{{ route('api.cecoco.tiempos-respuesta.datos') }}?${construirParametrosAnalisis()}`)
                    .then(r => r.json().then(payload => {
                        if (!r.ok) { throw new Error(payload.message || 'No se pudieron obtener los datos.'); }
                        return payload;
                    }))
                    .then(datos => {
                        ultimosDatosTiempoRespuesta = datos;

                        const textoFaltantes = datos.cobertura < datos.total_eventos_periodo
                            ? ' El resto tiene su expediente consultado pero su timeline no registra un recurso móvil pasando por "En desplazamiento" y "En atención".'
                            : '';
                        document.getElementById('tr-texto-cobertura').textContent =
                            `${fmtTiempoRespuesta(datos.cobertura)} de ${fmtTiempoRespuesta(datos.total_eventos_periodo)} eventos del período tienen un tiempo de respuesta calculable (${datos.cobertura_pct}% de cobertura).${textoFaltantes}`;

                        if (datos.cobertura === 0) {
                            document.getElementById('tr-sin-datos').style.display = 'block';
                            document.getElementById('tr-contenido-con-datos').style.display = 'none';
                            return;
                        }

                        document.getElementById('tr-sin-datos').style.display = 'none';
                        document.getElementById('tr-contenido-con-datos').style.display = 'block';

                        document.getElementById('tr-stat-promedio').textContent = fmtTiempoRespuesta(datos.promedio_minutos);
                        document.getElementById('tr-stat-mediana').textContent = fmtTiempoRespuesta(datos.mediana_minutos);
                        document.getElementById('tr-stat-minimo').textContent = fmtTiempoRespuesta(datos.minimo_minutos);
                        document.getElementById('tr-stat-maximo').textContent = fmtTiempoRespuesta(datos.maximo_minutos);

                        renderChartsTiempoRespuesta(datos);
                        renderTablaLentosTiempoRespuesta(datos.eventos_lentos);
                    })
                    .catch(err => {
                        console.error(err);
                        document.getElementById('tr-texto-cobertura').textContent = err.message || 'Error al obtener datos de tiempos de respuesta.';
                    });
            }

            function cargarDatos() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;

                if (!desde || !hasta) { alert('Seleccioná un período de fechas.'); return; }

                document.getElementById('loading-analitica').style.display = 'block';
                document.getElementById('contenido-analitica').style.display = 'none';

                if (tieneTiemposRespuesta) {
                    cargarTiemposRespuesta();
                }

                const params = construirParametrosAnalisis();

                fetch(`{{ route('api.cecoco.analitica.datos') }}?${params}`)
                    .then(r => r.json().then(payload => {
                        if (!r.ok) {
                            throw new Error(payload.message || 'No se pudieron obtener los datos.');
                        }

                        return payload;
                    }))
                    .then(datos => {
                        ultimosDatos = datos;

                        document.getElementById('stat-total').textContent = datos.total.toLocaleString('es-AR');
                        document.getElementById('stat-hora-pico').textContent = datos.hora_pico;
                        document.getElementById('stat-dia-pico').textContent = datos.dia_pico;
                        document.getElementById('stat-calle-top').textContent =
                            datos.top_calles && datos.top_calles.length > 0 ? datos.top_calles[0].calle : '-';
                        document.getElementById('stat-promedio-diario').textContent =
                            datos.promedio_diario != null ? datos.promedio_diario.toLocaleString('es-AR') : '-';

                        const indicadores = datos.indicadores_resultado || {};
                        const fmtIndicador = v => (v != null ? Number(v).toLocaleString('es-AR') : '-');
                        document.getElementById('stat-demorados').textContent = fmtIndicador(indicadores.demorados);
                        document.getElementById('stat-armas').textContent = fmtIndicador(indicadores.armas_secuestradas);
                        document.getElementById('stat-motos').textContent = fmtIndicador(indicadores.motos_recuperadas);
                        document.getElementById('stat-vehiculos').textContent = fmtIndicador(indicadores.vehiculos_recuperados);

                        renderCharts(datos);
                        mostrarIncidencias(datos);
                        mostrarTasaDetencion(datos);

                        document.getElementById('loading-analitica').style.display = 'none';
                        document.getElementById('contenido-analitica').style.display = 'block';
                        document.getElementById('btn-exportar-pdf').disabled = false;
                        document.getElementById('btn-ver-analizador').disabled = false;
                        aplicarDashboardSeleccionado(false);
                    })
                    .catch(err => {
                        console.error(err);
                        document.getElementById('loading-analitica').style.display = 'none';
                        document.getElementById('btn-exportar-pdf').disabled = true;
                        document.getElementById('btn-ver-analizador').disabled = true;
                        alert(err.message || 'Error al obtener datos. Revisá la consola.');
                    });
            }

            // ---- Observer de tema (igual que home) ----
            const themeObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                        if (ultimosDatos) renderCharts(ultimosDatos);
                        if (ultimosDatosTiempoRespuesta && ultimosDatosTiempoRespuesta.cobertura > 0) renderChartsTiempoRespuesta(ultimosDatosTiempoRespuesta);
                    }
                });
            });
            themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

            // Carga inicial
            cargarDatos();
        });
    </script>
@endsection
