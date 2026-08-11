<div class="custom-layer-control" id="customLayerControl3d">
    <div class="layer-control-header" onclick="toggleLayerControl3D()">
        <h6>Tipos de Dispositivos</h6>
        <span class="layer-control-toggle" id="layerControlToggle3d">▼</span>
    </div>
    <div class="layer-control-content" id="layerControlContent3d">
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
                            onclick="toggleLayerGroup3D('{{ $groupId }}')">
                        <span>{{ $groupName }}</span>
                        <i class="fas fa-chevron-down" id="layer3d-group-icon-{{ $groupId }}"></i>
                    </button>
                    <div class="layer-group__content" id="layer3d-group-content-{{ $groupId }}">
                        @foreach($tiposValidos as $tipo)
                            @php $info = $tiposDispositivos[$tipo]; @endphp
                            <div class="layer-item" data-tipo="{{ $tipo }}">
                                <label class="switch">
                                    <input type="checkbox" class="layer-toggle-3d" id="switch3d-{{ $tipo }}" data-tipo="{{ $tipo }}" checked>
                                    <span class="slider" style="background-color: {{ $info['color'] }}"></span>
                                </label>
                                <span class="layer-label" onclick="toggleSwitch3D('switch3d-{{ $tipo }}')">{{ $info['label'] }}</span>
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
                        onclick="toggleLayerGroup3D('otros')">
                    <span>Otros</span>
                    <i class="fas fa-chevron-down" id="layer3d-group-icon-otros"></i>
                </button>
                <div class="layer-group__content" id="layer3d-group-content-otros">
                    @foreach($otrosTipos as $tipo)
                        @php $info = $tiposDispositivos[$tipo]; @endphp
                        <div class="layer-item" data-tipo="{{ $tipo }}">
                            <label class="switch">
                                <input type="checkbox" class="layer-toggle-3d" id="switch3d-{{ $tipo }}" data-tipo="{{ $tipo }}" checked>
                                <span class="slider" style="background-color: {{ $info['color'] }}"></span>
                            </label>
                            <span class="layer-label" onclick="toggleSwitch3D('switch3d-{{ $tipo }}')">{{ $info['label'] }}</span>
                            <span class="layer-count" data-tipo-count="{{ $tipo }}">{{ $stats['por_tipo'][$tipo] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="layer-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.08);">
            <label class="switch">
                <input type="checkbox" id="show-inactive-3d" checked>
                <span class="slider" style="background-color: #6c757d"></span>
            </label>
            <span class="layer-label" onclick="toggleSwitch3D('show-inactive-3d')">Mostrar inactivos</span>
        </div>

        <div class="layer-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.08);">
            <button class="btn btn-sm btn-secondary" onclick="selectAllLayers3D()" style="width: 100%; margin-bottom: 6px;">
                <i class="fas fa-check-double"></i> Mostrar todos
            </button>
            <button class="btn btn-sm btn-secondary" onclick="deselectAllLayers3D()" style="width: 100%;">
                <i class="fas fa-times"></i> Ocultar todos
            </button>
        </div>
    </div>
</div>
