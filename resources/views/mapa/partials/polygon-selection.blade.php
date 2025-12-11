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

        // Función para verificar si un punto está dentro del polígono
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

        // Buscar en todas las capas de cámaras
        const allCameraLayers = [
            marcadores,
            markersCamarasLPR,
            markersCamarasFR,
            markersCamarasFijas,
            markersCamarasDomos,
            markersCamarasDomosDuales,
            markersBDE
        ];

        console.log('📹 Capas a revisar:', allCameraLayers.length);
        let totalMarkersChecked = 0;

        allCameraLayers.forEach((layer, layerIndex) => {
            console.log(`Revisando capa ${layerIndex}:`, layer);
            layer.eachLayer(function (marker) {
                if (marker instanceof L.Marker) {
                    const position = marker.getLatLng();
                    const cameraKey = `${position.lat.toFixed(6)},${position.lng.toFixed(6)}`;

                    console.log(`  📍 Marcador en: ${cameraKey}`);

                    if (seenCameras.has(cameraKey)) {
                        console.log(`  ⚠️ Cámara duplicada, saltando...`);
                        return;
                    }

                    // Verificar si está dentro del polígono
                    if (bounds.contains(position) &&
                        isPointInPolygon(position, polygon.getLatLngs()[0])) {
                        console.log(`  ✅ Cámara DENTRO del polígono`);

                        // Extraer información de la cámara
                        const cameraInfo = extractCameraInfo(marker);
                        if (cameraInfo) {
                            selectedCamerasInPolygon.push(cameraInfo);
                            seenCameras.add(cameraKey);
                            // Resaltar marcador
                            highlightCameraMarker(marker);
                        } else {
                            console.log(`  ❌ No se pudo extraer info de la cámara`);
                        }
                    } else {
                        console.log(`  ❌ Cámara FUERA del polígono`);
                    }
                }
            });
        });

        console.log(`📊 Total de marcadores revisados: ${totalMarkersChecked}`);
        console.log(`🎯 Cámaras encontradas: ${selectedCamerasInPolygon.length}`);


        // Mostrar resultados
        if (selectedCamerasInPolygon.length > 0) {
            console.log('✅ Mostrando modal...');
            showNotification(`${selectedCamerasInPolygon.length} cámaras encontradas`, 'success');
            showCamerasModal();
        } else {
            console.log('⚠️ No se encontraron cámaras');
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

            // Extraer datos del popup
            const titulo = tempDiv.querySelector('h5')?.textContent || 'N/A';
            const position = marker.getLatLng();

            // Extraer información adicional
            const info = {
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
            console.error('Error extrayendo info de cámara:', error);
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

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');

            // Capturar imagen del mapa
            const mapImage = await captureMapImage();

            // Título
            doc.setFontSize(16);
            doc.text('Reporte de Cámaras Seleccionadas', 14, 15);

            doc.setFontSize(10);
            const area = currentPolygon ? (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2) : 'N/A';
            doc.text(`Fecha: ${new Date().toLocaleDateString()} | Total: ${selectedCamerasInPolygon.length} cámaras | Área: ${area} km²`, 14, 22);

            // Agregar imagen del mapa
            if (mapImage) {
                doc.addImage(mapImage, 'PNG', 14, 28, 120, 80);
            }

            // Preparar datos para tabla
            const tableData = selectedCamerasInPolygon.map((camera, index) => [
                index + 1,
                camera.titulo,
                camera.tipo,
                camera.sitio,
                camera.dependencia,
                `${camera.latitud}, ${camera.longitud}`,
                camera.instalacion
            ]);

            // Generar tabla
            doc.autoTable({
                head: [['#', 'Título', 'Tipo', 'Sitio', 'Dependencia', 'Ubicación', 'Instalación']],
                body: tableData,
                startY: 115,
                theme: 'grid',
                styles: { fontSize: 8 },
                headStyles: { fillColor: [41, 128, 185] }
            });

            const fileName = `camaras_seleccionadas_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(fileName);

            showNotification('Archivo PDF exportado correctamente', 'success');
        } catch (error) {
            console.error('Error exportando PDF:', error);
            showNotification('Error al exportar PDF', 'error');
        }
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
                <button class="btn btn-sm btn-primary" onclick="showCamerasModal()">
                    <i class="fas fa-list"></i> Ver Lista (${selectedCamerasInPolygon.length})
                </button>
                <button class="btn btn-sm btn-danger" onclick="clearPolygonSelection()">
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

            // Guardar el estado actual del mapa
            const originalView = {
                center: mymap.getCenter(),
                zoom: mymap.getZoom()
            };

            // AGREGAR LEYENDA TEMPORAL
            const tempLegend = addTemporaryLegend();

            // Ocultar temporalmente los controles y elementos UI
            const elementsToHide = [
                document.getElementById('customLayerControl'),
                document.querySelector('.leaflet-control-zoom'),
                document.querySelector('.geocoder-control'),
                document.querySelector('#toggleMapBtn')?.parentElement,
                document.querySelector('#fullscreen-button')?.parentElement
            ];

            // Ocultar controles (pero NO la leyenda)
            elementsToHide.forEach(el => {
                if (el) {
                    el.style.display = 'none';
                }
            });

            // Ajustar la vista al polígono con padding
            const bounds = currentPolygon.getBounds();
            mymap.fitBounds(bounds, {
                padding: [50, 50],
                animate: false
            });

            // Esperar a que se carguen los tiles
            await new Promise(resolve => setTimeout(resolve, 800));

            // Capturar solo el contenedor del mapa
            const mapElement = document.getElementById('map');

            if (typeof html2canvas !== 'undefined') {
                const canvas = await html2canvas(mapElement, {
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    scale: 2,
                    logging: false,
                    width: mapElement.offsetWidth,
                    height: mapElement.offsetHeight,
                    windowWidth: mapElement.offsetWidth,
                    windowHeight: mapElement.offsetHeight
                });

                // REMOVER LEYENDA TEMPORAL
                mymap.removeControl(tempLegend);

                // Restaurar controles
                elementsToHide.forEach(el => {
                    if (el) {
                        el.style.display = '';
                    }
                });

                // Restaurar vista original
                mymap.setView(originalView.center, originalView.zoom, {
                    animate: false
                });

                return canvas.toDataURL('image/png');
            } else {
                console.warn('html2canvas no está cargado');

                // Limpiar
                mymap.removeControl(tempLegend);
                elementsToHide.forEach(el => {
                    if (el) {
                        el.style.display = '';
                    }
                });
                mymap.setView(originalView.center, originalView.zoom, {
                    animate: false
                });

                return null;
            }
        } catch (error) {
            console.error('Error capturando imagen del mapa:', error);

            // Restaurar en caso de error
            const elementsToShow = [
                document.getElementById('customLayerControl'),
                document.querySelector('.leaflet-control-zoom')
            ];

            elementsToShow.forEach(el => {
                if (el) {
                    el.style.display = '';
                }
            });

            return null;
        }
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
        if (layer && typeof layer.eachLayer === 'function') {
            // Forzar el zoom máximo para que se expandan los clusters
            const maxZoom = layer.options.maxZoom || 18;
            if (mymap.getZoom() < maxZoom) {
                // Los clusters se expandirán automáticamente al hacer zoom
            }
        }
    });
}

    // ========================================
    // AGREGAR LEYENDA TEMPORAL AL MAPA
    // ========================================
    function addTemporaryLegend() {
    const area = currentPolygon
        ? (L.GeometryUtil.geodesicArea(currentPolygon.getLatLngs()[0]) / 1000000).toFixed(2)
        : 'N/A';

    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'map-legend');
        div.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        div.style.padding = '12px';
        div.style.borderRadius = '8px';
        div.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
        div.style.fontSize = '13px';
        div.style.fontFamily = 'Arial, sans-serif';
        div.style.border = '2px solid #3388ff';
        div.innerHTML = `
            <strong style="color: #3388ff; font-size: 14px;">📍 Área Seleccionada</strong><br>
            <div style="margin-top: 6px;">
                📹 Cámaras: <strong>${selectedCamerasInPolygon.length}</strong><br>
                📐 Superficie: <strong>${area} km²</strong><br>
                📅 ${new Date().toLocaleDateString('es-AR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                })}
            </div>
        `;
        return div;
    };

    legend.addTo(mymap);
    return legend;
}

    // ========================================
    // INICIALIZAR AL CARGAR EL DOCUMENTO
    // ========================================
    $(document).ready(function () {
        // Esperar a que el mapa esté completamente cargado
        setTimeout(function () {
            initPolygonSelectionSystem();
        }, 1000);
    });
</script>
