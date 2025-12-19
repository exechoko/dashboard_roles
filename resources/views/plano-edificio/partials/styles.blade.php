<style>
/* Estilos generales del plano */
.plano-container {
    position: relative;
    width: 100%;
    height: 600px;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.plano-viewport {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: none;
    background-color: #ffffff;
    cursor: grab;
}

/* Contenedor interno transformable (zoom/pan) */
.plano-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transform-origin: 0 0;
}

.plano-svg {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    pointer-events: none;
    user-select: none;
    opacity: 1;
    filter: contrast(1.05) brightness(0.98);
}

/* Soporte tema claro/oscuro */
[data-theme="dark"] .plano-container {
    background: #2c3e50;
    border-color: #495057;
}

[data-theme="dark"] .plano-viewport {
    background-color: #1f2d3a;
}

[data-theme="dark"] .plano-svg {
    filter: none;
}

.dispositivos-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 10;
}

/* ========================================
   CONTROL PERSONALIZADO DE CAPAS (overlay)
   ======================================== */
.custom-layer-control {
    position: absolute !important;
    top: 10px;
    right: 10px;
    z-index: 10000 !important;
    background: #ffffff;
    padding: 12px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    font-size: 12px;
    max-width: 260px;
    border: 1px solid #ccc;
    pointer-events: auto;
}

.layer-control-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    margin-bottom: 8px;
}

.custom-layer-control h6 {
    margin: 0;
    font-weight: bold;
    color: #333;
}

.layer-control-toggle {
    font-weight: bold;
    color: #333;
}

.layer-control-content {
    display: block;
}

.custom-layer-control .layer-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.custom-layer-control .layer-item.active {
    background: rgba(0, 123, 255, 0.08);
    border-radius: 6px;
    padding: 4px 6px;
}

.custom-layer-control .layer-count {
    margin-left: auto;
    background: #6c757d;
    color: #ffffff;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

/* Switch estilo mapa */
.custom-layer-control .switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 18px;
}

.custom-layer-control .switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.custom-layer-control .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .2s;
    border-radius: 24px;
}

.custom-layer-control .slider:before {
    position: absolute;
    content: "";
    height: 12px;
    width: 12px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .2s;
    border-radius: 50%;
}

.custom-layer-control input:checked + .slider:before {
    transform: translateX(16px);
}

.custom-layer-control .layer-label {
    font-size: 12px;
    color: #333;
    cursor: pointer;
    user-select: none;
}

[data-theme="dark"] .custom-layer-control {
    background-color: var(--card-bg, #1e1e1e) !important;
    border: 1px solid var(--border-color, #333333) !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5) !important;
}

[data-theme="dark"] .custom-layer-control h6,
[data-theme="dark"] .custom-layer-control .layer-label,
[data-theme="dark"] .custom-layer-control .layer-control-toggle {
    color: var(--text-primary, #ffffff) !important;
}

/* Loader SVG */
.svg-loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #6c757d;
}

/* Iconos de dispositivos */
.device-icon {
    position: absolute;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    cursor: pointer;
    pointer-events: all;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    z-index: 20;
}

.device-icon:hover {
    transform: scale(1.2);
    z-index: 30;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

.device-icon.inactive {
    opacity: 0.5;
    filter: grayscale(100%);
}

.device-icon.dragging {
    cursor: grabbing;
    opacity: 0.8;
    z-index: 1000;
}

/* Tooltip */
.device-tooltip {
    position: absolute;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    pointer-events: none;
    z-index: 1000;
    max-width: 250px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.device-tooltip h6 {
    margin: 0 0 5px 0;
    color: #ffc107;
    font-size: 13px;
}

.device-tooltip p {
    margin: 2px 0;
    font-size: 11px;
}

.device-tooltip .actions {
    margin-top: 8px;
    padding-top: 5px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

/* Control de capas */
.layer-control {
    margin-bottom: 15px;
}

.layer-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px;
    margin-bottom: 5px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: background 0.2s;
}

.layer-item:hover {
    background: #e9ecef;
}

.layer-item.active {
    background: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.layer-icon {
    display: flex;
    align-items: center;
    gap: 8px;
}

.layer-icon i {
    width: 20px;
    text-align: center;
}

.layer-count {
    background: #6c757d;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.layer-item.active .layer-count {
    background: #2196f3;
}

/* Modal de dispositivo */
.modal-header .device-icon-preview {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-right: 15px;
}

.form-group.required .col-form-label::after {
    content: " *";
    color: #dc3545;
}

/* Campos condicionales */
.condicional-field {
    display: none;
}

.condicional-field.show {
    display: block;
}

/* Estados de dispositivos */
.status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.has-credentials {
    color: #28a745;
}

.no-credentials {
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .plano-container {
        height: 400px;
    }

    .device-icon {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }

    .device-tooltip {
        font-size: 10px;
        max-width: 200px;
    }
}

/* Animaciones */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(33, 150, 243, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0);
    }
}

.device-icon.highlighted {
    animation: pulse 2s infinite;
}

/* Modo fullscreen */
.plano-container.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    border-radius: 0;
}

.plano-container.fullscreen .plano-viewport {
    background-size: cover;
}

/* Drag and drop */
.drop-zone {
    position: absolute;
    border: 2px dashed #007bff;
    background: rgba(0, 123, 255, 0.1);
    border-radius: 8px;
    pointer-events: none;
    display: none;
    z-index: 15;
}

.drop-zone.active {
    display: block;
}

/* Loading states */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-overlay.hidden {
    display: none;
}

/* Dark theme support */
[data-theme="dark"] .plano-container {
    background: #2c3e50;
    border-color: #495057;
}

[data-theme="dark"] .layer-item {
    background: #343a40;
    color: #fff;
}

[data-theme="dark"] .layer-item:hover {
    background: #495057;
}

[data-theme="dark"] .device-tooltip {
    background: rgba(52, 58, 64, 0.95);
}
</style>
