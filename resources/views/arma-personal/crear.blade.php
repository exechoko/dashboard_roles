@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Nuevo Funcionario</h3>
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

                    <form action="{{ route('armas.personal.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido <span class="text-danger">*</span></label>
                                    <input type="text" name="apellido" id="apellido" class="form-control @error('apellido') is-invalid @enderror"
                                           value="{{ old('apellido') }}" required>
                                    @error('apellido')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lp">Legajo Policial (LP) <span class="text-danger">*</span></label>
                                    <input type="text" name="lp" id="lp" class="form-control @error('lp') is-invalid @enderror"
                                           value="{{ old('lp') }}" required maxlength="5" pattern="\d{5}" inputmode="numeric"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                    @error('lp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Debe tener exactamente 5 dígitos</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía <span class="text-danger">*</span></label>
                                    <input type="text" name="jerarquia" id="jerarquia" class="form-control @error('jerarquia') is-invalid @enderror"
                                           value="{{ old('jerarquia') }}" required>
                                    @error('jerarquia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ej: Oficial, Sargento, Subcomisario, Comisario</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
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
