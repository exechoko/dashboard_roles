@extends('layouts.movil')

@section('title', 'Personal')

@section('content')
    <div class="m-stats">
        <div class="m-stat m-stat--success">
            <i class="fas fa-user-check"></i>
            <div class="m-stat__body">
                <div class="m-stat__value">{{ $totalActivos }}</div>
                <div class="m-stat__label">Activos</div>
            </div>
        </div>
        <div class="m-stat m-stat--warning">
            <i class="fas fa-calendar-times"></i>
            <div class="m-stat__body">
                <div class="m-stat__value">{{ $totalDeLicencia }}</div>
                <div class="m-stat__label">De licencia</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('movil.personal.index') }}" class="m-search">
        <input type="text" name="texto" value="{{ $texto }}" placeholder="Apellido, nombre, LP o DNI…">
        <button type="submit" class="m-btn"><i class="fas fa-search"></i></button>
    </form>

    @if ($personales->isEmpty())
        <div class="m-empty">
            <i class="fas fa-users" style="font-size:1.6rem;"></i>
            <p>No se encontraron funcionarios.</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($personales as $personal)
                @php($deLicencia = $personal->resumen_licencia_actual)
                <a href="{{ route('movil.personal.show', $personal) }}" class="m-card">
                    <div class="m-card__title">{{ $personal->apellido }}, {{ $personal->nombre }}</div>
                    <div class="m-card__subtitle"><i class="fas fa-id-badge"></i> LP {{ $personal->lp }} · {{ $personal->jerarquia }}</div>
                    <div class="m-card__meta">
                        @if ($personal->situacion_personal911)
                            <span class="m-chip">{{ $personal->situacion_personal911 }}</span>
                        @endif
                        @if ($deLicencia)
                            <span class="m-chip" style="background-color: var(--m-warning); color:#fff;">De licencia</span>
                        @endif
                        @if ($personal->numeracion_arma)
                            <span class="m-chip"><i class="fas fa-crosshairs"></i> {{ $personal->numeracion_arma }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $personales->links() }}
        </div>
    @endif
@endsection
