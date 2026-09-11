@extends('layouts.movil')

@section('title', 'Activaciones Tótem pendientes')
@section('back', route('movil.index'))

@section('content')
    @if ($activaciones->isEmpty())
        <div class="m-empty"><p>No hay activaciones de Tótem pendientes.</p></div>
    @else
        <div class="m-list">
            @foreach ($activaciones as $activacion)
                <div class="m-card">
                    <div class="m-card__title">Exp. {{ $activacion->nro_expediente }}</div>
                    <div class="m-card__subtitle">{{ $activacion->fecha_evento->format('d/m/Y') }}</div>
                    @if ($activacion->esVencida())
                        <div class="m-card__meta"><span class="m-chip">Vencida (+{{ \App\Models\ActivacionTotem::MESES_RETENCION_LEGAL }} meses)</span></div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
