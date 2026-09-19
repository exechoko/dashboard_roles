@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Grabador TETRA (Modulaciones)</h3>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-headphones-alt"></i> Replay Server</h5></div>
                <div class="card-body">
                    <p>
                        Servicio de Windows <code>{{ $servicio }}</code> que sirve el audio de las modulaciones
                        (proxea al grabador TETRA). Corre centralizado en este servidor y atiende a todos los
                        operadores a la vez: si dos o más escuchan modulaciones al mismo tiempo puede quedar
                        colgado (acepta la conexión pero deja de responder) hasta que se reinicia.
                    </p>

                    <p class="mb-4">
                        Estado actual:
                        @if ($replayDisponible)
                            <span class="badge badge-success"><i class="fas fa-check"></i> Disponible</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times"></i> No disponible / colgado</span>
                        @endif
                    </p>

                    @can('reiniciar-infraestructura-grabador')
                        <form action="{{ route('infraestructura.grabador.replay.reiniciar') }}" method="POST"
                            onsubmit="return confirm('Esto corta por unos segundos el audio de modulaciones para todos los operadores que lo estén usando. ¿Reiniciar de todas formas?');">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sync"></i> Reiniciar Replay Server
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </section>
@endsection
