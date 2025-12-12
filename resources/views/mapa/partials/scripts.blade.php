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
var polygonLayer = L.featureGroup();
var drawControl = null;
var selectedCameras = [];
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

// ========================================
// FUNCIÓN PARA CREAR GRUPOS CON SPIDER
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
        iconCreateFunction: function(cluster) {
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

// Inicializar grupos con spider mode
var marcadores = createClusterGroupWithSpider();
var markersCamarasLPR = createClusterGroupWithSpider();
var markersCamarasFR = createClusterGroupWithSpider();
var markersCamarasFijas = createClusterGroupWithSpider();
var markersCamarasDomos = createClusterGroupWithSpider();
var markersCamarasDomosDuales = createClusterGroupWithSpider();
var markersBDE = createClusterGroupWithSpider();

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

    // Inicializar control de clustering
    setTimeout(function() {
        initClusteringControl();
    }, 1000);
});

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

    fullscreenButton.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
        div.innerHTML = '<div id="fullscreen-button"><i class="fas fa-expand"></i></div>';
        div.firstChild.addEventListener('click', function() {
            var element = map.getContainer();

            if (!document.fullscreenElement &&
                !document.webkitFullscreenElement &&
                !document.mozFullScreenElement &&
                !document.msFullscreenElement) {

                // Entrar en fullscreen
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            } else {
                // Salir de fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }

            // Actualizar estado después de un breve delay
            setTimeout(() => {
                if (typeof detectFullscreen === 'function') {
                    detectFullscreen();
                }
            }, 100);
        });
        return div;
    };

    fullscreenButton.addTo(mymap);
}
</script>
