@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; LibreNMS</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between py-2"
                            style="background: linear-gradient(135deg,#064e3b,#047857); color:#fff;">
                            <span><i class="fas fa-video mr-2"></i><strong>Estado CCTV (LibreNMS)</strong></span>
                            <small id="cctv-ultima-actualizacion" class="text-white-50"></small>
                        </div>
                        <div class="card-body py-3">
                            <small class="text-muted d-block mb-2" id="cctv-subtitulo">Puestos de
                                operador con mayor carga de CPU</small>
                            <div id="cctv-puestos-container" class="d-flex flex-wrap" style="gap:1.25rem;">
                                <span class="text-muted">Cargando...</span>
                            </div>
                            <hr class="my-3">
                            <small class="text-muted d-block mb-2" id="cctv-camaras-subtitulo">
                                <i class="fas fa-camera mr-1"></i>Cámaras 911</small>
                            <div id="cctv-camaras-container" class="d-flex flex-wrap" style="gap:0.75rem;">
                                <span class="text-muted">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (function cctvMonitor() {
        var url = '{{ route("api.infraestructura.estado-cctv") }}';

        function escapar(texto) {
            var div = document.createElement('div');
            div.textContent = texto;
            return div.innerHTML;
        }

        function colorCpu(promedio, umbral) {
            if (promedio > umbral) return '#ef4444';
            if (promedio > umbral - 15) return '#f59e0b';
            return '#22c55e';
        }

        function renderCamaras(c) {
            var cont = document.getElementById('cctv-camaras-container');
            var sub = document.getElementById('cctv-camaras-subtitulo');
            if (!cont) return;

            if (!c || !c.disponible) {
                cont.innerHTML = '<span class="text-muted"><i class="fas fa-exclamation-circle mr-1"></i>'
                    + 'Sin lectura reciente de cámaras (el monitor corre cada 5 minutos)</span>';
                return;
            }

            if (sub) {
                sub.innerHTML = '<i class="fas fa-camera mr-1"></i>Cámaras 911 — '
                    + (c.caidas > 0
                        ? '<span style="color:#ef4444;font-weight:bold;">' + c.caidas + ' offline</span> de ' + c.total
                        : 'las ' + c.total + ' online');
            }

            if (c.caidas === 0) {
                cont.innerHTML = '<span style="color:#22c55e;"><i class="fas fa-check-circle mr-1"></i>'
                    + 'Todas las cámaras respondiendo</span>';
                return;
            }

            var html = c.offline.map(function (cam) {
                return '<div style="min-width:230px;flex:0 0 auto;border-left:3px solid #ef4444;padding-left:8px;">'
                    + '<div><i class="fas fa-video-slash mr-1" style="color:#ef4444;"></i>'
                    + '<strong>' + escapar(cam.nombre) + '</strong></div>'
                    + '<small class="text-muted">' + (cam.ip ? escapar(cam.ip) + ' — ' : '')
                    + 'sin responder hace ' + (cam.caida_hace ? escapar(cam.caida_hace) : '?') + '</small>'
                    + '</div>';
            }).join('');

            if (c.caidas > c.offline.length) {
                html += '<div class="align-self-center"><small class="text-muted">... y '
                    + (c.caidas - c.offline.length) + ' más</small></div>';
            }

            cont.innerHTML = html;
        }

        function verificar() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var cont = document.getElementById('cctv-puestos-container');
                    if (!cont) return;

                    if (!d.disponible) {
                        cont.innerHTML = '<span class="text-muted"><i class="fas fa-exclamation-circle mr-1"></i>'
                            + 'Sin lectura reciente de LibreNMS (el monitor corre cada 5 minutos)</span>';
                        renderCamaras(d.camaras);
                        return;
                    }

                    cont.innerHTML = d.puestos.map(function (p) {
                        var color = colorCpu(p.promedio, d.umbral);
                        var alerta = p.promedio > d.umbral
                            ? '<i class="fas fa-exclamation-triangle ml-1" style="color:#ef4444;"></i>'
                            : '';
                        return '<div style="min-width:175px;flex:0 0 auto;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span><strong>' + escapar(p.hostname) + '</strong>' + alerta + '</span>'
                            + '<span style="color:' + color + ';font-weight:bold;">' + p.promedio + '%</span>'
                            + '</div>'
                            + '<div class="progress" style="height:8px;background:#e5e7eb;">'
                            + '<div class="progress-bar" style="width:' + Math.min(p.promedio, 100) + '%;background:' + color + ';"></div>'
                            + '</div>'
                            + '<small class="text-muted">núcleo máx ' + p.maximo + '%</small>'
                            + '</div>';
                    }).join('');

                    var sub = document.getElementById('cctv-subtitulo');
                    if (sub) {
                        sub.textContent = d.en_alerta > 0
                            ? 'Puestos con mayor carga de CPU — ' + d.en_alerta + ' sobre el umbral del ' + d.umbral + '% (' + d.total + ' monitoreados)'
                            : 'Puestos con mayor carga de CPU — todos por debajo del ' + d.umbral + '% (' + d.total + ' monitoreados)';
                    }

                    var el = document.getElementById('cctv-ultima-actualizacion');
                    if (el && d.consultado_en) {
                        el.textContent = 'Lectura LibreNMS: ' + d.consultado_en.substring(11, 16);
                    }

                    renderCamaras(d.camaras);
                })
                .catch(function () {
                    var cont = document.getElementById('cctv-puestos-container');
                    if (cont) {
                        cont.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-circle mr-1"></i>'
                            + 'No se pudo consultar el estado CCTV</span>';
                    }
                });
        }

        verificar();
        setInterval(verificar, 60000);
    })();
</script>
@endpush
