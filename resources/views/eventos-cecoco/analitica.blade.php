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
    </style>
@endsection

@section('content')
    <div class="mb-3">
        <a href="{{ route('cecoco.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    <h4 class="mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Analítica de eventos CECOCO</h4>
    <p class="text-muted mb-3" style="font-size:.9rem">Analizá patrones temporales y geográficos para optimizar el
        patrullaje.</p>

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
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
                <div class="col-12 col-lg-3 col-xl-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">TIPIFICACIÓN (opcional)</label>
                    <select id="filtro-tipo" class="form-select form-select-sm select2-tipificacion" style="width:100%">
                        <option value="">Todas las tipificaciones</option>
                        @foreach($tipos as $tipo)
                            @if($tipo)
                                <option value="{{ $tipo }}">{{ $tipo }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2 col-xl-2">
                    <label class="form-label fw-semibold mb-1" style="font-size:.82rem">COMPARAR CON</label>
                    <select id="filtro-comparar" class="form-select form-select-sm" style="width:100%">
                        <option value="mes" selected>Mes anterior</option>
                        <option value="semana">Semana anterior</option>
                        <option value="anio">Año anterior</option>
                    </select>
                </div>
                <div class="col-12 col-lg-auto col-xl-auto">
                    <button id="btn-analizar" class="btn btn-primary btn-sm px-4"
                        style="background: linear-gradient(135deg, #ec4899, #8b5cf6); border:none; box-shadow: 0 0 10px rgba(236, 72, 153, 0.5);">
                        <i class="bi bi-stars me-1"></i>Analizar
                    </button>
                </div>
            </div>
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

        {{-- Tendencia diaria --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6><i class="bi bi-telephone me-1"></i>Llamadas al 911 por día</h6>
                    <canvas id="chart-fecha" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Gráfico Comparativo --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6><i class="bi bi-bar-chart-steps me-1 text-primary"></i>Comparativa de Hechos de Relevancia (Período
                        vs Mismo Periodo Mes Ant.)</h6>
                    <canvas id="chart-comparativa" height="80"></canvas>
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="chart-card h-100">
                    <h6><i class="bi bi-clock me-1"></i>Eventos por hora del día</h6>
                    <canvas id="chart-hora" height="200"></canvas>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="chart-card h-100">
                    <h6><i class="bi bi-calendar-week me-1"></i>Eventos por día de semana</h6>
                    <canvas id="chart-dia" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <h6><i class="bi bi-list-ul me-1"></i>Top tipificaciones</h6>
                    <canvas id="chart-tipos" height="280"></canvas>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <h6><i class="bi bi-signpost-2 me-1"></i>Top calles / sectores con más incidentes</h6>
                    <canvas id="chart-calles" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Mayores incidencias en Paraná --}}
        <div class="card mb-4" id="seccion-incidencias">
            <div class="card-header fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i>
                Sectores con mayor concentración de incidencias — Paraná
            </div>
            <div class="card-body p-0">
                <div id="tabla-incidencias-container" class="px-3 py-2"></div>
            </div>
        </div>

    </div>
@endsection

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
            // Select2 para tipificación
            $('#filtro-tipo').select2({
                placeholder: 'Todas las tipificaciones',
                allowClear: true,
                width: '100%',
                dropdownAutoWidth: true
            });

            // Select2 para filtro comparar
            $('#filtro-comparar').select2({
                width: '100%',
                minimumResultsForSearch: Infinity
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
            let chartHora = null, chartDia = null, chartTipos = null, chartCalles = null, chartFecha = null, chartComparativa = null;

            // Inicializar filtros
            document.getElementById('filtro-desde').value = haceDias(7);
            document.getElementById('filtro-hasta').value = hoy();

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

            // ---- Carga de datos ----
            function cargarDatos() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;
                const tipo = document.getElementById('filtro-tipo').value;
                const compararCon = document.getElementById('filtro-comparar').value;

                if (!desde || !hasta) { alert('Seleccioná un período de fechas.'); return; }

                document.getElementById('loading-analitica').style.display = 'block';
                document.getElementById('contenido-analitica').style.display = 'none';

                const params = new URLSearchParams({ desde, hasta, comparar_con: compararCon });
                if (tipo) params.append('tipo', tipo);

                fetch(`{{ route('api.cecoco.analitica.datos') }}?${params}`)
                    .then(r => r.json())
                    .then(datos => {
                        ultimosDatos = datos;

                        document.getElementById('stat-total').textContent = datos.total.toLocaleString('es-AR');
                        document.getElementById('stat-hora-pico').textContent = datos.hora_pico;
                        document.getElementById('stat-dia-pico').textContent = datos.dia_pico;
                        document.getElementById('stat-calle-top').textContent =
                            datos.top_calles && datos.top_calles.length > 0 ? datos.top_calles[0].calle : '-';
                        document.getElementById('stat-promedio-diario').textContent =
                            datos.promedio_diario != null ? datos.promedio_diario.toLocaleString('es-AR') : '-';

                        renderCharts(datos);
                        mostrarIncidencias(datos);

                        document.getElementById('loading-analitica').style.display = 'none';
                        document.getElementById('contenido-analitica').style.display = 'block';
                    })
                    .catch(err => {
                        console.error(err);
                        document.getElementById('loading-analitica').style.display = 'none';
                        alert('Error al obtener datos. Revisá la consola.');
                    });
            }

            // ---- Observer de tema (igual que home) ----
            const themeObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                        if (ultimosDatos) renderCharts(ultimosDatos);
                    }
                });
            });
            themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

            // Carga inicial
            cargarDatos();
        });
    </script>
@endsection