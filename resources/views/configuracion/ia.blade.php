@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema — IA y API Keys</h3>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-light border alert-dismissible fade show">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revisá los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <div class="small">{{ $error }}</div>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Servidores, modelos y API keys de cada servicio de IA usado por el sistema
                (resumen de eventos CECOCO, transcripción, base de conocimiento y chatbot).
                Los campos de API key se muestran en blanco: dejarlos así conserva el valor actual.
            </div>

            @php
                $servicios = [
                    'ollama'    => ['label' => 'Ollama (resumen CECOCO)', 'claves' => ['IA_URL', 'IA_MODEL', 'IA_TIMEOUT', 'IA_KEEP_ALIVE', 'IA_NUM_THREAD', 'IA_THINK', 'IA_ENABLED']],
                    'ollama2'   => ['label' => 'Ollama (chat / otros)', 'claves' => ['OLLAMA_URL', 'OLLAMA_MODEL']],
                    'whisper'   => ['label' => 'Whisper (transcripción)', 'claves' => ['WHISPER_URL', 'TRANSCRIPTION_API_URL']],
                    'rag'       => ['label' => 'RAG (Base de Conocimiento)', 'claves' => ['RAG_URL']],
                    'opencode'  => ['label' => 'OpenCode (Chatbot)', 'claves' => ['OPENCODE_ENABLED', 'OPENCODE_URL', 'OPENCODE_USERNAME', 'OPENCODE_PASSWORD', 'OPENCODE_AGENT', 'OPENCODE_MODEL', 'OPENCODE_FALLBACK_MODEL', 'OPENCODE_CONNECT_TIMEOUT', 'OPENCODE_RESPONSE_TIMEOUT']],
                    'otros'     => ['label' => 'Otras API Keys', 'claves' => ['OPENAI_API_KEY', 'ELEVENS_API_KEY', 'TINY_API_KEY']],
                ];
                $probables = ['ollama' => 'ollama', 'ollama2' => 'ollama', 'whisper' => 'whisper', 'rag' => 'rag', 'opencode' => 'opencode'];
            @endphp

            <form action="{{ route('configuracion.ia.update') }}" method="POST">
                @csrf
                @method('PUT')

                @foreach ($servicios as $servicioKey => $servicio)
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ $servicio['label'] }}</h5>
                            @isset($probables[$servicioKey])
                                <button type="button" class="btn btn-sm btn-outline-primary js-probar-conexion"
                                    data-servicio="{{ $probables[$servicioKey] }}">
                                    <i class="fas fa-plug"></i> Probar conexión
                                </button>
                            @endisset
                        </div>
                        <div class="card-body">
                            @foreach ($servicio['claves'] as $clave)
                                @php $campoMeta = \App\Support\ConfiguracionCatalogo::todasLasClaves()[$clave] ?? ['tipo' => 'text', 'label' => $clave]; @endphp
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label" for="i_{{ $clave }}">
                                        {{ $campoMeta['label'] }}
                                        <br><small class="text-muted">{{ $clave }}</small>
                                    </label>
                                    <div class="col-sm-8">
                                        @include('configuracion._campo', ['clave' => $clave, 'meta' => $campoMeta, 'valor' => $valores[$clave] ?? '', 'idPrefijo' => 'i_', 'deshabilitado' => false])
                                        @isset($campoMeta['ayuda'])
                                            <small class="form-text text-muted">{{ $campoMeta['ayuda'] }}</small>
                                        @endisset
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            var boton = e.target.closest('.js-probar-conexion');
            if (!boton) { return; }

            var servicio = boton.getAttribute('data-servicio');
            var original = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';

            fetch('{{ url('configuracion/ia/probar') }}/' + servicio, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    boton.innerHTML = data.ok
                        ? '<i class="fas fa-check-circle text-success"></i> Responde'
                        : '<i class="fas fa-times-circle text-danger"></i> Sin respuesta';
                })
                .catch(function () {
                    boton.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Error';
                })
                .finally(function () {
                    setTimeout(function () {
                        boton.disabled = false;
                        boton.innerHTML = original;
                    }, 4000);
                });
        });
    </script>
@endpush
