<script>
// Variables globales
let dispositivos = [];
let currentZoom = 1;
let isDragging = false;
let draggedDevice = null;
let viewport = null;
let dispositivosLayer = null;
let inner = null;
let planoImage = null;
let panX = 0;
let panY = 0;
let lastViewportSize = { w: 0, h: 0 };

let tooltipPinned = false;
let pinnedDeviceId = null;
let pinnedDeviceData = null;
let pinnedAnchorEl = null;

const planoPerms = {
    canCreate: @json(auth()->user()->can('crear-plano-edificio')),
    canEdit: @json(auth()->user()->can('editar-plano-edificio')),
    canPosition: @json(auth()->user()->can('posicionar-plano-edificio')),
    canCredentials: @json(auth()->user()->can('credenciales-plano-edificio')),
    canExport: @json(auth()->user()->can('exportar-plano-edificio')),
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    viewport = document.getElementById('plano-viewport');
    dispositivosLayer = document.getElementById('dispositivos-layer');
    inner = document.getElementById('plano-inner');
    planoImage = document.getElementById('svg-image');

    if (viewport) {
        const r = viewport.getBoundingClientRect();
        lastViewportSize = { w: r.width, h: r.height };
    }

    // Inicializar visor
    initializeViewer();

    // Cargar dispositivos
    loadDevices();

    // Event listeners
    setupEventListeners();

    // Recalcular layout al cambiar tamaño o fullscreen
    setupLayoutStabilizers();

    document.addEventListener('click', function(e) {
        if (!tooltipPinned) return;

        const tooltip = document.getElementById('device-tooltip');
        const clickedOnTooltip = tooltip && tooltip.contains(e.target);
        const clickedOnDevice = e.target && e.target.closest && e.target.closest('.device-icon');
        if (clickedOnTooltip || clickedOnDevice) return;

        unpinTooltip();
    });

function setupLayoutStabilizers() {
    const schedule = debounce(() => {
        stabilizeAfterViewportResize();
    }, 80);

    window.addEventListener('resize', schedule);
    document.addEventListener('fullscreenchange', schedule);
}

function debounce(fn, waitMs) {
    let t = null;
    return function(...args) {
        if (t) window.clearTimeout(t);
        t = window.setTimeout(() => fn.apply(this, args), waitMs);
    };
}

function stabilizeAfterViewportResize() {
    if (!viewport) return;
    const prevW = lastViewportSize.w || 0;
    const prevH = lastViewportSize.h || 0;
    const zoom = currentZoom || 1;

    // Centro actual del mundo (antes del resize)
    const centerWorldX = prevW ? ((prevW / 2) - panX) / zoom : 0;
    const centerWorldY = prevH ? ((prevH / 2) - panY) / zoom : 0;

    // Esperar a que el layout (fullscreen/resize) termine de asentarse
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            const r = viewport.getBoundingClientRect();
            lastViewportSize = { w: r.width, h: r.height };

            // Mantener el mismo centro del mundo en el centro del viewport
            panX = (r.width / 2) - centerWorldX * zoom;
            panY = (r.height / 2) - centerWorldY * zoom;
            updateZoom();

            // Recalcular marcadores desde % de imagen (por cambios de object-fit/letterbox)
            refreshDevicePositionsFromImage();
        });
    });
}

function refreshDevicePositionsFromImage() {
    if (!dispositivosLayer) return;
    const nodes = dispositivosLayer.querySelectorAll('.device-icon');

    nodes.forEach((el) => {
        const id = parseInt(el.dataset.deviceId, 10);
        if (!Number.isFinite(id)) return;

        const device = dispositivos.find(d => d.id === id);
        if (!device) return;

        const imgX = parseFloat(device.posicion_x);
        const imgY = parseFloat(device.posicion_y);
        if (!Number.isFinite(imgX) || !Number.isFinite(imgY)) return;

        const innerPos = imagePercentToInnerPercent(imgX, imgY);
        if (!innerPos) return;

        el.style.left = `${innerPos.x}%`;
        el.style.top = `${innerPos.y}%`;
    });
}

    // Ocultar loader
    setTimeout(() => {
        document.getElementById('svg-loader').style.display = 'none';
    }, 1000);
});

function initializeViewer() {
    // Configurar zoom y pan
    setupZoomAndPan();

    // Configurar drag and drop
    setupDragAndDrop();
}

function setupZoomAndPan() {
    let isPanning = false;
    let startX, startY;

    viewport.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        if (isDragging) return;
        if (e.target && e.target.closest && e.target.closest('.device-icon')) return;
        if (e.target && e.target.closest && e.target.closest('#customLayerControl')) return;
        if (e.target && e.target.closest && e.target.closest('.modal')) return;

        isPanning = true;
        viewport.style.cursor = 'grabbing';
        startX = e.clientX;
        startY = e.clientY;
    });

    viewport.addEventListener('mouseleave', function() {
        isPanning = false;
        viewport.style.cursor = 'grab';
    });

    viewport.addEventListener('mouseup', function() {
        isPanning = false;
        viewport.style.cursor = 'grab';
    });

    viewport.addEventListener('mousemove', function(e) {
        if (!isPanning) return;
        e.preventDefault();
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        startX = e.clientX;
        startY = e.clientY;
        panX += dx;
        panY += dy;
        applyTransform(panX, panY);
    });

    // Wheel zoom
    viewport.addEventListener('wheel', function(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        const newZoom = Math.min(Math.max(currentZoom * delta, 0.5), 10);

        if (newZoom !== currentZoom) {
            zoomAt(e.clientX, e.clientY, newZoom);
        }
    });
}

function zoomAt(clientX, clientY, newZoom) {
    if (!viewport) return;
    const viewportRect = viewport.getBoundingClientRect();

    const sx = clientX - viewportRect.left;
    const sy = clientY - viewportRect.top;

    const prevZoom = currentZoom || 1;
    const worldX = (sx - panX) / prevZoom;
    const worldY = (sy - panY) / prevZoom;

    currentZoom = newZoom;

    // Ajustar pan para mantener el punto bajo el mouse fijo en pantalla
    panX = sx - worldX * newZoom;
    panY = sy - worldY * newZoom;

    updateZoom();
}

function applyTransform(panX, panY) {
    if (!inner) return;
    inner.style.transform = `translate(${panX}px, ${panY}px) scale(${currentZoom})`;

    if (tooltipPinned && pinnedDeviceData && pinnedAnchorEl) {
        positionTooltip(pinnedAnchorEl);
    }
}

function setupDragAndDrop() {
    // Click en el plano para agregar dispositivo
    viewport.addEventListener('dblclick', function(e) {
        if (!planoPerms.canCreate) {
            showToast('No tiene permisos para crear dispositivos en el plano', 'error');
            return;
        }
        if (e.target === viewport || e.target.classList.contains('plano-viewport') || e.target.classList.contains('plano-svg')) {
            const pos = getRelativePercentPosition(e.clientX, e.clientY);
            if (!pos) return;
            abrirModalCrear(pos.x.toFixed(2), pos.y.toFixed(2));
        }
    });

    // Drag de dispositivos existentes
    dispositivosLayer.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('device-icon')) {
            if (!planoPerms.canPosition) {
                return;
            }
            draggedDevice = e.target;
            draggedDevice.classList.add('dragging');
            isDragging = true;

            const startPointerWorld = getWorldPositionFromClient(e.clientX, e.clientY);
            if (!startPointerWorld) return;

            const startLeftPct = parseFloat(draggedDevice.style.left);
            const startTopPct = parseFloat(draggedDevice.style.top);
            const innerW = inner?.offsetWidth || 1;
            const innerH = inner?.offsetHeight || 1;

            const deviceWorldX = (Number.isFinite(startLeftPct) ? (startLeftPct / 100) : 0) * innerW;
            const deviceWorldY = (Number.isFinite(startTopPct) ? (startTopPct / 100) : 0) * innerH;

            const offsetWorldX = startPointerWorld.x - deviceWorldX;
            const offsetWorldY = startPointerWorld.y - deviceWorldY;

            function handleMouseMove(e) {
                if (!isDragging) return;

                const pointerWorld = getWorldPositionFromClient(e.clientX, e.clientY);
                if (!pointerWorld) return;

                const newWorldX = pointerWorld.x - offsetWorldX;
                const newWorldY = pointerWorld.y - offsetWorldY;
                const innerPos = worldPxToInnerPercent(newWorldX, newWorldY);
                if (!innerPos) return;

                draggedDevice.style.left = `${innerPos.x}%`;
                draggedDevice.style.top = `${innerPos.y}%`;
            }

            function handleMouseUp(e) {
                if (!isDragging) return;

                isDragging = false;
                draggedDevice.classList.remove('dragging');

                const finalX = parseFloat(draggedDevice.style.left);
                const finalY = parseFloat(draggedDevice.style.top);
                if (!Number.isFinite(finalX) || !Number.isFinite(finalY)) {
                    draggedDevice = null;
                    document.removeEventListener('mousemove', handleMouseMove);
                    document.removeEventListener('mouseup', handleMouseUp);
                    return;
                }

                const finalWorld = innerPercentToWorldPx(finalX, finalY);
                const imagePct = finalWorld ? worldPxToImagePercent(finalWorld.x, finalWorld.y) : null;
                if (!imagePct) {
                    draggedDevice = null;
                    document.removeEventListener('mousemove', handleMouseMove);
                    document.removeEventListener('mouseup', handleMouseUp);
                    return;
                }

                // Actualizar posición en la base de datos
                updateDevicePosition(draggedDevice.dataset.deviceId, imagePct.x.toFixed(2), imagePct.y.toFixed(2));

                draggedDevice = null;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            }

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        }
    });
}

function getWorldPositionFromClient(clientX, clientY) {
    if (!viewport) return null;
    const rect = viewport.getBoundingClientRect();
    const sx = clientX - rect.left;
    const sy = clientY - rect.top;
    const zoom = currentZoom || 1;

    return {
        x: (sx - panX) / zoom,
        y: (sy - panY) / zoom,
    };
}

function getImageWorldRect() {
    if (!planoImage) return null;
    const r = planoImage.getBoundingClientRect();

    const tl = getWorldPositionFromClient(r.left, r.top);
    const br = getWorldPositionFromClient(r.right, r.bottom);
    if (!tl || !br) return null;

    const w = br.x - tl.x;
    const h = br.y - tl.y;
    if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) return null;

    return { x: tl.x, y: tl.y, w, h };
}

function clampPct(p) {
    return Math.min(Math.max(p, 0), 100);
}

function worldPxToInnerPercent(worldX, worldY) {
    if (!inner) return null;
    const w = inner.offsetWidth || 1;
    const h = inner.offsetHeight || 1;

    return {
        x: clampPct((worldX / w) * 100),
        y: clampPct((worldY / h) * 100),
    };
}

function innerPercentToWorldPx(xPct, yPct) {
    if (!inner) return null;
    const w = inner.offsetWidth || 1;
    const h = inner.offsetHeight || 1;

    return {
        x: (xPct / 100) * w,
        y: (yPct / 100) * h,
    };
}

function worldPxToImagePercent(worldX, worldY) {
    const img = getImageWorldRect();
    if (!img) return null;

    return {
        x: clampPct(((worldX - img.x) / img.w) * 100),
        y: clampPct(((worldY - img.y) / img.h) * 100),
    };
}

function imagePercentToInnerPercent(xPct, yPct) {
    const img = getImageWorldRect();
    if (!img) return null;

    const worldX = img.x + (xPct / 100) * img.w;
    const worldY = img.y + (yPct / 100) * img.h;
    return worldPxToInnerPercent(worldX, worldY);
}

function getRelativePercentPosition(clientX, clientY) {
    // Opción 1: % relativo al área visible de la imagen, independiente de object-fit/letterbox
    const world = getWorldPositionFromClient(clientX, clientY);
    if (!world) return null;
    return worldPxToImagePercent(world.x, world.y);
}

function setupEventListeners() {
    // Filtros
    document.getElementById('filtro-oficina').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            aplicarFiltros();
        }
    });

    document.getElementById('filtro-piso').addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-activos').addEventListener('change', aplicarFiltros);
}

function loadDevices() {
    showLoading();

    fetch('/api/plano-edificio/devices')
        .then(async (response) => {
            const contentType = response.headers.get('content-type') || '';
            let payload = null;

            if (contentType.includes('application/json')) {
                try {
                    payload = await response.json();
                } catch (e) {
                    payload = null;
                }
            } else {
                // muchas veces un 302/403 devuelve HTML (login/denegado)
                try {
                    payload = await response.text();
                } catch (e) {
                    payload = null;
                }
            }

            if (!response.ok) {
                const message = (payload && typeof payload === 'object' && payload.message)
                    ? payload.message
                    : `Error al cargar dispositivos (HTTP ${response.status})`;
                throw { message, status: response.status, payload };
            }

            if (payload && typeof payload === 'object') {
                return payload;
            }

            throw { message: 'Respuesta inválida del servidor al cargar dispositivos', status: response.status, payload };
        })
        .then(data => {
            if (data.success) {
                dispositivos = data.data;
                renderDevices();
                updateLayerCounts();
                return;
            }
            throw { message: data.message || 'Error al cargar dispositivos', status: 200, payload: data };
        })
        .catch(error => {
            console.error('Error:', error);
            showToast(error?.message || 'Error al cargar dispositivos', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

function renderDevices() {
    dispositivosLayer.innerHTML = '';

    dispositivos.forEach(device => {
        const deviceElement = createDeviceElement(device);
        dispositivosLayer.appendChild(deviceElement);
    });
}

function createDeviceElement(device) {
    const div = document.createElement('div');
    div.className = `device-icon ${!device.activo ? 'inactive' : ''}`;
    div.dataset.deviceId = device.id;
    div.dataset.tipo = device.tipo;
    div.style.backgroundColor = device.color;

    const imgX = parseFloat(device.posicion_x);
    const imgY = parseFloat(device.posicion_y);
    const innerPos = (Number.isFinite(imgX) && Number.isFinite(imgY))
        ? imagePercentToInnerPercent(imgX, imgY)
        : null;

    div.style.left = `${(innerPos?.x ?? 0)}%`;
    div.style.top = `${(innerPos?.y ?? 0)}%`;
    div.innerHTML = `<i class="${device.icono}"></i>`;

    // Tooltip
    div.addEventListener('mouseenter', function(e) {
        if (tooltipPinned) return;
        showDeviceTooltip(device, e, false);
    });

    div.addEventListener('mouseleave', function() {
        if (tooltipPinned) return;
        hideDeviceTooltip();
    });

    // Click para fijar tooltip
    div.addEventListener('click', function(e) {
        if (isDragging) return;
        e.stopPropagation();
        togglePinnedTooltip(device, e);
    });

    // Click derecho para editar
    div.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        if (!planoPerms.canEdit) {
            showToast('No tiene permisos para editar dispositivos', 'error');
            return;
        }
        abrirModalEditar(device.id);
    });

    // Doble click para ver detalles
    div.addEventListener('dblclick', function(e) {
        e.stopPropagation();
        showDeviceDetails(device);
    });

    return div;
}

function togglePinnedTooltip(device, event) {
    if (tooltipPinned && pinnedDeviceId === device.id) {
        unpinTooltip();
        return;
    }

    tooltipPinned = true;
    pinnedDeviceId = device.id;
    pinnedDeviceData = device;
    pinnedAnchorEl = event.currentTarget;
    showDeviceTooltip(device, event, true);
}

function unpinTooltip() {
    tooltipPinned = false;
    pinnedDeviceId = null;
    pinnedDeviceData = null;
    pinnedAnchorEl = null;
    hideDeviceTooltip();
}

function showDeviceTooltip(device, event, pinned) {
    const tooltip = document.getElementById('device-tooltip');

    let content = `
        <h6>${device.nombre}</h6>
        <p><strong>Tipo:</strong> ${device.tipo_label}</p>
        <p><strong>Oficina:</strong> ${device.oficina}${device.piso ? ' - ' + device.piso : ''}</p>
    `;

    if (device.ip) {
        content += `<p><strong>IP:</strong> ${device.ip}</p>`;
    }

    if (device.tiene_credenciales) {
        content += `<p class="has-credentials"><i class="fas fa-key"></i> Tiene credenciales</p>`;
    } else {
        content += `<p class="no-credentials"><i class="fas fa-key"></i> Sin credenciales</p>`;
    }

    content += `
        <div class="actions">
            ${planoPerms.canEdit ? `
                <button class="btn btn-xs btn-primary" onclick="abrirModalEditar(${device.id})">
                    <i class="fas fa-edit"></i>
                </button>
            ` : ''}
            <button class="btn btn-xs btn-info" onclick="showDeviceDetails(${device.id})">
                <i class="fas fa-info-circle"></i>
            </button>
            ${(planoPerms.canCredentials && device.tiene_credenciales) ? `
                <button class="btn btn-xs btn-success" onclick="showCredentials(${device.id})">
                    <i class="fas fa-key"></i>
                </button>
            ` : ''}
        </div>
    `;

    tooltip.innerHTML = content;
    tooltip.style.display = 'block';

    if (pinned) {
        tooltip.classList.add('pinned');
    } else {
        tooltip.classList.remove('pinned');
    }

    positionTooltip(event.currentTarget || event.target);
}

function positionTooltip(anchorEl) {
    const tooltip = document.getElementById('device-tooltip');
    if (!tooltip || !anchorEl) return;

    const container = document.getElementById('plano-container');
    const containerRect = container ? container.getBoundingClientRect() : null;
    const rect = anchorEl.getBoundingClientRect();

    const tooltipWidth = tooltip.offsetWidth;
    const tooltipHeight = tooltip.offsetHeight;

    let left = rect.left;
    let top = rect.top - tooltipHeight - 10;

    if (containerRect) {
        left = left - containerRect.left;
        top = top - containerRect.top;

        const maxLeft = Math.max(0, containerRect.width - tooltipWidth - 6);
        const maxTop = Math.max(0, containerRect.height - tooltipHeight - 6);

        left = Math.min(Math.max(left, 6), maxLeft);
        top = Math.min(Math.max(top, 6), maxTop);
    }

    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function hideDeviceTooltip() {
    const tooltip = document.getElementById('device-tooltip');
    if (!tooltip) return;
    tooltip.style.display = 'none';
    tooltip.classList.remove('pinned');
}

function showDeviceDetails(deviceId) {
    const device = dispositivos.find(d => d.id === deviceId);
    if (!device) return;

    let details = `
        <h5>${device.nombre}</h5>
        <table class="table table-sm">
            <tr><td><strong>Tipo:</strong></td><td>${device.tipo_label}</td></tr>
            <tr><td><strong>Oficina:</strong></td><td>${device.oficina}</td></tr>
    `;

    if (device.piso) {
        details += `<tr><td><strong>Piso:</strong></td><td>${device.piso}</td></tr>`;
    }

    if (device.ip) {
        details += `<tr><td><strong>IP:</strong></td><td>${device.ip}</td></tr>`;
    }

    if (device.mac) {
        details += `<tr><td><strong>MAC:</strong></td><td>${device.mac}</td></tr>`;
    }

    if (device.marca) {
        details += `<tr><td><strong>Marca:</strong></td><td>${device.marca}</td></tr>`;
    }

    if (device.modelo) {
        details += `<tr><td><strong>Modelo:</strong></td><td>${device.modelo}</td></tr>`;
    }

    if (device.serie) {
        details += `<tr><td><strong>Serie:</strong></td><td>${device.serie}</td></tr>`;
    }

    if (device.sistema_operativo) {
        details += `<tr><td><strong>SO:</strong></td><td>${device.sistema_operativo}</td></tr>`;
    }

    if (device.puertos) {
        details += `<tr><td><strong>Puertos:</strong></td><td>${device.puertos}</td></tr>`;
    }

    details += `
        <tr><td><strong>Estado:</strong></td><td><span class="status-badge ${device.activo ? 'active' : 'inactive'}">${device.activo ? 'Activo' : 'Inactivo'}</span></td></tr>
        <tr><td><strong>Creado:</strong></td><td>${device.created_at}</td></tr>
        <tr><td><strong>Actualizado:</strong></td><td>${device.updated_at}</td></tr>
    `;

    if (device.observaciones) {
        details += `<tr><td><strong>Observaciones:</strong></td><td>${device.observaciones}</td></tr>`;
    }

    details += `</table>`;

    // Mostrar en modal de detalles
    showModal('Detalles del Dispositivo', details);
}

function showCredentials(deviceId) {
    fetch(`/api/plano-edificio/devices/${deviceId}/credentials`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const credentials = data.data;
                const content = `
                    <table class="table table-sm">
                        <tr><td><strong>Usuario:</strong></td><td>${credentials.username}</td></tr>
                        <tr><td><strong>Contraseña:</strong></td><td><code>${credentials.password}</code></td></tr>
                    </table>
                `;
                showModal('Credenciales del Dispositivo', content);
            } else {
                showToast('Error al obtener credenciales', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al obtener credenciales', 'error');
        });
}

function saveDevice() {
    const form = document.getElementById('deviceForm');
    const formData = new FormData(form);
    const deviceId = document.getElementById('device-id').value;

    // Convertir FormData a JSON
    const data = {};
    formData.forEach((value, key) => {
        if (value !== '') {
            data[key] = value;
        }
    });

    // Agregar checkbox activo
    data.activo = document.getElementById('device-activo').checked;

    const url = deviceId ? `/api/plano-edificio/devices/${deviceId}` : '/api/plano-edificio/devices';
    const method = deviceId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(async (response) => {
        let payload = null;
        try {
            payload = await response.json();
        } catch (e) {
            payload = null;
        }

        if (!response.ok) {
            const message = payload?.message || 'Error al guardar dispositivo';
            const errors = payload?.errors || null;
            throw { message, errors, status: response.status, payload };
        }

        return payload;
    })
    .then(data => {
        if (data && data.success) {
            showToast(data.message || 'Dispositivo guardado correctamente', 'success');
            // Bootstrap 4: cerrar modal con jQuery
            if (window.jQuery) {
                window.jQuery('#deviceModal').modal('hide');
            }
            loadDevices();
            return;
        }

        const message = data?.message || 'Error al guardar dispositivo';
        const errors = data?.errors || null;
        throw { message, errors, status: 200, payload: data };
    })
    .catch(error => {
        console.error('Error:', error);

        let message = error?.message || 'Error al guardar dispositivo';
        if (error?.errors) {
            const firstKey = Object.keys(error.errors)[0];
            const firstMsg = Array.isArray(error.errors[firstKey]) ? error.errors[firstKey][0] : error.errors[firstKey];
            if (firstMsg) {
                message = firstMsg;
            }
        }

        showToast(message, 'error');
    });
}

function updateDevicePosition(deviceId, x, y) {
    fetch(`/api/plano-edificio/devices/${deviceId}/position`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            posicion_x: x,
            posicion_y: y
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar datos locales
            const device = dispositivos.find(d => d.id == deviceId);
            if (device) {
                device.posicion_x = x;
                device.posicion_y = y;
            }
        }
    })
    .catch(error => {
        console.error('Error updating position:', error);
        showToast('Error al actualizar posición', 'error');
    });
}

function aplicarFiltros() {
    const oficina = document.getElementById('filtro-oficina').value;
    const piso = document.getElementById('filtro-piso').value;
    const activo = document.getElementById('filtro-activos').checked;

    showLoading();

    const params = new URLSearchParams();
    if (oficina) params.append('oficina', oficina);
    if (piso) params.append('piso', piso);
    params.append('activo', activo);

    fetch(`/api/plano-edificio/devices?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dispositivos = data.data;
                renderDevices();
                updateLayerCounts();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al aplicar filtros', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

// Funciones de zoom
function zoomIn() {
    currentZoom = Math.min(currentZoom * 1.2, 10);
    updateZoom();
}

function zoomOut() {
    currentZoom = Math.max(currentZoom * 0.8, 0.5);
    updateZoom();
}

function resetZoom() {
    currentZoom = 1;
    updateZoom();
}

function updateZoom() {
    applyTransform(panX, panY);
    updateDeviceScale();
}

function resetearVista() {
    resetZoom();
    panX = 0;
    panY = 0;
    applyTransform(panX, panY);
    document.getElementById('filtro-oficina').value = '';
    document.getElementById('filtro-piso').value = '';
    document.getElementById('filtro-activos').checked = true;
    loadDevices();
}

function updateDeviceScale() {
    const root = document.documentElement;
    if (!root) return;

    const safeZoom = currentZoom || 1;
    const scaleValue = (1 / safeZoom).toFixed(3);
    root.style.setProperty('--device-scale', scaleValue);
}

function toggleFullscreen() {
    return;
}

// Permitir salir del fullscreen con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const container = document.querySelector('.plano-container');
        if (container && container.classList.contains('fullscreen')) {
            toggleFullscreen();
        }
    }
});

function exportarDispositivos() {
    const params = new URLSearchParams();

    const oficina = document.getElementById('filtro-oficina') ? document.getElementById('filtro-oficina').value : '';
    const piso = document.getElementById('filtro-piso') ? document.getElementById('filtro-piso').value : '';
    const activo = document.getElementById('filtro-activos') ? document.getElementById('filtro-activos').checked : null;

    if (oficina) params.append('oficina', oficina);
    if (piso) params.append('piso', piso);
    if (activo !== null) params.append('activo', activo);

    const selectedTipos = Array.from(document.querySelectorAll('#customLayerControl .layer-toggle:checked'))
        .map(el => el.dataset.tipo)
        .filter(Boolean);

    selectedTipos.forEach(tipo => params.append('tipos[]', tipo));

    const url = params.toString()
        ? `/api/plano-edificio/export?${params.toString()}`
        : '/api/plano-edificio/export';

    window.open(url, '_blank');
}

// Utilidades
function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x"></i>';
    document.querySelector('.plano-container').appendChild(overlay);
}

function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showToast(message, type = 'info') {
    if (typeof iziToast !== 'undefined') {
        const opts = {
            title: type === 'error' ? 'Error' : (type === 'success' ? 'OK' : 'Info'),
            message: message,
            position: 'topRight',
            timeout: 5000,
        };

        if (type === 'error') {
            iziToast.error(opts);
            return;
        }
        if (type === 'success') {
            iziToast.success(opts);
            return;
        }
        iziToast.info(opts);
        return;
    }

    console.log(`${type}: ${message}`);
}

function showModal(title, content) {
    // Implementar modal genérico si no existe
    console.log(`${title}: ${content}`);
}
</script>
