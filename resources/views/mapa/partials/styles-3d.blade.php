{{-- mapa/partials/styles-3d.blade.php --}}
<style>
/* ========================================
   VISTA 3D DEL MAPA (MapLibre GL)
   ======================================== */
#map3d {
    height: 100vh !important;
    width: 100vw !important;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1;
    background: #dfe7ef;
}

[data-theme="dark"] #map3d {
    background: #1b2430;
}

#map3d-loader {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.65);
    z-index: 15000;
    color: #343a40;
    pointer-events: none;
}

[data-theme="dark"] #map3d-loader {
    background: rgba(27, 36, 48, 0.7);
    color: #e4e6eb;
}

#btn-vista-2d {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

/* Popups de MapLibre con el mismo look que los popups de Leaflet en 2D */
.maplibregl-popup {
    max-width: 300px !important;
}

.maplibregl-popup-content {
    padding: 12px;
    border-radius: 6px;
    font-size: 13px;
}

.maplibregl-popup-content h5,
.maplibregl-popup-content h6 {
    margin-top: 0;
    margin-bottom: 8px;
}

.maplibregl-popup-content img {
    max-width: 100%;
    border-radius: 4px;
    margin-bottom: 6px;
}

.maplibregl-popup-content .btn-group {
    margin-top: 8px;
}

[data-theme="dark"] .maplibregl-popup-content {
    background: var(--card-bg, #1e1e1e);
    color: var(--text-primary, #ffffff);
}

[data-theme="dark"] .maplibregl-popup-tip {
    border-top-color: var(--card-bg, #1e1e1e);
    border-bottom-color: var(--card-bg, #1e1e1e);
}

/* Control de basemap (satelital / claro / oscuro) */
.map3d-basemap-control button {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 8px;
    white-space: nowrap;
}
</style>
