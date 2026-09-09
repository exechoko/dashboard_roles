@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h3 class="page__heading"><i class="fas fa-link mr-2"></i>Links Públicos</h3>
        <div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalCrearLink">
                <i class="fas fa-plus"></i> Generar link
            </button>
            <a href="{{ route('descargas.admin.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Los links públicos permiten compartir archivos con personas externas al sistema. Cada link tiene un <strong>límite de usos configurable</strong> (1 por defecto) y expira automáticamente.
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Links generados</h5>
            </div>
            <div class="card-body p-0">
                @if($links->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Archivo</th>
                                    <th>Link</th>
                                    <th>Usos</th>
                                    <th>Expira</th>
                                    <th>Creado por</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($links as $link)
                                    <tr>
                                        <td>
                                            @if($link->archivo)
                                                {{ Str::limit($link->archivo->nombre_original, 30) }}
                                            @else
                                                <span class="text-muted">Eliminado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="small">{{ route('descargas.link.publico', $link->token) }}</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 btn-copy" data-link="{{ route('descargas.link.publico', $link->token) }}" title="Copiar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $link->usos_count }} / {{ $link->max_usos }}</span>
                                        </td>
                                        <td>
                                            {{ $link->expira_at->format('d/m/Y H:i') }}
                                            <br><small class="text-muted">({{ $link->expira_at->diffForHumans() }})</small>
                                        </td>
                                        <td>{{ $link->user->name ?? 'Sistema' }}</td>
                                        <td>
                                            @if($link->activo && $link->es_utilizable)
                                                <span class="badge badge-success">Activo</span>
                                            @elseif($link->esta_expirado)
                                                <span class="badge badge-warning">Expirado</span>
                                            @elseif($link->usos_count >= $link->max_usos)
                                                <span class="badge badge-secondary">Usado</span>
                                            @else
                                                <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                            @if($link->requierePassword())
                                                <i class="fas fa-lock text-muted ml-1" title="Protegido con contraseña"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($link->activo)
                                                <form action="{{ route('descargas.admin.links.destroy', $link) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este link?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar">
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
                        {{ $links->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-link fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay links públicos generados.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Modal Crear Link --}}
<div class="modal fade" id="modalCrearLink" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('descargas.admin.links.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generar link público</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Archivo *</label>
                        <select name="archivo_id" class="form-control" required>
                            <option value="">Seleccionar archivo...</option>
                            @foreach(\App\Models\DescargaArchivo::activos()->orderBy('nombre_original')->get() as $archivo)
                                <option value="{{ $archivo->id }}">
                                    {{ $archivo->nombre_original }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expiración (horas)</label>
                        <input type="number" name="expira_horas" class="form-control" value="24" min="1">
                        <small class="form-text text-muted">El link dejará de funcionar después de este tiempo</small>
                    </div>
                    <div class="form-group">
                        <label>Usos permitidos</label>
                        <input type="number" name="max_usos" class="form-control" value="1" min="1" max="1000">
                        <small class="form-text text-muted">Cantidad de veces que se puede usar el link antes de dejar de funcionar</small>
                    </div>
                    <div class="form-group">
                        <label>Contraseña (opcional)</label>
                        <input type="text" name="password" class="form-control" placeholder="Dejar vacío para sin contraseña">
                        <small class="form-text text-muted">Si se establece, se requerirá esta contraseña para descargar</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Generar link</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-copy').forEach(btn => {
    btn.addEventListener('click', function() {
        const link = this.dataset.link;
        navigator.clipboard.writeText(link).then(() => {
            const icon = this.querySelector('i');
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check', 'text-success');
            setTimeout(() => {
                icon.classList.remove('fa-check', 'text-success');
                icon.classList.add('fa-copy');
            }, 2000);
        });
    });
});
</script>
@endpush
