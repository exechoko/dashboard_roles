@extends('layouts.app')

@section('css')
    <style>
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

        #loading-tiempos {
            display: none;
        }

        .periodo-rapido .btn {
            font-size: .82rem;
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

        .cobertura-alerta {
            border-left: 4px solid #fd7e14;
            background: rgba(253, 126, 20, .07);
            border-radius: 0 8px 8px 0;
            padding: .8rem 1rem;
        }

        [data-theme="dark"] .cobertura-alerta {
            background: rgba(253, 126, 20, .13);
        }

        #tabla-lentos th, #tabla-lentos td {
            font-size: .85rem;
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    <div class="mb-3">
        <a href="{{ route('cecoco.analitica') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a Analítica de Delitos
        </a>
    </div>

    <h4 class="mb-1"><i class="bi bi-stopwatch-fill me-2 text-primary"></i>Tiempos de respuesta</h4>
    <p class="text-muted mb-3" style="font-size:.9rem">
        Minutos entre que un recurso pasa a "En desplazamiento" y llega a "En atención", según el timeline de los
        expedientes de CECOCO ya consultados. Se toma el recurso móvil más rápido de cada evento (se descartan bases,
        despachos, cámaras y otros puestos fijos). No todos los eventos consultados quedan cubiertos: muchos no
        tienen ese par de marcas para ningún recurso móvil (por ejemplo, si solo intervino un recurso fijo o nunca se
        despachó un móvil), y se excluyen del cálculo aunque su expediente ya fue procesado.
    </p>

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
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <label class="form-label fw-semibold mb-0" style="font-size:.82rem">TIPIFICACIONES A MOSTRAR</label>
                    <small class="text-muted">Marcá una o varias; con “Todas” no se aplica filtro por tipo.</small>
                </div>
                <div class="tipificaciones-panel">
                    <div class="row g-2">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipos-todas" checked>
                                <label class="form-check-label fw-semibold" for="tipos-todas">Todas las tipificaciones</label>
                            </div>
                        </div>
                        @foreach($tipos as $index => $tipo)
                            @if($tipo)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input tipo-checkbox" type="checkbox" value="{{ $tipo }}" id="tipo-{{ $index }}">
                                        <label class="form-check-label" for="tipo-{{ $index }}">{{ $tipo }}</label>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end flex-wrap gap-2 pt-2 border-top">
                <button id="btn-analizar" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-stopwatch me-1"></i>Analizar
                </button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div id="loading-tiempos" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Procesando timelines de expedientes...</p>
    </div>

    {{-- Contenido --}}
    <div id="contenido-tiempos" style="display:none">

        <div class="cobertura-alerta mb-4" id="alerta-cobertura">
            <i class="bi bi-info-circle me-1"></i>
            <span id="texto-cobertura">-</span>
        </div>

        <div id="sin-datos" class="text-center text-muted py-5" style="display:none">
            <i class="bi bi-emoji-frown" style="font-size:2rem"></i>
            <p class="mt-2 mb-0">Ningún expediente consultado en este período tiene datos de recurso asignados.</p>
        </div>

        <div id="contenido-con-datos">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-primary"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="stat-value" id="stat-promedio">-</div>
                            <div class="stat-label">Promedio (min)</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-info"><i class="bi bi-bar-chart-steps"></i></div>
                        <div>
                            <div class="stat-value" id="stat-mediana">-</div>
                            <div class="stat-label">Mediana (min)</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-success"><i class="bi bi-lightning-fill"></i></div>
                        <div>
                            <div class="stat-value" id="stat-minimo">-</div>
                            <div class="stat-label">Más rápido (min)</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-danger"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="stat-value" id="stat-maximo">-</div>
                            <div class="stat-label">Más lento (min)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="chart-card">
                        <h6><i class="bi bi-graph-up me-1"></i>Tiempo de respuesta promedio por día</h6>
                        <canvas id="chart-fecha" height="90"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="chart-card h-100">
                        <h6><i class="bi bi-clock me-1"></i>Promedio por hora de despacho</h6>
                        <canvas id="chart-hora" height="220"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="chart-card h-100">
                        <h6><i class="bi bi-bar-chart me-1"></i>Distribución de tiempos</h6>
                        <canvas id="chart-distribucion" height="220"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="chart-card h-100">
                        <h6><i class="bi bi-list-ul me-1"></i>Tipificaciones más lentas (promedio, mín. 3 casos)</h6>
                        <canvas id="chart-tipos" height="260"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                            Eventos con mayor tiempo de respuesta
                        </div>
                        <div class="card-body p-0" style="overflow-x:auto">
                            <table class="table table-sm mb-0" id="tabla-lentos">
                                <thead>
                                    <tr>
                                        <th>Expediente</th>
                                        <th>Fecha</th>
                                        <th>Tipificación</th>
                                        <th>Recurso</th>
                                        <th class="text-end">Minutos</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-lentos-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function detectTheme() {
                const html = document.documentElement;
                const theme = html.getAttribute('data-theme') || html.getAttribute('data-bs-theme') || '';
                return theme === 'dark';
            }

            function hoy() { return new Date().toISOString().slice(0, 10); }
            function haceDias(n) {
                const d = new Date();
                d.setDate(d.getDate() - n + 1);
                return d.toISOString().slice(0, 10);
            }

            let ultimosDatos = null;
            let chartFecha = null, chartHora = null, chartDistribucion = null, chartTipos = null;

            function obtenerTiposSeleccionados() {
                if (document.getElementById('tipos-todas').checked) {
                    return [];
                }
                return Array.from(document.querySelectorAll('.tipo-checkbox:checked')).map(input => input.value);
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

            document.getElementById('filtro-desde').value = haceDias(7);
            document.getElementById('filtro-hasta').value = hoy();

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
            document.getElementById('tipos-todas').addEventListener('change', sincronizarTipificaciones);
            document.querySelectorAll('.tipo-checkbox').forEach(input => {
                input.addEventListener('change', sincronizarTipificaciones);
            });

            function fmt(valor) {
                return valor === null || valor === undefined ? '-' : Number(valor).toLocaleString('es-AR');
            }

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

                if (chartFecha) chartFecha.destroy();
                const fechaLabels = Object.keys(datos.por_fecha || {});
                const fechaVals = Object.values(datos.por_fecha || {});
                chartFecha = new Chart(document.getElementById('chart-fecha'), {
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

                if (chartHora) chartHora.destroy();
                const horaLabels = Object.keys(datos.por_hora).map(h => String(h).padStart(2, '0') + 'h');
                const horaVals = Object.values(datos.por_hora).map(v => v === null ? 0 : v);
                chartHora = new Chart(document.getElementById('chart-hora'), {
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

                if (chartDistribucion) chartDistribucion.destroy();
                chartDistribucion = new Chart(document.getElementById('chart-distribucion'), {
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

                if (chartTipos) chartTipos.destroy();
                chartTipos = new Chart(document.getElementById('chart-tipos'), {
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

            function renderTablaLentos(eventos) {
                const body = document.getElementById('tabla-lentos-body');
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

            function construirParametros() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;
                const params = new URLSearchParams({ desde, hasta });
                obtenerTiposSeleccionados().forEach(tipo => params.append('tipos[]', tipo));
                return params;
            }

            function cargarDatos() {
                const desde = document.getElementById('filtro-desde').value;
                const hasta = document.getElementById('filtro-hasta').value;
                if (!desde || !hasta) { alert('Seleccioná un período de fechas.'); return; }

                document.getElementById('loading-tiempos').style.display = 'block';
                document.getElementById('contenido-tiempos').style.display = 'none';

                fetch(`{{ route('api.cecoco.tiempos-respuesta.datos') }}?${construirParametros()}`)
                    .then(r => r.json().then(payload => {
                        if (!r.ok) { throw new Error(payload.message || 'No se pudieron obtener los datos.'); }
                        return payload;
                    }))
                    .then(datos => {
                        ultimosDatos = datos;

                        document.getElementById('texto-cobertura').textContent =
                            `${fmt(datos.cobertura)} de ${fmt(datos.total_eventos_periodo)} eventos del período tienen un tiempo de respuesta calculable (${datos.cobertura_pct}% de cobertura). El resto tiene su expediente consultado pero su timeline no registra un recurso móvil pasando por "En desplazamiento" y "En atención" (solo intervino un recurso fijo, no se despachó un móvil, etc.).`;

                        document.getElementById('loading-tiempos').style.display = 'none';
                        document.getElementById('contenido-tiempos').style.display = 'block';

                        if (datos.cobertura === 0) {
                            document.getElementById('sin-datos').style.display = 'block';
                            document.getElementById('contenido-con-datos').style.display = 'none';
                            return;
                        }

                        document.getElementById('sin-datos').style.display = 'none';
                        document.getElementById('contenido-con-datos').style.display = 'block';

                        document.getElementById('stat-promedio').textContent = fmt(datos.promedio_minutos);
                        document.getElementById('stat-mediana').textContent = fmt(datos.mediana_minutos);
                        document.getElementById('stat-minimo').textContent = fmt(datos.minimo_minutos);
                        document.getElementById('stat-maximo').textContent = fmt(datos.maximo_minutos);

                        renderCharts(datos);
                        renderTablaLentos(datos.eventos_lentos);
                    })
                    .catch(err => {
                        console.error(err);
                        document.getElementById('loading-tiempos').style.display = 'none';
                        alert(err.message || 'Error al obtener datos. Revisá la consola.');
                    });
            }

            const themeObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                        if (ultimosDatos && ultimosDatos.cobertura > 0) renderCharts(ultimosDatos);
                    }
                });
            });
            themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

            cargarDatos();
        });
    </script>
@endsection
