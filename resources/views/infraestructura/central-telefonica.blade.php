@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Central Telefónica</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between py-2"
                            style="background: linear-gradient(135deg,#1e3a8a,#1d4ed8); color:#fff;">
                            <span><i class="fas fa-phone-volume mr-2"></i><strong>Troncales SIP Central Telefónica</strong></span>
                            <small id="troncales-ultima-actualizacion" class="text-white-50"></small>
                        </div>
                        <div class="card-body py-3">
                            <small class="text-muted d-block mb-2" id="troncales-subtitulo">Estado
                                de los troncales SIP del softswitch (911)</small>
                            <div id="troncales-container" class="d-flex flex-wrap" style="gap:1.25rem;">
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
    (function troncalesCentralTelefonicaMonitor() {
        var url = '{{ route("api.infraestructura.estado-troncales-central-telefonica") }}';

        function escapar(texto) {
            var div = document.createElement('div');
            div.textContent = texto;
            return div.innerHTML;
        }

        function verificar() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var cont = document.getElementById('troncales-container');
                    if (!cont) return;

                    if (!d.disponible) {
                        cont.innerHTML = '<span class="text-muted"><i class="fas fa-exclamation-circle mr-1"></i>'
                            + 'Sin lectura reciente de la central telefónica (el monitor corre cada 5 minutos)</span>';
                        return;
                    }

                    cont.innerHTML = d.troncales.map(function (t) {
                        var online = t.estado === 'online';
                        var color = online ? '#22c55e' : '#ef4444';
                        var icono = online ? 'fa-check-circle' : 'fa-times-circle';
                        return '<div style="min-width:220px;flex:0 0 auto;border-left:3px solid ' + color + ';padding-left:8px;">'
                            + '<div><i class="fas ' + icono + ' mr-1" style="color:' + color + ';"></i>'
                            + '<strong>' + escapar(t.nombre) + '</strong></div>'
                            + '<small class="text-muted">' + escapar(t.host) + (t.puerto ? ':' + escapar(t.puerto) : '')
                            + ' — <span style="color:' + color + ';font-weight:bold;">' + (online ? 'online' : 'OFFLINE') + '</span>'
                            + (online && t.latencia_ms !== null ? ' (' + t.latencia_ms + 'ms)' : '') + '</small>'
                            + '</div>';
                    }).join('');

                    var sub = document.getElementById('troncales-subtitulo');
                    if (sub) {
                        sub.textContent = d.caidos > 0
                            ? d.caidos + ' de ' + d.troncales.length + ' troncal(es) SIP caído(s)'
                            : 'Los ' + d.troncales.length + ' troncales SIP del softswitch (911) online';
                    }

                    var el = document.getElementById('troncales-ultima-actualizacion');
                    if (el && d.consultado_en) {
                        el.textContent = 'Lectura central: ' + d.consultado_en.substring(11, 16);
                    }
                })
                .catch(function () {
                    var cont = document.getElementById('troncales-container');
                    if (cont) {
                        cont.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-circle mr-1"></i>'
                            + 'No se pudo consultar el estado de los troncales SIP</span>';
                    }
                });
        }

        verificar();
        setInterval(verificar, 60000);
    })();
</script>
@endpush
