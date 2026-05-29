@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Entrega de Combustible</h1>
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

            <form action="{{ route('entrega-combustible.store') }}" method="POST">
                @csrf
                @include('entregas.entregas-combustible._form')

                <div class="text-right">
                    <a href="{{ route('entrega-combustible.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Entrega
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
