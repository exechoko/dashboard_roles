{{--
    Campo genérico de configuración, reutilizado por env/ia/workers.
    Espera: $clave, $meta (tipo/opciones), $valor, $idPrefijo, $deshabilitado
--}}
@php
    $id = $idPrefijo . $clave;
    $nombre = "valores[{$clave}]";
    $tipo = $meta['tipo'] ?? 'text';
@endphp

@if ($tipo === 'bool')
    <input type="hidden" name="{{ $nombre }}" value="false">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="{{ $id }}" name="{{ $nombre }}" value="true"
            {{ in_array(strtolower((string) $valor), ['true', '1', 'yes'], true) ? 'checked' : '' }}
            {{ $deshabilitado ? 'disabled' : '' }}>
        <label class="custom-control-label" for="{{ $id }}">Habilitado</label>
    </div>
@elseif ($tipo === 'select')
    <select class="form-control" id="{{ $id }}" name="{{ $nombre }}" {{ $deshabilitado ? 'disabled' : '' }}>
        @foreach ($meta['opciones'] ?? [] as $valorOpcion => $etiqueta)
            <option value="{{ $valorOpcion }}" {{ (string) $valor === (string) $valorOpcion ? 'selected' : '' }}>
                {{ $etiqueta }}
            </option>
        @endforeach
    </select>
@elseif ($tipo === 'password')
    <div class="input-group">
        <input type="password" class="form-control" id="{{ $id }}" name="{{ $nombre }}"
            value="{{ old("valores.$clave", $valor) }}" placeholder="•••••• (sin cambios)" autocomplete="new-password"
            {{ $deshabilitado ? 'disabled' : '' }}>
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="{{ $id }}" tabindex="-1">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>
@elseif ($tipo === 'number')
    <input type="number" class="form-control" id="{{ $id }}" name="{{ $nombre }}"
        value="{{ old("valores.$clave", $valor) }}" {{ $deshabilitado ? 'disabled' : '' }}>
@else
    <input type="text" class="form-control" id="{{ $id }}" name="{{ $nombre }}"
        value="{{ old("valores.$clave", $valor) }}" {{ $deshabilitado ? 'disabled' : '' }}>
@endif

@push('scripts')
    @once
        <script>
            document.addEventListener('click', function (e) {
                var boton = e.target.closest('.js-toggle-password');
                if (!boton) { return; }
                var input = document.getElementById(boton.getAttribute('data-target'));
                if (!input) { return; }
                input.type = input.type === 'password' ? 'text' : 'password';
                boton.querySelector('i').classList.toggle('fa-eye');
                boton.querySelector('i').classList.toggle('fa-eye-slash');
            });
        </script>
    @endonce
@endpush
