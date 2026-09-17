{{-- Sección plegable: qué recursos de este tipo aparecen en el parte. Espera $tipo y $configurables. --}}
@can('configurar-parte-diario')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header-modern" style="cursor:pointer" data-toggle="collapse" data-target="#configRecursosParte{{ $tipo }}">
        <div class="card-header-left">
            <div class="header-icon"><i class="fas fa-sliders-h"></i></div>
            <h5 class="header-title">Configurar recursos de este parte</h5>
        </div>
        <i class="fas fa-chevron-down"></i>
    </div>
    <div class="collapse" id="configRecursosParte{{ $tipo }}">
        <div class="card-body">
            <p class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                Destildá un recurso para que deje de aparecer en el listado de este parte. No lo da de baja ni lo saca
                de ningún otro lugar del sistema — sólo lo salta acá. Se puede reactivar en cualquier momento.
            </p>
            <form action="{{ route('flota-911.informes.parte-diario.configuracion.guardar') }}" method="POST">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                @include('flota-911.informes._parte-config-recurso-lista', ['recursos' => $configurables])
                <button type="submit" class="btn btn-primary btn-sm mt-3">
                    <i class="fas fa-save mr-1"></i> Guardar configuración
                </button>
            </form>
        </div>
    </div>
</div>
@endcan
