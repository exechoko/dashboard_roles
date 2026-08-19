@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">{{ $buzon->exists ? 'Editar Buzón' : 'Nuevo Buzón' }}</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form method="POST"
                          action="{{ $buzon->exists ? route('herramientas.mails.buzones.update', $buzon) : route('herramientas.mails.buzones.store') }}">
                        @csrf
                        @if ($buzon->exists)
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $buzon->nombre) }}" required>
                            @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Carpeta dentro de {{ config('mbox.ruta') }}</label>
                            <input type="text" name="carpeta" class="form-control @error('carpeta') is-invalid @enderror"
                                   value="{{ old('carpeta', $buzon->carpeta) }}" required>
                            @error('carpeta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Email real de la casilla (opcional)</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $buzon->email) }}">
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Rol con acceso a este buzón</label>
                            <select name="role_id" class="form-control select2">
                                <option value="">Sin rol (sólo administradores del visor)</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $buzon->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Los usuarios con este rol podrán ver los mensajes de este buzón. Si el rol
                                que necesitás no existe, creálo primero en Roles.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <textarea name="descripcion" class="form-control" rows="2">{{ old('descripcion', $buzon->descripcion) }}</textarea>
                        </div>

                        <div class="form-group form-check">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1" class="form-check-input" id="activo"
                                   {{ old('activo', $buzon->activo ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <a href="{{ route('herramientas.mails.buzones.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('.select2').select2({ width: '100%' });
        });
    </script>
@endpush
