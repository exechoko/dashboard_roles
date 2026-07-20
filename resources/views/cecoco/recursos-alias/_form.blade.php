@php
    $aliasActual = $alias ?? null;
@endphp

@if ($errors->any())
    <div class="alert alert-dark alert-dismissible fade show" role="alert">
        <strong>¡Revise los campos!</strong>
        @foreach ($errors->all() as $error)
            <span class="badge badge-danger">{{ $error }}</span>
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label for="alias_cecoco">Alias CECOCO</label>
            <input type="text" name="alias_cecoco" id="alias_cecoco" class="form-control" value="{{ old('alias_cecoco', $aliasActual?->alias_cecoco) }}" placeholder="P1018, MP12, JDP01" required>
            <small class="form-text text-muted">Se guarda en mayúsculas para comparar de forma consistente.</small>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label for="recurso_id">Recurso CAR911</label>
            <select name="recurso_id" id="recurso_id" class="form-control select2">
                <option value="">Sin recurso asociado</option>
                @foreach ($recursos as $recurso)
                    <option value="{{ $recurso->id }}" {{ (int) old('recurso_id', $aliasActual?->recurso_id) === $recurso->id ? 'selected' : '' }}>
                        {{ $recurso->nombre }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Si no elegís equipo, se usan los equipos activos de este recurso.</small>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group">
            <label for="equipo_id">Equipo CAR911</label>
            <select name="equipo_id" id="equipo_id" class="form-control select2">
                <option value="">Sin equipo específico</option>
                @foreach ($equipos as $equipo)
                    <option value="{{ $equipo->id }}" {{ (int) old('equipo_id', $aliasActual?->equipo_id) === $equipo->id ? 'selected' : '' }}>
                        ISSI {{ $equipo->issi ?? 's/d' }}@if($equipo->nombre_issi) - {{ $equipo->nombre_issi }}@endif @if($equipo->tei) - TEI {{ $equipo->tei }}@endif
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Tiene prioridad para alias como JDP01, JDP02, etc.</small>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Si seleccionás solo recurso, el sistema resolverá todos los ISSI activos de la flota. Si seleccionás equipo, usará directamente ese ISSI.
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <div class="control-label">Activo</div>
            <input type="hidden" name="activo" value="0">
            <label class="custom-switch mt-2">
                <input type="checkbox" name="activo" value="1" class="custom-switch-input" {{ old('activo', $aliasActual?->activo ?? true) ? 'checked' : '' }}>
                <span class="custom-switch-indicator"></span>
            </label>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-floating">
            <label for="observaciones">Observaciones</label>
            <textarea class="form-control" name="observaciones" id="observaciones" style="height: 100px">{{ old('observaciones', $aliasActual?->observaciones) }}</textarea>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Guardar
        </button>
        <a href="{{ route('cecoco.recursos-alias.index') }}" class="btn btn-secondary ml-2">
            <i class="fas fa-arrow-left mr-1"></i> Cancelar
        </a>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').select2({ width: '100%' });
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    let select2Field = document.querySelector('.select2-container--open .select2-search__field');
                    if (select2Field) {
                        select2Field.focus();
                    }
                }, 0);
            });
        });
    </script>
@endpush
