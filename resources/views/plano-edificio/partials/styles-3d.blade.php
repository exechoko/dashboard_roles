<style>
/* ========================================
   VISTA 3D DEL PLANO DEL EDIFICIO
   ======================================== */
.plano3d-container {
    position: relative;
    width: 100%;
    height: 72vh;
    min-height: 520px;
    overflow: hidden;
    background: linear-gradient(180deg, #dfe7ef 0%, #f4f7fa 100%);
}

#plano3d-canvas {
    display: block;
    width: 100%;
    height: 100%;
    outline: none;
}

.plano3d-container .loading-overlay {
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
}

.plano3d-container .device-tooltip {
    position: absolute;
    z-index: 12000;
    pointer-events: auto;
}

#pisos-control .custom-control {
    margin-bottom: 8px;
}

#btn-autorotate.active {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

/* Tema oscuro */
[data-theme="dark"] .plano3d-container {
    background: linear-gradient(180deg, #1b2430 0%, #2b3644 100%);
}

[data-theme="dark"] .plano3d-container .loading-overlay {
    background: rgba(30, 35, 42, 0.7);
    color: #e4e6eb;
}
</style>
