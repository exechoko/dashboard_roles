@extends('layouts.movil')

@section('title', $personal->apellido.', '.$personal->nombre)
@section('back', route('movil.personal.index'))

@section('content')
    @php($resumenLicencia = $personal->resumen_licencia_actual)

    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem;">
            {{ $personal->apellido }}, {{ $personal->nombre }}
            @if ($personal->situacion_personal911)
                <span class="m-chip">{{ $personal->situacion_personal911 }}</span>
            @endif
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>LP</dt><dd>{{ $personal->lp }}</dd></div>
            <div class="m-detail__row"><dt>DNI</dt><dd>{{ $personal->dni ?? '—' }}</dd></div>
            <div class="m-detail__row"><dt>Jerarquía</dt><dd>{{ $personal->jerarquia }}</dd></div>
            @if ($personal->funcion_personal911)
                <div class="m-detail__row"><dt>Función</dt><dd>{{ $personal->funcion_personal911 }}</dd></div>
            @endif
            @if ($personal->observaciones_personal911)
                <div class="m-detail__row"><dt>Observaciones</dt><dd style="white-space:pre-line;">{{ $personal->observaciones_personal911 }}</dd></div>
            @endif
        </dl>
    </div>

    @can('ver-datos-personales-personal')
        <div class="m-section-title">Datos personales</div>
        <div class="m-detail">
            <dl style="margin:0;">
                <div class="m-detail__row"><dt>Dirección</dt><dd>{{ $personal->direccion ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>Teléfono</dt><dd style="white-space: pre-line;">{{ $personal->telefono ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>Email</dt><dd>{{ $personal->email ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>Estado civil</dt><dd>{{ $personal->estado_civil ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>Fecha de nacimiento</dt><dd>{{ optional($personal->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>Edad</dt><dd>{{ $personal->edad !== null ? $personal->edad.' años' : '—' }}</dd></div>
            </dl>
        </div>
    @endcan

    <div class="m-section-title">Arma asignada</div>
    <div class="m-detail">
        @if ($personal->tieneArmaAsignada())
            <dl style="margin:0;">
                <div class="m-detail__row"><dt>Numeración</dt><dd>{{ $personal->numeracion_arma }}</dd></div>
                <div class="m-detail__row"><dt>Tipo</dt><dd>{{ $personal->tipoArma?->nombre ?? '—' }}</dd></div>
                <div class="m-detail__row"><dt>N.º chaleco</dt><dd>{{ $personal->nro_chaleco ?? 'No asignado' }}</dd></div>
            </dl>
        @else
            <p class="m-card__subtitle" style="margin:0;"><i class="fas fa-exclamation-circle"></i> No tiene un arma asignada.</p>
        @endif
    </div>

    <div class="m-section-title">Licencia</div>
    <div class="m-detail">
        @if ($resumenLicencia)
            <dl style="margin:0;">
                <div class="m-detail__row"><dt>Estado</dt><dd><span class="m-chip" style="background-color: var(--m-warning); color:#fff;">De licencia</span></dd></div>
                <div class="m-detail__row"><dt>Tipo(s)</dt><dd>{{ $resumenLicencia['tipos']->implode(', ') ?: 'Sin tipo informado' }}</dd></div>
                <div class="m-detail__row"><dt>Período</dt><dd>{{ $resumenLicencia['fecha_inicio']->format('d/m/Y') }} al {{ $resumenLicencia['fecha_fin']->format('d/m/Y') }}</dd></div>
                <div class="m-detail__row"><dt>Días transcurridos</dt><dd>{{ $resumenLicencia['dias_transcurridos'] }} ({{ $resumenLicencia['dias_otorgados'] }} otorgados)</dd></div>
            </dl>
        @elseif ($personal->indicaLicenciaEnFuncion())
            <p class="m-card__subtitle" style="margin:0;"><i class="fas fa-info-circle"></i> Personal 911 informa la función "{{ $personal->funcion_personal911 }}"; revisar observaciones.</p>
        @else
            <p class="m-card__subtitle" style="margin:0;">Sin licencia vigente.</p>
        @endif
    </div>

    @if ($personal->licencias->isNotEmpty())
        <div class="m-section-title">Historial de licencias</div>
        <div class="m-list">
            @foreach ($personal->licencias as $licencia)
                <div class="m-card">
                    <div class="m-card__title" style="font-size:.92rem;">{{ $licencia->tipo_licencia ?? 'Sin tipo informado' }}</div>
                    <div class="m-card__subtitle">
                        {{ optional($licencia->fecha_inicio)->format('d/m/Y') ?? '—' }}
                        al {{ optional($licencia->fecha_fin)->format('d/m/Y') ?? '—' }}
                        · {{ $licencia->cantidad_dias ?? '-' }} días
                    </div>
                    @if ($licencia->motivo)
                        <div class="m-card__subtitle">{{ $licencia->motivo }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
