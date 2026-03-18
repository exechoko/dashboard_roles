@extends('layouts.app')
@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Visor GIS CeCoCo</h3>
    </div>
    <div class="section-body">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>No se pudo conectar al Visor GIS:</strong> {{ $mensaje }}
        </div>
        <a href="{{ route('cecoco.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="{{ route('cecoco.mapa-gis') }}" class="btn btn-primary ml-2">
            <i class="fas fa-redo"></i> Reintentar
        </a>
    </div>
</section>
@endsection
