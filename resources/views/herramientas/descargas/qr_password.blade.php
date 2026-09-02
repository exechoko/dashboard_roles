@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-qrcode mr-2"></i>Código QR - Descargar Archivo</h3>
    </div>

    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-lock mr-2"></i>Acceso Protegido</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                {{ $error }}
                            </div>
                        @endif

                        <p class="text-center mb-4">
                            Este archivo está protegido con contraseña.<br>
                            Por favor, ingresa la contraseña para continuar.
                        </p>

                        <form method="GET" action="{{ route('descargas.qr.descargar', $qrCode->token) }}">
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" name="password" id="password" class="form-control" required autofocus>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-unlock mr-2"></i>Desbloquear y Descargar
                            </button>
                        </form>

                        <hr>

                        <div class="text-center text-muted small">
                            <p class="mb-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Este código QR es de un solo uso
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock mr-1"></i>
                                Expira: {{ $qrCode->expira_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
