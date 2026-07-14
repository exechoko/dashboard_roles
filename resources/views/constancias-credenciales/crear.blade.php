@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Acta de Credenciales</h1>
        </div>

        <div class="section-body">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('constancias-credenciales.store') }}" method="POST">
                @csrf
                @include('constancias-credenciales._form')

                <div class="text-right">
                    <a href="{{ route('constancias-credenciales.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Acta
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
