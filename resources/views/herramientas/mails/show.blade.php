@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading text-truncate">{{ $mensaje->asunto ?: '(sin asunto)' }}</h3>
            <div class="text-nowrap">
                <a href="{{ route('herramientas.mails.index', ['buzon_id' => $mensaje->buzon_id]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="{{ route('herramientas.mails.eml', $mensaje) }}" class="btn btn-outline-primary">
                    <i class="fas fa-download"></i> Descargar .eml
                </a>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-3">
                        <tr>
                            <th style="width:120px">De</th>
                            <td>{{ $mensaje->de_nombre }} @if($mensaje->de_email) &lt;{{ $mensaje->de_email }}&gt; @endif</td>
                        </tr>
                        <tr>
                            <th>Para</th>
                            <td>{{ $mensaje->para ?: '-' }}</td>
                        </tr>
                        @if ($mensaje->cc)
                            <tr>
                                <th>CC</th>
                                <td>{{ $mensaje->cc }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>Fecha</th>
                            <td>{{ $mensaje->fecha?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Carpeta</th>
                            <td><span class="badge badge-secondary">{{ \App\Models\MailMensaje::CARPETAS[$mensaje->carpeta] ?? $mensaje->carpeta }}</span></td>
                        </tr>
                        @if ($mensaje->cuerpo_truncado)
                            <tr>
                                <td colspan="2">
                                    <div class="alert alert-warning py-1 px-2 mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        El contenido de este mensaje es muy grande y se muestra recortado. Descargá el .eml para verlo completo.
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>

                    @if (!empty($adjuntos))
                        <div class="mb-3">
                            <strong>Adjuntos ({{ count($adjuntos) }}):</strong>
                            <div class="mt-1">
                                @foreach ($adjuntos as $adjunto)
                                    <a href="{{ route('herramientas.mails.adjunto', [$mensaje, $adjunto['parte']]) }}"
                                       class="btn btn-outline-secondary btn-sm mr-1 mb-1">
                                        <i class="fas fa-paperclip"></i>
                                        {{ $adjunto['nombre'] ?? 'adjunto' }}
                                        @if ($adjunto['tamano'])
                                            <span class="text-muted">({{ number_format($adjunto['tamano'] / 1024, 1) }} KB)</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sandbox sin allow-same-origin ni allow-scripts a propósito: aunque
                         sanitizarHtml() ya quita <script> y handlers on*, el iframe queda en
                         un origen opaco que no puede tocar las cookies ni el DOM del sistema. --}}
                    <iframe src="{{ route('herramientas.mails.cuerpo', $mensaje) }}"
                            sandbox="allow-popups allow-popups-to-escape-sandbox"
                            style="width:100%; height:70vh; border:1px solid #e0e0e0; border-radius:4px; background:#fff;">
                    </iframe>
                </div>
            </div>
        </div>
    </section>
@endsection
