<script>
// Función para redirigir a la ruta de edición
function editCamera(camaraId) {
    @can('editar-camara')
        window.location.href = '/camaras/' + camaraId + '/edit';
    @endcan
}

function openGoogleMaps(latitud, longitud) {
    // Abre Google Maps en una nueva pestaña con la ubicación especificada
    window.open(`https://www.google.com/maps?q=${latitud},${longitud}`, '_blank');
}

function openStreetView(latitud, longitud) {
    // Abre Google Maps en una nueva pestaña con el enlace directo a Street View
    window.open(`https://www.google.com/maps?q=&layer=c&cbll=${latitud},${longitud}`, '_blank');
}

function getRandomColor() {
    var letters = '0123456789ABCDEF';
    var color = '#';
    var ok = false;
    color += letters[Math.floor(Math.random() * 10)];
    while (!ok) {
        for (var i = 0; i < 5; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        if (!inArray(color, colores)) {
            break;
        }
    }
    colores.push(color);
    return color;
}

function getColor(d) {
    return d > 1000 ? '#800026' :
        d > 500 ? '#BD0026' :
            d > 200 ? '#E31A1C' :
                d > 100 ? '#FC4E2A' :
                    d > 50 ? '#FD8D3C' :
                        d > 20 ? '#FEB24C' :
                            d > 10 ? '#FED976' :
                                '#FFEDA0';
}

// Función para convertir orientación a grados
function getOrientationDegrees(orientacion) {
    switch (orientacion?.toLowerCase()) {
        case 'norte':
        case 'n':
            return 0;
        case 'noreste':
        case 'ne':
            return 45;
        case 'este':
        case 'e':
            return 90;
        case 'sureste':
        case 'se':
            return 135;
        case 'sur':
        case 's':
            return 180;
        case 'suroeste':
        case 'so':
        case 'sw':
            return 225;
        case 'oeste':
        case 'o':
        case 'w':
            return 270;
        case 'noroeste':
        case 'no':
        case 'nw':
            return 315;
        default:
            return 0; // Por defecto norte
    }
}

// Función para generar el path del SVG basado en ángulo y orientación
function generateCameraPath(angulo, orientacion) {
    // Radio del sector (distancia visual del campo de visión)
    const radio = 25;

    // Si es 360°, devolver un círculo completo
    if (parseFloat(angulo) === 360) {
        return {
            path: `<circle cx="0" cy="0" r="${radio}" fill="rgba(0,255,0,0.3)" />`,
            rotation: 0 // no rota porque es simétrico
        };
    }

    // Convertir ángulo de apertura a radianes (dividir por 2 para cada lado)
    const anguloApertura = (angulo || 60) / 2; // Default 60 grados si no se especifica
    const anguloRad = (anguloApertura * Math.PI) / 180;

    // Convertir orientación a grados
    const orientacionGrados = getOrientationDegrees(orientacion);

    // Calcular puntos del arco
    const x1 = radio * Math.cos(anguloRad);
    const y1 = -radio * Math.sin(anguloRad);
    const x2 = radio * Math.cos(-anguloRad);
    const y2 = -radio * Math.sin(-anguloRad);

    // Crear el path del sector
    const path = `M0,0 L${x1},${y1} A${radio},${radio} 0 0,1 ${x2},${y2} Z`;

    return {
        path: path,
        rotation: orientacionGrados - 90
    };
}

// Función para alternar el control de capas
function toggleLayerControl() {
    const content = document.getElementById('layerControlContent');
    const toggle = document.getElementById('layerControlToggle');

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        toggle.classList.remove('collapsed');
        toggle.textContent = '▼';
    } else {
        content.classList.add('hidden');
        toggle.classList.add('collapsed');
        toggle.textContent = '▶';
    }
}

// Función para alternar un switch al hacer clic en el label
function toggleSwitch(switchId) {
    const switchElement = document.getElementById(switchId);
    switchElement.checked = !switchElement.checked;
    switchElement.dispatchEvent(new Event('change'));
}

// Función principal para manejar las capas
function toggleLayer(layerType, isChecked) {
    // Actualizar el estado
    layerStates[layerType] = isChecked;

    // Lógica especial para las cámaras
    if (layerType === 'camaras') {
        // Si activamos "Todas las Cámaras", desactivar las específicas
        if (isChecked) {
            deactivateCameraSpecificLayers();
            mymap.addLayer(capa2);
        } else {
            mymap.removeLayer(capa2);
        }
    } else if (layerType.startsWith('camaras-') && layerType !== 'camaras-comisarias') {
        // Si activamos una cámara específica, desactivar "Todas las Cámaras"
        if (isChecked) {
            deactivateGeneralCameraLayer();
            activateSpecificCameraLayer(layerType);
        } else {
            deactivateSpecificCameraLayer(layerType);
        }
    } else {
        // Para otras capas (no cámaras)
        switch (layerType) {
            case 'comisarias':
                if (isChecked) {
                    mymap.addLayer(capa1);
                } else {
                    mymap.removeLayer(capa1);
                }
                break;
            case 'antenas':
                if (isChecked) {
                    mymap.addLayer(capa3);
                } else {
                    mymap.removeLayer(capa3);
                }
                break;
            case 'sitios':
                if (isChecked) {
                    mymap.addLayer(capaSitios);
                } else {
                    mymap.removeLayer(capaSitios);
                }
                break;
            case 'camaras-comisarias':
                if (isChecked) {
                    // Desactivar otras capas de cámaras
                    deactivateAllCameraLayers();
                    mymap.addLayer(capa5);
                } else {
                    mymap.removeLayer(capa5);
                }
                break;
        }
    }
}

// Función para desactivar la capa general de cámaras
function deactivateGeneralCameraLayer() {
    if (layerStates.camaras) {
        layerStates.camaras = false;
        document.getElementById('switch-camaras').checked = false;
        mymap.removeLayer(capa2);
    }
}

// Función para desactivar capas específicas de cámaras
function deactivateCameraSpecificLayers() {
    const cameraTypes = ['camaras-fijas', 'camaras-fr', 'camaras-lpr', 'camaras-domos', 'camaras-domos-duales', 'camaras-bde'];

    cameraTypes.forEach(type => {
        if (layerStates[type]) {
            layerStates[type] = false;
            document.getElementById(`switch-${type}`).checked = false;
            deactivateSpecificCameraLayer(type);
        }
    });
}

// Función para activar una capa específica de cámaras
function activateSpecificCameraLayer(layerType) {
    switch (layerType) {
        case 'camaras-fijas':
            mymap.addLayer(capaFija);
            break;
        case 'camaras-fr':
            mymap.addLayer(capaFR);
            break;
        case 'camaras-lpr':
            mymap.addLayer(capaLPR);
            break;
        case 'camaras-domos':
            mymap.addLayer(capaDomo);
            break;
        case 'camaras-domos-duales':
            mymap.addLayer(capaDomoDual);
            break;
        case 'camaras-bde':
            mymap.addLayer(capaBDE);
            break;
    }
}

// Función para desactivar una capa específica de cámaras
function deactivateSpecificCameraLayer(layerType) {
    switch (layerType) {
        case 'camaras-fijas':
            mymap.removeLayer(capaFija);
            break;
        case 'camaras-fr':
            mymap.removeLayer(capaFR);
            break;
        case 'camaras-lpr':
            mymap.removeLayer(capaLPR);
            break;
        case 'camaras-domos':
            mymap.removeLayer(capaDomo);
            break;
        case 'camaras-domos-duales':
            mymap.removeLayer(capaDomoDual);
            break;
        case 'camaras-bde':
            mymap.removeLayer(capaBDE);
            break;
    }
}

// Función para desactivar todas las capas de cámaras
function deactivateAllCameraLayers() {
    // Desactivar capa general
    deactivateGeneralCameraLayer();

    // Desactivar capas específicas
    deactivateCameraSpecificLayers();

    // Desactivar cámaras y comisarías
    if (layerStates['camaras-comisarias']) {
        layerStates['camaras-comisarias'] = false;
        document.getElementById('switch-camaras-comisarias').checked = false;
        mymap.removeLayer(capa5);
    }
}

// Función para limpiar todas las capas
function clearAllLayers() {
    // Desactivar todos los switches
    Object.keys(layerStates).forEach(layerType => {
        layerStates[layerType] = false;
        const switchElement = document.getElementById(`switch-${layerType}`);
        if (switchElement) {
            switchElement.checked = false;
        }
    });

    // Remover todas las capas del mapa
    mymap.removeLayer(capa1);
    mymap.removeLayer(capa2);
    mymap.removeLayer(capa3);
    mymap.removeLayer(capa5);
    mymap.removeLayer(capaFija);
    mymap.removeLayer(capaFR);
    mymap.removeLayer(capaLPR);
    mymap.removeLayer(capaDomo);
    mymap.removeLayer(capaDomoDual);
    mymap.removeLayer(capaSitios);
}

// Inicialización del mapa y variables globales
var zoom = 13;
var mymap = L.map('map', {
    editable: true,
    zoomControl: false
}).setView(new L.LatLng(-31.75899, -60.47825), zoom);

// Variables para marcadores y capas
var polygonLayer = L.featureGroup();
var drawControl = null;
var selectedCameras = [];
var marcadores = L.markerClusterGroup();
var markersCamarasLPR = L.markerClusterGroup();
var markersCamarasFR = L.markerClusterGroup();
var markersCamarasFijas = L.markerClusterGroup();
var markersCamarasDomos = L.markerClusterGroup();
var markersCamarasDomosDuales = L.markerClusterGroup();
var markersBDE = L.markerClusterGroup();
var marcadoresSitios = L.markerClusterGroup();
var capaSitios = L.layerGroup();
var capa1 = L.layerGroup();
var capa2 = L.geoJSON();
var capa3 = L.geoJSON();
var capa4 = L.layerGroup();
var capa5 = L.layerGroup();
var capaLPR = L.layerGroup();
var capaFR = L.layerGroup();
var capaFija = L.layerGroup();
var capaDomo = L.layerGroup();
var capaDomoDual = L.layerGroup();
var capaBDE = L.layerGroup();

// Colores para jurisdicciones
var colores = ['black', 'red', 'blue', 'purple', 'brown', 'orange', 'yellow'];

// Estado de las capas
let layerStates = {
    comisarias: false,
    camaras: true, // Iniciamos con cámaras activas
    'camaras-fijas': false,
    'camaras-fr': false,
    'camaras-lpr': false,
    'camaras-domos': false,
    'camaras-domos-duales': false,
    'camaras-bde': false,
    antenas: false,
    sitios: false,
    'camaras-comisarias': false
};

// MAPA CLARO
var mapaClaro = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
});

// MAPA OSCURO - CartoDB Dark (NATIVO, sin filtros CSS)
var mapaOscuro = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; Stadia Maps &copy; OpenMapTiles &copy; OpenStreetMap',
    maxZoom: 20,
    tileSize: 256,
    detectRetina: false,
    crossOrigin: true
});

// MAPA HÍBRIDO (el que ya tienes)
var mapaHibrido = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
    maxZoom: 20
});

// Diagnóstico de tiles
mapaClaro.on('tileerror', function(error, tile) {
    console.error('Error cargando tile CLARO:', error, tile.src);
});

mapaOscuro.on('tileerror', function(error, tile) {
    console.error('Error cargando tile OSCURO:', error, tile.src);
});

mapaHibrido.on('tileerror', function(error, tile) {
    console.error('Error cargando tile HÍBRIDO:', error, tile.src);
});

/* ========================================
   VARIABLE GLOBAL PARA CONTROL DE MAPA
   ======================================== */
var mapaActualLigero = null; // Referencia a la capa de mapa actual
var esHibrido = false; // Estado actual del mapa

/* ========================================
   FUNCIÓN PARA OBTENER MAPA SEGÚN TEMA
   ======================================== */
function getMapaSegunTema() {
    const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';
    return isDarkTheme ? mapaOscuro : mapaClaro;
}

/* ========================================
   FUNCIÓN PARA CAMBIAR CAPA DE MAPA
   ======================================== */
function cambiarCapaMapa(mymap) {
    const nuevoMapa = getMapaSegunTema();

    // Si ya hay una capa de mapa ligero, removerla
    if (mapaActualLigero) {
        mymap.removeLayer(mapaActualLigero);
    }

    // Agregar nueva capa de mapa
    mapaActualLigero = nuevoMapa;
    mymap.addLayer(nuevoMapa);

    // Traer al frente (debajo de otros elementos)
    nuevoMapa.bringToBack();
}

/* ========================================
   OBSERVADOR DE CAMBIOS DE TEMA
   ======================================== */
function observarCambiosTema(mymap) {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            // Detectar cambios en data-theme
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                // Esperar un pequeño delay para que se apliquen otros estilos
                setTimeout(function() {
                    cambiarCapaMapa(mymap);
                }, 50);
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
}

/* ========================================
   FUNCIÓN PARA MODIFICAR BOTÓN DE TOGGLE
   ======================================== */
function actualizarBotonesMapaConTema(mymap, botonesConfig) {
    const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';

    if (botonesConfig && botonesConfig.toggleMapBtn) {
        const btn = document.getElementById(botonesConfig.toggleMapBtn);
        if (btn) {
            if (esHibrido) {
                btn.textContent = isDarkTheme ? 'Mapa Oscuro' : 'Mapa Común';
            } else {
                btn.textContent = isDarkTheme ? 'Mapa Satelital' : 'Mapa Satelital';
            }
        }
    }
}

/* ========================================
   INICIALIZACIÓN OPCIONES POLIGONOS
   ======================================== */

// Configurar controles de dibujo después de la inicialización del mapa
function setupDrawControls() {
    // Control de dibujo
    drawControl = new L.Control.Draw({
        position: 'topleft',
        draw: {
            polygon: {
                allowIntersection: false,
                drawError: {
                    color: '#e1e100',
                    message: '<strong>¡Error!</strong> El polígono no puede intersectarse'
                },
                shapeOptions: {
                    color: '#2196F3',
                    fillColor: '#2196F3',
                    fillOpacity: 0.3,
                    weight: 2
                },
                showArea: true,
                metric: true,
                icon: new L.DivIcon({
                    iconSize: new L.Point(8, 8),
                    className: 'leaflet-div-icon leaflet-editing-icon'
                })
            },
            polyline: false,
            circle: false,
            rectangle: false,
            circlemarker: false,
            marker: false
        },
        edit: {
            featureGroup: polygonLayer,
            edit: false,
            remove: false
        }
    });

    mymap.addControl(drawControl);

    // Eventos de dibujo
    mymap.on(L.Draw.Event.CREATED, function(event) {
        var layer = event.layer;
        polygonLayer.addLayer(layer);

        // Buscar cámaras dentro del polígono
        findCamerasInPolygon(layer);

        // Agregar botón para cerrar polígono
        addPolygonControls(layer);
    });

    mymap.on(L.Draw.Event.DRAWSTOP, function() {
        // Limpiar selección anterior
        clearPolygonSelection();
    });
}

// Buscar cámaras dentro del polígono
function findCamerasInPolygon(polygon) {
    selectedCameras = [];

    // Recorrer todas las cámaras visibles
    marcadores.eachLayer(function(marker) {
        if (marker instanceof L.Marker) {
            var point = marker.getLatLng();
            if (polygon.getBounds().contains(point) &&
                isPointInPolygon(point, polygon.getLatLngs()[0])) {

                // Obtener información del marcador
                var cameraData = getCameraDataFromMarker(marker);
                if (cameraData) {
                    selectedCameras.push(cameraData);

                    // Resaltar marcador
                    highlightMarker(marker);
                }
            }
        }
    });

    // Mostrar resultados
    showPolygonResults(polygon);
}

// Verificar si un punto está dentro de un polígono
function isPointInPolygon(point, polygon) {
    var x = point.lat, y = point.lng;
    var inside = false;

    for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
        var xi = polygon[i].lat, yi = polygon[i].lng;
        var xj = polygon[j].lat, yj = polygon[j].lng;

        var intersect = ((yi > y) != (yj > y)) &&
            (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }

    return inside;
}

// Obtener datos de la cámara desde el marcador
function getCameraDataFromMarker(marker) {
    var popupContent = marker.getPopup().getContent();
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = popupContent;

    // Extraer información básica
    var title = tempDiv.querySelector('h5')?.textContent || '';
    var details = {};

    // Parsear detalles del popup
    var lines = popupContent.split('<br>');
    lines.forEach(function(line) {
        if (line.includes(':')) {
            var parts = line.split(':');
            var key = parts[0].trim().toLowerCase().replace(/\s+/g, '_');
            var value = parts[1].replace(/<[^>]*>/g, '').trim();
            details[key] = value;
        }
    });

    return {
        marker: marker,
        latlng: marker.getLatLng(),
        title: title,
        details: details,
        popupContent: popupContent
    };
}

// Resaltar marcador seleccionado
function highlightMarker(marker) {
    var icon = marker.options.icon;
    if (icon && icon.options) {
        // Guardar el icono original
        if (!marker._originalIcon) {
            marker._originalIcon = icon;
        }

        // Crear icono resaltado
        var highlightedIcon = L.divIcon({
            ...icon.options,
            html: icon.options.html.replace('stroke-width="1"', 'stroke-width="3"')
                .replace('stroke-width="2"', 'stroke-width="4"')
                .replace('stroke="#000000"', 'stroke="#FF0000"')
                .replace('stroke="#ffffff"', 'stroke="#FF0000"')
        });

        marker.setIcon(highlightedIcon);
    }
}

// Mostrar resultados del polígono
function showPolygonResults(polygon) {
    if (selectedCameras.length === 0) {
        showNotification('No se encontraron cámaras en el área seleccionada', 'warning');
        return;
    }

    // Crear botón flotante
    var resultsButton = L.control({ position: 'bottomleft' });

    resultsButton.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'polygon-results-button');
        div.innerHTML = `
            <button class="btn btn-success btn-sm"
                    onclick="showCamerasModal()"
                    style="box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                <i class="fas fa-camera"></i>
                ${selectedCameras.length} Cámaras encontradas
                <i class="fas fa-external-link-alt ml-1"></i>
            </button>
        `;
        return div;
    };

    resultsButton.addTo(mymap);

    // Guardar referencia
    polygon._resultsButton = resultsButton;

    // Mostrar notificación
    showNotification(`Encontró ${selectedCameras.length} cámaras en el área`, 'success');
}

// Agregar controles al polígono
function addPolygonControls(polygon) {
    // Botón para buscar cámaras nuevamente
    var findButton = L.control({ position: 'topleft' });

    findButton.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'polygon-control-button');
        div.innerHTML = `
            <button class="btn btn-primary btn-sm"
                    onclick="findCamerasInPolygon(polygonLayer.getLayers()[0])"
                    style="margin-right: 5px;">
                <i class="fas fa-search"></i> Buscar
            </button>
            <button class="btn btn-danger btn-sm" onclick="clearPolygonSelection()">
                <i class="fas fa-times"></i> Limpiar
            </button>
        `;
        return div;
    };

    findButton.addTo(mymap);
    polygon._controlButton = findButton;
}

// Limpiar selección de polígono
function clearPolygonSelection() {
    // Restaurar iconos originales
    selectedCameras.forEach(function(camera) {
        if (camera.marker && camera.marker._originalIcon) {
            camera.marker.setIcon(camera.marker._originalIcon);
            delete camera.marker._originalIcon;
        }
    });

    // Limpiar capa de polígonos
    polygonLayer.clearLayers();

    // Limpiar controles
    if (polygonLayer.getLayers().length > 0) {
        var polygon = polygonLayer.getLayers()[0];
        if (polygon._resultsButton) {
            mymap.removeControl(polygon._resultsButton);
        }
        if (polygon._controlButton) {
            mymap.removeControl(polygon._controlButton);
        }
    }

    selectedCameras = [];

    // Cerrar modal si está abierto
    var modal = document.getElementById('polygonCamerasModal');
    if (modal) {
        $(modal).modal('hide');
    }
}

// Mostrar modal con cámaras
function showCamerasModal() {
    // Crear contenido del modal
    var modalContent = `
        <div class="modal fade" id="polygonCamerasModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-camera mr-2"></i>
                            Cámaras en el Área Seleccionada: ${selectedCameras.length} encontradas
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Exportar a Excel
                                </button>
                                <button class="btn btn-primary ml-2" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                                </button>
                                <button class="btn btn-info ml-2" onclick="exportToCSV()">
                                    <i class="fas fa-file-csv"></i> Exportar a CSV
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="input-group">
                                    <input type="text" id="cameraSearch" class="form-control"
                                           placeholder="Buscar cámara..." onkeyup="filterCameras()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="camerasTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Sitio</th>
                                        <th>Ubicación</th>
                                        <th>Dependencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="camerasTableBody">
                                    ${generateCamerasTable()}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="zoomToSelectedArea()">
                            <i class="fas fa-search-location"></i> Zoom al Área
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al DOM si no existe
    if (!document.getElementById('polygonCamerasModal')) {
        document.body.insertAdjacentHTML('beforeend', modalContent);
    }

    // Mostrar modal
    $('#polygonCamerasModal').modal('show');
}

// Generar tabla de cámaras
function generateCamerasTable() {
    return selectedCameras.map(function(camera, index) {
        return `
            <tr data-camera-index="${index}">
                <td>${index + 1}</td>
                <td><strong>${camera.title}</strong></td>
                <td>${camera.details.tipo || 'N/A'}</td>
                <td>${camera.details.sitio || 'N/A'}</td>
                <td>${camera.latlng.lat.toFixed(5)}, ${camera.latlng.lng.toFixed(5)}</td>
                <td>${camera.details.dependencia || 'N/A'}</td>
                <td>
                    <span class="badge badge-success">Activa</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="zoomToCamera(${index})"
                            title="Ver en mapa">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="showCameraDetails(${index})"
                            title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    @can('editar-camara')
                    <button class="btn btn-sm btn-primary" onclick="editSelectedCamera(${index})"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    @endcan
                </td>
            </tr>
        `;
    }).join('');
}

// Filtrar cámaras en la tabla
function filterCameras() {
    var input = document.getElementById('cameraSearch');
    var filter = input.value.toUpperCase();
    var rows = document.querySelectorAll('#camerasTableBody tr');

    rows.forEach(function(row) {
        var text = row.textContent.toUpperCase();
        row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
    });
}

// Zoom a la cámara específica
function zoomToCamera(index) {
    var camera = selectedCameras[index];
    if (camera && camera.latlng) {
        mymap.setView(camera.latlng, 18);
        camera.marker.openPopup();
        $('#polygonCamerasModal').modal('hide');
    }
}

// Zoom al área del polígono
function zoomToSelectedArea() {
    if (polygonLayer.getLayers().length > 0) {
        var polygon = polygonLayer.getLayers()[0];
        mymap.fitBounds(polygon.getBounds());
        $('#polygonCamerasModal').modal('hide');
    }
}

// Mostrar detalles de la cámara
function showCameraDetails(index) {
    var camera = selectedCameras[index];

    Swal.fire({
        title: camera.title,
        html: camera.popupContent,
        width: '800px',
        showCloseButton: true,
        showConfirmButton: false
    });
}

// Editar cámara seleccionada
function editSelectedCamera(index) {
    var camera = selectedCameras[index];
    // Extraer ID de la cámara del popup content
    var matches = camera.popupContent.match(/editCamera\((\d+)\)/);
    if (matches && matches[1]) {
        editCamera(parseInt(matches[1]));
    }
}

// Función de notificación
function showNotification(message, type = 'info') {
    var icon = type === 'success' ? 'fas fa-check-circle' :
               type === 'warning' ? 'fas fa-exclamation-triangle' :
               'fas fa-info-circle';

    Swal.fire({
        icon: type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Exportar a Excel
function exportToExcel() {
    // Preparar datos para exportación
    var exportData = selectedCameras.map(function(camera, index) {
        return {
            'Nº': index + 1,
            'Nombre': camera.title,
            'Tipo': camera.details.tipo || '',
            'Sitio': camera.details.sitio || '',
            'Latitud': camera.latlng.lat,
            'Longitud': camera.latlng.lng,
            'Dependencia': camera.details.dependencia || '',
            'Instalación': camera.details.fecha_instalacion || '',
            'Marca': camera.details.marca || '',
            'Modelo': camera.details.modelo || '',
            'Nº Serie': camera.details.nro_serie || ''
        };
    });

    // Crear hoja de trabajo
    var ws = XLSX.utils.json_to_sheet(exportData);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Cámaras");

    // Exportar
    var date = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `camaras-seleccionadas-${date}.xlsx`);

    showNotification('Exportación a Excel completada', 'success');
}

// Exportar a PDF (usando jsPDF y autoTable)
function exportToPDF() {
    // Asegurarse de que jsPDF y autoTable estén cargados
    if (typeof jsPDF === 'undefined' || typeof autoTable === 'undefined') {
        showNotification('Error: Bibliotecas PDF no cargadas', 'error');
        return;
    }

    var doc = new jsPDF('landscape');

    // Título
    doc.setFontSize(16);
    doc.text('Reporte de Cámaras Seleccionadas', 14, 15);
    doc.setFontSize(10);
    doc.text(`Fecha: ${new Date().toLocaleDateString()} | Total: ${selectedCameras.length} cámaras`, 14, 22);

    // Preparar datos para la tabla
    var tableData = selectedCameras.map(function(camera, index) {
        return [
            index + 1,
            camera.title,
            camera.details.tipo || '',
            camera.details.sitio || '',
            `${camera.latlng.lat.toFixed(5)}, ${camera.latlng.lng.toFixed(5)}`,
            camera.details.dependencia || '',
            camera.details.fecha_instalacion || ''
        ];
    });

    // Crear tabla
    doc.autoTable({
        head: [['#', 'Nombre', 'Tipo', 'Sitio', 'Ubicación', 'Dependencia', 'Instalación']],
        body: tableData,
        startY: 30,
        theme: 'grid',
        styles: { fontSize: 8 },
        headStyles: { fillColor: [41, 128, 185] }
    });

    // Guardar PDF
    var date = new Date().toISOString().split('T')[0];
    doc.save(`camaras-seleccionadas-${date}.pdf`);

    showNotification('Exportación a PDF completada', 'success');
}

// Exportar a CSV
function exportToCSV() {
    var csvContent = "data:text/csv;charset=utf-8,";

    // Encabezados
    csvContent += "Nº,Nombre,Tipo,Sitio,Latitud,Longitud,Dependencia,Instalación,Marca,Modelo,Nº Serie\n";

    // Datos
    selectedCameras.forEach(function(camera, index) {
        var row = [
            index + 1,
            `"${camera.title}"`,
            `"${camera.details.tipo || ''}"`,
            `"${camera.details.sitio || ''}"`,
            camera.latlng.lat,
            camera.latlng.lng,
            `"${camera.details.dependencia || ''}"`,
            `"${camera.details.fecha_instalacion || ''}"`,
            `"${camera.details.marca || ''}"`,
            `"${camera.details.modelo || ''}"`,
            `"${camera.details.nro_serie || ''}"`
        ].join(',');

        csvContent += row + "\n";
    });

    // Descargar
    var encodedUri = encodeURI(csvContent);
    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `camaras-seleccionadas-${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showNotification('Exportación a CSV completada', 'success');
}
/* ========================================
   FIN DE LA FUNCIÓN DE SELECCIÓN POR POLÍGONO
   ======================================== */

/* ========================================
   INTEGRACIÓN CON setupMapToggleButton
   ======================================== */

function setupMapToggleButton() {
    var toggleControl = L.control({
        position: 'bottomleft'
    });

    toggleControl.onAdd = function() {
        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        div.innerHTML = '<button id="toggleMapBtn" class="btn btn-primary">Mapa Satelital</button>';
        div.style.backgroundColor = 'white';
        div.style.padding = '5px';
        L.DomEvent.disableClickPropagation(div);
        return div;
    };

    toggleControl.addTo(mymap);

    document.getElementById('toggleMapBtn').addEventListener('click', function() {
        const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';

        if (esHibrido) {
            // Cambiar a mapa normal (claro u oscuro según tema)
            mymap.removeLayer(mapaHibrido);
            cambiarCapaMapa(mymap);
            this.textContent = 'Mapa Satelital';
        } else {
            // Cambiar a mapa híbrido (satélite)
            if (mapaActualLigero) {
                mymap.removeLayer(mapaActualLigero);
            }
            mymap.addLayer(mapaHibrido);
            this.textContent = isDarkTheme ? 'Mapa Oscuro' : 'Mapa Común';
        }
        esHibrido = !esHibrido;
    });
}

$(document).ready(function() {
    // Inicialización de Select2
    $('.select2').select2({
        width: '100%'
    });

    // Forzar el foco en el campo de búsqueda cuando se abre el Select2
    $(document).on('select2:open', () => {
        let select2Field = document.querySelector('.select2-search__field');
        if (select2Field) {
            select2Field.focus();
        }
    });

    // Manejo del cambio en el select de cámaras
    $('#camara_select').on('change', function() {
        var selectElement = this;
        var selectedOption = selectElement.options[selectElement.selectedIndex];

        console.log('item_select', selectedOption);

        var lat = parseFloat(selectedOption.getAttribute('data-lat'));
        var lng = parseFloat(selectedOption.getAttribute('data-lng'));

        mymap.setView([lat, lng], 20);
    });

    // Agregar mapa según tema actual
    cambiarCapaMapa(mymap);

   // Observar cambios de tema
    observarCambiosTema(mymap);

    // Configuración del botón toggle para cambiar tipo de mapa
    setupMapToggleButton();

    // Configuración del control de búsqueda
    setupSearchControl();

    // Cargar marcadores de comisarías
    loadComisariaMarkers();

    // Cargar jurisdicciones
    loadJurisdicciones();

    // Cargar marcadores de cámaras
    loadCameraMarkers();

    // Cargar marcadores de antenas
    loadAntenasMarkers();

    // Cargar marcadores de sitios inactivos
    loadSitiosMarkers();

    // Configurar control de pantalla completa
    setupFullscreenControl();

    // Asegurar que el control de capas permanezca visible
    ensureLayerControlVisibility();

    // Añadir capa inicial de cámaras
    mymap.addLayer(capa2);

    setTimeout(function() {
        setupDrawControls();

        // Agregar capa de polígonos al mapa
        mymap.addLayer(polygonLayer);

        // Botón para activar/desactivar dibujo de polígonos
        addPolygonToggleButton();
    }, 1000);
});

// Agregar botón toggle para polígonos
function addPolygonToggleButton() {
    var polygonControl = L.control({ position: 'topleft' });

    polygonControl.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        div.innerHTML = `
            <button id="togglePolygonBtn" class="btn btn-warning"
                    style="background-color: #ff9800; color: white; border: none;">
                <i class="fas fa-draw-polygon"></i> Dibujar Área
            </button>
        `;

        L.DomEvent.disableClickPropagation(div);
        return div;
    };

    polygonControl.addTo(mymap);

    document.getElementById('togglePolygonBtn').addEventListener('click', function() {
        if (drawControl) {
            // Alternar modo dibujo
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                this.innerHTML = '<i class="fas fa-draw-polygon"></i> Dibujar Área';
                drawControl._toolbars.draw._modes.polygon.handler.disable();
            } else {
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                drawControl._toolbars.draw._modes.polygon.handler.enable();
            }
        }
    });
}

function ensureLayerControlVisibility() {
    // Asegurar que el control de capas siempre esté visible dentro del contenedor del mapa
    mymap.on('zoomend moveend', function() {
        const layerControl = document.getElementById('customLayerControl');
        if (layerControl) {
            layerControl.style.position = 'absolute';
            layerControl.style.zIndex = '10000';
            layerControl.style.top = '10px';
            layerControl.style.right = '10px';
        }
    });

    // También aplicar los estilos inmediatamente
    const layerControl = document.getElementById('customLayerControl');
    if (layerControl) {
        layerControl.style.position = 'absolute';
        layerControl.style.zIndex = '10000';
        layerControl.style.top = '10px';
        layerControl.style.right = '10px';
    }
}

function setupSearchControl() {
    var searchControl = new L.esri.Controls.Geosearch({
        position: 'topleft'
    }).addTo(mymap);

    var geocodeService = new L.esri.Services.Geocoding();
    var markerResultado = null;

    searchControl.on('results', function(data) {
        if (markerResultado) {
            mymap.removeLayer(markerResultado);
        }
        var result = data.results[0];
        var location = result.latlng;
        markerResultado = L.marker(location).addTo(mymap);
        markerResultado.bindPopup(result.text);
    });

    mymap.on('click', function(event) {
        if (markerResultado) {
            mymap.removeLayer(markerResultado);
        }
    });
}

function loadComisariaMarkers() {
    @foreach ($comisarias as $marcador)
        var numero = "{{ $marcador['numero'] }}";
        var markerIcon = L.divIcon({
            className: 'transparent',
            labelAnchor: [0, 0],
            popupAnchor: [0, 0],
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            html: '<div class="marker-comprador">' + numero + '</div>'
        });
        var marker = L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
            icon: markerIcon
        }).addTo(capa1).addTo(capa5)
            .bindPopup("{{ $marcador['titulo'] }}");
    @endforeach
}

function loadJurisdicciones() {
    @foreach ($jurisdicciones as $jurisdiccion)
        var indiceAleatorio = Math.floor(Math.random() * colores.length);
        var colorAleatorio = colores[indiceAleatorio];
        var coordenadas = @if ($jurisdiccion)
            {!! json_encode($jurisdiccion) !!}
        @else
            []
        @endif;

        if (coordenadas && coordenadas.jurisdiccion) {
            var objeto = JSON.parse(coordenadas.jurisdiccion);
            var polygonCoords = [];

            for (let i = 0; i < objeto.length; i++) {
                let coord = [objeto[i].lat, objeto[i].lng];
                polygonCoords.push(coord);
            }

            var polygon = L.polygon(polygonCoords).setStyle({
                fillColor: colorAleatorio,
                fillOpacity: 0.15,
                color: 'black',
                weight: 2
            }).addTo(capa1).addTo(capa5);
        }
    @endforeach
}

function loadCameraMarkers() {
    @foreach ($camaras as $marcador)
        var numero = "{{ $marcador['numero'] }}";
        var latitud = "{{ $marcador['latitud'] }}";
        var longitud = "{{ $marcador['longitud'] }}";
        var angulo = {{ $marcador['angulo'] ?? 60 }};
        var orientacion = "{{ $marcador['orientacion'] ?? 'norte' }}";
        var tipo_camara = "{{ $marcador['tipo_camara'] }}";

        // Generar el path y rotación dinámicamente
        var cameraGeometry = generateCameraPath(angulo, orientacion);

        // Detectar si está en modo oscuro
        var isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        var strokeColor = isDarkMode ? '#ffffff' : '#000000';
        var strokeWidth = isDarkMode ? '2' : '1';

        // Colores por tipo de cámara
        var fillColor = "rgba(0,0,255,0.3)"; // Default
        if (tipo_camara.includes("Domo")) {
            fillColor = "rgba(0,255,0,0.4)";
        } else if (tipo_camara.includes("LPR")) {
            fillColor = "rgba(255,0,0,0.4)";
        } else if (tipo_camara.includes("FR")) {
            fillColor = "rgba(255,165,0,0.4)";
        }

        var svgShape = angulo === 360
            ? `<circle cx="0" cy="0" r="20" fill="${fillColor}" stroke="${strokeColor}" stroke-width="${strokeWidth}" />`
            : `<path d="${cameraGeometry.path}" fill="${fillColor}" stroke="${strokeColor}" stroke-width="${strokeWidth}" />`;

        var cameraIcon = L.divIcon({
            className: '',
            html: `
                <div style="position: relative; width: 50px; height: 50px;">
                    <svg width="50" height="50" viewBox="-25 -25 50 50" xmlns="http://www.w3.org/2000/svg"
                        style="position: absolute; top: 0; left: 0; transform: rotate(${cameraGeometry.rotation}deg); z-index: 0;">
                        ${svgShape}
                    </svg>
                    <img src="{{ $marcador['imagen'] }}" style="width: 50px; height: 50px; position: absolute; top: 0; left: 0; z-index: 1;" />
                </div>
            `,
            iconSize: [50, 50],
            iconAnchor: [25, 25],
            popupAnchor: [0, -25]
        });

        var marker = L.marker([latitud, longitud], {
            icon: cameraIcon
        }).bindPopup(`
            <div>
                <img src='{{ $marcador['imagen'] }}' alt="" style="max-width: 200px;">
                <h5>{{ $marcador['titulo'] }}</h5>
                Tipo: <b>{{ $marcador['tipo_camara'] }}</b><br>
                Sitio: <b>{{ $marcador['sitio'] }}</b><br>
                Ángulo: <b>${angulo}°</b><br>
                Orientación: <b>${orientacion}</b><br>
                Señalizado: <b>{{ $marcador['cartel'] ? 'SI' : 'NO' }}</b><br>
                Dependencia: <b>{{ $marcador['dependencia'] }}</b><br>
                Etapa: <b>{{ $marcador['etapa'] }}</b><br>
                Instalación: <b>{{ $marcador['fecha_instalacion'] }}</b><br>
                Inteligencia: <b>{{ $marcador['inteligencia'] }}</b><br>
                Marca: <b>{{ $marcador['marca'] }}</b> - Mod.: <b>{{ $marcador['modelo'] }}</b><br>
                Nº serie: <b>{{ $marcador['nro_serie'] }}</b><br>
                <div class="btn-group" role="group">
                    <button class="btn btn-icon btn-primary" title="Editar cámara" onclick="editCamera(${numero})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(${latitud}, ${longitud})"><i class="fas fa-globe-americas"></i></button>
                    <button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(${latitud}, ${longitud})"><i class="fas fa-street-view"></i></button>
                </div>
            </div>
        `);

        marcadores.addLayer(marker);

        // Agregar a capas específicas según el tipo
        if (tipo_camara === "Fija - LPR" || tipo_camara === "Fija - LPR AV" || tipo_camara === "Fija - LPR NV") {
            markersCamarasLPR.addLayer(marker);
        } else if (tipo_camara === "Fija - FR") {
            markersCamarasFR.addLayer(marker);
        } else if (tipo_camara === "Fija") {
            markersCamarasFijas.addLayer(marker);
        } else if (tipo_camara === "Domo") {
            markersCamarasDomos.addLayer(marker);
        } else if (tipo_camara === "Domo Dual") {
            markersCamarasDomosDuales.addLayer(marker);
        } else if (tipo_camara === "BDE (Totem)") {
            markersBDE.addLayer(marker);
        }
    @endforeach

    // Añadir a las capas correspondientes
    marcadores.addTo(capa2).addTo(capa5);
    markersCamarasLPR.addTo(capaLPR);
    markersCamarasFR.addTo(capaFR);
    markersCamarasFijas.addTo(capaFija);
    markersCamarasDomos.addTo(capaDomo);
    markersCamarasDomosDuales.addTo(capaDomoDual);
    markersBDE.addTo(capaBDE);
}

// Escuchar cambios en el tema
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            // Limpiar marcadores existentes
            marcadores.clearLayers();
            markersCamarasLPR.clearLayers();
            markersCamarasFR.clearLayers();
            markersCamarasFijas.clearLayers();
            markersCamarasDomos.clearLayers();
            markersCamarasDomosDuales.clearLayers();
            markersBDE.clearLayers();

            // Recargar con los nuevos colores
            loadCameraMarkers();
        }
    });
});

observer.observe(document.documentElement, {
    attributes: true
});

function loadAntenasMarkers() {
    @foreach ($antenas as $marcador)
        var numero = "{{ $marcador['numero'] }}";
        var antenaIcon = L.icon({
            iconUrl: "/img/antena_icon.png",
            iconSize: [40, 40],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });

        var marker = L.marker([{{ $marcador['latitud'] }}, {{ $marcador['longitud'] }}], {
            icon: antenaIcon
        }).addTo(capa3)
            .bindPopup("{{ $marcador['titulo'] }}");
    @endforeach
}

function loadSitiosMarkers() {
    @foreach ($sitios as $sitio)
        @if($sitio['activo'] == 0)
            var numeroSitio = "{{ $sitio['numero'] }}";
            var latitudSitio = "{{ $sitio['latitud'] }}";
            var longitudSitio = "{{ $sitio['longitud'] }}";

            var sitioInactivoIcon = L.divIcon({
                className: 'transparent',
                labelAnchor: [0, 0],
                popupAnchor: [0, -15],
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                html: '<div style="width: 30px; height: 30px; background-color: #dc3545; border: 2px solid #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-times" style="color: white; font-size: 16px;"></i></div>'
            });

            var markerSitio = L.marker([latitudSitio, longitudSitio], {
                icon: sitioInactivoIcon
            }).bindPopup(`
                <div>
                    <h5>{{ $sitio['titulo'] }}</h5>
                    <strong>Estado:</strong> <span style="color: #dc3545;">INACTIVO</span><br>
                    @if(isset($sitio['cartel']))
                        <strong>Cartel:</strong> <b>{{ $sitio['cartel'] ? 'SI' : 'NO' }}</b><br>
                    @endif
                    @if (isset($sitio['observaciones']))
                        <strong>Observaciones:</strong> {{ $sitio['observaciones'] }}<br>
                    @endif
                    <div class="btn-group" role="group">
                        <button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(${latitudSitio}, ${longitudSitio})"><i class="fas fa-globe-americas"></i></button>
                        <button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(${latitudSitio}, ${longitudSitio})"><i class="fas fa-street-view"></i></button>
                    </div>
                </div>
            `);

            marcadoresSitios.addLayer(markerSitio);
        @endif
    @endforeach

    marcadoresSitios.addTo(capaSitios);
}

function setupFullscreenControl() {
    var fullscreenButton = L.control({
        position: 'bottomright'
    });

    var isFullscreen = false;

    fullscreenButton.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
        div.innerHTML = '<div id="fullscreen-button"><i class="fas fa-expand"></i></div>';
        div.firstChild.addEventListener('click', function() {
            var element = map.getContainer();
            if (!isFullscreen) {
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
                isFullscreen = true;
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
                isFullscreen = false;
                div.firstChild.innerHTML = '<i class="fas fa-expand"></i>';
            }
        });
        return div;
    };

    fullscreenButton.addTo(mymap);
}
</script>
