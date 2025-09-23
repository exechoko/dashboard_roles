@extends('layouts.app')

@section('css')
@include('mapa.partials.styles')
@endsection

@section('content')
    <section class="section">
        {{-- Header con estadísticas y controles --}}
        @include('mapa.partials.header', [
            'camaras' => $camaras,
            'fijas' => $fijas,
            'fijasFR' => $fijasFR,
            'fijasLPR' => $fijasLPR,
            'domos' => $domos,
            'domosDuales' => $domosDuales,
            'bde' => $bde,
            'total' => $total,
            'canales' => $canales,
            'camarasParana' => $camarasParana,
            'camarasCniaAvellaneda' => $camarasCniaAvellaneda,
            'camarasSanBenito' => $camarasSanBenito,
            'camarasOroVerde' => $camarasOroVerde,
            'sitiosParana' => $sitiosParana,
            'sitiosCniaAvellaneda' => $sitiosCniaAvellaneda,
            'sitiosSanBenito' => $sitiosSanBenito,
            'sitiosOroVerde' => $sitiosOroVerde,
            'cantidadSitios' => $cantidadSitios
        ])

        {{-- Container del mapa --}}
        <div class="col-lg-12 mt-3">
            <div id="map" style="height: 625px; position: relative;">
                {{-- Control personalizado de capas --}}
                @include('mapa.partials.layer-control')
            </div>
        </div>
    </section>

    {{-- Scripts del mapa --}}
    @include('mapa.partials.scripts')
@endsection
