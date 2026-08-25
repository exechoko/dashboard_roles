@extends('layouts.app')

@section('css')
    <style>
        .stat-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: .5rem;
            padding: 1rem 1.25rem;
            height: 100%;
        }

        .stat-card .stat-icon {
            font-size: 1.4rem;
            opacity: .55;
        }

        .stat-card .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-size: .8rem;
            color: var(--text-secondary, #6c757d);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .chart-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: .5rem;
            padding: 1rem 1.25rem;
        }

        .chart-card h6 {
            font-weight: 600;
            margin-bottom: .75rem;
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading mb-0">CeCoCo - Reporte Central Telefónica</h3>
            <div>
                <a href="{{ route('cecoco.llamadas-central-telefonica.buscar') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-search mr-1"></i> Buscar Número
                </a>
                @can('importar-llamadas-central-telefonica')
                    <a href="{{ route('cecoco.llamadas-central-telefonica.importar') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-upload mr-1"></i> Importar CSV
                    </a>
                @endcan
                <a href="{{ route('cecoco.llamadas-central-telefonica.exportar-docx') }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-word mr-1"></i> Exportar DOCX (por mes)
                </a>
            </div>
        </div>
        <div class="section-body">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form id="form-filtro" class="row align-items-end g-2" onsubmit="return false;">
                        <div class="col-md-3">
                            <label class="form-label mb-1" for="filtro-periodo">Período</label>
                            <select id="filtro-periodo" class="form-control">
                                <option value="">Elegir mes...</option>
                                @foreach ($periodos as $periodo)
                                    <option value="{{ $periodo }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $periodo)->translatedFormat('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1" for="filtro-desde">Desde</label>
                            <input type="date" id="filtro-desde" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1" for="filtro-hasta">Hasta</label>
                            <input type="date" id="filtro-hasta" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="btn-consultar" class="btn btn-primary w-100">
                                <i class="fas fa-search mr-1"></i> Consultar
                            </button>
                        </div>
                    </form>
                    <small class="text-muted d-block mt-2" id="rango-actual"></small>
                </div>
            </div>

            <div id="loading" class="text-center text-muted py-3" style="display:none;">
                <i class="fas fa-spinner fa-spin mr-1"></i> Cargando...
            </div>

            <div id="contenido">
                <div class="row mb-3">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Llamadas recibidas</span>
                                <i class="fas fa-phone-alt stat-icon text-primary"></i>
                            </div>
                            <div class="stat-value" id="stat-recibidas">-</div>
                            <small class="text-muted">Ingresadas a CeCoCo (línea 9999)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Atendidas</span>
                                <i class="fas fa-phone-volume stat-icon text-success"></i>
                            </div>
                            <div class="stat-value" id="stat-atendidas">-</div>
                            <small class="text-muted"><span id="stat-tasa">-</span>% del total recibido</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Descartadas</span>
                                <i class="fas fa-phone-slash stat-icon text-danger"></i>
                            </div>
                            <div class="stat-value" id="stat-descartadas">-</div>
                            <small class="text-muted">Cortaron antes de ser atendidas</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Tiempo de atención</span>
                                <i class="fas fa-stopwatch stat-icon text-info"></i>
                            </div>
                            <div class="stat-value" id="stat-tiempo-atencion">-</div>
                            <small class="text-muted">Promedio (máx. <span id="stat-tiempo-max">-</span>)</small>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Tiempo de espera</span>
                                <i class="fas fa-hourglass-half stat-icon text-warning"></i>
                            </div>
                            <div class="stat-value" id="stat-tiempo-espera">-</div>
                            <small class="text-muted">Promedio hasta ser atendida</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Llamadas salientes</span>
                                <i class="fas fa-phone-arrow-up-right stat-icon text-secondary"></i>
                            </div>
                            <div class="stat-value" id="stat-salientes">-</div>
                            <small class="text-muted">CeCoCo hacia afuera (despacho, avisos)</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-6 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between">
                                <span class="stat-label">Llamadas internas</span>
                                <i class="fas fa-headset stat-icon text-secondary"></i>
                            </div>
                            <div class="stat-value" id="stat-internas">-</div>
                            <small class="text-muted">Entre extensiones (rango 5000-5999)</small>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="chart-card h-100">
                            <h6><i class="fas fa-chart-line mr-1 text-primary"></i>Llamadas recibidas por día</h6>
                            <canvas id="chart-dia" height="90"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="chart-card h-100">
                            <h6><i class="fas fa-chart-bar mr-1 text-primary"></i>Por hora del día</h6>
                            <canvas id="chart-hora" height="90"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <div class="chart-card h-100">
                            <h6><i class="fas fa-list-ol mr-1 text-primary"></i>Destinos de las llamadas salientes</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Número marcado</th>
                                            <th class="text-end">Llamadas</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-destinos"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="chart-card h-100">
                            <h6><i class="fas fa-circle-info mr-1 text-primary"></i>Sobre este reporte</h6>
                            <p class="text-muted mb-1" style="font-size:.85rem;">
                                Datos extraídos de los CDR de la central telefónica (<code>docs/varios/CSVs_central_telefonica</code>).
                                No incluyen expedientes, tipificación ni tiempos de despacho/arribo/resolución: esa
                                información no está en los registros de llamada.
                            </p>
                            <p class="text-muted mb-0" style="font-size:.85rem;">
                                "Otras" (<span id="stat-otras">-</span>) agrupa filas sin caller ID reconocible que no
                                encajan en ninguna categoría anterior.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const urlDatos = @json(route('cecoco.llamadas-central-telefonica.datos'));
            const selPeriodo = document.getElementById('filtro-periodo');
            const inpDesde = document.getElementById('filtro-desde');
            const inpHasta = document.getElementById('filtro-hasta');
            const btnConsultar = document.getElementById('btn-consultar');
            const loading = document.getElementById('loading');
            const contenido = document.getElementById('contenido');

            let chartDia = null;
            let chartHora = null;

            function segundosATexto(segundos) {
                segundos = Math.round(segundos || 0);
                const m = Math.floor(segundos / 60);
                const s = segundos % 60;
                return m + ':' + String(s).padStart(2, '0');
            }

            function construirParams() {
                const params = new URLSearchParams();

                if (selPeriodo.value) {
                    params.set('periodo', selPeriodo.value);
                } else {
                    if (inpDesde.value) params.set('desde', inpDesde.value);
                    if (inpHasta.value) params.set('hasta', inpHasta.value);
                }

                return params;
            }

            function cargar() {
                loading.style.display = '';
                contenido.style.opacity = '.5';

                fetch(urlDatos + '?' + construirParams().toString())
                    .then(r => r.json())
                    .then(datos => {
                        pintarStats(datos);
                        pintarGraficos(datos);
                    })
                    .finally(() => {
                        loading.style.display = 'none';
                        contenido.style.opacity = '1';
                    });
            }

            function pintarStats(d) {
                document.getElementById('rango-actual').textContent =
                    'Mostrando ' + d.desde.substring(0, 10) + ' a ' + d.hasta.substring(0, 10);

                document.getElementById('stat-recibidas').textContent = d.total_recibidas.toLocaleString('es-AR');
                document.getElementById('stat-atendidas').textContent = d.atendidas.toLocaleString('es-AR');
                document.getElementById('stat-tasa').textContent = d.tasa_atencion;
                document.getElementById('stat-descartadas').textContent = d.descartadas.toLocaleString('es-AR');
                document.getElementById('stat-tiempo-atencion').textContent = segundosATexto(d.tiempo_atencion_promedio);
                document.getElementById('stat-tiempo-max').textContent = segundosATexto(d.tiempo_atencion_maximo);
                document.getElementById('stat-tiempo-espera').textContent = segundosATexto(d.tiempo_espera_promedio);
                document.getElementById('stat-salientes').textContent = d.salientes.toLocaleString('es-AR');
                document.getElementById('stat-internas').textContent = d.internas.toLocaleString('es-AR');
                document.getElementById('stat-otras').textContent = d.otras.toLocaleString('es-AR');

                const tabla = document.getElementById('tabla-destinos');
                tabla.innerHTML = '';
                d.top_destinos_salientes.forEach(fila => {
                    const etiqueta = fila.numero === '107' ? '107 (Emergencias médicas)' : fila.numero;
                    tabla.innerHTML += '<tr><td>' + etiqueta + '</td><td class="text-end">' + fila.total.toLocaleString('es-AR') + '</td></tr>';
                });
            }

            function pintarGraficos(d) {
                const labelsDia = d.por_dia.map(f => f.fecha.substring(8, 10) + '/' + f.fecha.substring(5, 7));

                if (chartDia) chartDia.destroy();
                chartDia = new Chart(document.getElementById('chart-dia'), {
                    type: 'bar',
                    data: {
                        labels: labelsDia,
                        datasets: [
                            { label: 'Atendidas', data: d.por_dia.map(f => f.atendidas), backgroundColor: '#28a745' },
                            { label: 'Descartadas', data: d.por_dia.map(f => f.descartadas), backgroundColor: '#dc3545' },
                        ],
                    },
                    options: {
                        responsive: true,
                        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
                    },
                });

                if (chartHora) chartHora.destroy();
                chartHora = new Chart(document.getElementById('chart-hora'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(d.por_hora).map(h => h + 'h'),
                        datasets: [{ label: 'Llamadas', data: Object.values(d.por_hora), backgroundColor: '#0d6efd' }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            }

            btnConsultar.addEventListener('click', cargar);
            selPeriodo.addEventListener('change', function () {
                if (this.value) {
                    inpDesde.value = '';
                    inpHasta.value = '';
                }
            });

            cargar();
        })();
    </script>
@endsection
