<div class="custom-layer-control" id="customLayerControl">
    <div class="layer-control-header" onclick="toggleLayerControl()">
        <h6>Control de Capas</h6>
        <span class="layer-control-toggle" id="layerControlToggle">▼</span>
    </div>
    <div class="layer-control-content" id="layerControlContent">
        {{-- Cámaras (todas) --}}
        @can('ver-camara')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras" checked
                        onchange="toggleLayer('camaras', this.checked)">
                    <span class="slider camaras"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras')">Todas las Cámaras</span>
            </div>
        @endcan

        {{-- Comisarías --}}
        @can('ver-dependencia')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-comisarias"
                        onchange="toggleLayer('comisarias', this.checked)">
                    <span class="slider comisarias"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-comisarias')">Comisarías</span>
            </div>
        @endcan

        {{-- Cámaras por tipo --}}
        @can('ver-camara')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-fijas"
                        onchange="toggleLayer('camaras-fijas', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-fijas')">Cámaras Fijas</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-fr"
                        onchange="toggleLayer('camaras-fr', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-fr')">Cámaras FR</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-lpr"
                        onchange="toggleLayer('camaras-lpr', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-lpr')">Cámaras LPR</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-domos"
                        onchange="toggleLayer('camaras-domos', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-domos')">Cámaras Domos</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-domos-duales"
                        onchange="toggleLayer('camaras-domos-duales', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-domos-duales')">Cámaras Domos Duales</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-camaras-bde"
                        onchange="toggleLayer('camaras-bde', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-camaras-bde')">Cámaras BDE</span>
            </div>
        @endcan

        {{-- Sitios Inactivos --}}
        <div class="layer-item">
            <label class="switch">
                <input type="checkbox" id="switch-sitios" onchange="toggleLayer('sitios', this.checked)">
                <span class="slider sitios"></span>
            </label>
            <span class="layer-label" onclick="toggleSwitch('switch-sitios')">Sitios Inactivos</span>
        </div>

        {{-- Antenas --}}
        @can('ver-dependencia')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch-antenas" onchange="toggleLayer('antenas', this.checked)">
                    <span class="slider antenas"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch('switch-antenas')">Antenas</span>
            </div>
        @endcan

        {{-- Botón para limpiar --}}
        <div class="layer-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
            <button class="btn btn-sm btn-secondary" onclick="clearAllLayers()" style="width: 100%;">
                <i class="fas fa-eraser"></i> Limpiar Todo
            </button>
        </div>
    </div>
</div>
