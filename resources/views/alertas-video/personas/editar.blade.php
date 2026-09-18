@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Persona {{ $personaAlerta->apellido_nombre }}</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Datos de la Persona</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('alertas-video.personas.update', $personaAlerta) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('alertas-video.personas._form')

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                            <a href="{{ route('alertas-video.personas.show', $personaAlerta) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('alertas-video._styles')
@endpush
