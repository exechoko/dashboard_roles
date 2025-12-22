<div class="custom-layer-control" id="customLayerControl">
    <div class="layer-control-header" onclick="toggleLayerControlPlanoEdificio()">
        <h6>Tipos de Dispositivos</h6>
        <span class="layer-control-toggle" id="layerControlTogglePlanoEdificio">▼</span>
    </div>
    <div class="layer-control-content" id="layerControlContentPlanoEdificio">
        @php
            $tiposDispositivos = \App\Models\DispositivoEdificio::getTiposDispositivos();
        @endphp

        @foreach($tiposDispositivos as $tipo => $info)
            <div class="layer-item" data-tipo="{{ $tipo }}">
                <label class="switch">
                    <input type="checkbox" class="layer-toggle" id="switch-{{ $tipo }}" data-tipo="{{ $tipo }}" checked>
                    <span class="slider" style="background-color: {{ $info['color'] }}"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitchPlanoEdificio('switch-{{ $tipo }}')">{{ $info['label'] }}</span>
                <span class="layer-count" data-tipo-count="{{ $tipo }}">{{ $stats['por_tipo'][$tipo] ?? 0 }}</span>
            </div>
        @endforeach

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
</script>
