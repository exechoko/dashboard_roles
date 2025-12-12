<script>
    // ========================================
    // VARIABLES GLOBALES PARA EL SISTEMA DE POLÍGONOS
    // ========================================
    let drawingEnabled = false;
    let currentPolygon = null;
    let selectedCamerasInPolygon = [];
    let polygonDrawControl = null;

    // ========================================
    // INICIALIZACIÓN DEL SISTEMA DE POLÍGONOS
    // ========================================
    function initPolygonSelectionSystem() {
        // Esperar a que el mapa esté listo
        if (!mymap) {
            console.error('El mapa no está inicializado');
            return;
        }

        // Agregar control de dibujo de polígonos
        addPolygonDrawControl();

        // Agregar eventos de mapa
        setupMapClickEvents();
    }

    // ========================================
    // CONTROL DE DIBUJO DE POLÍGONOS
    // ========================================
    function addPolygonDrawControl() {
        // Crear botón de control personalizado
        const polygonControlDiv = L.control({ position: 'topleft' });

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
        mymap.off('click'); // Eliminar mymap.off('dblclick');

        showNotification('Selección cancelada', 'info');
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

        // ARREGLADO: Procesar clusters expandidos
        function processMarkerClusters(layer) {
            if (!layer) return 0;
            let count = 0;

            // Si es un MarkerClusterGroup, obtener todos los hijos
            if (layer instanceof L.MarkerClusterGroup) {
                console.log(`Procesando cluster con ${layer.getLayers().length} markers`);

                // Recorrer todos los markers en el cluster
                layer.eachLayer(function (marker) {
                    if (marker instanceof L.Marker) {
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
                });
            } else if (layer.eachLayer) {
                // Para otras capas
                layer.eachLayer(function (marker) {
                    if (marker instanceof L.Marker) {
                        const position = marker.getLatLng();
                        // ... mismo procesamiento
                    }
                });
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
                id: cameraId, // ID único agregado
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
    // EXTRAER CAMPO DEL CONTENIDO HTML
    // ========================================
    function extractField(html, label) {
        const regex = new RegExp(label + '\\s*<b>([^<]*)</b>', 'i');
        const match = html.match(regex);
        return match ? match[1].trim() : 'N/A';
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

        // Agregar borde o efecto visual (esto depende del tipo de marcador)
        // Como usamos divIcon con SVG, podemos modificar el HTML
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
    // MODAL CON CÁMARAS SELECCIONADAS
    // ========================================
    function showCamerasModal() {
        const modalHtml = `
        <div class="modal fade" id="camerasPolygonModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-video"></i>
                            Cámaras Seleccionadas: ${selectedCamerasInPolygon.length}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
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

        // Remover modal anterior si existe
        $('#camerasPolygonModal').remove();

        // Agregar modal al DOM
        $('body').append(modalHtml);

        // Mostrar modal
        $('#camerasPolygonModal').modal('show');

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
    // EXPORTAR A PDF
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

            // CAPTURA DEL MAPA CON MÚLTIPLES ESTRATEGIAS
            const mapImage = await captureMapImageOptimized();

            if (mapImage) {
                updateProgress(60, 'Procesando imagen...');

                // Dimensiones optimizadas para el mapa
                const imgWidth = 250; // Ancho mayor para aprovechar A4 landscape
                const imgHeight = 110; // Alto proporcional
                const xPos = (pageWidth - imgWidth) / 2;
                const yPos = 28;

                // Agregar imagen con bordes
                doc.setDrawColor(100, 100, 100);
                doc.setLineWidth(0.5);
                doc.rect(xPos - 1, yPos - 1, imgWidth + 2, imgHeight + 2);
                doc.addImage(mapImage, 'PNG', xPos, yPos, imgWidth, imgHeight, '', 'FAST');

                console.log('✅ Imagen del mapa agregada al PDF');
            } else {
                // Dibujar placeholder mejorado
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
    // CAPTURA OPTIMIZADA CON MÚLTIPLES ESTRATEGIAS
    // ========================================
    async function captureMapImageOptimized() {
        try {
            console.log('📸 Iniciando captura optimizada del mapa...');

            if (!currentPolygon) {
                console.warn('No hay polígono para capturar');
                return null;
            }

            // Guardar estado original
            const originalState = saveMapState();

            // Preparar mapa para captura
            await prepareMapForCapture();

            let capturedImage = null;

            // ESTRATEGIA 1: leaflet-image (mejor calidad para tiles)
            if (typeof leafletImage !== 'undefined') {
                console.log('🔄 Intentando con leaflet-image...');
                capturedImage = await captureWithLeafletImage();
                if (capturedImage) {
                    console.log('✅ Captura exitosa con leaflet-image');
                    await restoreMapState(originalState);
                    return capturedImage;
                }
            }

            // ESTRATEGIA 2: html2canvas optimizado para Leaflet
            if (typeof html2canvas !== 'undefined') {
                console.log('🔄 Intentando con html2canvas optimizado...');
                capturedImage = await captureWithHtml2Canvas();
                if (capturedImage) {
                    console.log('✅ Captura exitosa con html2canvas');
                    await restoreMapState(originalState);
                    return capturedImage;
                }
            }

            // ESTRATEGIA 3: Canvas manual (fallback)
            console.log('🔄 Usando método de canvas manual...');
            capturedImage = await captureWithManualCanvas();

            await restoreMapState(originalState);
            return capturedImage;

        } catch (error) {
            console.error('❌ Error en captura optimizada:', error);
            return null;
        }
    }

    // ========================================
    // GUARDAR ESTADO DEL MAPA
    // ========================================
    function saveMapState() {
        return {
            center: mymap.getCenter(),
            zoom: mymap.getZoom(),
            controls: []
        };
    }

    // ========================================
    // PREPARAR MAPA PARA CAPTURA
    // ========================================
    async function prepareMapForCapture() {
        // Ocultar todos los controles
        const controlSelectors = [
            '.leaflet-control-zoom',
            '.leaflet-control-attribution',
            '.leaflet-control-scale',
            '.leaflet-bar',
            '.geocoder-control',
            '#customLayerControl',
            '#ver-lista',
            '#limpiar-seleccion'
        ];

        controlSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = 'none';
                el.setAttribute('data-hidden-for-capture', 'true');
            });
        });

        // Ajustar vista al polígono
        if (currentPolygon) {
            const bounds = currentPolygon.getBounds();
            mymap.fitBounds(bounds, {
                padding: [50, 50],
                animate: false,
                maxZoom: 17
            });
        }

        // Forzar renderizado
        mymap.invalidateSize(true);

        // Esperar a que se carguen todos los tiles
        await waitForTilesToLoad();

        // Espera adicional para estabilidad
        await new Promise(resolve => setTimeout(resolve, 1500));
    }

    // ========================================
    // ESPERAR CARGA DE TILES
    // ========================================
    function waitForTilesToLoad() {
        return new Promise((resolve) => {
            let tilesLoading = 0;
            let checkInterval;

            const checkTiles = () => {
                const tiles = document.querySelectorAll('.leaflet-tile');
                tilesLoading = 0;

                tiles.forEach(tile => {
                    if (!tile.complete) {
                        tilesLoading++;
                    }
                });

                if (tilesLoading === 0) {
                    clearInterval(checkInterval);
                    console.log('✅ Todos los tiles cargados');
                    resolve();
                } else {
                    console.log(`⏳ Esperando ${tilesLoading} tiles...`);
                }
            };

            checkInterval = setInterval(checkTiles, 300);

            // Timeout de seguridad
            setTimeout(() => {
                clearInterval(checkInterval);
                console.log('⚠️ Timeout en carga de tiles, continuando...');
                resolve();
            }, 5000);
        });
    }

    // ========================================
    // CAPTURA CON HTML2CANVAS OPTIMIZADO
    // ========================================
    async function captureWithHtml2Canvas() {
        try {
            const mapContainer = document.getElementById('map');

            // Opciones optimizadas para Leaflet
            const options = {
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#e5e3df', // Color del fondo de OpenStreetMap
                scale: 2, // Alta calidad
                logging: false,
                windowWidth: mapContainer.scrollWidth,
                windowHeight: mapContainer.scrollHeight,
                scrollX: 0,
                scrollY: 0,
                imageTimeout: 15000,
                removeContainer: false,
                foreignObjectRendering: false, // Importante para tiles
                onclone: function (clonedDoc) {
                    const clonedMap = clonedDoc.getElementById('map');
                    if (clonedMap) {
                        // Forzar dimensiones exactas
                        clonedMap.style.width = mapContainer.offsetWidth + 'px';
                        clonedMap.style.height = mapContainer.offsetHeight + 'px';

                        // Asegurar que todos los tiles sean visibles
                        const tiles = clonedMap.querySelectorAll('.leaflet-tile');
                        tiles.forEach(tile => {
                            tile.style.opacity = '1';
                            tile.style.visibility = 'visible';
                        });
                    }
                }
            };

            const canvas = await html2canvas(mapContainer, options);
            return canvas.toDataURL('image/png', 0.95);

        } catch (error) {
            console.error('Error en html2canvas:', error);
            return null;
        }
    }

    // ========================================
    // CAPTURA CON CANVAS MANUAL (FALLBACK)
    // ========================================
    async function captureWithManualCanvas() {
        try {
            const mapContainer = document.getElementById('map');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Dimensiones del mapa
            const width = mapContainer.offsetWidth;
            const height = mapContainer.offsetHeight;

            canvas.width = width;
            canvas.height = height;

            // Fondo del mapa
            ctx.fillStyle = '#e5e3df';
            ctx.fillRect(0, 0, width, height);

            // Capturar tiles del mapa
            const tiles = mapContainer.querySelectorAll('.leaflet-tile');
            const tilePromises = [];

            tiles.forEach(tile => {
                if (tile.complete && tile.naturalWidth > 0) {
                    const promise = new Promise((resolve) => {
                        const rect = tile.getBoundingClientRect();
                        const mapRect = mapContainer.getBoundingClientRect();

                        const x = rect.left - mapRect.left;
                        const y = rect.top - mapRect.top;

                        ctx.drawImage(tile, x, y, rect.width, rect.height);
                        resolve();
                    });
                    tilePromises.push(promise);
                }
            });

            await Promise.all(tilePromises);

            // Dibujar polígono encima
            if (currentPolygon) {
                drawPolygonOnCanvas(ctx, currentPolygon, mapContainer);
            }

            // Dibujar marcadores de cámaras
            drawMarkersOnCanvas(ctx, mapContainer);

            return canvas.toDataURL('image/png', 0.95);

        } catch (error) {
            console.error('Error en canvas manual:', error);
            return null;
        }
    }

    // ========================================
    // DIBUJAR POLÍGONO EN CANVAS
    // ========================================
    function drawPolygonOnCanvas(ctx, polygon, mapContainer) {
        const bounds = polygon.getLatLngs()[0];
        const mapRect = mapContainer.getBoundingClientRect();

        ctx.beginPath();
        ctx.strokeStyle = '#3388ff';
        ctx.lineWidth = 3;
        ctx.fillStyle = 'rgba(51, 136, 255, 0.2)';

        bounds.forEach((latlng, index) => {
            const point = mymap.latLngToContainerPoint(latlng);
            if (index === 0) {
                ctx.moveTo(point.x, point.y);
            } else {
                ctx.lineTo(point.x, point.y);
            }
        });

        ctx.closePath();
        ctx.fill();
        ctx.stroke();
    }

    // ========================================
    // DIBUJAR MARCADORES EN CANVAS
    // ========================================
    function drawMarkersOnCanvas(ctx, mapContainer) {
        selectedCamerasInPolygon.forEach(camera => {
            const point = mymap.latLngToContainerPoint([camera.latitud, camera.longitud]);

            // Dibujar marcador simple
            ctx.beginPath();
            ctx.arc(point.x, point.y, 6, 0, 2 * Math.PI);
            ctx.fillStyle = '#ff0000';
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    // ========================================
    // RECORTAR CANVAS AL ÁREA DEL POLÍGONO
    // ========================================
    function cropCanvasToPolygon(canvas) {
        if (!currentPolygon) return canvas;

        const bounds = currentPolygon.getBounds();
        const nw = mymap.latLngToContainerPoint(bounds.getNorthWest());
        const se = mymap.latLngToContainerPoint(bounds.getSouthEast());

        const cropWidth = se.x - nw.x;
        const cropHeight = se.y - nw.y;

        const croppedCanvas = document.createElement('canvas');
        croppedCanvas.width = cropWidth;
        croppedCanvas.height = cropHeight;

        const ctx = croppedCanvas.getContext('2d');
        ctx.drawImage(canvas, nw.x, nw.y, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

        return croppedCanvas;
    }

    // ========================================
    // RESTAURAR ESTADO DEL MAPA
    // ========================================
    async function restoreMapState(state) {
        // Restaurar vista
        mymap.setView(state.center, state.zoom, { animate: false });

        // Mostrar controles ocultos
        const hiddenElements = document.querySelectorAll('[data-hidden-for-capture]');
        hiddenElements.forEach(el => {
            el.style.display = '';
            el.removeAttribute('data-hidden-for-capture');
        });

        // Forzar renderizado
        mymap.invalidateSize(true);

        await new Promise(resolve => setTimeout(resolve, 300));
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
                resolve(); // No rechazar, continuar con fallback
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
        const controlDiv = L.control({ position: 'topleft' });

        controlDiv.onAdd = function () {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
            <div style="background: white; padding: 10px; border-radius: 4px;">
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

    // Función auxiliar para verificar punto en polígono
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
    // FUNCIÓN DE NOTIFICACIÓN (reutilizar la existente o crear una nueva)
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

    // ========================================
    // CAPTURAR IMAGEN DEL MAPA
    // ========================================
    async function captureMapImage() {
        try {
            if (!currentPolygon) {
                console.warn('No hay polígono para capturar');
                return null;
            }

            console.log('📸 Iniciando captura del mapa...');

            // Mostrar notificación de progreso
            Swal.fire({
                title: 'Capturando mapa...',
                text: 'Por favor espera, esto puede tomar unos segundos.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Guardar el estado actual del mapa
            const originalView = {
                center: mymap.getCenter(),
                zoom: mymap.getZoom()
            };

            // ========================================
            // PASO 1: PREPARAR EL MAPA PARA CAPTURA
            // ========================================

            // 1.1. Ocultar TODOS los controles del mapa
            const elementsToHide = [
                // Controles de Leaflet
                document.querySelector('.leaflet-control-zoom'),
                document.querySelector('.leaflet-control-attribution'),
                document.querySelector('.leaflet-control-scale'),

                // Nuestros controles personalizados
                document.getElementById('customLayerControl'),
                document.getElementById('togglePolygonDraw'),
                document.getElementById('toggleMapBtn')?.parentElement,
                document.querySelector('.geocoder-control'),
                document.querySelector('#fullscreen-button')?.parentElement,

                // Controles de polígono si existen
                document.getElementById('ver-lista'),
                document.getElementById('limpiar-seleccion')
            ];

            const originalDisplayValues = [];
            elementsToHide.forEach(el => {
                if (el) {
                    originalDisplayValues.push({
                        element: el,
                        display: el.style.display
                    });
                    el.style.display = 'none';
                }
            });

            // ARREGLADO: Forzar actualización del tamaño del mapa
            setTimeout(() => {
                mymap.invalidateSize(true);
            }, 100);

            // ========================================
            // PASO 2: CENTRAR CORRECTAMENTE EL POLÍGONO
            // ========================================

            // ARREGLADO: Usar fitBounds con padding específico para PDF
            if (currentPolygon) {
                const bounds = currentPolygon.getBounds();
                const mapSize = mymap.getSize();

                // Calcular padding proporcional para mejor centrado
                const paddingPercent = 0.10; // 10% de padding

                const paddingPixels = {
                    top: Math.floor(mapSize.y * paddingPercent),
                    bottom: Math.floor(mapSize.y * paddingPercent),
                    left: Math.floor(mapSize.x * paddingPercent),
                    right: Math.floor(mapSize.x * paddingPercent)
                };

                // Ajustar el zoom para incluir todo el polígono
                mymap.fitBounds(bounds, {
                    paddingTopLeft: [paddingPixels.left, paddingPixels.top],
                    paddingBottomRight: [paddingPixels.right, paddingPixels.bottom],
                    animate: false,
                    maxZoom: 18, // Limitar zoom máximo para mejor captura
                    duration: 0 // Sin animación
                });
            }

            // ========================================
            // PASO 3: ESPERAR A QUE EL MAPA SE ESTABILICE
            // ========================================

            // Esperar a que se carguen los tiles
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Forzar otro redibujado
            mymap.invalidateSize(true);
            await new Promise(resolve => setTimeout(resolve, 500));

            // ========================================
            // PASO 4: CAPTURAR LA IMAGEN CORRECTAMENTE
            // ========================================

            let capturedImage = null;

            if (typeof html2canvas !== 'undefined') {
                console.log('📷 Capturando con html2canvas...');

                // Obtener el contenedor del mapa
                const mapContainer = document.getElementById('map');

                //Calcular posición exacta del mapa en la página
                const rect = mapContainer.getBoundingClientRect();

                // Opciones de html2canvas optimizadas para mapas
                const options = {
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    scale: 1.5, // Resolución media para mejor balance
                    logging: true,
                    width: rect.width,
                    height: rect.height,
                    x: rect.left + window.scrollX,
                    y: rect.top + window.scrollY,
                    scrollX: 0,
                    scrollY: 0,
                    ignoreElements: function (element) {
                        // Ignorar elementos que no son parte del mapa
                        return !mapContainer.contains(element);
                    },
                    onclone: function (clonedDoc) {
                        // ARREGLADO: Asegurar que el clon tenga las mismas dimensiones
                        const clonedMap = clonedDoc.getElementById('map');
                        if (clonedMap) {
                            clonedMap.style.position = 'absolute';
                            clonedMap.style.left = '0';
                            clonedMap.style.top = '0';
                            clonedMap.style.width = rect.width + 'px';
                            clonedMap.style.height = rect.height + 'px';
                        }
                    }
                };

                try {
                    const canvas = await html2canvas(mapContainer, options);
                    capturedImage = canvas.toDataURL('image/png', 1.0);
                    console.log('✅ Imagen capturada exitosamente');

                } catch (error) {
                    console.error('Error con html2canvas:', error);

                    // Método alternativo: captura simple del contenedor
                    capturedImage = await simpleMapCapture();
                }
            }

            // ========================================
            // PASO 5: RESTAURAR TODO
            // ========================================

            // Restaurar vista original
            mymap.setView(originalView.center, originalView.zoom, { animate: false });

            // Restaurar controles
            originalDisplayValues.forEach(item => {
                if (item.element) {
                    item.element.style.display = item.display;
                }
            });

            // Forzar redibujado final
            mymap.invalidateSize(true);

            Swal.close();
            return capturedImage;

        } catch (error) {
            console.error('❌ Error capturando imagen del mapa:', error);

            // Restaurar controles en caso de error
            const hiddenElements = document.querySelectorAll('[style*="display: none"]');
            hiddenElements.forEach(el => {
                el.style.display = '';
            });

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo capturar la imagen del mapa. Intenta nuevamente.'
            });

            return null;
        }
    }

    // Método alternativo simplificado
    async function simpleMapCapture() {
        const mapContainer = document.getElementById('map');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = mapContainer.offsetWidth;
        canvas.height = mapContainer.offsetHeight;

        // Capturar la vista actual del mapa
        const mapImage = new Image();
        mapImage.src = mymap.getContainer().toDataURL('image/png');

        return new Promise((resolve) => {
            mapImage.onload = function () {
                ctx.drawImage(mapImage, 0, 0);
                resolve(canvas.toDataURL('image/png'));
            };
            mapImage.onerror = function () {
                resolve(null);
            };
        });
    }

    // ========================================
    // CAPTURA CON LEAFLET-IMAGE
    // ========================================
    function captureWithLeafletImage() {
        return new Promise((resolve) => {
            if (typeof leafletImage === 'undefined') {
                resolve(null);
                return;
            }

            try {
                leafletImage(mymap, function (err, canvas) {
                    if (err) {
                        console.error('Error en leafletImage:', err);
                        resolve(null);
                        return;
                    }

                    // Recortar canvas al área visible
                    const croppedCanvas = cropCanvasToPolygon(canvas);
                    resolve(croppedCanvas.toDataURL('image/png', 0.95));
                });
            } catch (error) {
                console.error('Error ejecutando leafletImage:', error);
                resolve(null);
            }
        });
    }

    // ========================================
    // FUNCIÓN MEJORADA PARA AGREGAR LEYENDA
    // ========================================
    function addTemporaryLegend() {
        const area = currentPolygon
            ? (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2)
            : 'N/A';

        const legend = L.control({ position: 'topright' });

        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');

            // Estilos para mejor visibilidad en la captura
            div.style.cssText = `
            background-color: rgba(255, 255, 255, 0.95) !important;
            padding: 15px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
            font-size: 14px !important;
            font-family: Arial, sans-serif !important;
            border: 3px solid #3388ff !important;
            z-index: 1000 !important;
            min-width: 200px !important;
            backdrop-filter: blur(5px) !important;
        `;

            const fecha = new Date().toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            div.innerHTML = `
            <div style="text-align: center; margin-bottom: 10px;">
                <strong style="color: #3388ff; font-size: 16px;">📍 ÁREA SELECCIONADA</strong>
            </div>
            <div style="line-height: 1.6;">
                <div style="display: flex; justify-content: space-between;">
                    <span>📹 Cámaras:</span>
                    <strong style="color: #e74c3c;">${selectedCamerasInPolygon.length}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>📐 Superficie:</span>
                    <strong style="color: #27ae60;">${area} km²</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>📅 Fecha:</span>
                    <strong>${fecha}</strong>
                </div>
            </div>
            <hr style="margin: 10px 0; border-color: #ddd;">
            <div style="font-size: 12px; color: #7f8c8d; text-align: center;">
                <i class="fas fa-map-marker-alt"></i> Sistema de Videovigilancia
            </div>
        `;

            return div;
        };

        legend.addTo(mymap);
        return legend;
    }

    // ========================================
    // FUNCIÓN AUXILIAR PARA CALCULAR PADDING DINÁMICO
    // ========================================
    function calculateDynamicPadding(bounds, mapSize) {
        const boundsSize = mymap.latLngToLayerPoint(bounds.getNorthEast())
            .subtract(mymap.latLngToLayerPoint(bounds.getSouthWest()));

        // Calcular qué porcentaje del mapa ocupa el polígono
        const widthRatio = boundsSize.x / mapSize.x;
        const heightRatio = boundsSize.y / mapSize.y;

        // Si ocupa más del 80% del mapa, usar poco padding
        if (widthRatio > 0.8 || heightRatio > 0.8) {
            return {
                top: 10,
                bottom: 10,
                left: 10,
                right: 10
            };
        }

        // Si ocupa menos del 50%, usar más padding
        if (widthRatio < 0.5 && heightRatio < 0.5) {
            return {
                top: Math.floor(mapSize.y * 0.2),
                bottom: Math.floor(mapSize.y * 0.2),
                left: Math.floor(mapSize.x * 0.2),
                right: Math.floor(mapSize.x * 0.2)
            };
        }

        // Caso intermedio
        return {
            top: Math.floor(mapSize.y * 0.15),
            bottom: Math.floor(mapSize.y * 0.15),
            left: Math.floor(mapSize.x * 0.15),
            right: Math.floor(mapSize.x * 0.15)
        };
    }

    // ========================================
    // EXPANDIR CLUSTERS PARA CAPTURA
    // ========================================
    function expandAllClusters() {
        const clusterLayers = [
            marcadores,
            markersCamarasLPR,
            markersCamarasFR,
            markersCamarasFijas,
            markersCamarasDomos,
            markersCamarasDomosDuales,
            markersBDE
        ];

        clusterLayers.forEach(layer => {
            if (layer && layer instanceof L.MarkerClusterGroup) {
                // Desactivar clustering completamente
                layer.disableClusteringAtZoom = 1; // Forzar desde zoom 1

                // Refrescar el layer para aplicar cambios
                layer.refreshClusters();

                // Alternativa: obtener todos los markers y agregarlos temporalmente sin cluster
                const allMarkers = [];
                layer.eachLayer(function (marker) {
                    allMarkers.push(marker);
                });

                console.log(`Layer tiene ${allMarkers.length} marcadores`);
            }
        });
    }

    // ========================================
    // INICIALIZAR AL CARGAR EL DOCUMENTO
    // ========================================
    $(document).ready(function () {
        // Esperar a que el mapa esté completamente cargado
        loadLeafletImage();
        setTimeout(function () {
            initPolygonSelectionSystem();
        }, 1000);
    });
</script>
