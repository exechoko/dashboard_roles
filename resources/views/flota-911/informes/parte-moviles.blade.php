@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Parte Diario de Móviles — División 911</h3>
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
            <input type="hidden" name="tipo" value="moviles">

            @include('flota-911.informes._parte-config')

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-car"></i></div>
                        <h5 class="header-title">Móviles ({{ $recursos->count() }})</h5>
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
                </div>
            </div>

            {{-- NOVEDADES (hoja de la División, una por guardia) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <h5 class="header-title">NOVEDADES — División 911</h5>
                            <small class="text-muted">Una por guardia. Vacío = "Sin Novedad".</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @php $contenidoNovedades = optional($novedades)->contenido ?? []; @endphp
                    <div class="row">
                        @foreach(\App\Models\ParteDiarioNovedades::RUBROS as $clave => $etiqueta)
                        @php $esPersonal = in_array($clave, \App\Models\ParteDiarioNovedades::RUBROS_PERSONAL, true); @endphp
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold mb-1">{{ $etiqueta }}</label>
                                @if($esPersonal)
                                <select class="form-control form-control-sm select2-novedad-personal mb-1"
                                    data-rubro="{{ $clave }}" data-placeholder="Agregar funcionario...">
                                    <option value=""></option>
                                    @foreach($personal as $p)
                                        <option value="{{ $p->id }}" data-corto="{{ trim($p->jerarquia.' '.$p->apellido.' '.$p->nombre) }}">
                                            {{ $p->getNombreCompletoAttribute() }}
                                        </option>
                                    @endforeach
                                </select>
                                @endif
                                <textarea name="novedades[{{ $clave }}]" class="form-control form-control-sm"
                                    rows="{{ $esPersonal ? 2 : 1 }}" maxlength="2000">{{ $contenidoNovedades[$clave] ?? '' }}</textarea>
                            </div>
                        </div>
                        @endforeach
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
@include('flota-911.informes._parte-scripts', ['tipo' => 'moviles'])
@endpush
@endsection
