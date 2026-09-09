@extends('layouts.movil')

@section('title', 'Sin conexión')

@section('content')
    <div class="m-offline">
        <i class="fas fa-wifi" style="font-size:2.2rem; color:var(--m-muted);"></i>
        <h2 style="margin:0;">Sin conexión</h2>
        <p class="m-card__subtitle">
            No se pudo conectar al servidor. Los datos de esta app no se guardan en el celular,
            así que necesitás señal para consultarlos.
        </p>
        <a href="{{ route('movil.index') }}" class="m-btn">Reintentar</a>
    </div>
@endsection
