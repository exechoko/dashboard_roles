<script>
    // ========================================
    // VARIABLES GLOBALES PARA CLUSTERING Y SPIDER
    // ========================================
    let clusteringEnabled = true;
    let clusterControlButton = null;
    let spiderfiers = {}; // Almacenar instancias de spider por capa

    // ========================================
    // INICIALIZAR OVERLAPPING MARKER SPIDERFIER
    // ========================================
    function createSpiderfier(map) {
        // Configuración de OverlappingMarkerSpiderfier
        const oms = new OverlappingMarkerSpiderfier(map, {
            keepSpiderfied: true,
            nearbyDistance: 20,
            circleSpiralSwitchover: 9,
            spiralFootSeparation: 28,
            spiralLengthStart: 15,
            spiralLengthFactor: 4,
            legWeight: 2,
            legColors: {
                usual: '#222',
                highlighted: '#f00'
            }
        });

        // Auto-spiderfy: Desplegar automáticamente marcadores superpuestos
        oms.addListener('format', function(marker, status) {
            // Este evento se dispara cuando se añaden marcadores
        });

        return oms;
    }

    // ========================================
    // AUTO-SPIDERFY: DESPLEGAR TODOS LOS MARCADORES SUPERPUESTOS
    // ========================================
    function autoSpiderfyOverlappingMarkers() {
        if (clusteringEnabled) return; // Solo funciona con clustering OFF

        // Esperar un poco para que todos los marcadores se hayan añadido
        setTimeout(() => {
            Object.keys(spiderfiers).forEach(layerName => {
                const oms = spiderfiers[layerName];
                if (!oms) return;

                // Obtener todos los marcadores del spiderfier
                const markers = oms.getMarkers();

                // Agrupar marcadores por posición
                const locationGroups = new Map();

                markers.forEach(marker => {
                    const latLng = marker.getLatLng();
                    const key = `${latLng.lat.toFixed(5)},${latLng.lng.toFixed(5)}`;

                    if (!locationGroups.has(key)) {
                        locationGroups.set(key, []);
                    }
                    locationGroups.get(key).push(marker);
                });

                // Desplegar automáticamente grupos con más de 1 marcador
                locationGroups.forEach((group, location) => {
                    if (group.length > 1) {
                        // Simular click en el primer marcador para desplegar el spider
                        oms.spiderfy(group[0].getLatLng(), group);
                    }
                });
            });

            console.log('✅ Auto-spider activado para marcadores superpuestos');
        }, 500);
    }

    // ========================================
    // CONFIGURACIÓN DE MARKERCLUSTERGROUP CON SPIDER
    // ========================================
    function createClusterGroupWithSpider() {
        return L.markerClusterGroup({
            maxClusterRadius: 80,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: true,
            zoomToBoundsOnClick: true,
            spiderfyDistanceMultiplier: 1.5,
            spiderLegPolylineOptions: {
                weight: 2,
                color: '#222',
                opacity: 0.5
            },
            iconCreateFunction: function (cluster) {
                const childCount = cluster.getChildCount();
                let className = 'marker-cluster-';

                if (childCount < 10) {
                    className += 'small';
                } else if (childCount < 50) {
                    className += 'medium';
                } else {
                    className += 'large';
                }

                return L.divIcon({
                    html: '<div><span>' + childCount + '</span></div>',
                    className: 'marker-cluster ' + className,
                    iconSize: L.point(40, 40)
                });
            }
        });
    }

    // ========================================
    // CREAR LAYER GROUP CON SPIDER PARA MARCADORES SUPERPUESTOS
    // ========================================
    function createLayerGroupWithSpider(layerName) {
        const layerGroup = L.layerGroup();

        // Crear una instancia de spiderfier para esta capa
        const oms = createSpiderfier(mymap);
        spiderfiers[layerName] = oms;

        // Override del método addLayer para añadir markers al spiderfier
        const originalAddLayer = layerGroup.addLayer.bind(layerGroup);
        layerGroup.addLayer = function(layer) {
            originalAddLayer(layer);
            if (layer instanceof L.Marker) {
                oms.addMarker(layer);
            }
            return this;
        };

        return layerGroup;
    }

    // ========================================
    // RECREAR GRUPOS DE CLUSTERS CON NUEVA CONFIGURACIÓN
    // ========================================
    function recreateClusterGroups() {
        // Guardar las capas actuales en el mapa
        const layersOnMap = {
            todas: mymap.hasLayer(capa2),
            lpr: mymap.hasLayer(capaLPR),
            fr: mymap.hasLayer(capaFR),
            fijas: mymap.hasLayer(capaFija),
            domos: mymap.hasLayer(capaDomo),
            domosDuales: mymap.hasLayer(capaDomoDual),
            bde: mymap.hasLayer(capaBDE)
        };

        // Remover capas actuales
        mymap.removeLayer(capa2);
        mymap.removeLayer(capaLPR);
        mymap.removeLayer(capaFR);
        mymap.removeLayer(capaFija);
        mymap.removeLayer(capaDomo);
        mymap.removeLayer(capaDomoDual);
        mymap.removeLayer(capaBDE);

        // Limpiar spiderfiers anteriores
        Object.values(spiderfiers).forEach(oms => {
            if (oms && oms.clearMarkers) {
                oms.clearMarkers();
            }
        });
        spiderfiers = {};

        // Recrear grupos con o sin clustering
        if (clusteringEnabled) {
            marcadores = createClusterGroupWithSpider();
            markersCamarasLPR = createClusterGroupWithSpider();
            markersCamarasFR = createClusterGroupWithSpider();
            markersCamarasFijas = createClusterGroupWithSpider();
            markersCamarasDomos = createClusterGroupWithSpider();
            markersCamarasDomosDuales = createClusterGroupWithSpider();
            markersBDE = createClusterGroupWithSpider();
        } else {
            // Sin clustering - usar LayerGroup con Spider para marcadores superpuestos
            marcadores = createLayerGroupWithSpider('marcadores');
            markersCamarasLPR = createLayerGroupWithSpider('lpr');
            markersCamarasFR = createLayerGroupWithSpider('fr');
            markersCamarasFijas = createLayerGroupWithSpider('fijas');
            markersCamarasDomos = createLayerGroupWithSpider('domos');
            markersCamarasDomosDuales = createLayerGroupWithSpider('domosDuales');
            markersBDE = createLayerGroupWithSpider('bde');
        }

        // Recrear las capas
        capa2 = L.geoJSON();
        capaLPR = L.layerGroup();
        capaFR = L.layerGroup();
        capaFija = L.layerGroup();
        capaDomo = L.layerGroup();
        capaDomoDual = L.layerGroup();
        capaBDE = L.layerGroup();

        // Recargar los marcadores
        loadCameraMarkers();

        // Restaurar las capas que estaban visibles
        if (layersOnMap.todas) mymap.addLayer(capa2);
        if (layersOnMap.lpr) mymap.addLayer(capaLPR);
        if (layersOnMap.fr) mymap.addLayer(capaFR);
        if (layersOnMap.fijas) mymap.addLayer(capaFija);
        if (layersOnMap.domos) mymap.addLayer(capaDomo);
        if (layersOnMap.domosDuales) mymap.addLayer(capaDomoDual);
        if (layersOnMap.bde) mymap.addLayer(capaBDE);

        // Si clustering está OFF, auto-desplegar marcadores superpuestos
        if (!clusteringEnabled) {
            autoSpiderfyOverlappingMarkers();
        }
    }

    // ========================================
    // ALTERNAR CLUSTERING
    // ========================================
    function toggleClustering() {
        clusteringEnabled = !clusteringEnabled;

        // Actualizar botón
        updateClusterButton();

        // Recrear grupos y marcadores
        recreateClusterGroups();

        // Mostrar notificación
        const message = clusteringEnabled
            ? 'Clustering activado - Las cámaras se agruparán por proximidad'
            : 'Clustering desactivado - Marcadores superpuestos se mostrarán en spider';

        showNotification(message, clusteringEnabled ? 'success' : 'info');
    }

    // ========================================
    // ACTUALIZAR APARIENCIA DEL BOTÓN
    // ========================================
    function updateClusterButton() {
        const button = document.getElementById('toggleClusterBtn');
        if (!button) return;

        if (clusteringEnabled) {
            button.classList.remove('btn-secondary');
            button.classList.add('btn-success');
            button.innerHTML = '<i class="fas fa-object-group"></i> Clustering ON';
            button.title = 'Click para desactivar agrupación de cámaras';
        } else {
            button.classList.remove('btn-success');
            button.classList.add('btn-secondary');
            button.innerHTML = '<i class="fas fa-object-ungroup"></i> Clustering OFF';
            button.title = 'Click para activar agrupación de cámaras';
        }
    }

    // ========================================
    // INICIALIZAR CONTROL DE CLUSTERING
    // ========================================
    function initClusteringControl() {
        // Esperar a que el mapa esté listo
        if (!mymap) {
            console.error('El mapa no está inicializado');
            setTimeout(initClusteringControl, 500);
            return;
        }

        // Agregar estilos CSS
        if (!document.getElementById('cluster-styles')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'cluster-styles';
            styleElement.innerHTML = clusterStyles;
            document.head.appendChild(styleElement);
        }

        // Agregar botón de control
        addClusterControlButton();

        console.log('✅ Control de clustering inicializado');
    }

    // ========================================
    // AGREGAR CONTROL DE CLUSTERING AL MAPA
    // ========================================
    function addClusterControlButton() {
        const clusterControl = L.control({ position: 'topleft' });

        clusterControl.onAdd = function () {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
            div.innerHTML = `
            <button id="toggleClusterBtn"
                    class="btn btn-success"
                    style="padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; margin-top: 5px;"
                    title="Click para desactivar agrupación de cámaras">
                <i class="fas fa-object-group"></i> Clustering ON
            </button>
        `;
            div.style.backgroundColor = 'transparent';
            div.style.border = 'none';
            L.DomEvent.disableClickPropagation(div);
            return div;
        };

        clusterControl.addTo(mymap);
        clusterControlButton = clusterControl;

        // Agregar evento al botón
        setTimeout(() => {
            const button = document.getElementById('toggleClusterBtn');
            if (button) {
                button.addEventListener('click', toggleClustering);
            }
        }, 100);
    }

    // ========================================
    // FUNCIÓN AUXILIAR PARA DEBUGGING
    // ========================================
    function debugClusterInfo() {
        console.log('=== DEBUG CLUSTER INFO ===');
        console.log('Clustering enabled:', clusteringEnabled);
        console.log('Marcadores totales:', marcadores.getLayers ? marcadores.getLayers().length : 'N/A');
        console.log('Clusters visibles:', document.querySelectorAll('.marker-cluster').length);

        // Contar cámaras por ubicación exacta
        const locations = new Map();
        if (marcadores.eachLayer) {
            marcadores.eachLayer(function (marker) {
                if (marker instanceof L.Marker) {
                    const key = `${marker.getLatLng().lat},${marker.getLatLng().lng}`;
                    locations.set(key, (locations.get(key) || 0) + 1);
                }
            });
        }

        console.log('Ubicaciones únicas:', locations.size);
        console.log('Ubicaciones con múltiples cámaras:');
        locations.forEach((count, location) => {
            if (count > 1) {
                console.log(`  ${location}: ${count} cámaras`);
            }
        });
        console.log('Spiderfiers activos:', Object.keys(spiderfiers).length);
        console.log('========================');
    }

    // Exponer función de debug globalmente
    window.debugClusterInfo = debugClusterInfo;

    const clusterStyles = `
    /* Estilos base para clusters */
    .marker-cluster-small {
        background-color: rgba(181, 226, 140, 0.6);
    }

    .marker-cluster-small div {
        background-color: rgba(110, 204, 57, 0.6);
    }

    .marker-cluster-medium {
        background-color: rgba(241, 211, 87, 0.6);
    }

    .marker-cluster-medium div {
        background-color: rgba(240, 194, 12, 0.6);
    }

    .marker-cluster-large {
        background-color: rgba(253, 156, 115, 0.6);
    }

    .marker-cluster-large div {
        background-color: rgba(241, 128, 23, 0.6);
    }

    /* Estilos comunes para todos los clusters */
    .marker-cluster {
        background-clip: padding-box;
        border-radius: 20px;
    }

    .marker-cluster div {
        width: 30px;
        height: 30px;
        margin-left: 5px;
        margin-top: 5px;
        text-align: center;
        border-radius: 15px;
        font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
        font-weight: bold;
    }

    .marker-cluster span {
        line-height: 30px;
        color: #fff;
    }

    /* Animación para spider legs */
    .leaflet-cluster-spider-leg {
        animation: spiderLegFade 0.3s ease-in-out;
    }

    /* Estilos para OverlappingMarkerSpiderfier */
    .oms-spider-leg {
        stroke: #222;
        stroke-width: 2;
        stroke-opacity: 0.5;
    }

    @keyframes spiderLegFade {
        from {
            opacity: 0;
            stroke-width: 0;
        }

        to {
            opacity: 0.5;
            stroke-width: 2;
        }
    }

    /* Estilos para modo oscuro */
    [data-theme="dark"] .marker-cluster-small {
        background-color: rgba(110, 204, 57, 0.8);
    }

    [data-theme="dark"] .marker-cluster-small div {
        background-color: rgba(110, 204, 57, 0.9);
    }

    [data-theme="dark"] .marker-cluster-medium {
        background-color: rgba(240, 194, 12, 0.8);
    }

    [data-theme="dark"] .marker-cluster-medium div {
        background-color: rgba(240, 194, 12, 0.9);
    }

    [data-theme="dark"] .marker-cluster-large {
        background-color: rgba(241, 128, 23, 0.8);
    }

    [data-theme="dark"] .marker-cluster-large div {
        background-color: rgba(241, 128, 23, 0.9);
    }

    [data-theme="dark"] .oms-spider-leg {
        stroke: #ffffff;
        stroke-opacity: 0.7;
    }

    /* Botón de clustering responsive */
    #toggleClusterBtn {
        transition: all 0.3s ease;
        font-size: 13px;
        min-width: 140px;
    }

    #toggleClusterBtn:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    [data-theme="dark"] #toggleClusterBtn {
        background-color: var(--card-bg, #1e1e1e) !important;
        color: var(--text-primary, #ffffff) !important;
    }

    [data-theme="dark"] #toggleClusterBtn.btn-success {
        background-color: #28a745 !important;
    }

    [data-theme="dark"] #toggleClusterBtn.btn-secondary {
        background-color: #6c757d !important;
    }

    @media (max-width: 768px) {
        #toggleClusterBtn {
            font-size: 11px;
            padding: 6px 8px !important;
            min-width: 120px;
        }

        #toggleClusterBtn i {
            font-size: 10px;
        }
    }
`;
</script>
