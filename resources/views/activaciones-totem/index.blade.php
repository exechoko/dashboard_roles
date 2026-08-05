@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading"><i class="fas fa-broadcast-tower"></i> Activaciones Tótem</h3>
            @can('editar-activacion-totem')
                <div>
                    <a href="{{ route('activaciones-totem.totems') }}" class="btn btn-secondary">
                        <i class="fas fa-folder-open"></i> Configurar carpetas
                    </a>
                    <form action="{{ route('activaciones-totem.escanear') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-sync"></i> Escanear ahora
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('activaciones-totem.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estados as $key => $label)
                                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Fecha del evento (desde)</label>
                                <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1">Fecha del evento (hasta)</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="vencidas" name="vencidas" value="1" {{ request()->boolean('vencidas') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="vencidas">
                                        <span class="text-danger">Vencidas</span> (+6 meses)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('activaciones-totem.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="min-width: 110px;">Fecha evento</th>
                                    <th style="min-width: 100px;">N° Expediente</th>
                                    <th>Descripción</th>
                                    <th style="min-width: 140px;">Estado</th>
                                    <th style="min-width: 140px;">Tótem</th>
                                    <th style="min-width: 160px;">Descarga / Eliminación</th>
                                    <th class="text-right" style="min-width: 170px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activaciones as $activacion)
                                    @php
                                        $vencida = $activacion->esVencida();
                                        $subidaEnCurso = in_array($activacion->subida_estado, [
                                            \App\Models\ActivacionTotem::SUBIDA_PENDIENTE,
                                            \App\Models\ActivacionTotem::SUBIDA_PROCESANDO,
                                        ], true);
                                        $puedeRegistrar = in_array($activacion->estado, [
                                            \App\Models\ActivacionTotem::ESTADO_PENDIENTE,
                                            \App\Models\ActivacionTotem::ESTADO_ELIMINADO,
                                        ], true) && !$subidaEnCurso;
                                    @endphp
                                    <tr class="{{ $vencida ? 'table-danger' : '' }}">
                                        <td>{{ $activacion->fecha_evento->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('cecoco.expediente', $activacion->evento_cecoco_id) }}" target="_blank">
                                                {{ $activacion->nro_expediente }}
                                            </a>
                                        </td>
                                        <td style="min-width: 320px; white-space: normal;">{{ $activacion->evento->descripcion }}</td>
                                        <td>
                                            @php
                                                $badge = match ($activacion->estado) {
                                                    \App\Models\ActivacionTotem::ESTADO_DESCARGADO => 'success',
                                                    \App\Models\ActivacionTotem::ESTADO_DESCARTADO => 'secondary',
                                                    \App\Models\ActivacionTotem::ESTADO_ELIMINADO => 'dark',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badge }}">{{ $estados[$activacion->estado] ?? $activacion->estado }}</span>
                                            @if ($vencida)
                                                <span class="badge badge-danger" title="Superó el plazo legal de retención de {{ \App\Models\ActivacionTotem::MESES_RETENCION_LEGAL }} meses">
                                                    <i class="fas fa-exclamation-triangle"></i> Vencido
                                                </span>
                                            @endif
                                            @if ($subidaEnCurso)
                                                <span class="badge badge-info" title="El video se está hasheando y copiando a la carpeta de red">
                                                    <i class="fas fa-spinner fa-spin"></i> Procesando video
                                                </span>
                                            @elseif ($activacion->subida_estado === \App\Models\ActivacionTotem::SUBIDA_ERROR)
                                                <span class="badge badge-danger" title="{{ $activacion->subida_error }}">
                                                    <i class="fas fa-exclamation-circle"></i> Error al subir
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $activacion->camara->nombre ?? '-' }}</td>
                                        <td>
                                            @if ($activacion->descargadoPor)
                                                <div>{{ $activacion->descargadoPor->name }}</div>
                                                <small class="text-muted d-block">Descargado: {{ $activacion->fecha_descarga->format('d/m/Y H:i') }}</small>
                                            @endif
                                            @if ($activacion->hash_sha256)
                                                <small class="text-muted d-block" title="{{ $activacion->hash_sha256 }}">
                                                    <i class="fas fa-fingerprint"></i> SHA-256: {{ Str::limit($activacion->hash_sha256, 16, '…') }}
                                                </small>
                                                <div class="mt-1">
                                                    <a href="{{ route('activaciones-totem.descargar-video', $activacion) }}" class="btn btn-xs btn-outline-primary" title="Descargar video">
                                                        <i class="fas fa-video"></i> Video
                                                    </a>
                                                    <a href="{{ route('activaciones-totem.descargar-certificado', $activacion) }}" class="btn btn-xs btn-outline-secondary" title="Descargar certificado de integridad (hash)">
                                                        <i class="fas fa-certificate"></i> Certificado
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($activacion->eliminadoPor)
                                                <div class="text-danger">{{ $activacion->eliminadoPor->name }}</div>
                                                <small class="text-muted d-block">Eliminado: {{ $activacion->fecha_eliminado->format('d/m/Y H:i') }}</small>
                                            @endif
                                            @if (!$activacion->descargadoPor && !$activacion->eliminadoPor)
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @can('editar-activacion-totem')
                                                <div class="totem-acciones">
                                                    @if ($puedeRegistrar)
                                                        <button type="button" class="btn btn-sm btn-primary btn-block" title="Subir video al sistema"
                                                                data-toggle="modal" data-target="#subirVideoModal{{ $activacion->id }}">
                                                            <i class="fas fa-upload"></i> Subir video
                                                        </button>
                                                    @endif
                                                    <div class="totem-acciones-secundarias">
                                                        @if ($puedeRegistrar)
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Registrar descarga manual (sin subir archivo)"
                                                                    data-toggle="modal" data-target="#descargarModal{{ $activacion->id }}">
                                                                <i class="fas fa-pen"></i>
                                                            </button>
                                                        @endif
                                                        @if ($activacion->estado === \App\Models\ActivacionTotem::ESTADO_PENDIENTE)
                                                            <button type="button" class="btn btn-sm btn-secondary" title="Descartar"
                                                                    onclick="if(confirm('¿Descartar esta activación? Se usa cuando no corresponde a un tótem real.')) document.getElementById('descartar-{{ $activacion->id }}').submit();">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                            <form id="descartar-{{ $activacion->id }}" action="{{ route('activaciones-totem.descartar', $activacion) }}" method="POST" class="d-none">
                                                                @csrf
                                                            </form>
                                                        @endif
                                                        @if ($activacion->estado === \App\Models\ActivacionTotem::ESTADO_DESCARGADO && $vencida)
                                                            <button type="button" class="btn btn-sm btn-danger" title="Marcar como eliminado (ya se borró el video)"
                                                                    onclick="if(confirm('¿Confirmás que ya borraste el video descargado? Esta acción queda registrada.')) document.getElementById('eliminar-{{ $activacion->id }}').submit();">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <form id="eliminar-{{ $activacion->id }}" action="{{ route('activaciones-totem.eliminar', $activacion) }}" method="POST" class="d-none">
                                                                @csrf
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay activaciones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $activaciones->links() }}
                </div>
            </div>
        </div>
    </section>

    @can('editar-activacion-totem')
        @foreach ($activaciones as $activacion)
            @php
                $puedeRegistrar = in_array($activacion->estado, [
                    \App\Models\ActivacionTotem::ESTADO_PENDIENTE,
                    \App\Models\ActivacionTotem::ESTADO_ELIMINADO,
                ], true) && !in_array($activacion->subida_estado, [
                    \App\Models\ActivacionTotem::SUBIDA_PENDIENTE,
                    \App\Models\ActivacionTotem::SUBIDA_PROCESANDO,
                ], true);
            @endphp
            @if ($puedeRegistrar)
                <div class="modal fade" id="subirVideoModal{{ $activacion->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-upload"></i> Subir video — Exp. {{ $activacion->nro_expediente }}</h5>
                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <form action="{{ route('activaciones-totem.subir-video', $activacion) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="camara_id_subir-{{ $activacion->id }}">Tótem involucrado <span class="text-danger">*</span></label>
                                        <select name="camara_id" id="camara_id_subir-{{ $activacion->id }}" class="form-control" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach ($totems as $totem)
                                                <option value="{{ $totem->id }}" {{ !$totem->carpeta_red ? 'disabled' : '' }}>
                                                    {{ $totem->nombre }} {{ !$totem->carpeta_red ? '(sin carpeta configurada)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Archivo de video <span class="text-danger">*</span></label>
                                        <div class="totem-dropzone" id="dropzone-{{ $activacion->id }}">
                                            <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                            <p class="mb-1">Arrastrá el video acá, o hacé clic para elegirlo</p>
                                            <small class="text-muted totem-dropzone-filename">Ningún archivo seleccionado</small>
                                            <input type="file" name="video" id="video-{{ $activacion->id }}" class="d-none" accept="video/*" required>
                                        </div>
                                        <small class="form-text text-muted">Máximo 180 MB. Formatos: MP4, AVI, MOV, MKV, WMV, ASF, MPEG.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="observaciones_subir-{{ $activacion->id }}">Observaciones</label>
                                        <textarea name="observaciones" id="observaciones_subir-{{ $activacion->id }}" class="form-control" rows="3" maxlength="1000"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Subir video</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="descargarModal{{ $activacion->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-download"></i> Registrar descarga — Exp. {{ $activacion->nro_expediente }}</h5>
                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <form action="{{ route('activaciones-totem.update', $activacion) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="camara_id-{{ $activacion->id }}">Tótem involucrado</label>
                                        <select name="camara_id" id="camara_id-{{ $activacion->id }}" class="form-control">
                                            <option value="">Sin especificar</option>
                                            @foreach ($totems as $totem)
                                                <option value="{{ $totem->id }}">{{ $totem->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="observaciones-{{ $activacion->id }}">Observaciones</label>
                                        <textarea name="observaciones" id="observaciones-{{ $activacion->id }}" class="form-control" rows="3" maxlength="1000"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-download"></i> Registrar descarga</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endcan
@endsection

@push('styles')
    <style>
        .totem-acciones {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
            min-width: 150px;
        }
        .totem-acciones-secundarias {
            display: flex;
            justify-content: flex-end;
            gap: 4px;
        }
        .totem-dropzone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 25px 15px;
            text-align: center;
            cursor: pointer;
            transition: all .15s ease-in-out;
            background: #fafafa;
        }
        .totem-dropzone:hover {
            border-color: #6c757d;
            background: #f0f0f0;
        }
        .totem-dropzone--dragover {
            border-color: #007bff;
            background: #eaf4ff;
        }
        .totem-dropzone--filled {
            border-color: #28a745;
            background: #f0fff4;
        }
        .totem-dropzone-filename {
            display: block;
            word-break: break-all;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            $('.totem-dropzone').each(function () {
                var $zone = $(this);
                var $input = $zone.find('input[type="file"]');
                var $filename = $zone.find('.totem-dropzone-filename');

                function mostrarArchivo(archivo) {
                    if (archivo) {
                        $filename.text(archivo.name);
                        $zone.addClass('totem-dropzone--filled');
                    }
                }

                $zone.on('click', function () {
                    $input.trigger('click');
                });

                $input.on('change', function () {
                    mostrarArchivo(this.files[0]);
                });

                $zone.on('dragenter dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $zone.addClass('totem-dropzone--dragover');
                });

                $zone.on('dragleave drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $zone.removeClass('totem-dropzone--dragover');
                });

                $zone.on('drop', function (e) {
                    var archivos = e.originalEvent.dataTransfer.files;
                    if (archivos.length > 0) {
                        $input[0].files = archivos;
                        mostrarArchivo(archivos[0]);
                    }
                });
            });
        });
    </script>
@endpush
