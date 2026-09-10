{{-- Card de configuración del turno. Espera $tipo, $fecha, $guardia, $horario, $fechaInicio, $fechaFin, $parte. --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header-modern">
        <div class="card-header-left">
            <div class="header-icon"><i class="fas fa-calendar-alt"></i></div>
            <h5 class="header-title">Configuración</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Fecha del parte <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" required id="inputFecha">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Guardia <span class="text-danger">*</span></label>
                    <select name="guardia" class="form-control" required id="inputGuardia">
                        <option value="">Seleccione...</option>
                        @foreach(\App\Models\RecursoEstadoDiario::$guardias as $k => $label)
                            <option value="{{ $k }}" {{ $guardia === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Horario <span class="text-danger">*</span></label>
                    <select name="horario" class="form-control" required id="inputHorario">
                        @foreach(\App\Models\RecursoEstadoDiario::$horarios as $k => $label)
                            <option value="{{ $k }}" {{ $horario === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Inicio del turno <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_inicio" class="form-control"
                        value="{{ $fechaInicio->format('Y-m-d\TH:i') }}" required id="inputFechaInicio">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Fin del turno <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_fin" class="form-control"
                        value="{{ $fechaFin->format('Y-m-d\TH:i') }}" required id="inputFechaFin">
                </div>
            </div>
            <div class="col-md-6">
                <p class="text-muted small mb-1 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    El inicio y fin se autocompletan según fecha y horario. Ajústelos si el cambio de guardia se movió.
                </p>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnCargarTurno">
                    <i class="fas fa-sync-alt mr-1"></i> Cargar parte guardado de este turno
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" id="btnPreArmar">
                    <i class="fas fa-magic mr-1"></i> Pre-armar desde la guardia
                </button>
                <button type="button" class="btn btn-outline-info btn-sm" id="btnDesdeGuardia">
                    <i class="fas fa-history mr-1"></i> Traer del último parte de esta guardia
                </button>
            </div>
        </div>
        <p class="text-muted small mb-0">
            <i class="fas fa-lightbulb mr-1"></i>
            "Pre-armar" trae de la base de personal 911 la guardia interna, las licencias y las novedades de sala.
            "Traer del último parte de esta guardia" copia el parte anterior de la guardia elegida para que solo ajustes las diferencias.
        </p>
        @if($parte)
        <a href="{{ route('flota-911.informes.parte-diario.docx', ['tipo' => $tipo, 'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i')]) }}"
           class="btn btn-success btn-sm mt-3">
            <i class="fas fa-file-word mr-1"></i> Descargar parte de {{ $tipo === 'motos' ? 'motopatrullas' : 'móviles' }} (.docx)
        </a>
        @endif
    </div>
</div>
