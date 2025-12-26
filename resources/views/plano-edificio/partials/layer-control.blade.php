<div class="custom-layer-control" id="customLayerControl">
    <div class="layer-control-header" onclick="toggleLayerControlPlanoEdificio()">
        <h6>Tipos de Dispositivos</h6>
        <span class="layer-control-toggle" id="layerControlTogglePlanoEdificio">▼</span>
    </div>
    <div class="layer-control-content" id="layerControlContentPlanoEdificio">
        @php
            $tiposDispositivos = \App\Models\DispositivoEdificio::getTiposDispositivos();
            $groups = [
                'PCs' => ['pc', 'puesto_cecoco', 'puesto_video'],
                'Servidores' => ['servidor', 'servidor_cecoco', 'servidor_nebula'],
                'CCTV' => ['camara_interna', 'nvr'],
                'Red' => ['router', 'switch', 'access_point'],
            ];
            $agrupados = collect($groups)->flatten()->all();
        @endphp

        @foreach($groups as $groupName => $tipos)
            @php
                $tiposValidos = array_filter($tipos, fn($tipo) => isset($tiposDispositivos[$tipo]));
                $groupId = \Illuminate\Support\Str::slug($groupName, '-');
            @endphp
            @if(!empty($tiposValidos))
                <div class="layer-group" data-group="{{ $groupId }}">
                    <button type="button"
                            class="layer-group__header"
                            onclick="toggleLayerGroup('{{ $groupId }}')">
                        <span>{{ $groupName }}</span>
                        <i class="fas fa-chevron-down" id="layer-group-icon-{{ $groupId }}"></i>
                    </button>
                    <div class="layer-group__content" id="layer-group-content-{{ $groupId }}">
                        @foreach($tiposValidos as $tipo)
                            @php $info = $tiposDispositivos[$tipo]; @endphp
                            <div class="layer-item" data-tipo="{{ $tipo }}">
                                <label class="switch">
                                    <input type="checkbox" class="layer-toggle" id="switch-{{ $tipo }}" data-tipo="{{ $tipo }}" checked>
                                    <span class="slider" style="background-color: {{ $info['color'] }}"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitchPlanoEdificio('switch-{{ $tipo }}')">{{ $info['label'] }}</span>
                                <span class="layer-count" data-tipo-count="{{ $tipo }}">{{ $stats['por_tipo'][$tipo] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @php
            $otrosTipos = array_diff(array_keys($tiposDispositivos), $agrupados);
        @endphp

        @if(!empty($otrosTipos))
            <div class="layer-group" data-group="otros">
                <button type="button"
                        class="layer-group__header"
                        onclick="toggleLayerGroup('otros')">
                    <span>Otros</span>
                    <i class="fas fa-chevron-down" id="layer-group-icon-otros"></i>
                </button>
                <div class="layer-group__content" id="layer-group-content-otros">
                    @foreach($otrosTipos as $tipo)
                        @php $info = $tiposDispositivos[$tipo]; @endphp
                        <div class="layer-item" data-tipo="{{ $tipo }}">
                            <label class="switch">
                                <input type="checkbox" class="layer-toggle" id="switch-{{ $tipo }}" data-tipo="{{ $tipo }}" checked>
                                <span class="slider" style="background-color: {{ $info['color'] }}"></span>
                            </label>
                            <span class="layer-label" onclick="toggleSwitchPlanoEdificio('switch-{{ $tipo }}')">{{ $info['label'] }}</span>
                            <span class="layer-count" data-tipo-count="{{ $tipo }}">{{ $stats['por_tipo'][$tipo] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="layer-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.08);">
            <label class="switch">
                <input type="checkbox" id="show-inactive" checked>
                <span class="slider" style="background-color: #6c757d"></span>
            </label>
            <span class="layer-label" onclick="toggleSwitchPlanoEdificio('show-inactive')">Mostrar inactivos</span>
        </div>

        <div class="layer-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.08);">
            <button class="btn btn-sm btn-secondary" onclick="selectAllLayersPlanoEdificio()" style="width: 100%; margin-bottom: 6px;">
                <i class="fas fa-check-double"></i> Mostrar todos
            </button>
            <button class="btn btn-sm btn-secondary" onclick="deselectAllLayersPlanoEdificio()" style="width: 100%;">
                <i class="fas fa-times"></i> Ocultar todos
            </button>
        </div>
    </div>
</div>

<script>
// Control de capas (overlay)
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para toggles de capas
    document.querySelectorAll('#customLayerControl .layer-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const tipo = this.dataset.tipo;
            const isActive = this.checked;
            toggleLayer(tipo, isActive);
        });
    });

    // Toggle para dispositivos inactivos
    document.getElementById('show-inactive').addEventListener('change', function() {
        const showInactive = this.checked;
        toggleInactiveDevices(showInactive);
    });

    // Inicializar grupos colapsables
    document.querySelectorAll('.layer-group').forEach(group => {
        const groupId = group.dataset.group;
        if (groupId) {
            setLayerGroupState(groupId, false);
        }
    });

});

function toggleLayerControlPlanoEdificio() {
    const content = document.getElementById('layerControlContentPlanoEdificio');
    const toggle = document.getElementById('layerControlTogglePlanoEdificio');

    if (!content || !toggle) return;

    const isHidden = content.style.display === 'none';
    content.style.display = isHidden ? 'block' : 'none';
    toggle.textContent = isHidden ? '▼' : '▲';
}

function toggleSwitchPlanoEdificio(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.checked = !el.checked;
    el.dispatchEvent(new Event('change'));
}

function toggleLayer(tipo, isActive) {
    const layerItem = document.querySelector(`.layer-item[data-tipo="${tipo}"]`);
    const dispositivos = document.querySelectorAll(`.device-icon[data-tipo="${tipo}"]`);

    if (isActive) {
        layerItem.classList.add('active');
        dispositivos.forEach(device => {
            device.style.display = 'flex';
        });
    } else {
        layerItem.classList.remove('active');
        dispositivos.forEach(device => {
            device.style.display = 'none';
        });
    }

    // Actualizar contador
    updateLayerCounts();
}

function toggleInactiveDevices(showInactive) {
    const inactiveDevices = document.querySelectorAll('.device-icon.inactive');

    inactiveDevices.forEach(device => {
        const layerToggle = document.querySelector(`.layer-toggle[data-tipo="${device.dataset.tipo}"]`);
        const layerActive = layerToggle && layerToggle.checked;

        if (showInactive && layerActive) {
            device.style.display = 'flex';
        } else {
            device.style.display = 'none';
        }
    });
}

function updateLayerCounts() {
    // Actualizar contadores de dispositivos visibles
    document.querySelectorAll('.layer-count').forEach(counter => {
        const tipo = counter.dataset.tipoCount;
        const layerToggle = document.querySelector(`.layer-toggle[data-tipo="${tipo}"]`);
        const showInactive = document.getElementById('show-inactive').checked;

        if (layerToggle && layerToggle.checked) {
            const allDevices = document.querySelectorAll(`.device-icon[data-tipo="${tipo}"]`);
            const activeDevices = showInactive ?
                allDevices :
                Array.from(allDevices).filter(d => !d.classList.contains('inactive'));

            counter.textContent = activeDevices.length;
        } else {
            counter.textContent = '0';
        }
    });
}

function selectAllLayersPlanoEdificio() {
    document.querySelectorAll('#customLayerControl .layer-toggle').forEach(toggle => {
        toggle.checked = true;
        toggle.dispatchEvent(new Event('change'));
    });
}

function deselectAllLayersPlanoEdificio() {
    document.querySelectorAll('#customLayerControl .layer-toggle').forEach(toggle => {
        toggle.checked = false;
        toggle.dispatchEvent(new Event('change'));
    });
}

function toggleLayerGroup(groupId) {
    const content = document.getElementById(`layer-group-content-${groupId}`);

    if (!content) return;

    const isOpen = content.classList.contains('is-open');
    setLayerGroupState(groupId, !isOpen);
}

function setLayerGroupState(groupId, open) {
    const content = document.getElementById(`layer-group-content-${groupId}`);
    const icon = document.getElementById(`layer-group-icon-${groupId}`);
    const header = document.querySelector(`.layer-group[data-group="${groupId}"] .layer-group__header`);

    if (!content || !icon || !header) return;

    if (open) {
        content.classList.add('is-open');
        header.classList.add('open');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.remove('is-open');
        header.classList.remove('open');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>
