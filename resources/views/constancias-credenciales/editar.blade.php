@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-edit"></i> Editar Acta de Credenciales N° {{ $constancia->id }}</h1>
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

            <form action="{{ route('constancias-credenciales.update', $constancia) }}" method="POST">
                @csrf
                @method('PUT')
                @include('constancias-credenciales._form')

                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-check-circle"></i> Estado del Acta</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="firmada" name="firmada" value="1" {{ $constancia->firmada ? 'checked' : '' }}>
                                <label class="custom-control-label" for="firmada">Acta firmada en conformidad</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <a href="{{ route('constancias-credenciales.show', $constancia) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Acta
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
