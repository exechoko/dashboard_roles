@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Dominio {{ $dominioAlerta->dominio }}</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Datos del Dominio</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('alertas-video.dominios.update', $dominioAlerta) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('alertas-video.dominios._form')

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                            <a href="{{ route('alertas-video.dominios.show', $dominioAlerta) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
