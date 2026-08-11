<script>
// ========================================
// MAPA 3D (MapLibre GL) — cámaras, comisarías, antenas, sitios y edificios reales
// ========================================

const MAPA3D_DATA = @json($geojson);
const STADIA_API_KEY = @json($stadiaApiKey);
const MAPA3D_CENTRO = [-60.47825, -31.75899];

let map3d = null;
let esHibrido3d = false;

// Claves de tipo de cámara agrupadas para los switches específicos del control de capas
const TIPOS_CAMARA_POR_CLAVE = {
    'camaras-fijas': ['Fija'],
    'camaras-fr': ['Fija - FR'],
    'camaras-lpr': ['Fija - LPR', 'Fija - LPR NV', 'Fija - LPR AV'],
    'camaras-domos': ['Domo'],
    'camaras-domos-duales': ['Domo Dual'],
    'camaras-bde': ['BDE (Totem)'],
};

// Capas de MapLibre controladas por cada switch general del control de capas
const LAYER_IDS_3D = {
    camaras: ['camaras-poste', 'camaras-cabeza', 'camaras-cono'],
    comisarias: ['comisarias-vol', 'comisarias-num'],
    sitios: ['sitios-vol'],
    antenas: ['antenas-base', 'antenas-mastil'],
    jurisdicciones: ['jurisdicciones-fill', 'jurisdicciones-line'],
    edificios: ['edificios-3d'],
};

document.addEventListener('DOMContentLoaded', function() {
    initMapa3D();
});

// ----------------------------------------
// Inicialización
// ----------------------------------------
function initMapa3D() {
    let mapa;
    try {
        mapa = new maplibregl.Map({
            container: 'map3d',
            style: estiloClaroOscuro3D(),
            center: MAPA3D_CENTRO,
            zoom: 14,
            pitch: 60,
            bearing: -20,
            maxPitch: 85,
            antialias: true,
        });
    } catch (e) {
        document.getElementById('map3d-loader').innerHTML = 'Su navegador no soporta WebGL';
        return;
    }

    map3d = mapa;
    map3d.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'bottom-right');
    map3d.addControl(crearControlBasemap3D(), 'top-left');

    map3d.on('style.load', agregarCapas3D);
    map3d.once('load', function() {
        bindInteracciones3D();
        ocultarLoader3D();
    });

    observarTema3D();
}

function ocultarLoader3D() {
    const loader = document.getElementById('map3d-loader');
    if (loader) loader.style.display = 'none';
}

// ----------------------------------------
// Basemaps
// ----------------------------------------
function isDarkTheme3D() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

function estiloClaro3D() {
    return 'https://tiles.stadiamaps.com/styles/alidade_smooth.json?api_key=' + STADIA_API_KEY;
}

function estiloOscuro3D() {
    return 'https://tiles.stadiamaps.com/styles/alidade_smooth_dark.json?api_key=' + STADIA_API_KEY;
}

function estiloClaroOscuro3D() {
    return isDarkTheme3D() ? estiloOscuro3D() : estiloClaro3D();
}

function estiloSatelital3D() {
    return {
        version: 8,
        glyphs: 'https://tiles.stadiamaps.com/fonts/{fontstack}/{range}.pbf',
        sources: {
            'google-satelite': {
                type: 'raster',
                tiles: [
                    'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                    'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                    'https://mt2.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                    'https://mt3.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                ],
                tileSize: 256,
                maxzoom: 20,
            },
            openmaptiles: {
                type: 'vector',
                tiles: ['https://tiles.stadiamaps.com/data/openmaptiles/{z}/{x}/{y}.pbf?api_key=' + STADIA_API_KEY],
                maxzoom: 14,
            },
        },
        layers: [
            { id: 'google-satelite-layer', type: 'raster', source: 'google-satelite' },
        ],
    };
}

function crearControlBasemap3D() {
    return {
        onAdd: function() {
            const div = document.createElement('div');
            div.className = 'maplibregl-ctrl maplibregl-ctrl-group map3d-basemap-control';
            div.innerHTML = '<button type="button" id="toggleMapBtn3d">Mapa Satelital</button>';
            div.querySelector('button').addEventListener('click', toggleBasemapSatelital3D);
            return div;
        },
        onRemove: function() {},
    };
}

function toggleBasemapSatelital3D() {
    esHibrido3d = !esHibrido3d;
    map3d.setStyle(esHibrido3d ? estiloSatelital3D() : estiloClaroOscuro3D());
    document.getElementById('toggleMapBtn3d').textContent = esHibrido3d ? 'Mapa Común' : 'Mapa Satelital';
}

function observarTema3D() {
    const obs = new MutationObserver(function() {
        if (!map3d) return;
        if (!esHibrido3d) {
            map3d.setStyle(estiloClaroOscuro3D());
        } else if (map3d.getLayer('edificios-3d')) {
            map3d.setPaintProperty('edificios-3d', 'fill-extrusion-color', isDarkTheme3D() ? '#3a4552' : '#c9d2db');
        }
    });
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
}

// ----------------------------------------
// Geometría (proyección local metros → grados)
// ----------------------------------------
function metrosALongitud(metros, lat) {
    return metros / (111320 * Math.cos(lat * Math.PI / 180));
}

function metrosALatitud(metros) {
    return metros / 110540;
}

function poligonoCircular(lng, lat, radioM, pasos) {
    pasos = pasos || 24;
    const coords = [];
    for (let i = 0; i <= pasos; i++) {
        const ang = (i / pasos) * 2 * Math.PI;
        const dx = radioM * Math.cos(ang);
        const dy = radioM * Math.sin(ang);
        coords.push([lng + metrosALongitud(dx, lat), lat + metrosALatitud(dy)]);
    }
    return coords;
}

function poligonoCuadrado(lng, lat, ladoM) {
    const r = ladoM / 2;
    const esquinas = [[-r, -r], [r, -r], [r, r], [-r, r], [-r, -r]];
    return esquinas.map(function(p) {
        return [lng + metrosALongitud(p[0], lat), lat + metrosALatitud(p[1])];
    });
}

// Sector de visión de una cámara (o círculo completo si el ángulo es 360°)
function poligonoSector(lng, lat, radioM, anguloGrados, orientacionGrados) {
    if (parseFloat(anguloGrados) === 360) {
        return poligonoCircular(lng, lat, radioM, 24);
    }
    const pasos = 12;
    const half = (parseFloat(anguloGrados) || 60) / 2;
    const coords = [[lng, lat]];
    for (let i = 0; i <= pasos; i++) {
        const bearing = orientacionGrados - half + (2 * half * i / pasos);
        const bRad = bearing * Math.PI / 180;
        const dx = radioM * Math.sin(bRad);
        const dy = radioM * Math.cos(bRad);
        coords.push([lng + metrosALongitud(dx, lat), lat + metrosALatitud(dy)]);
    }
    coords.push([lng, lat]);
    return coords;
}

// Misma tabla que la vista 2D (scripts.blade.php)
function getOrientationDegrees(orientacion) {
    switch ((orientacion || '').toLowerCase()) {
        case 'norte': case 'n': return 0;
        case 'noreste': case 'ne': return 45;
        case 'este': case 'e': return 90;
        case 'sureste': case 'se': return 135;
        case 'sur': case 's': return 180;
        case 'suroeste': case 'so': case 'sw': return 225;
        case 'oeste': case 'o': case 'w': return 270;
        case 'noroeste': case 'no': case 'nw': return 315;
        default: return 0;
    }
}

function colorPorTipoCamara(tipo) {
    tipo = tipo || '';
    if (tipo.indexOf('Domo') !== -1) return '#28a745';
    if (tipo.indexOf('LPR') !== -1) return '#dc3545';
    if (tipo.indexOf('FR') !== -1) return '#fd7e14';
    return '#007bff';
}

// ----------------------------------------
// Construcción de geometrías 3D a partir del GeoJSON del servidor
// ----------------------------------------
function construirGeometriasCamara() {
    const postes = [], cabezas = [], conos = [];

    (MAPA3D_DATA.camaras.features || []).forEach(function(f) {
        const lng = f.geometry.coordinates[0];
        const lat = f.geometry.coordinates[1];
        const props = f.properties;
        const color = colorPorTipoCamara(props.tipo_camara);
        const bearing = getOrientationDegrees(props.orientacion);

        postes.push({
            type: 'Feature',
            properties: Object.assign({}, props, { base: 0, altura: 6, color: '#555555' }),
            geometry: { type: 'Polygon', coordinates: [poligonoCuadrado(lng, lat, 0.6)] },
        });
        cabezas.push({
            type: 'Feature',
            properties: Object.assign({}, props, { base: 6, altura: 7.2, color: color }),
            geometry: { type: 'Polygon', coordinates: [poligonoCuadrado(lng, lat, 1.4)] },
        });
        conos.push({
            type: 'Feature',
            properties: Object.assign({}, props, { base: 5, altura: 7, color: color }),
            geometry: { type: 'Polygon', coordinates: [poligonoSector(lng, lat, 25, props.angulo, bearing)] },
        });
    });

    return {
        postes: { type: 'FeatureCollection', features: postes },
        cabezas: { type: 'FeatureCollection', features: cabezas },
        conos: { type: 'FeatureCollection', features: conos },
    };
}

function construirGeometriaComisarias() {
    const volumenes = [], puntos = [];

    (MAPA3D_DATA.comisarias.features || []).forEach(function(f) {
        const lng = f.geometry.coordinates[0];
        const lat = f.geometry.coordinates[1];
        volumenes.push({
            type: 'Feature',
            properties: Object.assign({}, f.properties, { base: 0, altura: 14, color: '#424EB0' }),
            geometry: { type: 'Polygon', coordinates: [poligonoCircular(lng, lat, 12, 6)] },
        });
        puntos.push({
            type: 'Feature',
            properties: f.properties,
            geometry: { type: 'Point', coordinates: [lng, lat] },
        });
    });

    return {
        volumenes: { type: 'FeatureCollection', features: volumenes },
        puntos: { type: 'FeatureCollection', features: puntos },
    };
}

function construirGeometriaAntenas() {
    const bases = [], mastiles = [];

    (MAPA3D_DATA.antenas.features || []).forEach(function(f) {
        const lng = f.geometry.coordinates[0];
        const lat = f.geometry.coordinates[1];
        bases.push({
            type: 'Feature',
            properties: Object.assign({}, f.properties, { base: 0, altura: 4, color: '#6f42c1' }),
            geometry: { type: 'Polygon', coordinates: [poligonoCuadrado(lng, lat, 8)] },
        });
        mastiles.push({
            type: 'Feature',
            properties: Object.assign({}, f.properties, { base: 4, altura: 38, color: '#6f42c1' }),
            geometry: { type: 'Polygon', coordinates: [poligonoCuadrado(lng, lat, 1.5)] },
        });
    });

    return {
        bases: { type: 'FeatureCollection', features: bases },
        mastiles: { type: 'FeatureCollection', features: mastiles },
    };
}

function construirGeometriaSitios() {
    const volumenes = (MAPA3D_DATA.sitios.features || []).map(function(f) {
        const lng = f.geometry.coordinates[0];
        const lat = f.geometry.coordinates[1];
        return {
            type: 'Feature',
            properties: Object.assign({}, f.properties, { base: 0, altura: 7, color: '#dc3545' }),
            geometry: { type: 'Polygon', coordinates: [poligonoCuadrado(lng, lat, 6)] },
        };
    });

    return { type: 'FeatureCollection', features: volumenes };
}

function construirGeometriaJurisdicciones() {
    const paleta = ['#000000', '#ff0000', '#0000ff', '#800080', '#a52a2a', '#ffa500', '#ffff00'];
    const features = (MAPA3D_DATA.jurisdicciones.features || []).map(function(f, i) {
        return {
            type: 'Feature',
            properties: { color: paleta[i % paleta.length] },
            geometry: f.geometry,
        };
    });

    return { type: 'FeatureCollection', features: features };
}

// ----------------------------------------
// Fuentes y capas (se reconstruyen tras cada cambio de estilo/basemap)
// ----------------------------------------
function agregarFuente3D(id, data) {
    if (map3d.getSource(id)) {
        map3d.getSource(id).setData(data);
    } else {
        map3d.addSource(id, { type: 'geojson', data: data });
    }
}

function agregarCapaExtrusion3D(id, sourceId, opacidad) {
    if (map3d.getLayer(id)) return;
    map3d.addLayer({
        id: id,
        type: 'fill-extrusion',
        source: sourceId,
        paint: {
            'fill-extrusion-color': ['get', 'color'],
            'fill-extrusion-height': ['get', 'altura'],
            'fill-extrusion-base': ['get', 'base'],
            'fill-extrusion-opacity': opacidad || 0.9,
        },
    });
}

function agregarCapas3D() {
    const geoCamaras = construirGeometriasCamara();
    agregarFuente3D('camaras-postes-src', geoCamaras.postes);
    agregarFuente3D('camaras-cabezas-src', geoCamaras.cabezas);
    agregarFuente3D('camaras-conos-src', geoCamaras.conos);

    const geoComisarias = construirGeometriaComisarias();
    agregarFuente3D('comisarias-vol-src', geoComisarias.volumenes);
    agregarFuente3D('comisarias-puntos-src', geoComisarias.puntos);

    const geoAntenas = construirGeometriaAntenas();
    agregarFuente3D('antenas-bases-src', geoAntenas.bases);
    agregarFuente3D('antenas-mastiles-src', geoAntenas.mastiles);

    agregarFuente3D('sitios-vol-src', construirGeometriaSitios());
    agregarFuente3D('jurisdicciones-src', construirGeometriaJurisdicciones());

    if (!map3d.getLayer('edificios-3d')) {
        map3d.addLayer({
            id: 'edificios-3d',
            type: 'fill-extrusion',
            source: 'openmaptiles',
            'source-layer': 'building',
            minzoom: 14,
            paint: {
                'fill-extrusion-color': isDarkTheme3D() ? '#3a4552' : '#c9d2db',
                'fill-extrusion-height': ['coalesce', ['get', 'render_height'], 5],
                'fill-extrusion-base': ['coalesce', ['get', 'render_min_height'], 0],
                'fill-extrusion-opacity': 0.85,
            },
        });
    }

    agregarCapaExtrusion3D('camaras-poste', 'camaras-postes-src', 0.95);
    agregarCapaExtrusion3D('camaras-cabeza', 'camaras-cabezas-src', 0.95);
    agregarCapaExtrusion3D('camaras-cono', 'camaras-conos-src', 0.35);
    agregarCapaExtrusion3D('comisarias-vol', 'comisarias-vol-src', 0.9);
    agregarCapaExtrusion3D('antenas-base', 'antenas-bases-src', 0.9);
    agregarCapaExtrusion3D('antenas-mastil', 'antenas-mastiles-src', 0.9);
    agregarCapaExtrusion3D('sitios-vol', 'sitios-vol-src', 0.9);

    if (!map3d.getLayer('comisarias-num')) {
        map3d.addLayer({
            id: 'comisarias-num',
            type: 'symbol',
            source: 'comisarias-puntos-src',
            layout: {
                'text-field': ['to-string', ['get', 'numero']],
                'text-size': 13,
                'text-allow-overlap': true,
            },
            paint: {
                'text-color': '#ffffff',
                'text-halo-color': '#424EB0',
                'text-halo-width': 2,
            },
        });
    }

    if (!map3d.getLayer('jurisdicciones-fill')) {
        map3d.addLayer({
            id: 'jurisdicciones-fill',
            type: 'fill',
            source: 'jurisdicciones-src',
            paint: { 'fill-color': ['get', 'color'], 'fill-opacity': 0.15 },
        });
    }
    if (!map3d.getLayer('jurisdicciones-line')) {
        map3d.addLayer({
            id: 'jurisdicciones-line',
            type: 'line',
            source: 'jurisdicciones-src',
            paint: { 'line-color': '#000000', 'line-width': 2 },
        });
    }

    sincronizarCapasConSwitches3D();
}

// ----------------------------------------
// Control de capas
// ----------------------------------------
function toggleLayerControl3D() {
    const content = document.getElementById('layerControlContent3d');
    const toggle = document.getElementById('layerControlToggle3d');
    if (!content || !toggle) return;
    const oculto = content.classList.toggle('hidden');
    toggle.classList.toggle('collapsed', oculto);
    toggle.textContent = oculto ? '▶' : '▼';
}

function toggleSwitch3D(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.checked = !el.checked;
    el.dispatchEvent(new Event('change'));
}

function toggleLayer3D(clave, checked) {
    if (LAYER_IDS_3D[clave]) {
        LAYER_IDS_3D[clave].forEach(function(id) {
            if (map3d.getLayer(id)) {
                map3d.setLayoutProperty(id, 'visibility', checked ? 'visible' : 'none');
            }
        });
        return;
    }
    // Switches específicos de tipo de cámara (camaras-fijas, camaras-fr, ...)
    aplicarFiltroCamaras3D();
}

function tiposCamaraVisibles3D() {
    let tipos = [];
    Object.keys(TIPOS_CAMARA_POR_CLAVE).forEach(function(clave) {
        const el = document.getElementById('switch3d-' + clave);
        if (!el || el.checked) {
            tipos = tipos.concat(TIPOS_CAMARA_POR_CLAVE[clave]);
        }
    });
    return tipos;
}

function aplicarFiltroCamaras3D() {
    const filtro = ['in', ['get', 'tipo_camara'], ['literal', tiposCamaraVisibles3D()]];
    ['camaras-poste', 'camaras-cabeza', 'camaras-cono'].forEach(function(id) {
        if (map3d.getLayer(id)) map3d.setFilter(id, filtro);
    });
}

function sincronizarCapasConSwitches3D() {
    Object.keys(LAYER_IDS_3D).forEach(function(clave) {
        const el = document.getElementById('switch3d-' + clave);
        const checked = el ? el.checked : true;
        LAYER_IDS_3D[clave].forEach(function(id) {
            if (map3d.getLayer(id)) {
                map3d.setLayoutProperty(id, 'visibility', checked ? 'visible' : 'none');
            }
        });
    });
    aplicarFiltroCamaras3D();
}

function clearAllLayers3D() {
    document.querySelectorAll('#customLayerControl3d input[type=checkbox]').forEach(function(el) {
        el.checked = false;
    });
    sincronizarCapasConSwitches3D();
}

// ----------------------------------------
// Interacción (click en volúmenes → popup)
// ----------------------------------------
function bindInteracciones3D() {
    const capasClicables = [
        'camaras-poste', 'camaras-cabeza', 'camaras-cono',
        'comisarias-vol', 'comisarias-num',
        'antenas-base', 'antenas-mastil',
        'sitios-vol',
    ];

    capasClicables.forEach(function(id) {
        map3d.on('click', id, function(e) {
            const feature = e.features[0];
            if (!feature) return;
            mostrarPopup3D(id, feature.properties, e.lngLat);
        });
        map3d.on('mouseenter', id, function() { map3d.getCanvas().style.cursor = 'pointer'; });
        map3d.on('mouseleave', id, function() { map3d.getCanvas().style.cursor = ''; });
    });

    document.getElementById('map3d-loader');
}

function mostrarPopup3D(layerId, props, lngLat) {
    let html;
    if (layerId.indexOf('camaras') === 0) {
        html = popupCamara3D(props, lngLat.lng, lngLat.lat);
    } else if (layerId.indexOf('comisarias') === 0) {
        html = popupSimple3D(props.titulo);
    } else if (layerId.indexOf('antenas') === 0) {
        html = popupSimple3D(props.titulo);
    } else if (layerId === 'sitios-vol') {
        html = popupSitio3D(props, lngLat.lng, lngLat.lat);
    } else {
        return;
    }

    new maplibregl.Popup({ maxWidth: '320px', closeOnClick: true })
        .setLngLat(lngLat)
        .setHTML(html)
        .addTo(map3d);
}

function popupSimple3D(titulo) {
    return '<div><h6>' + escapeHtml(titulo) + '</h6></div>';
}

function popupCamara3D(props, lng, lat) {
    const canales = props.canales || 1;
    const cartel = props.cartel ? 'SI' : 'NO';

    let html = '<div>';
    if (props.imagen) {
        html += '<img src="' + escapeHtml(props.imagen) + '" alt="" onerror="this.style.display=\'none\'">';
    }
    html += '<h5>' + escapeHtml(props.titulo) + '</h5>'
        + 'Tipo: <b>' + escapeHtml(props.tipo_camara) + '</b><br>'
        + 'Sitio: <b>' + escapeHtml(props.sitio) + '</b><br>'
        + 'Ángulo: <b>' + (props.angulo || 60) + '°</b><br>'
        + 'Orientación: <b>' + escapeHtml(props.orientacion) + '</b><br>'
        + 'Señalizado: <b>' + cartel + '</b><br>'
        + 'Dependencia: <b>' + escapeHtml(props.dependencia) + '</b><br>'
        + 'Etapa: <b>' + escapeHtml(props.etapa) + '</b><br>'
        + 'Instalación: <b>' + escapeHtml(props.fecha_instalacion) + '</b><br>'
        + 'Inteligencia: <b>' + escapeHtml(props.inteligencia) + '</b><br>'
        + 'Marca: <b>' + escapeHtml(props.marca) + '</b> - Mod.: <b>' + escapeHtml(props.modelo) + '</b><br>'
        + 'Nº serie: <b>' + escapeHtml(props.nro_serie) + '</b><br>'
        + '<div class="btn-group" role="group">'
        + '<button class="btn btn-icon btn-primary" title="Editar cámara" onclick="editCamera(' + props.id + ')"><i class="fas fa-edit"></i></button>'
        + '<button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(' + lat + ',' + lng + ')"><i class="fas fa-globe-americas"></i></button>'
        + '<button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(' + lat + ',' + lng + ')"><i class="fas fa-street-view"></i></button>'
        @can('ver-stream-camara')
        + '<button class="btn btn-icon btn-success" title="Ver en Vivo" data-camara-id="' + escapeHtml(props.id) + '" data-camara-titulo="' + escapeHtml(props.titulo) + '" data-camara-canales="' + escapeHtml(canales) + '" onclick="openCameraStreamFromButton(this)"><i class="fas fa-video"></i></button>'
        @endcan
        + '</div></div>';

    return html;
}

function popupSitio3D(props, lng, lat) {
    const cartel = (props.cartel === null || props.cartel === undefined) ? null : (props.cartel ? 'SI' : 'NO');

    let html = '<div><h5>' + escapeHtml(props.titulo) + '</h5>'
        + '<strong>Estado:</strong> <span style="color: #dc3545;">INACTIVO</span><br>';
    if (cartel !== null) {
        html += '<strong>Cartel:</strong> <b>' + cartel + '</b><br>';
    }
    if (props.observaciones) {
        html += '<strong>Observaciones:</strong> ' + escapeHtml(props.observaciones) + '<br>';
    }
    html += '<div class="btn-group" role="group">'
        + '<button class="btn btn-icon btn-info" title="Abrir en Google Maps" onclick="openGoogleMaps(' + lat + ',' + lng + ')"><i class="fas fa-globe-americas"></i></button>'
        + '<button class="btn btn-icon btn-warning" title="Abrir en Street View" onclick="openStreetView(' + lat + ',' + lng + ')"><i class="fas fa-street-view"></i></button>'
        + '</div></div>';

    return html;
}

// ----------------------------------------
// Buscador de cámara (header)
// ----------------------------------------
function volarACamara3D(lat, lng) {
    if (!map3d) return;
    map3d.flyTo({ center: [lng, lat], zoom: 18, pitch: 65, speed: 1.2 });
}
</script>
