@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0"><i class="fas fa-lock mr-2"></i>Acceso protegido</h4>
                    </div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endif

                        <p class="text-center mb-4">
                            Este archivo está protegido con contraseña.<br>
                            Ingresa la contraseña para continuar.
                        </p>

                        <form method="GET" action="{{ route('descargas.link.publico', $link->token) }}">
                            <div class="form-group">
                                <label>Contraseña</label>
                                <input type="password" name="password" class="form-control" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-unlock"></i> Acceder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
