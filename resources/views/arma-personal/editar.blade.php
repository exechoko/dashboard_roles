@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Funcionario</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('armas.personal.update', $personal) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="apellido" class="form-control"
                                           value="{{ old('apellido', $personal->apellido) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="nombre" class="form-control"
                                           value="{{ old('nombre', $personal->nombre) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lp">Legajo Policial (LP) <span class="badge badge-secondary">No editable</span></label>
                                    <input type="text" id="lp" class="form-control"
                                           value="{{ old('lp', $personal->lp) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía <span class="text-danger">*</span></label>
                                    <input type="text" name="jerarquia" id="jerarquia" class="form-control @error('jerarquia') is-invalid @enderror"
                                           value="{{ old('jerarquia', $personal->jerarquia) }}" required>
                                    @error('jerarquia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                                <a href="{{ route('armas.personal.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
