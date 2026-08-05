@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="page__heading"><i class="fas fa-folder-open"></i> Carpetas de red por tótem</h3>
            <a href="{{ route('activaciones-totem.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Nombre exacto de la subcarpeta dentro de <code>\\193.169.1.247\totems\</code> para cada tótem.
                Se usa al subir un video para guardarlo en el lugar correcto. Si un tótem no tiene carpeta
                configurada, no se va a poder subir video para sus activaciones (sí se puede seguir registrando
                la descarga de forma manual).
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Tótem</th>
                                    <th>Carpeta de red</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($totems as $totem)
                                    <tr>
                                        <td>{{ $totem->nombre }}</td>
                                        <td>
                                            <form action="{{ route('activaciones-totem.totems.update', $totem) }}" method="POST" class="form-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="carpeta_red" class="form-control mr-2" style="min-width: 320px;"
                                                       value="{{ $totem->carpeta_red }}" placeholder="Sin configurar">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-save"></i> Guardar
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-right">
                                            @if ($totem->carpeta_red)
                                                <span class="badge badge-success">Configurada</span>
                                            @else
                                                <span class="badge badge-warning">Sin configurar</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No hay tótems cargados.</td>
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
