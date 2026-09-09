@extends('layouts.movil')

@section('title', $flota->equipo->tei ?? 'Equipo')
@section('back', $volver)

@section('content')
    @php
        $estadoPatrimonial = [
            'sin_patrimoniar' => 'Sin patrimoniar',
            'pendiente_firma' => 'Pendiente de firma',
            'patrimoniado' => 'Patrimoniado',
        ][$flota->estado_patrimonial] ?? null;
    @endphp

    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            {{ $flota->equipo->tei ?? '—' }}
            @if ($flota->equipo?->estado)
                <span class="m-chip">{{ $flota->equipo->estado->nombre }}</span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>ISSI</dt><dd>{{ $flota->equipo->issi ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Marca / modelo</dt><dd>{{ $flota->equipo?->tipo_terminal?->marca }} {{ $flota->equipo?->tipo_terminal?->modelo }}</dd></div>
            <div class="m-detail__row"><dt>N.º batería</dt><dd>{{ $flota->equipo->numero_bateria ?? '—' }}</dd></div>
        </dl>
    </div>

    <div class="m-section-title">Asignación actual</div>
    <div class="m-detail">
        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Móvil / recurso</dt><dd>{{ $flota->recurso->nombre ?? '—' }}</dd></div>
            @if ($flota->recurso?->vehiculo)
                <div class="m-detail__row"><dt>Vehículo</dt><dd>{{ $flota->recurso->vehiculo->marca }} {{ $flota->recurso->vehiculo->modelo }} {{ $flota->recurso->vehiculo->dominio ? '· '.$flota->recurso->vehiculo->dominio : '' }}</dd></div>
            @endif
            <div class="m-detail__row"><dt>Destino</dt><dd>{{ $flota->destino->nombre ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Fecha de asignación</dt><dd>{{ optional($flota->fecha_asignacion)->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Ticket PER</dt><dd>{{ $flota->ticket_per ?? '—' }}</dd></div>
            @if ($estadoPatrimonial)
                <div class="m-detail__row"><dt>Patrimonio</dt><dd>{{ $estadoPatrimonial }}</dd></div>
            @endif
            @if ($flota->observaciones)
                <div class="m-detail__row"><dt>Observaciones</dt><dd>{{ $flota->observaciones }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="m-section-title">Historial de movimientos</div>

    @if ($historico->isEmpty())
        <div class="m-empty"><p>Sin movimientos registrados.</p></div>
    @else
        <div class="m-list">
            @foreach ($historico as $h)
                <div class="m-card">
                    <div class="m-card__meta" style="margin-top:0; margin-bottom:.4rem;">
                        @if ($h->tipoMovimiento)
                            <span class="m-chip" style="background-color: {{ $h->tipoMovimiento->color ?? '#6777ef' }}; color:#fff;">
                                {{ $h->tipoMovimiento->nombre }}
                            </span>
                        @endif
                        <span class="m-card__subtitle">{{ optional($h->fecha_asignacion)->format('d/m/Y H:i') }}</span>
                    </div>
                    @if ($h->recurso_asignado)
                        <div class="m-card__subtitle"><i class="fas fa-arrow-right"></i> {{ $h->recurso_asignado }}</div>
                    @endif
                    @if ($h->destino)
                        <div class="m-card__subtitle"><i class="fas fa-building"></i> {{ $h->destino->nombre }}</div>
                    @endif
                    @if ($h->observaciones)
                        <div class="m-card__subtitle" style="margin-top:.3rem;">{{ $h->observaciones }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
