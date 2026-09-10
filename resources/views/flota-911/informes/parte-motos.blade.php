@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Parte Diario de Motopatrullas — División 911</h3>
    </div>
    <div class="section-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <form action="{{ route('flota-911.informes.parte-diario.generar') }}" method="POST" id="formParteDiario">
            @csrf
            <input type="hidden" name="tipo" value="motos">

            @include('flota-911.informes._parte-config')

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-motorcycle"></i></div>
                        <h5 class="header-title">Motopatrullas ({{ $recursos->count() }})</h5>
                    </div>
                </div>
                <div class="card-body">
                    @include('flota-911.informes._parte-recursos')

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Guardia (suboficiales de guardia interna)</label>
                            <textarea name="guardia_interna" class="form-control" rows="2" maxlength="1000">{{ optional($parte)->guardia_interna }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Personal de Licencia Ordinaria</label>
                            <textarea name="licencia_ordinaria" class="form-control" rows="2" maxlength="1000">{{ optional($parte)->licencia_ordinaria }}</textarea>
                        </div>
                    </div>

                    @php
                        $asignacionesGuardadas = collect(optional($parte)->asignaciones)
                            ->mapWithKeys(fn($a) => [trim($a->grupo.'|'.$a->nombre) => $a->asignacion_texto]);
                    @endphp
                    <div class="mt-3">
                        <label class="small font-weight-bold">Asignación de servicios</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2">
                                <thead>
                                    <tr>
                                        <th style="width:160px">Grupo</th>
                                        <th style="width:200px">Consigna</th>
                                        <th>Asignación (móvil / moto / HT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consignas as $i => $c)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="asignaciones[{{ $i }}][grupo]" value="{{ $c->grupo }}">
                                            <small class="text-muted">{{ $c->grupo ?: '—' }}</small>
                                        </td>
                                        <td>
                                            <input type="hidden" name="asignaciones[{{ $i }}][nombre]" value="{{ $c->nombre }}">
                                            {{ $c->nombre }}
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                name="asignaciones[{{ $i }}][asignacion_texto]"
                                                value="{{ $asignacionesGuardadas[trim($c->grupo.'|'.$c->nombre)] ?? '' }}"
                                                maxlength="255" placeholder="ej. 31 ht 05">
                                        </td>
                                    </tr>
                                    @endforeach
                                    @for($e = 0; $e < 3; $e++)
                                    @php $ei = $consignas->count() + $e; @endphp
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm" name="asignaciones[{{ $ei }}][grupo]" maxlength="80" placeholder="(opcional)"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="asignaciones[{{ $ei }}][nombre]" maxlength="120" placeholder="Consigna extra"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="asignaciones[{{ $ei }}][asignacion_texto]" maxlength="255"></td>
                                    </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        <label class="small font-weight-bold">NOVEDADES (pie del parte de motos)</label>
                        <textarea name="novedades_pie" class="form-control" rows="2" maxlength="2000">{{ optional($parte)->novedades_pie }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('flota-911.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-1"></i> Guardar parte
                </button>
            </div>
        </form>

    </div>
</section>

@push('scripts')
@include('flota-911.informes._parte-scripts', ['tipo' => 'motos'])
@endpush
@endsection
