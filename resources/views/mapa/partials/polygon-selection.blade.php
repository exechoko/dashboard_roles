<script>
    // ========================================
    // VARIABLES GLOBALES PARA EL SISTEMA DE POLÍGONOS
    // ========================================
    let drawingEnabled = false;
    let currentPolygon = null;
    let selectedCamerasInPolygon = [];
    let selectedSitiosInPolygon = [];
    let polygonDrawControl = null;
    let isFullscreen = false;

    // ========================================
    // CONFIGURACIÓN DE SEPARACIÓN DE MARCADORES (PDF)
    // Ajustar estos valores para regular la separación
    // ========================================
    const MARKER_SEPARATION_CONFIG = {
        // Tolerancia para detectar marcadores en "mismo lugar" (en grados)
        // 0.0001 ≈ 10 metros
        tolerance: 0.0001,

        // Radio base de separación (en grados)
        // 0.0005 ≈ 50 metros
        baseRadius: 0.00025,

        // Incremento de radio por cada marcador adicional (en grados)
        // 0.00015 ≈ 15 metros extra por marcador
        radiusIncrement: 0.00005,

        // Rotación inicial del patrón circular (en radianes)
        // Math.PI / 4 = 45 grados
        rotationOffset: Math.PI / 4
    };

    // ========================================
    // FUNCIÓN GLOBAL PARA VERIFICAR PUNTO EN POLÍGONO
    // ========================================
    function isPointInPolygon(point, polygonPoints) {
        const x = point.lat;
        const y = point.lng;
        let inside = false;

        for (let i = 0, j = polygonPoints.length - 1; i < polygonPoints.length; j = i++) {
            const xi = polygonPoints[i].lat;
            const yi = polygonPoints[i].lng;
            const xj = polygonPoints[j].lat;
            const yj = polygonPoints[j].lng;

            const intersect = ((yi > y) !== (yj > y)) &&
                (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }

        return inside;
    }

    // ========================================
    // DETECTAR MODO FULLSCREEN
    // ========================================
    function detectFullscreen() {
        isFullscreen = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement ||
            document.fullscreen ||
            document.webkitIsFullScreen ||
            document.mozFullScreen
        );

        return isFullscreen;
    }

    // ========================================
    // AGREGAR ESTILOS PARA MODAL EN FULLSCREEN
    // ========================================
    function addFullscreenModalStyles() {
        const styleId = 'fullscreen-modal-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Modal overlay en fullscreen */
            .leaflet-container:fullscreen .modal-backdrop {
                background-color: rgba(0, 0, 0, 0.9) !important;
                z-index: 10000 !important;
            }

            /* Modal en fullscreen */
            .leaflet-container:fullscreen .modal {
                z-index: 10001 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .leaflet-container:fullscreen .modal-dialog {
                margin: 20px auto !important;
                max-height: 90vh !important;
                max-width: 95vw !important;
            }

            .leaflet-container:fullscreen .modal-content {
                max-height: 90vh !important;
                overflow: hidden !important;
            }

            /* Asegurar visibilidad */
            .leaflet-container:-webkit-full-screen .modal {
                z-index: 10001 !important;
            }

            .leaflet-container:-moz-full-screen .modal {
                z-index: 10001 !important;
            }

            .leaflet-container:-ms-fullscreen .modal {
                z-index: 10001 !important;
            }

            /* Forzar modal por encima del mapa */
            .modal.show {
                display: block !important;
            }
        `;
        document.head.appendChild(style);
    }

    // ========================================
    // INICIALIZACIÓN DEL SISTEMA DE POLÍGONOS
    // ========================================
    function initPolygonSelectionSystem() {
        // Esperar a que el mapa esté listo
        if (!mymap) {
            console.error('El mapa no está inicializado');
            return;
        }

        // Agregar estilos para fullscreen
        addFullscreenModalStyles();

        // Configurar handlers de fullscreen
        setupFullscreenHandlers();

        // Agregar control de dibujo de polígonos
        addPolygonDrawControl();

        // Agregar eventos de mapa
        setupMapClickEvents();

        console.log('✅ Sistema de polígonos inicializado');
    }

    // ========================================
    // CONTROL DE DIBUJO DE POLÍGONOS
    // ========================================
    function addPolygonDrawControl() {
        // Crear botón de control personalizado
        const polygonControlDiv = L.control({ position: 'bottomright' });

        polygonControlDiv.onAdd = function (map) {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
            <button id="togglePolygonDraw"
                    class="btn btn-warning"
                    style="padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer;"
                    title="Dibujar polígono para seleccionar cámaras">
                <i class="fas fa-draw-polygon"></i> Seleccionar Área
            </button>
        `;

            // Prevenir propagación de clicks
            L.DomEvent.disableClickPropagation(div);

            return div;
        };

        polygonControlDiv.addTo(mymap);

        // Agregar evento al botón
        document.getElementById('togglePolygonDraw').addEventListener('click', togglePolygonDrawing);
    }

    // ========================================
    // ALTERNAR MODO DE DIBUJO
    // ========================================
    function togglePolygonDrawing() {
        const button = document.getElementById('togglePolygonDraw');

        if (!drawingEnabled) {
            // Activar modo de dibujo
            drawingEnabled = true;
            button.classList.remove('btn-warning');
            button.classList.add('btn-danger');
            button.innerHTML = '<i class="fas fa-times"></i> Cancelar Selección';

            // Cambiar cursor
            mymap.getContainer().style.cursor = 'crosshair';

            // Cerrar cualquier modal abierto
            $('#camerasPolygonModal').modal('hide');

            // Mostrar instrucciones
            showNotification('Haz clic en el mapa para dibujar el polígono. Doble clic para cerrar.', 'info');

            // Iniciar dibujo
            startPolygonDrawing();
        } else {
            // Desactivar modo de dibujo
            cancelPolygonDrawing();
        }
    }

    // ========================================
    // VERIFICAR SI ESTÁ EN MODO FULLSCREEN
    // ========================================
    function isFullscreenMode() {
        return !!(document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement ||
            document.fullscreen ||
            document.webkitIsFullScreen ||
            document.mozFullScreen);
    }

    // ========================================
    // MANEJAR CAMBIOS DE FULLSCREEN
    // ========================================
    function setupFullscreenHandlers() {
        const mapContainer = document.getElementById('map');

        const fullscreenEvents = [
            'fullscreenchange',
            'webkitfullscreenchange',
            'mozfullscreenchange',
            'MSFullscreenChange'
        ];

        fullscreenEvents.forEach(event => {
            document.addEventListener(event, function () {
                setTimeout(() => {
                    detectFullscreen();

                    // Si hay un modal abierto, reubicarlo
                    const modal = document.getElementById('camerasPolygonModal');
                    if (modal && modal.classList.contains('show')) {
                        repositionModalForFullscreen();
                    }

                    // Forzar redibujado del mapa
                    if (window.mymap) {
                        window.mymap.invalidateSize();
                    }
                }, 100);
            });
        });
    }

    // ========================================
    // REPOSICIONAR MODAL PARA FULLSCREEN
    // ========================================
    function repositionModalForFullscreen() {
        const modal = document.getElementById('camerasPolygonModal');
        if (!modal) return;

        if (detectFullscreen()) {
            // En fullscreen: mover el modal al body del documento
            if (!document.body.contains(modal)) {
                document.body.appendChild(modal);
            }

            // Estilos para fullscreen
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 10001 !important;
                background: transparent !important;
            `;

            // Asegurar backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.cssText = `
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100vw !important;
                    height: 100vh !important;
                    z-index: 10000 !important;
                    background-color: rgba(0, 0, 0, 0.9) !important;
                `;
            }

            // Ajustar contenido del modal
            const modalDialog = modal.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.cssText = `
                    margin: auto !important;
                    max-height: 90vh !important;
                    max-width: 95vw !important;
                `;
            }

            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.maxHeight = '90vh';
                modalContent.style.overflow = 'hidden';
            }
        } else {
            // En modo normal: restaurar estilos
            modal.style.cssText = '';
            const modalDialog = modal.querySelector('.modal-dialog');
            if (modalDialog) modalDialog.style.cssText = '';
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) modalContent.style.cssText = '';
        }
    }

    // ========================================
    // INICIAR DIBUJO DE POLÍGONO
    // ========================================
    function startPolygonDrawing() {
        // Limpiar polígono anterior si existe
        if (currentPolygon) {
            mymap.removeLayer(currentPolygon);
            currentPolygon = null;
        }

        // Array para almacenar puntos del polígono
        let polygonPoints = [];
        let tempPolyline = null;
        let markers = [];
        let firstMarker = null;

        // Función para verificar si un clic está cerca del primer punto
        const isNearFirstPoint = function (latlng) {
            if (!firstMarker || polygonPoints.length < 3) return false;

            const firstPoint = polygonPoints[0];
            const distance = mymap.distance(latlng, firstPoint);
            const threshold = 20; // 20 metros de tolerancia

            return distance < threshold;
        };

        // Función para completar el polígono
        const completePolygon = function () {
            if (polygonPoints.length < 3) {
                showNotification('Necesitas al menos 3 puntos para crear un polígono', 'warning');
                return;
            }

            // Remover eventos temporales
            mymap.off('click', onMapClick);

            // Remover línea y marcadores temporales
            if (tempPolyline) {
                mymap.removeLayer(tempPolyline);
            }
            markers.forEach(m => mymap.removeLayer(m));

            // LIMPIAR REFERENCIAS
            markers = [];
            firstMarker = null;

            // Crear polígono final
            currentPolygon = L.polygon(polygonPoints, {
                color: '#3388ff',
                fillColor: '#3388ff',
                fillOpacity: 0.2,
                weight: 2
            }).addTo(mymap);

            // Agregar popup al polígono
            const area = L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]);
            currentPolygon.bindPopup(`
            <strong>Área seleccionada</strong><br>
            ${(area / 1000000).toFixed(2)} km²
        `);

            // Buscar cámaras dentro del polígono
            findCamerasInPolygon(currentPolygon);

            // Resetear estado
            drawingEnabled = false;
            const button = document.getElementById('togglePolygonDraw');
            button.classList.remove('btn-danger');
            button.classList.add('btn-warning');
            button.innerHTML = '<i class="fas fa-draw-polygon"></i> Seleccionar Área';
            mymap.getContainer().style.cursor = '';

            // Agregar controles al polígono
            addPolygonControls(currentPolygon);
        };

        // Evento de click en el mapa
        const onMapClick = function (e) {
            if (!drawingEnabled) return;

            // Verificar si se hizo clic cerca del primer punto (para cerrar)
            if (isNearFirstPoint(e.latlng)) {
                completePolygon();
                return;
            }

            // Agregar punto
            polygonPoints.push(e.latlng);

            // Crear o actualizar marcador
            const isFirst = polygonPoints.length === 1;
            const marker = L.circleMarker(e.latlng, {
                radius: isFirst ? 8 : 6,
                fillColor: isFirst ? '#00ff00' : '#ff0000',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(mymap);

            if (isFirst) {
                firstMarker = marker;
                // Agregar tooltip al primer marcador
                marker.bindTooltip('Haz clic aquí para cerrar', {
                    permanent: false,
                    direction: 'top'
                });
            }

            markers.push(marker);

            // Dibujar línea temporal
            if (tempPolyline) {
                mymap.removeLayer(tempPolyline);
            }

            if (polygonPoints.length > 1) {
                // Crear línea temporal con conexión al primer punto si hay 3+ puntos
                const linePoints = polygonPoints.length >= 3
                    ? [...polygonPoints, polygonPoints[0]]
                    : polygonPoints;

                tempPolyline = L.polyline(linePoints, {
                    color: '#ff0000',
                    weight: 2,
                    dashArray: '5, 5'
                }).addTo(mymap);
            }

            // Mostrar instrucción después del tercer punto
            if (polygonPoints.length === 3) {
                showNotification('Haz clic en el punto verde inicial para cerrar el polígono', 'info');
            }
        };

        // Agregar eventos al mapa
        mymap.on('click', onMapClick);
    }

    // ========================================
    // CANCELAR DIBUJO DE POLÍGONO
    // ========================================
    function cancelPolygonDrawing() {
        drawingEnabled = false;

        const button = document.getElementById('togglePolygonDraw');
        button.classList.remove('btn-danger');
        button.classList.add('btn-warning');
        button.innerHTML = '<i class="fas fa-draw-polygon"></i> Seleccionar Área';

        mymap.getContainer().style.cursor = '';
        mymap.off('click');

        showNotification('Selección cancelada', 'info');
    }

    // ========================================
    // BUSCAR CÁMARAS Y SITIOS INACTIVOS DENTRO DEL POLÍGONO
    // ========================================
    function findCamerasInPolygon(polygon) {
        console.log('🔍 Buscando cámaras y sitios inactivos en polígono...');
        selectedCamerasInPolygon = [];
        selectedSitiosInPolygon = [];
        const bounds = polygon.getBounds();
        const seenCameras = new Set();
        const seenSitios = new Set();

        console.log('📦 Bounds del polígono:', bounds);

        // Función mejorada para verificar punto en polígono
        function isPointInPolygon(point, polygonPoints) {
            const x = point.lat;
            const y = point.lng;
            let inside = false;

            for (let i = 0, j = polygonPoints.length - 1; i < polygonPoints.length; j = i++) {
                const xi = polygonPoints[i].lat;
                const yi = polygonPoints[i].lng;
                const xj = polygonPoints[j].lat;
                const yj = polygonPoints[j].lng;

                const intersect = ((yi > y) !== (yj > y)) &&
                    (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }

            return inside;
        }

        // Procesar clusters expandidos Y LayerGroups con Spider
        function processMarkerClusters(layer) {
            if (!layer) return 0;
            let count = 0;

            // Función común para procesar cada marcador
            function processMarker(marker) {
                if (!(marker instanceof L.Marker)) return;

                const position = marker.getLatLng();

                // Usar ID único de la cámara en lugar de coordenadas
                const popup = marker.getPopup();
                let cameraId = marker.options.cameraId ||
                    (popup ? popup.getContent().hashCode() : null) ||
                    `${position.lat.toFixed(6)}_${position.lng.toFixed(6)}_${Date.now()}`;

                if (seenCameras.has(cameraId)) {
                    console.log(`  ⚠️ Cámara duplicada (${cameraId}), saltando...`);
                    return;
                }

                // Verificar si está dentro del polígono
                const polygonPoints = polygon.getLatLngs()[0];
                if (bounds.contains(position) &&
                    isPointInPolygon(position, polygonPoints)) {
                    console.log(`  ✅ Cámara DENTRO del polígono: ${cameraId}`);

                    // Extraer información de la cámara
                    const cameraInfo = extractCameraInfo(marker);
                    if (cameraInfo) {
                        cameraInfo.id = cameraId; // Agregar ID único
                        selectedCamerasInPolygon.push(cameraInfo);
                        seenCameras.add(cameraId);
                        highlightCameraMarker(marker);
                        count++;
                    }
                }
            }

            // Si es un MarkerClusterGroup, obtener todos los hijos
            if (layer instanceof L.MarkerClusterGroup) {
                console.log(`Procesando MarkerClusterGroup con ${layer.getLayers().length} markers`);
                layer.eachLayer(processMarker);
            }
            // Si es un LayerGroup regular (modo clustering OFF)
            else if (layer instanceof L.LayerGroup) {
                console.log(`Procesando LayerGroup con ${layer.getLayers().length} markers`);
                layer.eachLayer(processMarker);
            }
            // Fallback para cualquier otra capa con eachLayer
            else if (layer.eachLayer) {
                console.log(`Procesando capa genérica...`);
                layer.eachLayer(processMarker);
            }

            return count;
        }

        // Procesar todas las capas
        const allCameraLayers = [
            { name: 'marcadores', layer: marcadores },
            { name: 'LPR', layer: markersCamarasLPR },
            { name: 'FR', layer: markersCamarasFR },
            { name: 'Fijas', layer: markersCamarasFijas },
            { name: 'Domos', layer: markersCamarasDomos },
            { name: 'DomosDuales', layer: markersCamarasDomosDuales },
            { name: 'BDE', layer: markersBDE }
        ];

        let totalFound = 0;
        allCameraLayers.forEach(({ name, layer }) => {
            console.log(`Revisando capa ${name}...`);
            const found = processMarkerClusters(layer);
            totalFound += found;
            console.log(`  → Encontradas en ${name}: ${found}`);
        });

        console.log(`📊 Total de cámaras encontradas: ${selectedCamerasInPolygon.length}`);

        // ========================================
        // BUSCAR SITIOS INACTIVOS
        // ========================================
        console.log('🔍 Buscando sitios inactivos...');

        function processSitiosLayer(layer) {
            if (!layer) return 0;
            let count = 0;

            function processSitioMarker(marker) {
                if (!(marker instanceof L.Marker)) return;

                const position = marker.getLatLng();
                const sitioId = `sitio_${position.lat.toFixed(6)}_${position.lng.toFixed(6)}`;

                if (seenSitios.has(sitioId)) return;

                const polygonPoints = polygon.getLatLngs()[0];
                if (bounds.contains(position) && isPointInPolygon(position, polygonPoints)) {
                    console.log(`  ✅ Sitio inactivo DENTRO del polígono: ${sitioId}`);

                    const sitioInfo = extractSitioInfo(marker);
                    if (sitioInfo) {
                        sitioInfo.id = sitioId;
                        selectedSitiosInPolygon.push(sitioInfo);
                        seenSitios.add(sitioId);
                        highlightSitioMarker(marker);
                        count++;
                    }
                }
            }

            if (layer instanceof L.MarkerClusterGroup) {
                layer.eachLayer(processSitioMarker);
            } else if (layer instanceof L.LayerGroup) {
                layer.eachLayer(processSitioMarker);
            } else if (layer.eachLayer) {
                layer.eachLayer(processSitioMarker);
            }

            return count;
        }

        // Procesar capa de sitios inactivos
        if (typeof marcadoresSitios !== 'undefined') {
            const sitiosFound = processSitiosLayer(marcadoresSitios);
            console.log(`  → Sitios inactivos encontrados: ${sitiosFound}`);
        }

        console.log(`📊 Total de sitios inactivos encontrados: ${selectedSitiosInPolygon.length}`);

        // Mostrar resultados
        const totalItems = selectedCamerasInPolygon.length + selectedSitiosInPolygon.length;
        if (totalItems > 0) {
            showNotification(`${selectedCamerasInPolygon.length} cámaras y ${selectedSitiosInPolygon.length} sitios inactivos encontrados`, 'success');
            showCamerasModal();
        } else {
            showNotification('No se encontraron cámaras ni sitios inactivos en esta área', 'warning');
        }
    }

    // ========================================
    // EXTRAER INFORMACIÓN DEL SITIO INACTIVO
    // ========================================
    function extractSitioInfo(marker) {
        try {
            const popup = marker.getPopup();
            if (!popup) return null;

            const content = popup.getContent();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;

            const titulo = tempDiv.querySelector('h5')?.textContent ||
                tempDiv.querySelector('strong')?.textContent ||
                'Sitio sin nombre';

            const position = marker.getLatLng();

            const info = {
                titulo: titulo,
                latitud: position.lat.toFixed(6),
                longitud: position.lng.toFixed(6),
                estado: 'INACTIVO',
                cartel: extractField(content, 'Cartel:'),
                observaciones: extractFieldText(content, 'Observaciones:'),
                marker: marker
            };

            return info;
        } catch (error) {
            console.error('Error extrayendo info de sitio:', error);
            return null;
        }
    }

    // ========================================
    // EXTRAER CAMPO DEL CONTENIDO HTML
    // ========================================
    function extractField(html, label) {
        const regex = new RegExp(label + '\\s*<b>([^<]*)</b>', 'i');
        const match = html.match(regex);
        return match ? match[1].trim() : 'N/A';
    }

    // ========================================
    // EXTRAER CAMPO DE TEXTO SIN BOLD
    // ========================================
    function extractFieldText(html, label) {
        const regex = new RegExp(label + '\\s*([^<]*)', 'i');
        const match = html.match(regex);
        return match ? match[1].trim() : 'N/A';
    }

    // ========================================
    // RESALTAR MARCADOR DE SITIO
    // ========================================
    function highlightSitioMarker(marker) {
        if (!marker._originalStyle) {
            marker._originalStyle = {
                icon: marker.options.icon
            };
        }

        const icon = marker.options.icon;
        if (icon && icon.options && icon.options.html) {
            const highlightedHtml = icon.options.html.replace(
                /background-color:\s*#dc3545/,
                'background-color: #ff6b6b; border: 3px solid #ffff00; box-shadow: 0 0 15px #ffff00'
            );

            const highlightedIcon = L.divIcon({
                ...icon.options,
                html: highlightedHtml
            });

            marker.setIcon(highlightedIcon);
        }
    }
    // ========================================
    // EXTRAER INFORMACIÓN DE LA CÁMARA
    // ========================================
    function extractCameraInfo(marker) {
        try {
            const popup = marker.getPopup();
            if (!popup) return null;

            const content = popup.getContent();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;

            const cameraImageElement = tempDiv.querySelector('img');
            const cameraImageSrc = cameraImageElement ? (cameraImageElement.getAttribute('src') || cameraImageElement.src) : null;

            // Extraer título
            const titulo = tempDiv.querySelector('h5')?.textContent ||
                tempDiv.querySelector('strong')?.textContent ||
                'Cámara sin nombre';

            const position = marker.getLatLng();

            // Extraer ID único si existe en el popup
            let cameraId = null;
            const idElement = tempDiv.querySelector('[data-camera-id]');
            if (idElement) {
                cameraId = idElement.getAttribute('data-camera-id');
            }

            // Generar ID único si no existe
            if (!cameraId) {
                cameraId = `cam_${titulo.replace(/\s+/g, '_')}_${position.lat.toFixed(6)}_${position.lng.toFixed(6)}`;
            }

            const info = {
                id: cameraId,
                titulo: titulo,
                latitud: position.lat.toFixed(6),
                longitud: position.lng.toFixed(6),
                imagen: cameraImageSrc,
                tipo: extractField(content, 'Tipo:'),
                sitio: extractField(content, 'Sitio:'),
                dependencia: extractField(content, 'Dependencia:'),
                etapa: extractField(content, 'Etapa:'),
                instalacion: extractField(content, 'Instalación:'),
                marca: extractField(content, 'Marca:'),
                modelo: extractField(content, 'Mod.:'),
                serie: extractField(content, 'Nº serie:'),
                marker: marker
            };

            return info;
        } catch (error) {
            console.error('Error extrayendo info:', error);
            return null;
        }
    }

    // ========================================
    // RESALTAR MARCADOR DE CÁMARA
    // ========================================
    function highlightCameraMarker(marker) {
        // Guardar estilo original si no existe
        if (!marker._originalStyle) {
            marker._originalStyle = {
                icon: marker.options.icon
            };
        }

        // Agregar borde o efecto visual
        const icon = marker.options.icon;
        if (icon && icon.options && icon.options.html) {
            const highlightedHtml = icon.options.html.replace(
                /<div style="position: relative/,
                '<div style="position: relative; border: 3px solid #ff0000; border-radius: 50%; box-shadow: 0 0 10px #ff0000;'
            );

            const highlightedIcon = L.divIcon({
                ...icon.options,
                html: highlightedHtml
            });

            marker.setIcon(highlightedIcon);
        }
    }

    // ========================================
    // MODAL CON PESTAÑAS - CÁMARAS Y SITIOS INACTIVOS
    // ========================================
    function showCamerasModal() {
        // Cerrar modal existente si hay
        $('#camerasPolygonModal').modal('hide');
        $('#camerasPolygonModal').remove();
        $('.modal-backdrop').remove();

        // Crear nuevo modal con pestañas
        const modalHtml = `
        <div class="modal fade" id="camerasPolygonModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl modal-fullscreen-md-down" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-map-marked-alt"></i>
                            Área Seleccionada - Total: ${selectedCamerasInPolygon.length + selectedSitiosInPolygon.length} elementos
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Pestañas de navegación -->
                        <ul class="nav nav-tabs" id="polygonResultsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="camaras-tab" data-toggle="tab" href="#camarasTabContent" role="tab" aria-controls="camarasTabContent" aria-selected="true">
                                    <i class="fas fa-video text-primary"></i>
                                    Cámaras <span class="badge badge-primary">${selectedCamerasInPolygon.length}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="sitios-tab" data-toggle="tab" href="#sitiosTabContent" role="tab" aria-controls="sitiosTabContent" aria-selected="false">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    Sitios Inactivos <span class="badge badge-danger">${selectedSitiosInPolygon.length}</span>
                                </a>
                            </li>
                        </ul>

                        <!-- Contenido de las pestañas -->
                        <div class="tab-content" id="polygonResultsTabContent">
                            <!-- Pestaña de Cámaras -->
                            <div class="tab-pane fade show active" id="camarasTabContent" role="tabpanel" aria-labelledby="camaras-tab">
                                <div class="row mb-3 mt-3">
                                    <div class="col-md-6">
                                        <input type="text" id="searchCameraInList" class="form-control" placeholder="Buscar cámaras...">
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </button>
                                        <button class="btn btn-info btn-sm" onclick="exportToDOCX()">
                                            <i class="fas fa-file-word"></i> DOCX
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-striped table-hover table-sm" id="camerasTable">
                                        <thead class="thead-dark" style="position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th>#</th>
                                                <th>Título</th>
                                                <th>Tipo</th>
                                                <th>Sitio</th>
                                                <th>Dependencia</th>
                                                <th>Ubicación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="camerasTableBody">
                                            ${generateCamerasTableRows()}
                                        </tbody>
                                    </table>
                                </div>
                                ${selectedCamerasInPolygon.length === 0 ? '<div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> No se encontraron cámaras en el área seleccionada.</div>' : ''}
                            </div>

                            <!-- Pestaña de Sitios Inactivos -->
                            <div class="tab-pane fade" id="sitiosTabContent" role="tabpanel" aria-labelledby="sitios-tab">
                                <div class="row mb-3 mt-3">
                                    <div class="col-md-6">
                                        <input type="text" id="searchSitioInList" class="form-control" placeholder="Buscar sitios inactivos...">
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <span class="text-muted">Los sitios inactivos se incluyen en la exportación PDF</span>
                                    </div>
                                </div>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-striped table-hover table-sm" id="sitiosTable">
                                        <thead class="thead-danger" style="position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre</th>
                                                <th>Estado</th>
                                                <th>Cartel</th>
                                                <th>Observaciones</th>
                                                <th>Ubicación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sitiosTableBody">
                                            ${generateSitiosTableRows()}
                                        </tbody>
                                    </table>
                                </div>
                                ${selectedSitiosInPolygon.length === 0 ? '<div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> No se encontraron sitios inactivos en el área seleccionada.</div>' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="mr-auto">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                ${selectedCamerasInPolygon.length} cámaras | ${selectedSitiosInPolygon.length} sitios inactivos
                            </small>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="zoomToPolygon()">
                            <i class="fas fa-search-location"></i> Ver Área
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

        // Agregar modal al body del documento
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Agregar estilos inline para fullscreen
        if (detectFullscreen()) {
            const modal = document.getElementById('camerasPolygonModal');
            modal.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 10001 !important;
                background: transparent !important;
            `;

            // Asegurar backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                z-index: 10000 !important;
                background-color: rgba(0, 0, 0, 0.9) !important;
            `;
            document.body.appendChild(backdrop);
        }

        // Configurar modal
        const modalElement = document.getElementById('camerasPolygonModal');

        // Evento cuando se muestra el modal
        $(modalElement).on('show.bs.modal', function () {
            if (detectFullscreen()) {
                repositionModalForFullscreen();
            }
        });

        // Evento cuando se oculta el modal
        $(modalElement).on('hidden.bs.modal', function () {
            $(this).remove();
            $('.modal-backdrop').remove();
        });

        // Mostrar modal
        $(modalElement).modal({
            backdrop: 'static',
            keyboard: true,
            show: true
        });

        // Ajustar tamaño en pantalla completa
        if (detectFullscreen()) {
            const modalDialog = modalElement.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.cssText = `
                    margin: auto !important;
                    max-height: 90vh !important;
                    max-width: 95vw !important;
                `;
            }

            const modalContent = modalElement.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.maxHeight = '90vh';
                modalContent.style.overflow = 'hidden';
            }
        }

        // Agregar funcionalidad de búsqueda para cámaras
        $('#searchCameraInList').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            $('#camerasTableBody tr').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        });

        // Agregar funcionalidad de búsqueda para sitios
        $('#searchSitioInList').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            $('#sitiosTableBody tr').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        });
    }

    // ========================================
    // GENERAR FILAS DE LA TABLA DE CÁMARAS
    // ========================================
    function generateCamerasTableRows() {
        return selectedCamerasInPolygon.map((camera, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${camera.titulo}</strong></td>
            <td><span class="badge badge-info">${camera.tipo}</span></td>
            <td>${camera.sitio}</td>
            <td>${camera.dependencia}</td>
            <td><small>${camera.latitud}, ${camera.longitud}</small></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="zoomToCamera(${index})" title="Ver en mapa">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
                <button class="btn btn-sm btn-primary" onclick="showCameraDetails(${index})" title="Ver detalles">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
        </tr>
    `).join('');
    }

    // ========================================
    // GENERAR FILAS DE LA TABLA DE SITIOS INACTIVOS
    // ========================================
    function generateSitiosTableRows() {
        return selectedSitiosInPolygon.map((sitio, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${sitio.titulo}</strong></td>
            <td><span class="badge badge-danger">${sitio.estado}</span></td>
            <td>${sitio.cartel}</td>
            <td><small>${sitio.observaciones !== 'N/A' ? sitio.observaciones.substring(0, 50) + '...' : 'Sin observaciones'}</small></td>
            <td><small>${sitio.latitud}, ${sitio.longitud}</small></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="zoomToSitio(${index})" title="Ver en mapa">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
                <button class="btn btn-sm btn-warning" onclick="showSitioDetails(${index})" title="Ver detalles">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
        </tr>
    `).join('');
    }

    // ========================================
    // ZOOM A SITIO ESPECÍFICO
    // ========================================
    function zoomToSitio(index) {
        const sitio = selectedSitiosInPolygon[index];
        if (sitio && sitio.marker) {
            mymap.setView([sitio.latitud, sitio.longitud], 18);
            sitio.marker.openPopup();
            $('#camerasPolygonModal').modal('hide');
        }
    }

    // ========================================
    // MOSTRAR DETALLES DE SITIO
    // ========================================
    function showSitioDetails(index) {
        const sitio = selectedSitiosInPolygon[index];

        Swal.fire({
            title: sitio.titulo,
            html: `
            <table class="table table-sm table-bordered">
                <tr><td><strong>Estado:</strong></td><td><span class="badge badge-danger">${sitio.estado}</span></td></tr>
                <tr><td><strong>Cartel:</strong></td><td>${sitio.cartel}</td></tr>
                <tr><td><strong>Observaciones:</strong></td><td>${sitio.observaciones}</td></tr>
                <tr><td><strong>Latitud:</strong></td><td>${sitio.latitud}</td></tr>
                <tr><td><strong>Longitud:</strong></td><td>${sitio.longitud}</td></tr>
            </table>
        `,
            width: '500px',
            confirmButtonText: 'Cerrar'
        });
    }

    // ========================================
    // ZOOM A CÁMARA ESPECÍFICA
    // ========================================
    function zoomToCamera(index) {
        const camera = selectedCamerasInPolygon[index];
        if (camera && camera.marker) {
            mymap.setView([camera.latitud, camera.longitud], 18);
            camera.marker.openPopup();
            $('#camerasPolygonModal').modal('hide');
        }
    }

    // ========================================
    // MOSTRAR DETALLES DE CÁMARA
    // ========================================
    function showCameraDetails(index) {
        const camera = selectedCamerasInPolygon[index];

        Swal.fire({
            title: camera.titulo,
            html: `
            <table class="table table-sm table-bordered">
                <tr><td><strong>Tipo:</strong></td><td>${camera.tipo}</td></tr>
                <tr><td><strong>Sitio:</strong></td><td>${camera.sitio}</td></tr>
                <tr><td><strong>Dependencia:</strong></td><td>${camera.dependencia}</td></tr>
                <tr><td><strong>Etapa:</strong></td><td>${camera.etapa}</td></tr>
                <tr><td><strong>Instalación:</strong></td><td>${camera.instalacion}</td></tr>
                <tr><td><strong>Marca:</strong></td><td>${camera.marca}</td></tr>
                <tr><td><strong>Modelo:</strong></td><td>${camera.modelo}</td></tr>
                <tr><td><strong>Nº Serie:</strong></td><td>${camera.serie}</td></tr>
                <tr><td><strong>Latitud:</strong></td><td>${camera.latitud}</td></tr>
                <tr><td><strong>Longitud:</strong></td><td>${camera.longitud}</td></tr>
            </table>
        `,
            width: '600px',
            confirmButtonText: 'Cerrar'
        });
    }

    // ========================================
    // ZOOM AL POLÍGONO
    // ========================================
    function zoomToPolygon() {
        if (currentPolygon) {
            mymap.fitBounds(currentPolygon.getBounds());
            $('#camerasPolygonModal').modal('hide');
        }
    }

    // ========================================
    // EXPORTAR A EXCEL - CON HOJAS SEPARADAS
    // ========================================
    function exportToExcel() {
        if (typeof XLSX === 'undefined') {
            showNotification('Librería XLSX no cargada', 'error');
            return;
        }

        const wb = XLSX.utils.book_new();

        // Hoja 1: Cámaras
        if (selectedCamerasInPolygon.length > 0) {
            const camarasData = selectedCamerasInPolygon.map((camera, index) => ({
                '#': index + 1,
                'Título': camera.titulo,
                'Tipo': camera.tipo,
                'Sitio': camera.sitio,
                'Dependencia': camera.dependencia,
                'Etapa': camera.etapa,
                'Instalación': camera.instalacion,
                'Marca': camera.marca,
                'Modelo': camera.modelo,
                'Nº Serie': camera.serie
            }));
            const wsCamaras = XLSX.utils.json_to_sheet(camarasData);
            XLSX.utils.book_append_sheet(wb, wsCamaras, 'Cámaras');
        }

        // Hoja 2: Sitios Inactivos
        if (selectedSitiosInPolygon.length > 0) {
            const sitiosData = selectedSitiosInPolygon.map((sitio, index) => ({
                '#': index + 1,
                'Nombre': sitio.titulo,
                'Estado': sitio.estado,
                'Cartel': sitio.cartel,
                'Observaciones': sitio.observaciones
            }));
            const wsSitios = XLSX.utils.json_to_sheet(sitiosData);
            XLSX.utils.book_append_sheet(wb, wsSitios, 'Sitios Inactivos');
        }

        // Hoja 3: Resumen
        const resumenData = [{
            'Total Cámaras': selectedCamerasInPolygon.length,
            'Total Sitios Inactivos': selectedSitiosInPolygon.length,
            'Total Elementos': selectedCamerasInPolygon.length + selectedSitiosInPolygon.length,
            'Fecha Exportación': new Date().toLocaleDateString('es-AR'),
            'Hora Exportación': new Date().toLocaleTimeString('es-AR')
        }];
        const wsResumen = XLSX.utils.json_to_sheet(resumenData);
        XLSX.utils.book_append_sheet(wb, wsResumen, 'Resumen');

        const totalItems = selectedCamerasInPolygon.length + selectedSitiosInPolygon.length;
        const fileName = `reporte_area_${new Date().toISOString().split('T')[0]}_${totalItems}_elementos.xlsx`;
        XLSX.writeFile(wb, fileName);

        showNotification('Archivo Excel exportado con cámaras y sitios inactivos', 'success');
    }

    // ========================================
    // CAPTURA MEJORADA DEL MAPA PARA PDF
    // ========================================

    // ========================================
    // EXPORTAR A PDF - CON TABLAS SEPARADAS PARA CÁMARAS Y SITIOS
    // ========================================
    async function exportToPDF() {
        if (typeof jspdf === 'undefined') {
            showNotification('Librería jsPDF no cargada', 'error');
            return;
        }

        try {
            showNotification('Generando PDF con mapa...', 'info');

            // Mostrar loader con progreso
            Swal.fire({
                title: 'Generando PDF',
                html: '<div id="pdf-progress">Preparando mapa... 0%</div>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'mm', 'a4');

            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();

            // ========================================
            // PÁGINA 1: TÍTULO, MAPA Y RESUMEN
            // ========================================

            // Título principal
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('REPORTE DE ÁREA SELECCIONADA', pageWidth / 2, 15, { align: 'center' });

            // Información del reporte
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            const area = currentPolygon ?
                (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2) : 'N/A';
            const reportInfo = `Fecha: ${new Date().toLocaleDateString('es-AR')} | Hora: ${new Date().toLocaleTimeString('es-AR')} | Cámaras: ${selectedCamerasInPolygon.length} | Sitios Inactivos: ${selectedSitiosInPolygon.length} | Área: ${area} km²`;
            doc.text(reportInfo, pageWidth / 2, 22, { align: 'center' });

            // Actualizar progreso
            updateProgress(20, 'Capturando imagen del mapa...');

            // CAPTURA DEL MAPA
            const mapImage = await captureMapImageOptimized();

            if (mapImage) {
                updateProgress(50, 'Procesando imagen...');

                // Dimensiones optimizadas para el mapa
                const imgWidth = 250;
                const imgHeight = 100;
                const xPos = (pageWidth - imgWidth) / 2;
                const yPos = 28;

                // Agregar imagen con bordes
                doc.setDrawColor(100, 100, 100);
                doc.setLineWidth(0.5);
                doc.rect(xPos - 1, yPos - 1, imgWidth + 2, imgHeight + 2);
                doc.addImage(mapImage, 'PNG', xPos, yPos, imgWidth, imgHeight, '', 'FAST');

                console.log('✅ Imagen del mapa agregada al PDF');
            } else {
                // Dibujar placeholder
                const xPos = (pageWidth - 250) / 2;
                const yPos = 28;

                doc.setFillColor(240, 240, 240);
                doc.rect(xPos, yPos, 250, 100, 'F');
                doc.setDrawColor(200, 200, 200);
                doc.setLineWidth(1);
                doc.rect(xPos, yPos, 250, 100);

                doc.setFontSize(14);
                doc.setTextColor(150, 150, 150);
                doc.text('Mapa no disponible', pageWidth / 2, yPos + 50, { align: 'center' });
            }

            // ========================================
            // RESUMEN EN PÁGINA 1
            // ========================================
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('RESUMEN', 14, 140);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`• Total de Cámaras: ${selectedCamerasInPolygon.length}`, 20, 148);
            doc.text(`• Total de Sitios Inactivos: ${selectedSitiosInPolygon.length}`, 20, 155);
            doc.text(`• Superficie del Área: ${area} km²`, 20, 162);

            // Footer página 1
            doc.setFontSize(8);
            doc.setTextColor(128);
            doc.text(`Sistema de Videovigilancia - Página 1`, pageWidth / 2, pageHeight - 10, { align: 'center' });

            updateProgress(60, 'Generando tabla de cámaras...');

            // ========================================
            // PÁGINA 2+: TABLA DE CÁMARAS
            // ========================================
            if (selectedCamerasInPolygon.length > 0) {
                doc.addPage();

                // Título de sección
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('LISTADO DE CÁMARAS', pageWidth / 2, 15, { align: 'center' });

                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text(`Total: ${selectedCamerasInPolygon.length} cámaras en el área seleccionada`, pageWidth / 2, 22, { align: 'center' });

                // Tabla de cámaras
                const camerasTableData = selectedCamerasInPolygon.map((camera, index) => [
                    index + 1,
                    camera.titulo.substring(0, 30),
                    camera.tipo || 'N/A',
                    camera.sitio || 'N/A',
                    camera.dependencia || 'N/A'
                ]);

                doc.autoTable({
                    head: [['#', 'Título', 'Tipo', 'Sitio', 'Dependencia']],
                    body: camerasTableData,
                    startY: 28,
                    theme: 'grid',
                    styles: {
                        fontSize: 8,
                        cellPadding: 2,
                        overflow: 'linebreak',
                        halign: 'left'
                    },
                    headStyles: {
                        fillColor: [41, 128, 185],
                        textColor: 255,
                        fontStyle: 'bold',
                        halign: 'center'
                    },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 10 },
                        1: { cellWidth: 70 },
                        2: { cellWidth: 35 },
                        3: { cellWidth: 45 },
                        4: { cellWidth: 65 }
                    },
                    margin: { left: 14, right: 14 },
                    didDrawPage: function (data) {
                        doc.setFontSize(8);
                        doc.setTextColor(128);
                        doc.text(
                            `Sistema de Videovigilancia - Cámaras - Página ${doc.internal.getCurrentPageInfo().pageNumber}`,
                            pageWidth / 2,
                            pageHeight - 10,
                            { align: 'center' }
                        );
                    }
                });
            }

            updateProgress(80, 'Generando tabla de sitios inactivos...');

            // ========================================
            // PÁGINA NUEVA: TABLA DE SITIOS INACTIVOS
            // ========================================
            if (selectedSitiosInPolygon.length > 0) {
                doc.addPage();

                // Título de sección
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('LISTADO DE SITIOS INACTIVOS', pageWidth / 2, 15, { align: 'center' });

                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text(`Total: ${selectedSitiosInPolygon.length} sitios inactivos en el área seleccionada`, pageWidth / 2, 22, { align: 'center' });

                // Tabla de sitios inactivos
                const sitiosTableData = selectedSitiosInPolygon.map((sitio, index) => [
                    index + 1,
                    sitio.titulo.substring(0, 35),
                    sitio.estado,
                    sitio.cartel || 'N/A',
                    (sitio.observaciones && sitio.observaciones !== 'N/A') ? sitio.observaciones.substring(0, 40) : 'Sin obs.'
                ]);

                doc.autoTable({
                    head: [['#', 'Nombre', 'Estado', 'Cartel', 'Observaciones']],
                    body: sitiosTableData,
                    startY: 28,
                    theme: 'grid',
                    styles: {
                        fontSize: 8,
                        cellPadding: 2,
                        overflow: 'linebreak',
                        halign: 'left'
                    },
                    headStyles: {
                        fillColor: [220, 53, 69],
                        textColor: 255,
                        fontStyle: 'bold',
                        halign: 'center'
                    },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 10 },
                        1: { cellWidth: 70 },
                        2: { cellWidth: 30 },
                        3: { cellWidth: 25 },
                        4: { cellWidth: 115 }
                    },
                    margin: { left: 14, right: 14 },
                    didDrawPage: function (data) {
                        doc.setFontSize(8);
                        doc.setTextColor(128);
                        doc.text(
                            `Sistema de Videovigilancia - Sitios Inactivos - Página ${doc.internal.getCurrentPageInfo().pageNumber}`,
                            pageWidth / 2,
                            pageHeight - 10,
                            { align: 'center' }
                        );
                    }
                });
            }

            updateProgress(95, 'Finalizando PDF...');

            // Guardar PDF
            const totalItems = selectedCamerasInPolygon.length + selectedSitiosInPolygon.length;
            const fileName = `reporte_area_${new Date().toISOString().slice(0, 10)}_${totalItems}_elementos.pdf`;
            doc.save(fileName);

            updateProgress(100, 'PDF generado exitosamente');

            setTimeout(() => {
                Swal.close();
                showNotification('PDF exportado correctamente con cámaras y sitios inactivos', 'success');
            }, 500);

        } catch (error) {
            console.error('❌ Error exportando PDF:', error);
            Swal.close();
            showNotification('Error al exportar PDF: ' + error.message, 'error');
        }
    }

    // ========================================
    // CAPTURA OPTIMIZADA
    // ========================================
    async function captureMapImageOptimized(drawPolygon = true) {
        try {
            console.log('📸 Iniciando captura del área seleccionada...');

            if (!currentPolygon) {
                console.warn('No hay polígono para capturar');
                return null;
            }

            // Guardar estado original
            const originalState = {
                center: mymap.getCenter(),
                zoom: mymap.getZoom()
            };

            await preloadPdfCameraIcons(selectedCamerasInPolygon);

            try {
                // Preparar mapa (ocultar controles y marcadores)
                await prepareMapForCapture();

                // Capturar imagen del área completa
                let capturedImage = null;

                if (typeof html2canvas !== 'undefined') {
                    console.log('🔄 Capturando con html2canvas...');
                    capturedImage = await captureMapAreaOnly(drawPolygon);
                }

                // Si falla html2canvas, usar canvas manual
                if (!capturedImage) {
                    console.log('🔄 Usando captura manual...');
                    capturedImage = await captureWithManualCanvasSimple(drawPolygon);
                }

                return capturedImage;

            } finally {
                // Asegurar que siempre se restaure el estado
                await restoreMapState(originalState);
            }

        } catch (error) {
            console.error('❌ Error en captura:', error);
            showNotification('Error al capturar el mapa: ' + error.message, 'error');
            return null;
        }
    }

    // ========================================
    // CAPTURA SOLO EL ÁREA DEL MAPA
    // ========================================
    async function captureMapAreaOnly(drawPolygon = true) {
        try {
            const mapContainer = document.getElementById('map');

            if (!mapContainer) {
                console.error('No se encontró el contenedor del mapa');
                return null;
            }

            console.log('📷 Capturando área del mapa...');

            const options = {
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#e5e3df',
                scale: 2,
                logging: false,
                width: mapContainer.offsetWidth,
                height: mapContainer.offsetHeight,
                windowWidth: mapContainer.offsetWidth,
                windowHeight: mapContainer.offsetHeight,
                scrollX: 0,
                scrollY: 0,
                imageTimeout: 0,
                removeContainer: false,
                foreignObjectRendering: false,

                ignoreElements: function (element) {
                    if (element.tagName === 'IMG' && element.src && element.src.includes('uploads')) {
                        return true;
                    }
                    if (element.classList.contains('leaflet-overlay-pane')) {
                        return true;
                    }
                    if (element.tagName === 'path' || element.tagName === 'svg') {
                        return true;
                    }
                    if (element.style.display === 'none') {
                        return true;
                    }
                    return false;
                },

                onclone: function (clonedDoc) {
                    const clonedMap = clonedDoc.getElementById('map');
                    if (clonedMap) {
                        clonedMap.style.width = mapContainer.offsetWidth + 'px';
                        clonedMap.style.height = mapContainer.offsetHeight + 'px';

                        const tiles = clonedMap.querySelectorAll('.leaflet-tile');
                        tiles.forEach(tile => {
                            tile.style.opacity = '1';
                            tile.style.visibility = 'visible';
                        });

                        const overlayPane = clonedMap.querySelector('.leaflet-overlay-pane');
                        if (overlayPane) {
                            overlayPane.style.display = 'none';
                        }

                        const svgs = clonedMap.querySelectorAll('svg');
                        svgs.forEach(svg => svg.remove());

                        const paths = clonedMap.querySelectorAll('path');
                        paths.forEach(path => path.remove());

                        const markerImages = clonedMap.querySelectorAll('img[src*="uploads"]');
                        markerImages.forEach(img => img.remove());
                    }
                }
            };

            const canvas = await html2canvas(mapContainer, options);

            if (canvas && canvas.width > 0 && canvas.height > 0) {
                console.log(`✅ Mapa capturado: ${canvas.width}x${canvas.height}px`);

                const ctx = canvas.getContext('2d');
                drawMarkersOnCanvas(ctx, mapContainer, drawPolygon);

                return canvas.toDataURL('image/png', 0.92);
            }

            return null;

        } catch (error) {
            console.error('❌ Error en captura:', error);
            return null;
        }
    }

    function normalizePdfIconUrl(src) {
        if (!src) return null;
        try {
            return new URL(src, window.location.href).toString();
        } catch (e) {
            return src;
        }
    }

    function preloadPdfIconImage(url) {
        return new Promise(resolve => {
            if (!url) {
                resolve(null);
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.decoding = 'async';

            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = url;

            if (img.complete && img.naturalWidth > 0) {
                resolve(img);
            }
        });
    }

    async function preloadPdfCameraIcons(cameras) {
        try {
            const urls = Array.from(new Set(
                (cameras || [])
                    .map(c => normalizePdfIconUrl(c?.imagen))
                    .filter(Boolean)
            ));

            if (urls.length === 0) {
                window._pdfCameraIconCache = new Map();
                return;
            }

            const results = await Promise.all(urls.map(async (url) => {
                const img = await preloadPdfIconImage(url);
                return { url, img };
            }));

            const cache = new Map();
            results.forEach(({ url, img }) => {
                if (img) {
                    cache.set(url, img);
                }
            });

            window._pdfCameraIconCache = cache;
        } catch (e) {
            window._pdfCameraIconCache = new Map();
        }
    }

    function getPreloadedPdfCameraIcon(camera) {
        const cache = window._pdfCameraIconCache;
        if (!cache || !(cache instanceof Map)) return null;

        const url = normalizePdfIconUrl(camera?.imagen);
        if (!url) return null;

        return cache.get(url) || null;
    }

    // ========================================
    // CAPTURA MANUAL SIMPLIFICADA
    // ========================================
    async function captureWithManualCanvasSimple(drawPolygon = true) {
        try {
            console.log('🎨 Captura manual del área...');

            const mapContainer = document.getElementById('map');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            const width = mapContainer.offsetWidth;
            const height = mapContainer.offsetHeight;

            canvas.width = width;
            canvas.height = height;

            // Fondo
            ctx.fillStyle = '#e5e3df';
            ctx.fillRect(0, 0, width, height);

            // Capturar solo tiles del mapa base
            const tilePane = mapContainer.querySelector('.leaflet-tile-pane');
            if (tilePane) {
                const tiles = tilePane.querySelectorAll('.leaflet-tile');
                console.log(`🗺️ Dibujando ${tiles.length} tiles...`);

                for (const tile of tiles) {
                    if (tile.complete && tile.naturalWidth > 0) {
                        try {
                            const rect = tile.getBoundingClientRect();
                            const mapRect = mapContainer.getBoundingClientRect();

                            const x = rect.left - mapRect.left;
                            const y = rect.top - mapRect.top;

                            ctx.drawImage(tile, x, y, rect.width, rect.height);
                        } catch (err) {
                            console.warn('Error dibujando tile:', err);
                        }
                    }
                }
            }

            // Dibujar marcadores
            drawMarkersOnCanvas(ctx, mapContainer, drawPolygon);

            return canvas.toDataURL('image/png', 0.92);

        } catch (error) {
            console.error('❌ Error en captura manual:', error);
            return null;
        }
    }

    // ========================================
    // PREPARAR MAPA PARA CAPTURA
    // ========================================
    async function prepareMapForCapture() {
        console.log('🔧 Preparando mapa para captura con desagrupación de clusters...');

        // Guardar estado original
        const originalState = {
            clusteringEnabled: clusteringEnabled,
            zoom: mymap.getZoom(),
            center: mymap.getCenter()
        };

        // Array para marcadores temporales
        window._tempPdfMarkers = [];
        window._originalState = originalState;

        // ============================================
        // PASO 1: OCULTAR CONTROLES Y UI
        // ============================================
        console.log('📦 Ocultando elementos de UI...');

        const controlSelectors = [
            '.leaflet-control-zoom',
            '.leaflet-control-attribution',
            '.leaflet-control-scale',
            '.leaflet-bar:not(.leaflet-control-custom)',
            '.geocoder-control',
            '#customLayerControl',
            '#ver-lista',
            '#limpiar-seleccion',
            '.leaflet-popup',
            '.leaflet-tooltip',
            '#toggleClusterBtn',
            '#header-toggle',
            '#map-header'
        ];

        controlSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.display = 'none';
                el.setAttribute('data-hidden-for-pdf', 'true');
            });
        });

        // Ocultar el polígono temporalmente
        const overlayPane = document.querySelector('.leaflet-overlay-pane');
        if (overlayPane) {
            overlayPane.style.display = 'none';
            overlayPane.setAttribute('data-hidden-for-pdf', 'true');
        }

        // ============================================
        // PASO 2: REMOVER TODAS LAS CAPAS DE CLUSTERS
        // ============================================
        console.log('🗑️ Removiendo capas de clusters...');

        const layersToRemove = [
            { name: 'Todas', layer: capa2, cluster: marcadores },
            { name: 'LPR', layer: capaLPR, cluster: markersCamarasLPR },
            { name: 'FR', layer: capaFR, cluster: markersCamarasFR },
            { name: 'Fijas', layer: capaFija, cluster: markersCamarasFijas },
            { name: 'Domos', layer: capaDomo, cluster: markersCamarasDomos },
            { name: 'Domos Duales', layer: capaDomoDual, cluster: markersCamarasDomosDuales },
            { name: 'BDE', layer: capaBDE, cluster: markersBDE }
        ];

        // Guardar estado de visibilidad
        window._layerVisibilityState = {};

        layersToRemove.forEach(({ name, layer, cluster }) => {
            window._layerVisibilityState[name] = mymap.hasLayer(layer) || mymap.hasLayer(cluster);

            if (mymap.hasLayer(layer)) {
                mymap.removeLayer(layer);
            }
            if (mymap.hasLayer(cluster)) {
                mymap.removeLayer(cluster);
            }
        });

        console.log('📊 Estado de capas guardado:', window._layerVisibilityState);

        // ============================================
        // PASO 3: CREAR MARCADORES INDIVIDUALES SEPARADOS
        // ============================================
        console.log('🎯 Creando marcadores individuales para cámaras en el polígono...');
        console.log(`   Total de cámaras seleccionadas: ${selectedCamerasInPolygon.length}`);

        // Agrupar cámaras por posición para debugging
        const positionGroups = {};
        selectedCamerasInPolygon.forEach(camera => {
            const key = `${parseFloat(camera.latitud).toFixed(6)},${parseFloat(camera.longitud).toFixed(6)}`;
            if (!positionGroups[key]) {
                positionGroups[key] = [];
            }
            positionGroups[key].push(camera);
        });

        console.log('📍 Grupos de cámaras por posición:');
        Object.entries(positionGroups).forEach(([pos, cameras]) => {
            console.log(`   ${pos}: ${cameras.length} cámara(s)`);
        });

        // Crear marcadores con offsets para separación
        selectedCamerasInPolygon.forEach((camera, index) => {
            try {
                // Calcular offset para separar marcadores superpuestos
                const offset = calculateMarkerOffset(camera, selectedCamerasInPolygon, index);

                const originalCoords = [parseFloat(camera.latitud), parseFloat(camera.longitud)];
                const adjustedCoords = [originalCoords[0] + offset.lat, originalCoords[1] + offset.lng];

                // Solo si hubo desplazamiento, dibujamos la línea "araña"
                if (offset.lat !== 0 || offset.lng !== 0) {
                    const spiderLine = L.polyline([originalCoords, adjustedCoords], {
                        color: '#ff4444', // Color llamativo para el reporte
                        weight: 1.5,
                        opacity: 0.6,
                        dashArray: '3, 5'
                    }).addTo(mymap);

                    window._tempPdfMarkers.push(spiderLine);
                }

                const marker = L.marker(adjustedCoords, {
                    icon: createSimpleCameraIcon(index + 1, selectedCamerasInPolygon.length),
                    zIndexOffset: 1000 + index
                });

                // Agregar al mapa
                marker.addTo(mymap);
                window._tempPdfMarkers.push(marker);

                console.log(`   ✓ Marcador ${index + 1}: ${camera.titulo.substring(0, 30)}... (offset: ${offset.lat.toFixed(6)}, ${offset.lng.toFixed(6)})`);

            } catch (error) {
                console.error(`   ✗ Error creando marcador ${index + 1}:`, error);
            }
        });

        console.log(`✅ Creados ${window._tempPdfMarkers.length} marcadores individuales`);

        // ============================================
        // PASO 4: AJUSTAR VISTA AL POLÍGONO
        // ============================================
        if (currentPolygon) {
            console.log('🎯 Ajustando vista al polígono...');
            const bounds = currentPolygon.getBounds();

            mymap.fitBounds(bounds, {
                padding: [60, 60],
                animate: false,
                maxZoom: 17,
                duration: 0
            });
        }

        // ============================================
        // PASO 5: FORZAR RENDERIZADO Y ESPERAR
        // ============================================
        console.log('⏳ Esperando estabilización del mapa...');

        mymap.invalidateSize(true);
        await new Promise(resolve => setTimeout(resolve, 500));

        // Esperar carga de tiles
        await waitForTilesToLoad();

        // Espera adicional para asegurar renderizado completo
        await new Promise(resolve => setTimeout(resolve, 1000));

        console.log('✅ Mapa preparado y listo para captura');

        return true;
    }

    function calculateMarkerOffset(camera, allCameras, globalIndex) {
        const currentLat = parseFloat(camera.latitud);
        const currentLng = parseFloat(camera.longitud);

        // Usar configuración global
        const config = MARKER_SEPARATION_CONFIG;

        // Encontrar todas las cámaras en la misma posición
        const samePositionCameras = [];
        let indexInGroup = 0;

        allCameras.forEach((cam, idx) => {
            const latDiff = Math.abs(parseFloat(cam.latitud) - currentLat);
            const lngDiff = Math.abs(parseFloat(cam.longitud) - currentLng);

            if (latDiff < config.tolerance && lngDiff < config.tolerance) {
                samePositionCameras.push({ cam, idx });
                if (idx === globalIndex) {
                    indexInGroup = samePositionCameras.length - 1;
                }
            }
        });

        const totalInPosition = samePositionCameras.length;

        console.log(`📍 Cámara ${globalIndex + 1}: ${totalInPosition} en misma posición, índice en grupo: ${indexInGroup}`);

        // Si solo hay una cámara en esta posición, no necesita offset
        if (totalInPosition <= 1) {
            return { lat: 0, lng: 0 };
        }

        // Radio dinámico usando configuración
        const dynamicRadius = config.baseRadius + (totalInPosition * config.radiusIncrement);

        let angle;

        if (totalInPosition === 2) {
            // Caso especial: 2 marcadores → expansión HORIZONTAL (izquierda/derecha)
            angle = indexInGroup === 0 ? Math.PI : 0;
        } else {
            // Múltiples marcadores: distribución circular con rotación
            const angleStep = (2 * Math.PI) / totalInPosition;
            angle = config.rotationOffset + (indexInGroup * angleStep);
        }

        // TODOS los marcadores tienen offset (incluido el primero)
        const offset = {
            lat: dynamicRadius * Math.sin(angle),
            lng: dynamicRadius * Math.cos(angle)
        };

        console.log(`   → Offset: lat=${offset.lat.toFixed(6)}, lng=${offset.lng.toFixed(6)} (radio: ${(dynamicRadius * 111000).toFixed(0)}m)`);

        return offset;
    }

    // ========================================
    // CREAR ÍCONO SIMPLIFICADO PARA CAPTURA
    // ========================================
    function createSimpleCameraIcon(number, totalCameras) {
        // Colores según cantidad para mejor visualización
        const color = totalCameras > 50 ? '#dc3545' :
            totalCameras > 20 ? '#fd7e14' :
                '#28a745';

        return L.divIcon({
            className: 'pdf-camera-marker',
            html: `
            <div style="position: relative; width: 35px; height: 45px;">
                <svg width="35" height="45" viewBox="0 0 35 45" xmlns="http://www.w3.org/2000/svg">
                    <!-- Sombra -->
                    <ellipse cx="17.5" cy="42" rx="10" ry="4" fill="rgba(0,0,0,0.25)"/>

                    <!-- Cuerpo del pin -->
                    <path d="M17.5 3 C10 3 4 9 4 16.5 C4 26 17.5 38 17.5 38 C17.5 38 31 26 31 16.5 C31 9 25 3 17.5 3 Z"
                          fill="${color}"
                          stroke="#ffffff"
                          stroke-width="2.5"
                          filter="url(#shadow)"/>

                    <!-- Círculo interior blanco -->
                    <circle cx="17.5" cy="16.5" r="8" fill="#ffffff" stroke="${color}" stroke-width="1"/>

                    <!-- Número -->
                    <text x="17.5" y="21"
                          font-family="Arial, sans-serif"
                          font-size="11"
                          font-weight="bold"
                          text-anchor="middle"
                          fill="${color}">${number}</text>

                    <!-- Sombra para el texto -->
                    <defs>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="2"/>
                            <feOffset dx="1" dy="2" result="offsetblur"/>
                            <feComponentTransfer>
                                <feFuncA type="linear" slope="0.3"/>
                            </feComponentTransfer>
                            <feMerge>
                                <feMergeNode/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                </svg>
            </div>
        `,
            iconSize: [35, 45],
            iconAnchor: [17.5, 45],
            popupAnchor: [0, -45]
        });
    }

    // ========================================
    // VERIFICAR SI UN MARCADOR ESTÁ DENTRO DEL POLÍGONO
    // ========================================
    function isMarkerInsidePolygon(latLng) {
        if (!currentPolygon) return false;

        const bounds = currentPolygon.getBounds();
        if (!bounds.contains(latLng)) return false;

        const polygonPoints = currentPolygon.getLatLngs()[0];
        return isPointInPolygon(latLng, polygonPoints);
    }

    // ========================================
    // VERIFICAR SI UN CLUSTER ESTÁ DENTRO DEL POLÍGONO
    // ========================================
    function isClusterInsidePolygon(latLng) {
        if (!currentPolygon) return false;

        const bounds = currentPolygon.getBounds();
        return bounds.contains(latLng);
    }

    // ========================================
    // OBTENER POSICIÓN DE UN CLUSTER
    // ========================================
    function getClusterPosition(clusterElement) {
        try {
            const transform = clusterElement.style.transform;
            if (transform) {
                const match = transform.match(/translate3d\((.+?)px,\s*(.+?)px/);
                if (match) {
                    const x = parseFloat(match[1]);
                    const y = parseFloat(match[2]);

                    const point = L.point(x, y);
                    return mymap.containerPointToLatLng(point);
                }
            }
        } catch (error) {
            console.warn('No se pudo obtener posición del cluster:', error);
        }
        return null;
    }

    // ========================================
    // ESPERAR CARGA DE TILES
    // ========================================
    function waitForTilesToLoad() {
        return new Promise((resolve) => {
            let checksCount = 0;
            const maxChecks = 15;
            let checkInterval;

            const checkTiles = () => {
                checksCount++;

                const tiles = document.querySelectorAll('.leaflet-tile-pane .leaflet-tile');
                let tilesLoading = 0;

                tiles.forEach(tile => {
                    if (!tile.complete || tile.naturalWidth === 0) {
                        tilesLoading++;
                    }
                });

                console.log(`⏳ Check ${checksCount}/${maxChecks}: ${tilesLoading} tiles pendientes`);

                if (tilesLoading === 0 || checksCount >= maxChecks) {
                    clearInterval(checkInterval);
                    if (tilesLoading === 0) {
                        console.log('✅ Todos los tiles del mapa base cargados');
                    } else {
                        console.log(`⚠️ Continuando con ${tilesLoading} tiles pendientes`);
                    }
                    resolve();
                }
            };

            checkInterval = setInterval(checkTiles, 300);

            setTimeout(() => {
                clearInterval(checkInterval);
                console.log('⏰ Timeout alcanzado, continuando con la captura...');
                resolve();
            }, 6000);
        });
    }

    // ========================================
    // DIBUJAR LEYENDA EN CANVAS
    // ========================================
    function drawLegendOnCanvas(ctx, canvasWidth, canvasHeight) {
        try {
            const area = currentPolygon ?
                (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2) : 'N/A';

            const legendWidth = 250;
            const legendHeight = 140;
            const padding = 15;
            const x = canvasWidth - legendWidth - padding;
            const y = padding;

            // Fondo con sombra
            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
            ctx.shadowBlur = 10;
            ctx.shadowOffsetX = 3;
            ctx.shadowOffsetY = 3;

            // Fondo semi-transparente
            ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
            ctx.strokeStyle = '#3388ff';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.roundRect(x, y, legendWidth, legendHeight, 8);
            ctx.fill();
            ctx.stroke();

            ctx.restore();

            // Título
            ctx.fillStyle = '#3388ff';
            ctx.font = 'bold 16px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('📍 ÁREA SELECCIONADA', x + legendWidth / 2, y + 25);

            // Línea separadora
            ctx.strokeStyle = '#dddddd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x + 15, y + 35);
            ctx.lineTo(x + legendWidth - 15, y + 35);
            ctx.stroke();

            // Información
            ctx.textAlign = 'left';
            ctx.font = '13px Arial';
            ctx.fillStyle = '#333333';

            const info = [
                { icon: '📹', label: 'Cámaras:', value: selectedCamerasInPolygon.length, color: '#2980b9' },
                { icon: '❌', label: 'Sitios Inactivos:', value: selectedSitiosInPolygon.length, color: '#dc3545' },
                { icon: '📐', label: 'Superficie:', value: area + ' km²', color: '#27ae60' }
            ];

            let currentY = y + 55;
            info.forEach(item => {
                ctx.fillStyle = '#666666';
                ctx.fillText(item.icon + ' ' + item.label, x + 20, currentY);

                ctx.fillStyle = item.color;
                ctx.font = 'bold 14px Arial';
                ctx.textAlign = 'right';
                ctx.fillText(item.value.toString(), x + legendWidth - 20, currentY);

                ctx.textAlign = 'left';
                ctx.font = '13px Arial';
                currentY += 22;
            });

            // Fecha
            const fecha = new Date().toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            ctx.fillStyle = '#999999';
            ctx.font = '11px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('📅 ' + fecha, x + legendWidth / 2, y + legendHeight - 10);

        } catch (error) {
            console.error('Error dibujando leyenda:', error);
        }
    }

    // ========================================
    // DIBUJAR POLÍGONO EN CANVAS
    // ========================================
    function drawPolygonOnCanvas(ctx, mapContainer) {
        if (!currentPolygon) {
            console.log('⚠️ No hay polígono para dibujar');
            return;
        }

        try {
            console.log('📐 Dibujando polígono en canvas...');

            const latLngs = currentPolygon.getLatLngs()[0];
            if (!latLngs || latLngs.length < 3) return;

            ctx.save();

            // Convertir coordenadas a puntos del canvas
            ctx.beginPath();
            const firstPoint = mymap.latLngToContainerPoint(latLngs[0]);
            ctx.moveTo(firstPoint.x, firstPoint.y);

            for (let i = 1; i < latLngs.length; i++) {
                const point = mymap.latLngToContainerPoint(latLngs[i]);
                ctx.lineTo(point.x, point.y);
            }
            ctx.closePath();

            // Relleno semi-transparente azul
            ctx.fillStyle = 'rgba(51, 136, 255, 0.2)';
            ctx.fill();

            // Borde azul
            ctx.strokeStyle = '#3388ff';
            ctx.lineWidth = 3;
            ctx.stroke();

            ctx.restore();
            console.log('✅ Polígono dibujado en canvas');

        } catch (error) {
            console.error('Error dibujando polígono:', error);
        }
    }

    // ========================================
    // DIBUJAR MARCADORES EN CANVAS
    // ========================================
    function drawMarkersOnCanvas(ctx, mapContainer, drawPolygon = true) {
        if (drawPolygon) {
            drawPolygonOnCanvas(ctx, mapContainer);
        }

        console.log(`🎯 Dibujando ${selectedCamerasInPolygon.length} marcadores en canvas...`);

        selectedCamerasInPolygon.forEach((camera, index) => {
            try {
                // Calcular offset para separar marcadores superpuestos
                const offset = calculateMarkerOffset(camera, selectedCamerasInPolygon, index);

                const adjustedLat = parseFloat(camera.latitud) + offset.lat;
                const adjustedLng = parseFloat(camera.longitud) + offset.lng;

                const point = mymap.latLngToContainerPoint([adjustedLat, adjustedLng]);

                if (point.x < 0 || point.y < 0 ||
                    point.x > mapContainer.offsetWidth ||
                    point.y > mapContainer.offsetHeight) {
                    return;
                }

                // Si hay offset, dibujar línea spider al punto original
                if (offset.lat !== 0 || offset.lng !== 0) {
                    const originalPoint = mymap.latLngToContainerPoint([camera.latitud, camera.longitud]);
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(originalPoint.x, originalPoint.y);
                    ctx.lineTo(point.x, point.y);
                    ctx.strokeStyle = '#ff4444';
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([3, 3]);
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.restore();
                }

                const pinHeight = 30;
                const pinWidth = 24;
                const x = point.x;
                const y = point.y;

                const cameraIconImage = getPreloadedPdfCameraIcon(camera);
                if (cameraIconImage) {
                    const size = 40;
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(x, y, size / 2, 0, 2 * Math.PI);
                    ctx.closePath();
                    ctx.clip();

                    ctx.drawImage(cameraIconImage, x - size / 2, y - size / 2, size, size);
                    ctx.restore();

                    ctx.beginPath();
                    ctx.arc(x, y, size / 2, 0, 2 * Math.PI);
                    ctx.strokeStyle = '#ffffff';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    return;
                }

                // Sombra
                ctx.save();
                ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
                ctx.shadowBlur = 6;
                ctx.shadowOffsetX = 2;
                ctx.shadowOffsetY = 2;

                // Cuerpo del pin
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.bezierCurveTo(
                    x - pinWidth / 2, y - pinHeight / 2,
                    x - pinWidth / 2, y - pinHeight,
                    x, y - pinHeight
                );
                ctx.arc(x, y - pinHeight, pinWidth / 2, Math.PI, 0, false);
                ctx.bezierCurveTo(
                    x + pinWidth / 2, y - pinHeight,
                    x + pinWidth / 2, y - pinHeight / 2,
                    x, y
                );
                ctx.closePath();

                // Gradiente para el pin
                const gradient = ctx.createLinearGradient(x - pinWidth / 2, y - pinHeight, x + pinWidth / 2, y);
                gradient.addColorStop(0, '#ff3333');
                gradient.addColorStop(1, '#cc0000');
                ctx.fillStyle = gradient;
                ctx.fill();

                ctx.restore();

                // Borde blanco
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Círculo interior blanco
                ctx.beginPath();
                ctx.arc(x, y - pinHeight + 8, 6, 0, 2 * Math.PI);
                ctx.fillStyle = '#ffffff';
                ctx.fill();

                // Número de cámara
                if (selectedCamerasInPolygon.length <= 50) {
                    ctx.fillStyle = '#cc0000';
                    ctx.font = 'bold 10px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText((index + 1).toString(), x, y - pinHeight + 8);
                }

            } catch (error) {
                console.error(`Error dibujando marcador ${index}:`, error);
            }
        });

        console.log('✅ Marcadores dibujados en canvas');
    }

    // ========================================
    // RESTAURAR ESTADO DEL MAPA
    // ========================================
    async function restoreMapState(state) {
        console.log('🔄 Restaurando estado original del mapa...');

        try {
            // ============================================
            // PASO 1: REMOVER MARCADORES TEMPORALES
            // ============================================
            if (window._tempPdfMarkers && window._tempPdfMarkers.length > 0) {
                console.log(`🗑️ Removiendo ${window._tempPdfMarkers.length} marcadores temporales...`);

                window._tempPdfMarkers.forEach(marker => {
                    try {
                        if (mymap.hasLayer(marker)) {
                            mymap.removeLayer(marker);
                        }
                    } catch (e) {
                        console.warn('Error removiendo marcador:', e);
                    }
                });

                window._tempPdfMarkers = [];
            }

            // ============================================
            // PASO 2: RESTAURAR CAPAS ORIGINALES
            // ============================================
            console.log('📦 Restaurando capas de clusters...');

            const layersToRestore = [
                { name: 'Todas', layer: capa2, cluster: marcadores },
                { name: 'LPR', layer: capaLPR, cluster: markersCamarasLPR },
                { name: 'FR', layer: capaFR, cluster: markersCamarasFR },
                { name: 'Fijas', layer: capaFija, cluster: markersCamarasFijas },
                { name: 'Domos', layer: capaDomo, cluster: markersCamarasDomos },
                { name: 'Domos Duales', layer: capaDomoDual, cluster: markersCamarasDomosDuales },
                { name: 'BDE', layer: capaBDE, cluster: markersBDE }
            ];

            if (window._layerVisibilityState) {
                layersToRestore.forEach(({ name, layer, cluster }) => {
                    if (window._layerVisibilityState[name]) {
                        console.log(`   ✓ Restaurando capa: ${name}`);

                        if (clusteringEnabled) {
                            if (!mymap.hasLayer(cluster)) {
                                mymap.addLayer(cluster);
                            }
                        } else {
                            if (!mymap.hasLayer(layer)) {
                                mymap.addLayer(layer);
                            }
                        }
                    }
                });
            }

            // ============================================
            // PASO 3: RESTAURAR VISTA ORIGINAL
            // ============================================
            if (window._originalState) {
                console.log('🎯 Restaurando vista original...');
                mymap.setView(
                    window._originalState.center,
                    window._originalState.zoom,
                    { animate: false }
                );
            } else if (state && state.center && state.zoom) {
                mymap.setView(state.center, state.zoom, { animate: false });
            }

            // ============================================
            // PASO 4: MOSTRAR ELEMENTOS OCULTOS
            // ============================================
            console.log('👁️ Mostrando elementos de UI...');

            document.querySelectorAll('[data-hidden-for-pdf]').forEach(el => {
                el.style.display = '';
                el.removeAttribute('data-hidden-for-pdf');
            });

            // Restaurar overlay-pane
            const overlayPane = document.querySelector('.leaflet-overlay-pane');
            if (overlayPane) {
                overlayPane.style.display = '';
                overlayPane.removeAttribute('data-hidden-for-pdf');
            }

            // ============================================
            // PASO 5: LIMPIAR Y FORZAR RENDERIZADO
            // ============================================
            mymap.invalidateSize(true);
            await new Promise(resolve => setTimeout(resolve, 300));

            // Limpiar variables temporales
            window._originalState = null;
            window._layerVisibilityState = null;

            console.log('✅ Estado restaurado completamente');

        } catch (error) {
            console.error('❌ Error restaurando estado:', error);

            // Intentar recuperación básica
            mymap.invalidateSize(true);
            document.querySelectorAll('[data-hidden-for-pdf]').forEach(el => {
                el.style.display = '';
                el.removeAttribute('data-hidden-for-pdf');
            });
        }
    }

    // ========================================
    // ACTUALIZAR PROGRESO EN EL LOADER
    // ========================================
    function updateProgress(percent, message) {
        const progressElement = document.getElementById('pdf-progress');
        if (progressElement) {
            progressElement.innerHTML = `${message} ${percent}%`;
        }
    }

    // ========================================
    // FUNCIÓN AUXILIAR: AGREGAR LIBRERÍA LEAFLET-IMAGE
    // ========================================
    function loadLeafletImage() {
        return new Promise((resolve, reject) => {
            if (typeof leafletImage !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-image/0.4.0/leaflet-image.min.js';
            script.onload = () => {
                console.log('✅ leaflet-image cargado');
                resolve();
            };
            script.onerror = () => {
                console.warn('⚠️ No se pudo cargar leaflet-image');
                resolve();
            };
            document.head.appendChild(script);
        });
    }

    function loadDocxLibrary() {
        return new Promise((resolve) => {
            if (typeof window.docx !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/docx@8.2.4/build/index.umd.js';
            script.onload = () => resolve();
            script.onerror = () => resolve();
            document.head.appendChild(script);
        });
    }

    function dataUrlToUint8Array(dataUrl) {
        const parts = (dataUrl || '').split(',');
        if (parts.length < 2) return null;
        const base64 = parts[1];
        const binary = atob(base64);
        const len = binary.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    function downloadBlob(blob, fileName) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    async function exportToDOCX() {
        try {
            await loadDocxLibrary();
            if (typeof window.docx === 'undefined') {
                showNotification('No se pudo cargar la librería DOCX', 'error');
                return;
            }

            showNotification('Generando DOCX con mapa...', 'info');

            Swal.fire({
                title: 'Generando DOCX',
                html: '<div id="pdf-progress">Preparando mapa... 0%</div>',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const totalItems = selectedCamerasInPolygon.length + selectedSitiosInPolygon.length;
            const fileName = `reporte_area_${new Date().toISOString().slice(0, 10)}_${totalItems}_elementos.docx`;

            const area = currentPolygon ?
                (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2) : 'N/A';

            updateProgress(20, 'Capturando imagen del mapa...');
            const mapImage = await captureMapImageOptimized(false);

            const mapBytes = mapImage ? dataUrlToUint8Array(mapImage) : null;

            updateProgress(50, 'Armando documento...');

            const {
                Document,
                Packer,
                Paragraph,
                TextRun,
                HeadingLevel,
                AlignmentType,
                Table,
                TableRow,
                TableCell,
                WidthType,
                ImageRun
            } = window.docx;

            const nowDate = new Date().toLocaleDateString('es-AR');
            const nowTime = new Date().toLocaleTimeString('es-AR');
            const reportInfo = `Fecha: ${nowDate} | Hora: ${nowTime} | Cámaras: ${selectedCamerasInPolygon.length} | Sitios Inactivos: ${selectedSitiosInPolygon.length} | Área: ${area} km²`;

            const children = [];

            children.push(new Paragraph({
                text: 'REPORTE DE ÁREA SELECCIONADA',
                heading: HeadingLevel.HEADING_1,
                alignment: AlignmentType.CENTER
            }));

            children.push(new Paragraph({
                children: [new TextRun({ text: reportInfo })],
                alignment: AlignmentType.CENTER
            }));

            children.push(new Paragraph({ text: '' }));

            if (mapBytes) {
                children.push(new Paragraph({
                    children: [
                        new ImageRun({
                            data: mapBytes,
                            transformation: { width: 800, height: 320 }
                        })
                    ],
                    alignment: AlignmentType.CENTER
                }));
            } else {
                children.push(new Paragraph({
                    children: [new TextRun({ text: 'Mapa no disponible' })],
                    alignment: AlignmentType.CENTER
                }));
            }

            children.push(new Paragraph({ text: '' }));

            children.push(new Paragraph({
                text: 'RESUMEN',
                heading: HeadingLevel.HEADING_2
            }));

            children.push(new Paragraph({ text: `• Total de Cámaras: ${selectedCamerasInPolygon.length}` }));
            children.push(new Paragraph({ text: `• Total de Sitios Inactivos: ${selectedSitiosInPolygon.length}` }));
            children.push(new Paragraph({ text: `• Superficie del Área: ${area} km²` }));

            if (selectedCamerasInPolygon.length > 0) {
                children.push(new Paragraph({ text: '' }));
                children.push(new Paragraph({
                    text: 'LISTADO DE CÁMARAS',
                    heading: HeadingLevel.HEADING_2
                }));

                const cameraHeaderRow = new TableRow({
                    children: [
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: '#', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Título', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Tipo', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Sitio', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Dependencia', bold: true })] })] })
                    ]
                });

                const cameraRows = selectedCamerasInPolygon.map((camera, index) => new TableRow({
                    children: [
                        new TableCell({ children: [new Paragraph(String(index + 1))] }),
                        new TableCell({ children: [new Paragraph(String(camera.titulo || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(camera.tipo || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(camera.sitio || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(camera.dependencia || 'N/A'))] })
                    ]
                }));

                children.push(new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [cameraHeaderRow, ...cameraRows]
                }));
            }

            if (selectedSitiosInPolygon.length > 0) {
                children.push(new Paragraph({ text: '' }));
                children.push(new Paragraph({
                    text: 'LISTADO DE SITIOS INACTIVOS',
                    heading: HeadingLevel.HEADING_2
                }));

                const sitioHeaderRow = new TableRow({
                    children: [
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: '#', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Nombre', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Estado', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Cartel', bold: true })] })] }),
                        new TableCell({ children: [new Paragraph({ children: [new TextRun({ text: 'Observaciones', bold: true })] })] })
                    ]
                });

                const sitioRows = selectedSitiosInPolygon.map((sitio, index) => new TableRow({
                    children: [
                        new TableCell({ children: [new Paragraph(String(index + 1))] }),
                        new TableCell({ children: [new Paragraph(String(sitio.titulo || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(sitio.estado || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(sitio.cartel || 'N/A'))] }),
                        new TableCell({ children: [new Paragraph(String(sitio.observaciones || 'N/A'))] })
                    ]
                }));

                children.push(new Table({
                    width: { size: 100, type: WidthType.PERCENTAGE },
                    rows: [sitioHeaderRow, ...sitioRows]
                }));
            }

            const doc = new Document({
                sections: [{
                    properties: {},
                    children
                }]
            });

            updateProgress(90, 'Generando archivo...');
            const blob = await Packer.toBlob(doc);
            downloadBlob(blob, fileName);

            updateProgress(100, 'DOCX generado exitosamente');
            setTimeout(() => {
                Swal.close();
                showNotification('DOCX exportado correctamente con mapa, cámaras y sitios inactivos', 'success');
            }, 500);

        } catch (error) {
            console.error('❌ Error exportando DOCX:', error);
            Swal.close();
            showNotification('Error al exportar DOCX: ' + error.message, 'error');
        }
    }

    // ========================================
    // EXPORTAR A CSV - CON CÁMARAS Y SITIOS INACTIVOS
    // ========================================
    function exportToCSV() {
        let csvContent = "data:text/csv;charset=utf-8,";

        // ========== SECCIÓN CÁMARAS ==========
        csvContent += "=== CÁMARAS ===\n";
        csvContent += "Nº,Título,Tipo,Sitio,Dependencia,Etapa,Instalación,Marca,Modelo,Nº Serie\n";

        selectedCamerasInPolygon.forEach((camera, index) => {
            const row = [
                index + 1,
                `\"${camera.titulo}\"`,
                `\"${camera.tipo}\"`,
                `\"${camera.sitio}\"`,
                `\"${camera.dependencia}\"`,
                `\"${camera.etapa}\"`,
                `\"${camera.instalacion}\"`,
                `\"${camera.marca}\"`,
                `\"${camera.modelo}\"`,
                `\"${camera.serie}\"`
            ].join(',');

            csvContent += row + "\n";
        });

        // ========== SECCIÓN SITIOS INACTIVOS ==========
        csvContent += "\n=== SITIOS INACTIVOS ===\n";
        csvContent += "Nº,Nombre,Estado,Cartel,Observaciones\n";

        selectedSitiosInPolygon.forEach((sitio, index) => {
            const row = [
                index + 1,
                `\"${sitio.titulo}\"`,
                `\"${sitio.estado}\"`,
                `\"${sitio.cartel}\"`,
                `\"${sitio.observaciones}\"`
            ].join(',');

            csvContent += row + "\n";
        });

        // ========== RESUMEN ==========
        csvContent += "\n=== RESUMEN ===\n";
        csvContent += `Total Cámaras,${selectedCamerasInPolygon.length}\n`;
        csvContent += `Total Sitios Inactivos,${selectedSitiosInPolygon.length}\n`;
        csvContent += `Total Elementos,${selectedCamerasInPolygon.length + selectedSitiosInPolygon.length}\n`;
        csvContent += `Fecha Exportación,${new Date().toLocaleDateString('es-AR')}\n`;

        // Crear link de descarga
        const totalItems = selectedCamerasInPolygon.length + selectedSitiosInPolygon.length;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `reporte_area_${new Date().toISOString().split('T')[0]}_${totalItems}_elementos.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification('Archivo CSV exportado con cámaras y sitios inactivos', 'success');
    }

    // ========================================
    // AGREGAR CONTROLES AL POLÍGONO
    // ========================================
    function addPolygonControls(polygon) {
        // Crear controles de polígono
        const controlDiv = L.control({ position: 'bottomright' });

        controlDiv.onAdd = function () {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
            <div style="background: white; padding: 10px; border-radius: 4px; margin-bottom: 50px;">
                <button id="ver-lista" class="btn btn-sm btn-primary" onclick="showCamerasModal()">
                    <i class="fas fa-list"></i> Ver Lista (${selectedCamerasInPolygon.length + selectedSitiosInPolygon.length})
                </button>
                <button id="limpiar-seleccion" class="btn btn-sm btn-danger" onclick="clearPolygonSelection()">
                    <i class="fas fa-trash"></i> Limpiar
                </button>
            </div>
        `;

            L.DomEvent.disableClickPropagation(div);
            return div;
        };

        controlDiv.addTo(mymap);
        polygon._controls = controlDiv;
    }

    // ========================================
    // LIMPIAR SELECCIÓN DE POLÍGONO
    // ========================================
    function clearPolygonSelection() {
        // Remover polígono
        if (currentPolygon) {
            mymap.removeLayer(currentPolygon);

            // Remover controles
            if (currentPolygon._controls) {
                mymap.removeControl(currentPolygon._controls);
            }

            currentPolygon = null;
        }

        // Restaurar marcadores de cámaras
        selectedCamerasInPolygon.forEach(camera => {
            if (camera.marker && camera.marker._originalStyle) {
                camera.marker.setIcon(camera.marker._originalStyle.icon);
            }
        });

        // Restaurar marcadores de sitios inactivos
        selectedSitiosInPolygon.forEach(sitio => {
            if (sitio.marker && sitio.marker._originalStyle) {
                sitio.marker.setIcon(sitio.marker._originalStyle.icon);
            }
        });

        selectedCamerasInPolygon = [];
        selectedSitiosInPolygon = [];

        // Cerrar modal si está abierto
        $('#camerasPolygonModal').modal('hide');

        showNotification('Selección limpiada', 'info');
    }

    // ========================================
    // CONFIGURAR EVENTOS DE MAPA
    // ========================================
    function setupMapClickEvents() {
        // Evento para hacer clic dentro del polígono
        mymap.on('click', function (e) {
            if (currentPolygon && !drawingEnabled) {
                // Verificar si el click fue dentro del polígono
                const bounds = currentPolygon.getBounds();
                if (bounds.contains(e.latlng)) {
                    const polygonPoints = currentPolygon.getLatLngs()[0];
                    if (isPointInPolygon(e.latlng, polygonPoints)) {
                        showCamerasModal();
                    }
                }
            }
        });
    }

    // ========================================
    // FUNCIÓN DE NOTIFICACIÓN
    // ========================================
    function showNotification(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(message);
        }
    }

    // Función para generar hash de contenido
    String.prototype.hashCode = function () {
        let hash = 0;
        for (let i = 0; i < this.length; i++) {
            const char = this.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16);
    };

    // ========================================
    // INICIALIZAR AL CARGAR EL DOCUMENTO
    // ========================================
    $(document).ready(function () {
        // Esperar a que el mapa esté completamente cargado
        if (typeof loadLeafletImage === 'function') {
            loadLeafletImage();
        }

        // Inicializar sistema de polígonos después de un delay
        setTimeout(function () {
            initPolygonSelectionSystem();
        }, 1500);

        // También detectar fullscreen al cargar
        detectFullscreen();
    });
</script>
