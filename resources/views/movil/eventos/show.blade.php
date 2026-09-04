@extends('layouts.movil')

@section('title', 'Expte. ' . $eventoCecoco->nro_expediente)
@section('back', $volver)

@section('content')
    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            Expte. {{ $eventoCecoco->nro_expediente }}
            @if ($eventoCecoco->tipo_servicio)
                <span class="m-chip">{{ $eventoCecoco->tipo_servicio }}</span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Fecha</dt><dd>{{ optional($eventoCecoco->fecha_hora)->format('d/m/Y H:i') }}</dd></div>
            <div class="m-detail__row"><dt>Operador</dt><dd>{{ $eventoCecoco->operador ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Dirección</dt><dd>{{ $eventoCecoco->direccion ?? '—' }}</dd></div>
            @if ($eventoCecoco->telefono)
                <div class="m-detail__row"><dt>Teléfono</dt><dd>{{ $eventoCecoco->telefono }}</dd></div>
            @endif
            @if ($eventoCecoco->descripcion)
                <div class="m-detail__row"><dt>Descripción</dt><dd>{{ $eventoCecoco->descripcion }}</dd></div>
            @endif
        </dl>
    </div>

    @include('eventos-cecoco.partials.resumen_ia', ['eventoCecoco' => $eventoCecoco])

    @if ($errorExpediente)
        <div class="m-alert m-alert--danger">{{ $errorExpediente }}</div>
    @elseif ($detalle)
        @php $historial = $detalle['historial'] ?? []; @endphp
        <div class="m-section-title">Expediente completo</div>
        <div class="m-detail">
            <dl style="margin:0;">
                @if (!empty($historial['barrio']))
                    <div class="m-detail__row"><dt>Barrio</dt><dd>{{ $historial['barrio'] }}</dd></div>
                @endif
                @if (!empty($historial['jurisdiccion']))
                    <div class="m-detail__row"><dt>Jurisdicción</dt><dd>{{ $historial['jurisdiccion'] }}</dd></div>
                @endif
                @if (!empty($historial['municipio']) || !empty($historial['localidad']))
                    <div class="m-detail__row"><dt>Localidad</dt><dd>{{ $historial['municipio'] ?? $historial['localidad'] }}</dd></div>
                @endif
                @if (!empty($historial['estado']))
                    <div class="m-detail__row"><dt>Estado</dt><dd>{{ $historial['estado'] }}</dd></div>
                @endif
            </dl>
        </div>

        @if (!empty($detalle['timeline']))
            <div class="m-section-title">Cronología</div>
            <div class="m-list">
                @foreach ($detalle['timeline'] as $paso)
                    <div class="m-card">
                        <div class="m-card__subtitle">{{ $paso['fecha_hora'] ?? '' }} · {{ $paso['operador'] ?? '' }}</div>
                        <div>{{ $paso['descripcion'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (!empty($detalle['cierre']))
            <div class="m-section-title">Cierre</div>
            <div class="m-detail">
                <dl style="margin:0;">
                    <div class="m-detail__row"><dt>Fecha</dt><dd>{{ $detalle['cierre']['fecha'] ?? '—' }}</dd></div>
                    <div class="m-detail__row"><dt>Tipo</dt><dd>{{ $detalle['cierre']['tipo'] ?? '—' }}</dd></div>
                    @if (!empty($detalle['cierre']['observaciones']))
                        <div class="m-detail__row"><dt>Observaciones</dt><dd>{{ $detalle['cierre']['observaciones'] }}</dd></div>
                    @endif
                </dl>
            </div>
        @endif
    @endif
@endsection
