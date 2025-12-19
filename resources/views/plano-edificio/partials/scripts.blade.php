<script>
// Variables globales
let dispositivos = [];
let currentZoom = 1;
let isDragging = false;
let draggedDevice = null;
let viewport = null;
let dispositivosLayer = null;
let inner = null;
let panX = 0;
let panY = 0;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    viewport = document.getElementById('plano-viewport');
    dispositivosLayer = document.getElementById('dispositivos-layer');
    inner = document.getElementById('plano-inner');

    // Inicializar visor
    initializeViewer();

    // Cargar dispositivos
    loadDevices();

    // Event listeners
    setupEventListeners();

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
        const newZoom = Math.min(Math.max(currentZoom * delta, 0.5), 3);

        if (newZoom !== currentZoom) {
            currentZoom = newZoom;
            updateZoom();
        }
    });
}

function applyTransform(panX, panY) {
    if (!inner) return;
    inner.style.transform = `translate(${panX}px, ${panY}px) scale(${currentZoom})`;
}

function setupDragAndDrop() {
    // Click en el plano para agregar dispositivo
    viewport.addEventListener('dblclick', function(e) {
        if (e.target === viewport || e.target.classList.contains('plano-viewport') || e.target.classList.contains('plano-svg')) {
            const pos = getRelativePercentPosition(e.clientX, e.clientY);
            if (!pos) return;
            abrirModalCrear(pos.x.toFixed(2), pos.y.toFixed(2));
        }
    });

    // Drag de dispositivos existentes
    dispositivosLayer.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('device-icon')) {
            draggedDevice = e.target;
            draggedDevice.classList.add('dragging');
            isDragging = true;

            const deviceRect = draggedDevice.getBoundingClientRect();
            const offsetX = e.clientX - deviceRect.left;
            const offsetY = e.clientY - deviceRect.top;

            function handleMouseMove(e) {
                if (!isDragging) return;

                const pos = getRelativePercentPosition(e.clientX - offsetX, e.clientY - offsetY, true);
                if (!pos) return;

                draggedDevice.style.left = `${pos.x}%`;
                draggedDevice.style.top = `${pos.y}%`;
            }

            function handleMouseUp(e) {
                if (!isDragging) return;

                isDragging = false;
                draggedDevice.classList.remove('dragging');

                const pos = getRelativePercentPosition(e.clientX, e.clientY);
                if (!pos) {
                    draggedDevice = null;
                    document.removeEventListener('mousemove', handleMouseMove);
                    document.removeEventListener('mouseup', handleMouseUp);
                    return;
                }

                // Actualizar posición en la base de datos
                updateDevicePosition(draggedDevice.dataset.deviceId, pos.x.toFixed(2), pos.y.toFixed(2));

                draggedDevice = null;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            }

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        }
    });
}

function getRelativePercentPosition(clientX, clientY, isAlreadyTopLeft = false) {
    if (!inner) return null;
    const rect = inner.getBoundingClientRect();
    const xPx = isAlreadyTopLeft ? (clientX - rect.left) : (clientX - rect.left);
    const yPx = isAlreadyTopLeft ? (clientY - rect.top) : (clientY - rect.top);

    const x = (xPx / rect.width) * 100;
    const y = (yPx / rect.height) * 100;

    return {
        x: Math.min(Math.max(x, 0), 100),
        y: Math.min(Math.max(y, 0), 100)
    };
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
    div.style.left = `${device.posicion_x}%`;
    div.style.top = `${device.posicion_y}%`;
    div.innerHTML = `<i class="${device.icono}"></i>`;

    // Tooltip
    div.addEventListener('mouseenter', function(e) {
        showDeviceTooltip(device, e);
    });

    div.addEventListener('mouseleave', function() {
        hideDeviceTooltip();
    });

    // Click derecho para editar
    div.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        abrirModalEditar(device.id);
    });

    // Doble click para ver detalles
    div.addEventListener('dblclick', function(e) {
        e.stopPropagation();
        showDeviceDetails(device);
    });

    return div;
}

function showDeviceTooltip(device, event) {
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
            <button class="btn btn-xs btn-primary" onclick="abrirModalEditar(${device.id})">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-xs btn-info" onclick="showDeviceDetails(${device.id})">
                <i class="fas fa-info-circle"></i>
            </button>
            ${device.tiene_credenciales ? `
                <button class="btn btn-xs btn-success" onclick="showCredentials(${device.id})">
                    <i class="fas fa-key"></i>
                </button>
            ` : ''}
        </div>
    `;

    tooltip.innerHTML = content;
    tooltip.style.display = 'block';

    // Posicionar tooltip
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
}

function hideDeviceTooltip() {
    document.getElementById('device-tooltip').style.display = 'none';
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
    currentZoom = Math.min(currentZoom * 1.2, 3);
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

function toggleFullscreen() {
    const container = document.querySelector('.plano-container');
    const icon = document.getElementById('fullscreen-icon');

    if (!container) return;

    container.classList.toggle('fullscreen');

    if (icon) {
        if (container.classList.contains('fullscreen')) {
            if (icon.classList.contains('fa-expand')) {
                icon.classList.replace('fa-expand', 'fa-compress');
            } else {
                icon.classList.add('fa-compress');
                icon.classList.remove('fa-expand');
            }
        } else {
            if (icon.classList.contains('fa-compress')) {
                icon.classList.replace('fa-compress', 'fa-expand');
            } else {
                icon.classList.add('fa-expand');
                icon.classList.remove('fa-compress');
            }
        }
    }
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
    window.open('/api/plano-edificio/export', '_blank');
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
