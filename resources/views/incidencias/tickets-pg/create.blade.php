@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-ticket-alt"></i> Nuevo Ticket PG</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Redacción rápida</h4>
                <a href="{{ route('incidencias.tickets-pg.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <form method="POST" action="{{ route('incidencias.tickets-pg.store') }}">
                @csrf
                <div class="card-body">
                    @include('incidencias.tickets-pg._form')
                </div>
                <div class="card-footer text-right">
                    <button type="submit" name="accion" value="guardar" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar borrador
                    </button>
                    @can('enviar-ticket-pg')
                        <button type="submit" name="accion" value="enviar" class="btn btn-success" onclick="return confirm('¿Enviar este ticket a la ticketera?');">
                            <i class="fas fa-paper-plane"></i> Guardar y enviar
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
