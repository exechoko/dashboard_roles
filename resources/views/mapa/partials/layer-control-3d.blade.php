{{-- mapa/partials/layer-control-3d.blade.php --}}
<div class="custom-layer-control" id="customLayerControl3d">
    <div class="layer-control-header" onclick="toggleLayerControl3D()">
        <h6>Control de Capas</h6>
        <span class="layer-control-toggle" id="layerControlToggle3d">▼</span>
    </div>
    <div class="layer-control-content" id="layerControlContent3d">
        {{-- Cámaras (todas) --}}
        @can('ver-camara')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras" checked
                        onchange="toggleLayer3D('camaras', this.checked)">
                    <span class="slider camaras"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras')">Todas las Cámaras</span>
            </div>
        @endcan

        {{-- Comisarías --}}
        @can('ver-dependencia')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-comisarias" checked
                        onchange="toggleLayer3D('comisarias', this.checked)">
                    <span class="slider comisarias"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-comisarias')">Comisarías</span>
            </div>
        @endcan

        {{-- Cámaras por tipo --}}
        @can('ver-camara')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-fijas" checked
                        onchange="toggleLayer3D('camaras-fijas', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-fijas')">Cámaras Fijas</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-fr" checked
                        onchange="toggleLayer3D('camaras-fr', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-fr')">Cámaras FR</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-lpr" checked
                        onchange="toggleLayer3D('camaras-lpr', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-lpr')">Cámaras LPR</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-domos" checked
                        onchange="toggleLayer3D('camaras-domos', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-domos')">Cámaras Domos</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-domos-duales" checked
                        onchange="toggleLayer3D('camaras-domos-duales', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-domos-duales')">Cámaras Domos Duales</span>
            </div>

            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-camaras-bde" checked
                        onchange="toggleLayer3D('camaras-bde', this.checked)">
                    <span class="slider camaras-tipo"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-camaras-bde')">Cámaras BDE</span>
            </div>
        @endcan

        {{-- Sitios Inactivos --}}
        <div class="layer-item">
            <label class="switch">
                <input type="checkbox" id="switch3d-sitios" checked onchange="toggleLayer3D('sitios', this.checked)">
                <span class="slider sitios"></span>
            </label>
            <span class="layer-label" onclick="toggleSwitch3D('switch3d-sitios')">Sitios Inactivos</span>
        </div>

        {{-- Antenas --}}
        @can('ver-dependencia')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-antenas" checked onchange="toggleLayer3D('antenas', this.checked)">
                    <span class="slider antenas"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-antenas')">Antenas</span>
            </div>
        @endcan

        {{-- Jurisdicciones --}}
        @can('ver-dependencia')
            <div class="layer-item">
                <label class="switch">
                    <input type="checkbox" id="switch3d-jurisdicciones" onchange="toggleLayer3D('jurisdicciones', this.checked)">
                    <span class="slider comisarias"></span>
                </label>
                <span class="layer-label" onclick="toggleSwitch3D('switch3d-jurisdicciones')">Jurisdicciones</span>
            </div>
        @endcan

        {{-- Edificios 3D --}}
        <div class="layer-item">
            <label class="switch">
                <input type="checkbox" id="switch3d-edificios" checked onchange="toggleLayer3D('edificios', this.checked)">
                <span class="slider antenas"></span>
            </label>
            <span class="layer-label" onclick="toggleSwitch3D('switch3d-edificios')">Edificios 3D</span>
        </div>

        {{-- Botón para limpiar --}}
        <div class="layer-item" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
            <button class="btn btn-sm btn-secondary" onclick="clearAllLayers3D()" style="width: 100%;">
                <i class="fas fa-eraser"></i> Limpiar Todo
            </button>
        </div>
    </div>
</div>
