<script>
    // ========================================
    // VARIABLES GLOBALES PARA EL SISTEMA DE POLÍGONOS
    // ========================================
    let drawingEnabled = false;
    let currentPolygon = null;
    let selectedCamerasInPolygon = [];
    let polygonDrawControl = null;
    let isFullscreen = false;

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
    // FUNCIÓN AUXILIAR PARA VERIFICAR PUNTO EN POLÍGONO
    // ========================================
    function isPointInPolygon(point, vs) {
        const x = point.lat;
        const y = point.lng;
        let inside = false;

        for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
            const xi = vs[i].lat;
            const yi = vs[i].lng;
            const xj = vs[j].lat;
            const yj = vs[j].lng;

            const intersect = ((yi > y) !== (yj > y)) &&
                (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }

        return inside;
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
    // EXTRAER INFORMACIÓN DE LA CÁMARA
    // ========================================
    function extractCameraInfo(marker) {
        try {
            const popup = marker.getPopup();
            if (!popup) return null;

            const content = popup.getContent();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;

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
    // BUSCAR CÁMARAS DENTRO DEL POLÍGONO
    // ========================================
    function findCamerasInPolygon(polygon) {
        console.log('🔍 Buscando cámaras en polígono...');
        selectedCamerasInPolygon = [];
        const bounds = polygon.getBounds();
        const seenCameras = new Set();

        console.log('📦 Bounds del polígono:', bounds);

        // Función para procesar marcadores en clusters
        function processMarkerClusters(layer) {
            if (!layer) return 0;
            let count = 0;

            // Verificar si es un MarkerClusterGroup antes de usar métodos específicos
            const isMarkerCluster = layer instanceof L.MarkerClusterGroup;
            const isLayerGroup = layer instanceof L.LayerGroup;

            if (!isMarkerCluster && !isLayerGroup) {
                console.log('  ⚠️ Capa no compatible:', layer);
                return 0;
            }

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
                        cameraInfo.id = cameraId;
                        selectedCamerasInPolygon.push(cameraInfo);
                        seenCameras.add(cameraId);
                        highlightCameraMarker(marker);
                        count++;
                    }
                }
            }

            if (isMarkerCluster) {
                console.log(`Procesando MarkerClusterGroup con ${layer.getLayers().length} markers`);
                layer.eachLayer(processMarker);
            } else if (isLayerGroup) {
                console.log(`Procesando LayerGroup con ${layer.getLayers().length} markers`);
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

        // Mostrar resultados
        if (selectedCamerasInPolygon.length > 0) {
            showNotification(`${selectedCamerasInPolygon.length} cámaras encontradas`, 'success');
            showCamerasModal();
        } else {
            showNotification('No se encontraron cámaras en esta área', 'warning');
        }
    }

    // ========================================
    // MODAL CON CÁMARAS SELECCIONADAS
    // ========================================
    function showCamerasModal() {
        // Cerrar modal existente si hay
        $('#camerasPolygonModal').modal('hide');
        $('#camerasPolygonModal').remove();
        $('.modal-backdrop').remove();

        // Crear nuevo modal
        const modalHtml = `
        <div class="modal fade" id="camerasPolygonModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl modal-fullscreen-md-down" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-video"></i>
                            Cámaras Seleccionadas: ${selectedCamerasInPolygon.length}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Barra de búsqueda y botones de exportación -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text"
                                       id="searchCameraInList"
                                       class="form-control"
                                       placeholder="Buscar en la lista...">
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button class="btn btn-danger" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="btn btn-info" onclick="exportToCSV()">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de cámaras -->
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-striped table-hover" id="camerasTable">
                                <thead class="thead-dark" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th>#</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Sitio</th>
                                        <th>Dependencia</th>
                                        <th>Latitud</th>
                                        <th>Longitud</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="camerasTableBody">
                                    ${generateCamerasTableRows()}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
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

        // Agregar funcionalidad de búsqueda
        $('#searchCameraInList').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            $('#camerasTableBody tr').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        });
    }

    // ========================================
    // GENERAR FILAS DE LA TABLA
    // ========================================
    function generateCamerasTableRows() {
        return selectedCamerasInPolygon.map((camera, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${camera.titulo}</strong></td>
            <td>${camera.tipo}</td>
            <td>${camera.sitio}</td>
            <td>${camera.dependencia}</td>
            <td>${camera.latitud}</td>
            <td>${camera.longitud}</td>
            <td>
                <button class="btn btn-sm btn-info"
                        onclick="zoomToCamera(${index})"
                        title="Ver en mapa">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
                <button class="btn btn-sm btn-primary"
                        onclick="showCameraDetails(${index})"
                        title="Ver detalles">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
        </tr>
    `).join('');
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
    // EXPORTAR A EXCEL
    // ========================================
    function exportToExcel() {
        if (typeof XLSX === 'undefined') {
            showNotification('Librería XLSX no cargada', 'error');
            return;
        }

        const data = selectedCamerasInPolygon.map((camera, index) => ({
            'Nº': index + 1,
            'Título': camera.titulo,
            'Tipo': camera.tipo,
            'Sitio': camera.sitio,
            'Dependencia': camera.dependencia,
            'Latitud': camera.latitud,
            'Longitud': camera.longitud,
            'Etapa': camera.etapa,
            'Instalación': camera.instalacion,
            'Marca': camera.marca,
            'Modelo': camera.modelo,
            'Nº Serie': camera.serie
        }));

        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cámaras');

        const fileName = `camaras_seleccionadas_${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(wb, fileName);

        showNotification('Archivo Excel exportado correctamente', 'success');
    }

    // ========================================
    // EXPORTAR A PDF - VERSIÓN MEJORADA
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

            // Título
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('REPORTE DE CÁMARAS - ÁREA SELECCIONADA', pageWidth / 2, 15, { align: 'center' });

            // Información del reporte
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            const area = currentPolygon ?
                (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2) : 'N/A';
            const reportInfo = `Fecha: ${new Date().toLocaleDateString('es-AR')} | Hora: ${new Date().toLocaleTimeString('es-AR')} | Cámaras: ${selectedCamerasInPolygon.length} | Área: ${area} km²`;
            doc.text(reportInfo, pageWidth / 2, 22, { align: 'center' });

            // Actualizar progreso
            updateProgress(20, 'Capturando imagen del mapa...');

            // CAPTURA DEL MAPA
            const mapImage = await captureMapImageOptimized();

            if (mapImage) {
                updateProgress(60, 'Procesando imagen...');

                // Dimensiones optimizadas para el mapa
                const imgWidth = 250;
                const imgHeight = 110;
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
                doc.rect(xPos, yPos, 250, 110, 'F');
                doc.setDrawColor(200, 200, 200);
                doc.setLineWidth(1);
                doc.rect(xPos, yPos, 250, 110);

                doc.setFontSize(14);
                doc.setTextColor(150, 150, 150);
                doc.text('Mapa no disponible', pageWidth / 2, yPos + 55, { align: 'center' });

                console.warn('⚠️ No se pudo capturar el mapa');
            }

            updateProgress(70, 'Generando tabla de datos...');

            // Tabla de datos mejorada
            const tableData = selectedCamerasInPolygon.map((camera, index) => [
                index + 1,
                camera.titulo.substring(0, 35),
                camera.tipo || 'N/A',
                camera.sitio || 'N/A',
                camera.dependencia || 'N/A',
                `${camera.latitud}, ${camera.longitud}`
            ]);

            doc.autoTable({
                head: [['#', 'Título', 'Tipo', 'Sitio', 'Dependencia', 'Ubicación']],
                body: tableData,
                startY: 145,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
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
                    1: { cellWidth: 60 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 35 },
                    4: { cellWidth: 40 },
                    5: { cellWidth: 50, fontSize: 7 }
                },
                margin: { left: 14, right: 14 },
                didDrawPage: function (data) {
                    // Footer en cada página
                    doc.setFontSize(8);
                    doc.setTextColor(128);
                    doc.text(
                        `Sistema de Videovigilancia - Página ${doc.internal.getCurrentPageInfo().pageNumber}`,
                        pageWidth / 2,
                        pageHeight - 10,
                        { align: 'center' }
                    );
                }
            });

            updateProgress(90, 'Finalizando PDF...');

            // Guardar PDF
            const fileName = `camaras_seleccionadas_${new Date().toISOString().slice(0, 10)}_${selectedCamerasInPolygon.length}.pdf`;
            doc.save(fileName);

            updateProgress(100, 'PDF generado exitosamente');

            setTimeout(() => {
                Swal.close();
                showNotification('PDF exportado correctamente', 'success');
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
    async function captureMapImageOptimized() {
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

            try {
                // Preparar mapa (ocultar controles y marcadores)
                await prepareMapForCapture();

                // Capturar imagen del área completa
                let capturedImage = null;

                if (typeof html2canvas !== 'undefined') {
                    console.log('🔄 Capturando con html2canvas...');
                    capturedImage = await captureMapAreaOnly();
                }

                // Si falla html2canvas, usar canvas manual
                if (!capturedImage) {
                    console.log('🔄 Usando captura manual...');
                    capturedImage = await captureWithManualCanvasSimple();
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
    async function captureMapAreaOnly() {
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
                drawMarkersOnCanvas(ctx, mapContainer);

                return canvas.toDataURL('image/png', 0.92);
            }

            return null;

        } catch (error) {
            console.error('❌ Error en captura:', error);
            return null;
        }
    }

    // ========================================
    // CAPTURA MANUAL SIMPLIFICADA
    // ========================================
    async function captureWithManualCanvasSimple() {
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
            drawMarkersOnCanvas(ctx, mapContainer);

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

    function calculateMarkerOffset(camera, allCameras, groupIndex) {
        const currentLat = parseFloat(camera.latitud);
        const currentLng = parseFloat(camera.longitud);

        // 1. Tolerancia más flexible (aprox 1 metro de diferencia se considera "mismo lugar")
        const samePositionCameras = allCameras.filter(cam => {
            const latDiff = Math.abs(parseFloat(cam.latitud) - currentLat);
            const lngDiff = Math.abs(parseFloat(cam.longitud) - currentLng);
            return latDiff < 0.00005 && lngDiff < 0.00005;
        });

        const totalInPosition = samePositionCameras.length;
        if (totalInPosition <= 1) return { lat: 0, lng: 0 };

        const indexInGroup = samePositionCameras.findIndex(cam => cam.id === camera.id);

        // AJUSTE: Radio dinámico (Espiral de Arquímedes)
        // El radio base es pequeño, pero crece por cada marcador en el grupo
        const baseRadius = 0.0002;
        const distanceStep = 0.00015; // Cuánto se aleja por cada vuelta
        const angleStep = (2 * Math.PI) / (totalInPosition > 8 ? 8 : totalInPosition);

        const angle = indexInGroup * angleStep;
        const dynamicRadius = baseRadius + (indexInGroup * distanceStep);

        return {
            lat: dynamicRadius * Math.cos(angle),
            lng: dynamicRadius * Math.sin(angle)
        };
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
    // DIBUJAR MARCADORES EN CANVAS
    // ========================================
    function drawMarkersOnCanvas(ctx, mapContainer) {
        console.log(`🎯 Dibujando ${selectedCamerasInPolygon.length} marcadores en canvas...`);

        selectedCamerasInPolygon.forEach((camera, index) => {
            try {
                const point = mymap.latLngToContainerPoint([camera.latitud, camera.longitud]);

                if (point.x < 0 || point.y < 0 ||
                    point.x > mapContainer.offsetWidth ||
                    point.y > mapContainer.offsetHeight) {
                    return;
                }

                const pinHeight = 30;
                const pinWidth = 24;
                const x = point.x;
                const y = point.y;

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

    // ========================================
    // EXPORTAR A CSV
    // ========================================
    function exportToCSV() {
        let csvContent = "data:text/csv;charset=utf-8,";

        // Encabezados
        csvContent += "Nº,Título,Tipo,Sitio,Dependencia,Latitud,Longitud,Etapa,Instalación,Marca,Modelo,Nº Serie\n";

        // Datos
        selectedCamerasInPolygon.forEach((camera, index) => {
            const row = [
                index + 1,
                `"${camera.titulo}"`,
                `"${camera.tipo}"`,
                `"${camera.sitio}"`,
                `"${camera.dependencia}"`,
                camera.latitud,
                camera.longitud,
                `"${camera.etapa}"`,
                `"${camera.instalacion}"`,
                `"${camera.marca}"`,
                `"${camera.modelo}"`,
                `"${camera.serie}"`
            ].join(',');

            csvContent += row + "\n";
        });

        // Crear link de descarga
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `camaras_seleccionadas_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification('Archivo CSV exportado correctamente', 'success');
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
                    <i class="fas fa-list"></i> Ver Lista (${selectedCamerasInPolygon.length})
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

        // Restaurar marcadores
        selectedCamerasInPolygon.forEach(camera => {
            if (camera.marker && camera.marker._originalStyle) {
                camera.marker.setIcon(camera.marker._originalStyle.icon);
            }
        });

        selectedCamerasInPolygon = [];

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
