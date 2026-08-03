@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Cargar Arma Secundaria</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Datos del Arma</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('armas.armeria.armas.store') }}" method="POST">
                        @csrf
                        @include('arma-armeria.armas._form')

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <a href="{{ route('armas.armeria.armas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
