@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Servidores</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between py-2"
                            style="background: linear-gradient(135deg,#1e1b4b,#312e81); color:#fff;">
                            <span><i class="fas fa-robot mr-2"></i><strong>Servicios IA y Geocodificación</strong></span>
                            <small id="ia-ultima-actualizacion" class="text-white-50"></small>
                        </div>
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap" id="ia-servicios-container" style="gap:1rem;">
                                <div class="d-flex align-items-center mr-4">
                                    <span id="ia-dot-ollama" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>Ollama</strong> <span id="ia-label-ollama"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>
                                <div class="d-flex align-items-center mr-4">
                                    <span id="ia-dot-rag" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>RAG (ChromaDB)</strong> <span id="ia-label-rag"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>
                                <div class="d-flex align-items-center mr-4">
                                    <span id="ia-dot-whisper" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>Whisper</strong> <span id="ia-label-whisper"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>
                                <div class="d-flex align-items-center mr-4">
                                    <span id="ia-dot-callanalysis" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>Análisis 911</strong> <span id="ia-label-callanalysis"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>
                                <div class="d-flex align-items-center mr-4">
                                    <span id="ia-dot-nominatim" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>Nominatim</strong> <span id="ia-label-nominatim"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    @include('infraestructura.partials.grid', [
                        'grupo' => 'servidores',
                        'titulo' => 'Servidores',
                        'icono' => 'fas fa-server',
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function iaServidoresMonitor() {
            var estadoUrl = '{{ route('rag.estado') }}';
            var callHealthUrl = '{{ route('callanalysis.health') }}';
            var nominatimUrl = '{{ route('api.infraestructura.estado-nominatim') }}';

            function actualizarServicio(nombre, activo) {
                var dot = document.getElementById('ia-dot-' + nombre);
                var label = document.getElementById('ia-label-' + nombre);
                if (!dot || !label) return;
                dot.style.background = activo ? '#22c55e' : '#ef4444';
                label.className = 'badge ' + (activo ? 'badge-success' : 'badge-danger');
                label.textContent = activo ? 'Activo' : 'Inactivo';
            }

            function verificar() {
                fetch(estadoUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        actualizarServicio('ollama', !!d.ollama);
                        actualizarServicio('rag', !!d.rag);
                        actualizarServicio('whisper', !!d.whisper);
                    })
                    .catch(function () {
                        ['ollama', 'rag', 'whisper'].forEach(function (n) { actualizarServicio(n, false); });
                    });

                fetch(callHealthUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { actualizarServicio('callanalysis', r.ok); })
                    .catch(function () { actualizarServicio('callanalysis', false); });

                fetch(nominatimUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { actualizarServicio('nominatim', !!d.online); })
                    .catch(function () { actualizarServicio('nominatim', false); });

                var el = document.getElementById('ia-ultima-actualizacion');
                if (el) el.textContent = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
            }

            verificar();
            setInterval(verificar, 60000);
        })();
    </script>
@endpush
