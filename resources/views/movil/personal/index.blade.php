@extends('layouts.movil')

@section('title', 'Personal')

@section('content')
    <div class="m-stats">
        <a href="{{ route('movil.personal.index', array_filter(['texto' => $texto])) }}"
            class="m-stat m-stat--success {{ !$soloLicencia ? 'm-stat--active' : '' }}">
            <i class="fas fa-user-check"></i>
            <div class="m-stat__body">
                <div class="m-stat__value">{{ $totalActivos }}</div>
                <div class="m-stat__label">Todos</div>
            </div>
        </a>
        <a href="{{ route('movil.personal.index', array_filter(['texto' => $texto, 'licencia' => 1])) }}"
            class="m-stat m-stat--warning {{ $soloLicencia ? 'm-stat--active' : '' }}">
            <i class="fas fa-calendar-times"></i>
            <div class="m-stat__body">
                <div class="m-stat__value">{{ $totalDeLicencia }}</div>
                <div class="m-stat__label">De licencia</div>
            </div>
        </a>
    </div>

    <form method="GET" action="{{ route('movil.personal.index') }}" class="m-search">
        @if ($soloLicencia)
            <input type="hidden" name="licencia" value="1">
        @endif
        <input type="text" name="texto" value="{{ $texto }}" placeholder="Apellido, nombre, LP o DNI…">
        <button type="submit" class="m-btn"><i class="fas fa-search"></i></button>
    </form>

    @if ($personales->isEmpty())
        <div class="m-empty">
            <i class="fas fa-users" style="font-size:1.6rem;"></i>
            <p>{{ $soloLicencia ? 'No hay nadie de licencia.' : 'No se encontraron funcionarios.' }}</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($personales as $personal)
                @php($deLicencia = $personal->resumen_licencia_actual)
                <div class="m-card m-personal-card">
                    @if ($personal->tieneFotoPersonal911())
                        <button type="button" class="m-personal-thumb"
                            data-modal-tpl="personal-foto-{{ $personal->id }}"
                            data-modal-title="{{ $personal->jerarquia }} {{ $personal->apellido }}, {{ $personal->nombre }}"
                            aria-label="Ver foto de {{ $personal->apellido }}, {{ $personal->nombre }}">
                            <img src="{{ route('personal.foto', $personal) }}" alt="" loading="lazy">
                        </button>
                        <template id="personal-foto-{{ $personal->id }}">
                            <img src="{{ route('personal.foto', $personal) }}"
                                alt="Foto de {{ $personal->apellido }}, {{ $personal->nombre }}"
                                class="m-personal-foto-grande">
                            <div class="m-modal__meta" style="margin:.8rem 0 0;text-align:center;">LP {{ $personal->lp }}</div>
                        </template>
                    @else
                        <span class="m-personal-thumb m-personal-thumb--vacio"><i class="fas fa-user"></i></span>
                    @endif
                    <a href="{{ route('movil.personal.show', $personal) }}" class="m-personal-info">
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
                </div>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $personales->links() }}
        </div>
    @endif
    @include('movil.partials.modal')
@endsection
