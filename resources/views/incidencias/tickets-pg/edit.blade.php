@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-edit"></i> Editar {{ $ticket->codigo_interno }}</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Borrador</h4>
                <a href="{{ route('incidencias.tickets-pg.show', $ticket) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <form method="POST" action="{{ route('incidencias.tickets-pg.update', $ticket) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @include('incidencias.tickets-pg._form', ['ticket' => $ticket])
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
