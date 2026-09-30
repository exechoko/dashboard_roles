{{--
    Selector visual de íconos de Font Awesome (grilla + buscador), en vez de
    tener que escribir a mano la clase (ej. "fas fa-folder").
    ============================================================

    Props:
        name    (string) requerido — name del input
        id      (string) requerido — id del input (único por instancia)
        value   (string) default 'fas fa-folder' — clase inicial

    El input de texto subyacente se mantiene (mismo id/name de siempre), así
    que cualquier JS externo que le asigne `.value` directamente sigue
    funcionando — con tal de disparar un evento 'input' después para que la
    vista previa se actualice (ver admin/categorias.blade.php).

    Uso:
        <x-fontawesome-icon-picker name="icono" id="icono" value="fas fa-folder" />
--}}
@props([
    'name',
    'id',
    'value' => 'fas fa-folder',
])

@php
    $iconos = [
        'fas fa-folder', 'fas fa-folder-open', 'fas fa-file', 'fas fa-file-alt',
        'fas fa-file-pdf', 'fas fa-file-word', 'fas fa-file-excel', 'fas fa-file-powerpoint',
        'fas fa-file-image', 'fas fa-file-video', 'fas fa-file-audio', 'fas fa-file-archive',
        'fas fa-file-code', 'fas fa-file-csv', 'fas fa-file-contract', 'fas fa-file-invoice',
        'fas fa-file-signature', 'fas fa-images', 'fas fa-video', 'fas fa-camera',
        'fas fa-photo-video', 'fas fa-landmark', 'fas fa-university', 'fas fa-flag',
        'fas fa-star', 'fas fa-shield-alt', 'fas fa-building', 'fas fa-city',
        'fas fa-tags', 'fas fa-tag', 'fas fa-book', 'fas fa-book-open',
        'fas fa-clipboard', 'fas fa-clipboard-list', 'fas fa-list', 'fas fa-chart-bar',
        'fas fa-chart-line', 'fas fa-chart-pie', 'fas fa-table', 'fas fa-database',
        'fas fa-cogs', 'fas fa-tools', 'fas fa-wrench', 'fas fa-gavel',
        'fas fa-balance-scale', 'fas fa-user-shield', 'fas fa-users', 'fas fa-id-card',
        'fas fa-calendar-alt', 'fas fa-map', 'fas fa-map-marked-alt', 'fas fa-truck',
        'fas fa-car', 'fas fa-motorcycle', 'fas fa-ambulance', 'fas fa-fire-extinguisher',
        'fas fa-first-aid', 'fas fa-heartbeat', 'fas fa-graduation-cap', 'fas fa-newspaper',
        'fas fa-bullhorn', 'fas fa-info-circle', 'fas fa-exclamation-triangle', 'fas fa-lock',
        'fas fa-key', 'fas fa-globe', 'fas fa-envelope', 'fas fa-phone',
        'fas fa-headset', 'fas fa-microphone', 'fas fa-broadcast-tower',
    ];
@endphp

<div class="fa-icon-picker" data-picker-id="{{ $id }}">
    <div class="fa-icon-picker__trigger" data-toggle-picker="{{ $id }}">
        <span class="fa-icon-picker__preview"><i class="{{ $value }}" id="{{ $id }}_preview"></i></span>
        <input type="text" name="{{ $name }}" id="{{ $id }}" class="form-control fa-icon-picker__input"
               value="{{ $value }}" placeholder="fas fa-folder" autocomplete="off">
        <span class="fa-icon-picker__caret"><i class="fas fa-chevron-down"></i></span>
    </div>

    <div class="fa-icon-picker__panel" id="{{ $id }}_panel">
        <input type="text" class="form-control form-control-sm fa-icon-picker__search"
               id="{{ $id }}_search" placeholder="Buscar ícono... (ej: video, alerta, mapa)">
        <div class="fa-icon-picker__grid" id="{{ $id }}_grid">
            @foreach($iconos as $icono)
                <button type="button" class="fa-icon-picker__option" data-icon="{{ $icono }}"
                        title="{{ $icono }}">
                    <i class="{{ $icono }}"></i>
                </button>
            @endforeach
        </div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .fa-icon-picker { position: relative; }

        .fa-icon-picker__trigger {
            display: flex;
            align-items: stretch;
            border: 1px solid var(--input-border, #ced4da);
            border-radius: .25rem;
            overflow: hidden;
            background-color: var(--input-bg, #fff);
        }

        .fa-icon-picker__preview {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            flex-shrink: 0;
            background-color: var(--bg-tertiary, #f8f9fa);
            border-right: 1px solid var(--input-border, #ced4da);
            color: var(--text-primary, #495057);
        }

        .fa-icon-picker__input {
            border: none !important;
            border-radius: 0 !important;
            flex: 1;
        }

        .fa-icon-picker__input:focus {
            box-shadow: none !important;
        }

        .fa-icon-picker__caret {
            display: flex;
            align-items: center;
            padding: 0 .75rem;
            cursor: pointer;
            color: var(--text-secondary, #6c757d);
        }

        .fa-icon-picker__panel {
            display: none;
            position: absolute;
            z-index: 1060;
            top: calc(100% + .25rem);
            left: 0;
            right: 0;
            background-color: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #ced4da);
            border-radius: .5rem;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .18);
            padding: .6rem;
        }

        .fa-icon-picker__panel.show {
            display: block;
        }

        .fa-icon-picker__grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: .3rem;
            max-height: 220px;
            overflow-y: auto;
            margin-top: .5rem;
        }

        .fa-icon-picker__option {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 2.25rem;
            border: 1px solid transparent;
            border-radius: .35rem;
            background: transparent;
            color: var(--text-primary, #495057);
            cursor: pointer;
            transition: all .1s ease;
        }

        .fa-icon-picker__option:hover {
            background-color: var(--bg-tertiary, #f0f2f5);
            border-color: var(--accent-primary, #0099ff);
            color: var(--accent-primary, #0099ff);
        }

        .fa-icon-picker__option.selected {
            background-color: var(--accent-primary, #0099ff);
            color: #fff;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    (function() {
        function initPicker(root) {
            const id = root.dataset.pickerId;
            const input = document.getElementById(id);
            const preview = document.getElementById(id + '_preview');
            const panel = document.getElementById(id + '_panel');
            const search = document.getElementById(id + '_search');
            const grid = document.getElementById(id + '_grid');
            const trigger = root.querySelector('[data-toggle-picker]');

            function marcarSeleccionado() {
                grid.querySelectorAll('.fa-icon-picker__option').forEach(function(btn) {
                    btn.classList.toggle('selected', btn.dataset.icon === input.value.trim());
                });
            }

            function actualizarPreview() {
                preview.className = input.value.trim() || 'fas fa-folder';
                marcarSeleccionado();
            }

            function abrirPanel() {
                document.querySelectorAll('.fa-icon-picker__panel.show').forEach(function(p) {
                    if (p !== panel) p.classList.remove('show');
                });
                panel.classList.add('show');
                marcarSeleccionado();
                search.value = '';
                grid.querySelectorAll('.fa-icon-picker__option').forEach(function(btn) {
                    btn.style.display = '';
                });
                search.focus();
            }

            trigger.addEventListener('click', function(e) {
                if (e.target === input) return;
                e.preventDefault();
                panel.classList.contains('show') ? panel.classList.remove('show') : abrirPanel();
            });

            input.addEventListener('focus', abrirPanel);
            input.addEventListener('input', actualizarPreview);

            grid.querySelectorAll('.fa-icon-picker__option').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    input.value = btn.dataset.icon;
                    actualizarPreview();
                    panel.classList.remove('show');
                });
            });

            search.addEventListener('input', function() {
                const term = search.value.trim().toLowerCase();
                grid.querySelectorAll('.fa-icon-picker__option').forEach(function(btn) {
                    const texto = btn.dataset.icon.replace('fas fa-', '').replace(/-/g, ' ');
                    btn.style.display = texto.includes(term) ? '' : 'none';
                });
            });

            document.addEventListener('click', function(e) {
                if (!root.contains(e.target)) {
                    panel.classList.remove('show');
                }
            });

            actualizarPreview();
        }

        document.querySelectorAll('.fa-icon-picker').forEach(initPicker);
    })();
    </script>
    @endpush
@endonce
