@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Buzones de Correo</h3>
            <div>
                <a href="{{ route('herramientas.mails.buzones.detectar-oficinas') }}" class="btn btn-info">
                    <i class="fas fa-search"></i> Detectar Oficinas
                </a>
                <a href="{{ route('herramientas.mails.buzones.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Buzón
                </a>
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Carpeta</th>
                                    <th>Rol asociado</th>
                                    <th class="text-right">Mensajes indexados</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($buzones as $buzon)
                                    <tr>
                                        <td><strong>{{ $buzon->nombre }}</strong></td>
                                        <td><code>{{ $buzon->carpeta }}</code></td>
                                        <td>
                                            @if ($buzon->role)
                                                <span class="badge badge-primary">{{ $buzon->role->name }}</span>
                                            @else
                                                <span class="badge badge-warning">Sin rol asignado</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($buzon->mensajes_count) }}</td>
                                        <td>
                                            @if (!$buzon->activo)
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @elseif ($buzon->archivos->isEmpty())
                                                <span class="badge badge-light">Sin archivos</span>
                                            @else
                                                @php($ultimo = $buzon->archivos->first())
                                                <span class="badge badge-{{ ['pendiente' => 'secondary', 'indexando' => 'info', 'indexado' => 'success', 'error' => 'danger'][$ultimo->estado] }}">
                                                    {{ ucfirst($ultimo->estado) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('herramientas.mails.buzones.archivos', $buzon) }}" class="btn btn-sm btn-outline-primary" title="Archivos">
                                                <i class="fas fa-file-archive"></i>
                                            </a>
                                            <a href="{{ route('herramientas.mails.buzones.edit', $buzon) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('herramientas.mails.buzones.destroy', $buzon) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar el buzón {{ $buzon->nombre }}? Se borra el índice, no el .mbox del disco.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No hay buzones creados todavía. Usá "Detectar Oficinas" para darlos de alta.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
