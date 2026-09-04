@extends('layouts.movil')

@section('title', 'Eventos CECOCO')

@section('content')
    <form method="GET" action="{{ route('movil.eventos.index') }}" class="m-filters">
        <div class="m-field">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N.º de expediente, dirección, operador…">
        </div>

        <div class="m-field">
            <label for="desde_datetime">Desde</label>
            <input type="datetime-local" id="desde_datetime" name="desde_datetime" value="{{ request('desde_datetime') }}">
        </div>
        <div class="m-field">
            <label for="hasta_datetime">Hasta</label>
            <input type="datetime-local" id="hasta_datetime" name="hasta_datetime" value="{{ request('hasta_datetime') }}">
        </div>

        <div class="m-field">
            <label for="orden">Orden</label>
            <select id="orden" name="orden">
                <option value="fecha_reciente" @selected(request('orden', 'fecha_reciente') == 'fecha_reciente')>Más recientes primero</option>
                <option value="fecha_antigua" @selected(request('orden') == 'fecha_antigua')>Más antiguos primero</option>
                <option value="expediente_mayor_menor" @selected(request('orden') == 'expediente_mayor_menor')>N.º expediente, mayor a menor</option>
                <option value="expediente_menor_mayor" @selected(request('orden') == 'expediente_menor_mayor')>N.º expediente, menor a mayor</option>
            </select>
        </div>

        <button type="submit" class="m-btn"><i class="fas fa-search"></i> Buscar</button>
    </form>

    @if (is_null($eventos))
        <div class="m-empty">
            <i class="fas fa-list-alt" style="font-size:1.6rem;"></i>
            <p>Ingresá un texto o un rango de fechas para buscar eventos.</p>
        </div>
    @elseif ($eventos->isEmpty())
        <div class="m-empty"><p>No se encontraron eventos.</p></div>
    @else
        <div class="m-list">
            @foreach ($eventos as $evento)
                <a href="{{ route('movil.eventos.show', $evento->id) }}" class="m-card">
                    <div class="m-card__title">Expte. {{ $evento->nro_expediente }}</div>
                    <div class="m-card__subtitle">{{ optional($evento->fecha_hora)->format('d/m/Y H:i') }} · {{ $evento->operador }}</div>
                    <div class="m-card__subtitle">{{ $evento->direccion }}</div>
                    @if ($evento->tipo_servicio)
                        <div class="m-card__meta"><span class="m-chip">{{ $evento->tipo_servicio }}</span></div>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $eventos->links() }}
        </div>
    @endif
@endsection
