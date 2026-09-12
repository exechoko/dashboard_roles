@extends('layouts.movil')

@section('title', 'Workers y Bases de Datos')
@section('back', route('movil.index'))

@section('content')
    <div class="m-card__subtitle" style="margin-bottom:.8rem; display:flex; justify-content:space-between; align-items:center;">
        <span>Estado de procesos y base de datos</span>
        <small id="mInfraActualizado"></small>
    </div>

    <div class="m-section-title">Worker</div>
    <div class="m-detail">
        <dl style="margin:0;">
            <div class="m-detail__row">
                <dt>Estado</dt>
                <dd>
                    <span id="mInfraDot" style="width:10px; height:10px; border-radius:50%; display:inline-block; background:#aaa; margin-right:.4rem; vertical-align:middle;"></span>
                    <span id="mInfraLabel" class="badge badge-secondary">Verificando...</span>
                </dd>
            </div>
            <div class="m-detail__row"><dt>Pendientes</dt><dd><span id="mInfraPendientes" class="badge badge-secondary">—</span></dd></div>
            <div class="m-detail__row"><dt>Procesando</dt><dd><span id="mInfraProcesando" class="badge badge-secondary">—</span></dd></div>
            <div class="m-detail__row"><dt>Fallidos</dt><dd><span id="mInfraFallidos" class="badge badge-secondary">—</span></dd></div>
        </dl>
    </div>

    <div class="m-section-title">Geocodificación</div>
    <div class="m-detail">
        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Servicio</dt><dd><span id="mInfraGeoServicio" class="badge badge-secondary">Verificando...</span></dd></div>
            <div class="m-detail__row"><dt>Cacheadas</dt><dd><span id="mInfraGeoCacheadas" class="badge badge-success">—</span></dd></div>
            <div class="m-detail__row"><dt>Pendientes</dt><dd><span id="mInfraGeoPendientes" class="badge badge-secondary">—</span></dd></div>
        </dl>
    </div>

    @can('ver-infraestructura-librenms')
        <div class="m-section-title">Cámaras 911 (LibreNMS)</div>
        <div class="m-detail">
            <dl style="margin:0;">
                <div class="m-detail__row"><dt>Estado</dt><dd><span id="mInfraCamarasEstado" class="badge badge-secondary">Verificando...</span></dd></div>
            </dl>
        </div>
        <dl id="mInfraCamarasCaidas" class="m-detail" style="display:none; margin-top:-.4rem;"></dl>
    @endcan

    <div class="m-section-title">Tamaño de bases de datos</div>
    <div class="m-detail">
        <dl style="margin:0;">
            <div class="m-detail__row">
                <dt>Restauraciones CECOCO</dt>
                <dd>
                    <span id="mInfraRestMb" class="badge badge-secondary">—</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" id="mInfraRestRefresh" title="Consultar ahora">
                        <i class="fas fa-sync-alt" id="mInfraRestRefreshIcon"></i>
                    </button>
                </dd>
            </div>
            <div class="m-detail__row">
                <dt>Restauraciones GPS</dt>
                <dd>
                    <span id="mInfraRestGpsMb" class="badge badge-secondary">—</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" id="mInfraRestGpsRefresh" title="Consultar ahora">
                        <i class="fas fa-sync-alt" id="mInfraRestGpsRefreshIcon"></i>
                    </button>
                </dd>
            </div>
        </dl>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var urlEstado = '{{ route('api.infraestructura.workers-status') }}';

            function pintarEstadoVacio(dotEl, labelEl, texto, clase) {
                if (dotEl) dotEl.style.background = '#f59e0b';
                if (labelEl) { labelEl.className = 'badge ' + clase; labelEl.textContent = texto; }
            }

            function verificar() {
                fetch(urlEstado, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        var dot = document.getElementById('mInfraDot');
                        var label = document.getElementById('mInfraLabel');

                        if (d.error === 'tabla_jobs_inexistente') {
                            pintarEstadoVacio(dot, label, 'Sin configurar', 'badge-warning');
                            return;
                        }

                        if (d.worker_activo) {
                            dot.style.background = '#22c55e';
                            label.className = 'badge badge-success';
                            label.textContent = 'Activo';
                        } else {
                            dot.style.background = d.pendientes > 0 ? '#ef4444' : '#6b7280';
                            label.className = d.pendientes > 0 ? 'badge badge-danger' : 'badge badge-secondary';
                            label.textContent = d.pendientes > 0 ? 'Detenido' : 'Inactivo';
                        }

                        var elPend = document.getElementById('mInfraPendientes');
                        elPend.textContent = d.pendientes;
                        elPend.className = d.pendientes > 0 ? 'badge badge-warning' : 'badge badge-secondary';

                        var elProc = document.getElementById('mInfraProcesando');
                        elProc.textContent = d.procesando;
                        elProc.className = d.procesando > 0 ? 'badge badge-info' : 'badge badge-secondary';

                        var elFall = document.getElementById('mInfraFallidos');
                        elFall.textContent = d.fallidos;
                        elFall.className = d.fallidos > 0 ? 'badge badge-danger' : 'badge badge-secondary';

                        var elGeoServicio = document.getElementById('mInfraGeoServicio');
                        var motor = d.geo_servicio_motor || 'Geocodificación';
                        if (d.geo_servicio_online) {
                            elGeoServicio.textContent = motor + ': Online';
                            elGeoServicio.className = 'badge badge-success';
                        } else {
                            elGeoServicio.textContent = motor + ': Offline';
                            elGeoServicio.className = 'badge badge-danger';
                        }

                        var elGeoCach = document.getElementById('mInfraGeoCacheadas');
                        elGeoCach.textContent = d.geo_cacheadas !== null ? d.geo_cacheadas : '—';

                        var elGeoPend = document.getElementById('mInfraGeoPendientes');
                        if (d.geo_pendientes === null) {
                            elGeoPend.textContent = 'calculando...';
                            elGeoPend.className = 'badge badge-secondary';
                        } else {
                            elGeoPend.textContent = d.geo_pendientes;
                            elGeoPend.className = d.geo_pendientes > 0 ? 'badge badge-warning' : 'badge badge-success';
                        }

                        pintarTamanoBd('mInfraRestMb', d.restauraciones_mb, d.restauraciones_umbral_mb || 4000);
                        pintarTamanoBd('mInfraRestGpsMb', d.restauraciones_gps_mb, d.restauraciones_gps_umbral_mb || 4000);

                        pintarCamarasLibreNms(d.camaras_librenms);

                        var hora = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        document.getElementById('mInfraActualizado').textContent = 'Actualizado: ' + hora;
                    })
                    .catch(function () {
                        pintarEstadoVacio(document.getElementById('mInfraDot'), document.getElementById('mInfraLabel'), 'Error', 'badge-warning');
                    });
            }

            function escapar(texto) {
                var div = document.createElement('div');
                div.textContent = texto == null ? '' : String(texto);
                return div.innerHTML;
            }

            function pintarCamarasLibreNms(datos) {
                var estado = document.getElementById('mInfraCamarasEstado');
                var caidas = document.getElementById('mInfraCamarasCaidas');
                if (!estado) return;

                if (!datos || !datos.disponible) {
                    estado.textContent = 'sin lectura reciente';
                    estado.className = 'badge badge-warning';
                    if (caidas) caidas.style.display = 'none';
                    return;
                }

                var nroCaidas = datos.caidas || 0;
                if (nroCaidas > 0) {
                    estado.textContent = nroCaidas + ' caída' + (nroCaidas === 1 ? '' : 's') + ' de ' + datos.total;
                    estado.className = 'badge badge-danger';
                } else {
                    estado.textContent = 'las ' + datos.total + ' online';
                    estado.className = 'badge badge-success';
                }

                if (!caidas) return;

                if (nroCaidas === 0) {
                    caidas.style.display = 'none';
                    return;
                }

                caidas.style.display = '';
                caidas.innerHTML = (datos.offline || []).map(function (cam) {
                    return '<div class="m-detail__row"><dt><i class="fas fa-video-slash text-danger"></i> ' + escapar(cam.nombre) + '</dt>'
                        + '<dd><small>' + (cam.ip ? escapar(cam.ip) + ' — ' : '') + 'hace ' + escapar(cam.caida_hace || '?') + '</small></dd></div>';
                }).join('');
            }

            function pintarTamanoBd(elId, mb, umbral) {
                var el = document.getElementById(elId);
                if (mb === null || typeof mb === 'undefined') {
                    el.textContent = 'sin datos';
                    el.className = 'badge badge-secondary';
                    return;
                }
                var mbFmt = Number(mb).toLocaleString('es-AR', { maximumFractionDigits: 0 });
                el.textContent = mbFmt + ' MB';
                el.className = mb > umbral ? 'badge badge-danger' : 'badge badge-success';
            }

            function refrescar(botonId, iconoId, elId, ruta) {
                var boton = document.getElementById(botonId);
                var icono = document.getElementById(iconoId);
                var el = document.getElementById(elId);

                boton.addEventListener('click', function () {
                    boton.disabled = true;
                    icono.className = 'fas fa-hourglass-half fa-spin';
                    el.textContent = '...';
                    el.className = 'badge badge-secondary';

                    fetch(ruta, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (r) { return r.json().then(function (d) { return { status: r.status, body: d }; }); })
                        .then(function (res) {
                            if (res.status === 429 || !res.body.ok) {
                                el.textContent = 'error';
                                el.className = 'badge badge-danger';
                                boton.disabled = false;
                                icono.className = 'fas fa-sync-alt';
                                return;
                            }

                            el.textContent = 'consultando...';
                            el.className = 'badge badge-info';

                            var intentos = 0;
                            var intervalo = setInterval(function () {
                                intentos++;
                                verificar();
                                if (intentos >= 5) {
                                    clearInterval(intervalo);
                                    boton.disabled = false;
                                    icono.className = 'fas fa-sync-alt';
                                }
                            }, 4000);
                        })
                        .catch(function () {
                            el.textContent = 'error';
                            el.className = 'badge badge-danger';
                            boton.disabled = false;
                            icono.className = 'fas fa-sync-alt';
                        });
                });
            }

            refrescar('mInfraRestRefresh', 'mInfraRestRefreshIcon', 'mInfraRestMb', '{{ route('api.infraestructura.refresh-restauraciones') }}');
            refrescar('mInfraRestGpsRefresh', 'mInfraRestGpsRefreshIcon', 'mInfraRestGpsMb', '{{ route('api.infraestructura.refresh-restauraciones-gps') }}');

            verificar();
            setInterval(verificar, 60000);
        })();
    </script>
@endsection
