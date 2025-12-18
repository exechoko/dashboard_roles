@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Tarea</h1>
        </div>

        <div class="section-body">
            <form action="{{ route('tareas.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-tasks"></i> Información</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="200">
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="recurrencia_tipo">Recurrencia <span class="text-danger">*</span></label>
                                            <select class="form-control @error('recurrencia_tipo') is-invalid @enderror" id="recurrencia_tipo" name="recurrencia_tipo" required>
                                                @foreach($recurrencias as $key => $label)
                                                    <option value="{{ $key }}" {{ old('recurrencia_tipo', 'none') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('recurrencia_tipo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="recurrencia_intervalo">Intervalo</label>
                                            <input type="number" class="form-control @error('recurrencia_intervalo') is-invalid @enderror" id="recurrencia_intervalo" name="recurrencia_intervalo" min="1" value="{{ old('recurrencia_intervalo', 1) }}">
                                            @error('recurrencia_intervalo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fecha_inicio">Fecha inicio</label>
                                            <input type="date" class="form-control @error('fecha_inicio') is-invalid @enderror" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio') }}">
                                            @error('fecha_inicio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="weeklyFields" style="display:none;">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="recurrencia_dia_semana">Día de semana</label>
                                            <select class="form-control @error('recurrencia_dia_semana') is-invalid @enderror" id="recurrencia_dia_semana" name="recurrencia_dia_semana">
                                                <option value="">(Auto)</option>
                                                <option value="1" {{ old('recurrencia_dia_semana') == 1 ? 'selected' : '' }}>Lunes</option>
                                                <option value="2" {{ old('recurrencia_dia_semana') == 2 ? 'selected' : '' }}>Martes</option>
                                                <option value="3" {{ old('recurrencia_dia_semana') == 3 ? 'selected' : '' }}>Miércoles</option>
                                                <option value="4" {{ old('recurrencia_dia_semana') == 4 ? 'selected' : '' }}>Jueves</option>
                                                <option value="5" {{ old('recurrencia_dia_semana') == 5 ? 'selected' : '' }}>Viernes</option>
                                                <option value="6" {{ old('recurrencia_dia_semana') == 6 ? 'selected' : '' }}>Sábado</option>
                                                <option value="7" {{ old('recurrencia_dia_semana') == 7 ? 'selected' : '' }}>Domingo</option>
                                            </select>
                                            @error('recurrencia_dia_semana')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="monthlyFields" style="display:none;">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="recurrencia_dia_mes">Día del mes</label>
                                            <input type="number" class="form-control @error('recurrencia_dia_mes') is-invalid @enderror" id="recurrencia_dia_mes" name="recurrencia_dia_mes" min="1" max="31" value="{{ old('recurrencia_dia_mes') }}">
                                            @error('recurrencia_dia_mes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="activa" name="activa" value="1" {{ old('activa', '1') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="activa">Activa</label>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-info-circle"></i> Acciones</h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('tareas.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    function toggleRecurrenceFields() {
        const tipo = document.getElementById('recurrencia_tipo').value;
        document.getElementById('weeklyFields').style.display = (tipo === 'weekly') ? 'flex' : 'none';
        document.getElementById('monthlyFields').style.display = (tipo === 'monthly') ? 'flex' : 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleRecurrenceFields();
        document.getElementById('recurrencia_tipo').addEventListener('change', toggleRecurrenceFields);
    });
</script>
@endpush
