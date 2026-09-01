@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-qrcode mr-2"></i>Códigos QR Generados</h3>
        <a href="{{ route('descargas.admin.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('descargas.admin.qrs') }}" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="activos" {{ request('estado') == 'activos' ? 'selected' : '' }}>Activos</option>
                            <option value="expirados" {{ request('estado') == 'expirados' ? 'selected' : '' }}>Expirados</option>
                            <option value="usados" {{ request('estado') == 'usados' ? 'selected' : '' }}>Usados</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="{{ route('descargas.admin.qrs') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Lista de QRs --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list mr-2"></i>
                    Códigos QR
                    <span class="badge badge-secondary ml-2">{{ $qrs->total() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($qrs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 80px;">QR</th>
                                    <th>Archivo</th>
                                    <th>Generado por</th>
                                    <th>Usos</th>
                                    <th>Expira</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($qrs as $qr)
                                    <tr>
                                        <td class="text-center">
                                            <img src="{{ route('descargas.admin.qr.descargar-imagen', $qr) }}" 
                                                 alt="QR" style="width: 50px; height: 50px;">
                                        </td>
                                        <td>
                                            <a href="{{ route('descargas.show', $qr->archivo) }}" class="font-weight-bold">
                                                {{ Str::limit($qr->archivo->nombre_original, 40) }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $qr->archivo->categoria->nombre }}</small>
                                        </td>
                                        <td>
                                            {{ $qr->generadoPorUser->name ?? 'Sistema' }}
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $qr->usos_count }} / {{ $qr->max_usos }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $qr->expira_at->format('d/m/Y H:i') }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $qr->expira_at->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td>
                                            @if(!$qr->activo)
                                                <span class="badge badge-secondary">Desactivado</span>
                                            @elseif($qr->expira_at->isPast())
                                                <span class="badge badge-danger">Expirado</span>
                                            @elseif($qr->usos_count >= $qr->max_usos)
                                                <span class="badge badge-warning">Usado</span>
                                            @else
                                                <span class="badge badge-success">Activo</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $qr->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('descargas.admin.qr.descargar-imagen', $qr) }}" 
                                               class="btn btn-sm btn-primary" title="Descargar imagen QR">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($qr->activo && !$qr->expira_at->isPast() && $qr->usos_count < $qr->max_usos)
                                                <button type="button" class="btn btn-sm btn-info btn-ver-qr" 
                                                        data-qr-url="{{ route('descargas.qr.descargar', $qr->token) }}"
                                                        title="Ver código QR">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                                <form action="{{ route('descargas.admin.qr.desactivar', $qr) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('¿Desactivar este código QR?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Desactivar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $qrs->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No se encontraron códigos QR.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Modal para ver QR --}}
<div class="modal fade" id="modalVerQr" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>Código QR</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="qrImage" src="" alt="Código QR" class="img-fluid mb-3" style="max-width: 300px;">
                <p class="text-muted small">
                    Escanea este código con tu dispositivo móvil para descargar el archivo
                </p>
                <div class="input-group mb-3">
                    <input type="text" id="qrUrl" class="form-control" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary btn-copiar" type="button">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.btn-ver-qr').click(function() {
        const qrUrl = $(this).data('qr-url');
        $('#qrUrl').val(qrUrl);
        $('#qrImage').attr('src', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(qrUrl));
        $('#modalVerQr').modal('show');
    });

    $('.btn-copiar').click(function() {
        const input = $('#qrUrl')[0];
        input.select();
        document.execCommand('copy');
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-check"></i> Copiado');
        setTimeout(function() {
            btn.html(originalHtml);
        }, 2000);
    });
});
</script>
@endpush
@endsection
