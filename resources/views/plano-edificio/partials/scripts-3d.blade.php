<script>
// ========================================
// PLANO 3D DEL EDIFICIO 911 (Three.js)
// ========================================

// Configuración de pisos y dimensiones
const FLOORS_3D = [
    { key: 'PB', label: 'Planta Baja' },
    { key: '1',  label: 'Piso 1' },
    { key: '2',  label: 'Piso 2' },
    { key: '3',  label: 'Piso 3' },
    { key: '4',  label: 'Piso 4' },
];
const PLANO3D_W = 120;                 // ancho del piso en unidades
const PLANO3D_H = 135;                 // largo (mantiene aspecto del SVG 32356x36401)
const FLOOR3D_HEIGHT = 16;             // separación base entre pisos
const SLAB3D_THICKNESS = 1.6;          // grosor de la losa

// Estado global
let scene3d, camera3d, renderer3d, controls3d;
let floorGroups3d = {};                // key => THREE.Group
let floorTopMeshes3d = [];             // meshes superiores para doble click
let pickMeshes3d = [];                 // meshes clickeables de dispositivos
let markers3d = [];                    // { group, device }
let dispositivos3d = [];
let separacion3d = 1;
let floorTexture3d = null;
let textureLista3d = false;
let dispositivosListos3d = false;
let raycaster3d = null;
let hovered3d = null;
let tooltipPinned3d = false;
let pinnedMarker3d = null;
let suppressUnpin3d = false;
let pointerDownPos = null;
const iconTextureCache3d = {};

const plano3dPerms = {
    canCreate: @json(auth()->user()->can('crear-plano-edificio')),
    canEdit: @json(auth()->user()->can('editar-plano-edificio')),
    canCredentials: @json(auth()->user()->can('credenciales-plano-edificio')),
};

// Glifos FontAwesome 5 (solid) para los íconos de dispositivos
const FA_GLYPHS_3D = {
    'fas fa-desktop': '\uf108',
    'fas fa-headset': '\uf590',
    'fas fa-video': '\uf03d',
    'fas fa-wifi': '\uf1eb',
    'fas fa-network-wired': '\uf6ff',
    'fas fa-server': '\uf233',
    'fas fa-cloud': '\uf0c2',
    'fas fa-record-vinyl': '\uf8d9',
    'fas fa-hdd': '\uf0a0',
    'fas fa-broadcast-tower': '\uf519',
    'fas fa-cube': '\uf1b2',
};

// Toast (por si el layout no lo define)
if (typeof window.showToast !== 'function') {
    window.showToast = function(message, type) {
        if (typeof iziToast !== 'undefined') {
            const opts = {
                title: type === 'error' ? 'Error' : (type === 'success' ? 'OK' : 'Info'),
                message: message,
                position: 'topRight',
                timeout: 5000,
            };
            if (type === 'error') { iziToast.error(opts); return; }
            if (type === 'success') { iziToast.success(opts); return; }
            iziToast.info(opts);
            return;
        }
        console.log(type + ': ' + message);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    initEscena3D();
    construirPisos3D();
    cargarTexturaPlano3D();
    cargarDispositivos3D();
    construirTogglesPisos3D();
    setupEventos3D();
});

// ----------------------------------------
// Escena
// ----------------------------------------
function initEscena3D() {
    const container = document.getElementById('plano3d-container');
    const canvas = document.getElementById('plano3d-canvas');

    scene3d = new THREE.Scene();

    camera3d = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 2000);
    camera3d.position.set(95, 105, 150);

    try {
        renderer3d = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
    } catch (e) {
        document.getElementById('plano3d-loader').innerHTML = 'Su navegador no soporta WebGL';
        return;
    }
    renderer3d.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer3d.setSize(container.clientWidth, container.clientHeight);
    renderer3d.outputEncoding = THREE.sRGBEncoding;

    controls3d = new THREE.OrbitControls(camera3d, renderer3d.domElement);
    controls3d.enableDamping = true;
    controls3d.dampingFactor = 0.08;
    controls3d.target.set(0, FLOOR3D_HEIGHT * 1.6, 0);
    controls3d.minDistance = 25;
    controls3d.maxDistance = 520;
    controls3d.maxPolarAngle = Math.PI * 0.52;
    controls3d.autoRotateSpeed = 1.2;

    // Luces
    const hemi = new THREE.HemisphereLight(0xffffff, 0x556677, 0.95);
    scene3d.add(hemi);
    const dir = new THREE.DirectionalLight(0xffffff, 0.55);
    dir.position.set(90, 160, 70);
    scene3d.add(dir);

    // Suelo
    const ground = new THREE.Mesh(
        new THREE.PlaneGeometry(900, 900),
        new THREE.MeshStandardMaterial({ color: 0xccd5dd, roughness: 1 })
    );
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -0.8;
    ground.name = 'ground3d';
    scene3d.add(ground);

    raycaster3d = new THREE.Raycaster();

    aplicarTema3D();
    observarTema3D();

    // Resize
    window.addEventListener('resize', onResize3D);

    // Loop
    (function animate3d() {
        requestAnimationFrame(animate3d);
        controls3d.update();
        if (tooltipPinned3d && pinnedMarker3d) {
            posicionarTooltip3D(pinnedMarker3d);
        }
        renderer3d.render(scene3d, camera3d);
    })();
}

function isDarkTheme3D() {
    return document.documentElement.getAttribute('data-theme') === 'dark'
        || (document.body && document.body.getAttribute('data-theme') === 'dark');
}

function aplicarTema3D() {
    if (!scene3d) return;
    const dark = isDarkTheme3D();
    scene3d.background = new THREE.Color(dark ? 0x1b2430 : 0xdfe7ef);
    const ground = scene3d.getObjectByName('ground3d');
    if (ground) {
        ground.material.color.set(dark ? 0x232c38 : 0xccd5dd);
    }
}

function observarTema3D() {
    const obs = new MutationObserver(aplicarTema3D);
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    if (document.body) {
        obs.observe(document.body, { attributes: true, attributeFilter: ['data-theme'] });
    }
}

function onResize3D() {
    const container = document.getElementById('plano3d-container');
    if (!container || !renderer3d) return;
    camera3d.aspect = container.clientWidth / container.clientHeight;
    camera3d.updateProjectionMatrix();
    renderer3d.setSize(container.clientWidth, container.clientHeight);
}

// ----------------------------------------
// Pisos (losas apiladas con el plano como textura)
// ----------------------------------------
function construirPisos3D() {
    FLOORS_3D.forEach(function(floor, idx) {
        const group = new THREE.Group();
        group.position.y = idx * FLOOR3D_HEIGHT * separacion3d;
        group.name = 'floor3d-' + floor.key;

        // Losa
        const slab = new THREE.Mesh(
            new THREE.BoxGeometry(PLANO3D_W, SLAB3D_THICKNESS, PLANO3D_H),
            new THREE.MeshStandardMaterial({ color: 0xe6eaf0, roughness: 0.9, metalness: 0.05 })
        );
        group.add(slab);

        // Borde de la losa
        const edges = new THREE.LineSegments(
            new THREE.EdgesGeometry(slab.geometry),
            new THREE.LineBasicMaterial({ color: 0x8a94a0 })
        );
        group.add(edges);

        // Cara superior con el plano
        const top = new THREE.Mesh(
            new THREE.PlaneGeometry(PLANO3D_W, PLANO3D_H),
            new THREE.MeshBasicMaterial({ color: 0xffffff, polygonOffset: true, polygonOffsetFactor: -1 })
        );
        top.rotation.x = -Math.PI / 2;
        top.position.y = SLAB3D_THICKNESS / 2 + 0.02;
        top.name = 'floorTop-' + floor.key;
        top.userData.piso = floor.key;
        group.add(top);
        floorTopMeshes3d.push(top);

        // Etiqueta del piso
        const label = crearTextoSprite3D(floor.label);
        label.position.set(-PLANO3D_W / 2 - 12, SLAB3D_THICKNESS / 2 + 2, 0);
        group.add(label);

        scene3d.add(group);
        floorGroups3d[floor.key] = group;
    });
}

function crearTextoSprite3D(texto) {
    const c = document.createElement('canvas');
    c.width = 512; c.height = 128;
    const ctx = c.getContext('2d');
    ctx.fillStyle = 'rgba(52, 58, 64, 0.85)';
    redondearRect3D(ctx, 8, 24, 496, 80, 18);
    ctx.fill();
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 52px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(texto, 256, 66);

    const tex = new THREE.CanvasTexture(c);
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false }));
    sprite.scale.set(22, 5.5, 1);
    sprite.renderOrder = 5;
    return sprite;
}

function redondearRect3D(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

// Textura del plano: usa el PNG rasterizado del SVG (el SVG contiene
// foreignObject de draw.io, lo que contamina el canvas de WebGL)
function cargarTexturaPlano3D() {
    const loader = new THREE.TextureLoader();
    loader.load(
        '{{ asset("img/edificio_911_grande.png") }}',
        function(texture) {
            floorTexture3d = texture;
            floorTexture3d.anisotropy = renderer3d ? renderer3d.capabilities.getMaxAnisotropy() : 4;
            floorTexture3d.encoding = THREE.sRGBEncoding;

            floorTopMeshes3d.forEach(function(top) {
                top.material.map = floorTexture3d;
                top.material.needsUpdate = true;
            });

            textureLista3d = true;
            ocultarLoader3DSiListo();
        },
        undefined,
        function() {
            textureLista3d = true;
            ocultarLoader3DSiListo();
            showToast('No se pudo cargar la imagen del plano', 'error');
        }
    );
}

// ----------------------------------------
// Dispositivos como pines 3D
// ----------------------------------------
function pisoKey3D(piso) {
    const key = (piso === null || piso === undefined || piso === '') ? 'PB' : String(piso);
    return floorGroups3d.hasOwnProperty(key) ? key : 'PB';
}

function posicionDispositivo3D(device) {
    const px = parseFloat(device.posicion_x);
    const py = parseFloat(device.posicion_y);
    const x = (Number.isFinite(px) ? (px / 100 - 0.5) : 0) * PLANO3D_W;
    const z = (Number.isFinite(py) ? (py / 100 - 0.5) : 0) * PLANO3D_H;
    return { x: x, y: SLAB3D_THICKNESS / 2, z: z };
}

function crearMarcador3D(device) {
    const group = new THREE.Group();
    const color = new THREE.Color(device.color || '#6c757d');
    const opacidad = device.activo ? 1 : 0.35;

    const matStem = new THREE.MeshStandardMaterial({ color: color, transparent: opacidad < 1, opacity: opacidad });
    const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.32, 4.4, 10), matStem);
    stem.position.y = 2.2;

    const matHead = new THREE.MeshStandardMaterial({
        color: color,
        emissive: color.clone().multiplyScalar(0.3),
        transparent: opacidad < 1,
        opacity: opacidad
    });
    const head = new THREE.Mesh(new THREE.SphereGeometry(1.5, 18, 14), matHead);
    head.position.y = 5.6;

    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: getIconTexture3D(device),
        transparent: true,
        depthTest: false,
        opacity: opacidad
    }));
    sprite.scale.set(7, 7, 1);
    sprite.position.y = 10;
    sprite.renderOrder = 10;

    [stem, head, sprite].forEach(function(obj) {
        obj.userData.deviceId = device.id;
        group.add(obj);
    });
    pickMeshes3d.push(head, sprite);

    const pos = posicionDispositivo3D(device);
    group.position.set(pos.x, pos.y, pos.z);
    group.userData = { deviceId: device.id, tipo: device.tipo, piso: pisoKey3D(device.piso), activo: device.activo };

    return group;
}

function getIconTexture3D(device) {
    const key = device.tipo + '|' + (device.color || '');
    if (iconTextureCache3d[key]) {
        return iconTextureCache3d[key];
    }

    const c = document.createElement('canvas');
    c.width = 128; c.height = 128;
    const ctx = c.getContext('2d');

    ctx.beginPath();
    ctx.arc(64, 64, 54, 0, Math.PI * 2);
    ctx.fillStyle = device.color || '#6c757d';
    ctx.fill();
    ctx.lineWidth = 8;
    ctx.strokeStyle = 'rgba(255,255,255,0.95)';
    ctx.stroke();

    const glyph = FA_GLYPHS_3D[device.icono] || null;
    let glyphOk = false;
    if (glyph && document.fonts && document.fonts.check) {
        try {
            glyphOk = document.fonts.check('900 56px "Font Awesome 5 Free"');
        } catch (e) {
            glyphOk = false;
        }
    }

    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    if (glyph && glyphOk) {
        ctx.font = '900 56px "Font Awesome 5 Free"';
        ctx.fillText(glyph, 64, 68);
    } else {
        ctx.font = 'bold 52px Arial';
        ctx.fillText((device.tipo_label || '?').trim().charAt(0).toUpperCase(), 64, 68);
    }

    const tex = new THREE.CanvasTexture(c);
    tex.anisotropy = 4;
    iconTextureCache3d[key] = tex;
    return tex;
}

function reconstruirMarcadores3D() {
    // Quitar marcadores anteriores
    markers3d.forEach(function(m) {
        if (m.group.parent) {
            m.group.parent.remove(m.group);
        }
    });
    markers3d = [];
    pickMeshes3d = [];

    dispositivos3d.forEach(function(device) {
        const marker = crearMarcador3D(device);
        const pisoKey = pisoKey3D(device.piso);
        floorGroups3d[pisoKey].add(marker);
        markers3d.push({ group: marker, device: device });
    });

    actualizarVisibilidad3D();
}

function actualizarVisibilidad3D() {
    const showInactive = document.getElementById('show-inactive-3d')
        ? document.getElementById('show-inactive-3d').checked
        : true;

    markers3d.forEach(function(m) {
        const tipoToggle = document.querySelector('.layer-toggle-3d[data-tipo="' + m.device.tipo + '"]');
        const tipoVisible = tipoToggle ? tipoToggle.checked : true;
        const pisoGroup = floorGroups3d[pisoKey3D(m.device.piso)];
        const pisoVisible = pisoGroup ? pisoGroup.visible : true;
        const activoVisible = m.device.activo || showInactive;

        m.group.visible = tipoVisible && pisoVisible && activoVisible;
    });

    actualizarContadores3D();
}

function actualizarContadores3D() {
    document.querySelectorAll('#customLayerControl3d .layer-count').forEach(function(counter) {
        const tipo = counter.dataset.tipoCount;
        const count = markers3d.filter(function(m) {
            return m.device.tipo === tipo && m.group.visible;
        }).length;
        counter.textContent = count;
    });
}

// ----------------------------------------
// Carga de datos y filtros (misma API que 2D)
// ----------------------------------------
function cargarDispositivos3D() {
    fetch('/api/plano-edificio/devices')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                dispositivos3d = data.data;
                reconstruirMarcadores3D();
            } else {
                showToast(data.message || 'Error al cargar dispositivos', 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            showToast('Error al cargar dispositivos', 'error');
        })
        .finally(function() {
            dispositivosListos3d = true;
            ocultarLoader3DSiListo();
        });
}

function aplicarFiltros3D() {
    const oficina = document.getElementById('filtro-oficina').value;
    const activo = document.getElementById('filtro-activos').checked;

    const params = new URLSearchParams();
    if (oficina) params.append('oficina', oficina);
    params.append('activo', activo);

    fetch('/api/plano-edificio/devices?' + params)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                dispositivos3d = data.data;
                reconstruirMarcadores3D();
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            showToast('Error al aplicar filtros', 'error');
        });
}

// ----------------------------------------
// Interacción (hover / click / doble click)
// ----------------------------------------
function setupEventos3D() {
    const canvas = renderer3d.domElement;

    document.getElementById('filtro-oficina').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') aplicarFiltros3D();
    });
    document.getElementById('filtro-activos').addEventListener('change', aplicarFiltros3D);

    document.getElementById('separacion-pisos').addEventListener('input', function() {
        separacion3d = parseFloat(this.value);
        FLOORS_3D.forEach(function(floor, idx) {
            floorGroups3d[floor.key].position.y = idx * FLOOR3D_HEIGHT * separacion3d;
        });
    });

    document.querySelectorAll('#customLayerControl3d .layer-toggle-3d').forEach(function(toggle) {
        toggle.addEventListener('change', actualizarVisibilidad3D);
    });
    document.getElementById('show-inactive-3d').addEventListener('change', actualizarVisibilidad3D);

    canvas.addEventListener('pointerdown', function(e) {
        pointerDownPos = { x: e.clientX, y: e.clientY };
    });

    canvas.addEventListener('pointerup', function(e) {
        if (!pointerDownPos) return;
        const dx = e.clientX - pointerDownPos.x;
        const dy = e.clientY - pointerDownPos.y;
        pointerDownPos = null;
        if (Math.sqrt(dx * dx + dy * dy) > 5) return; // fue arrastre, no click
        manejarClick3D(e);
    });

    canvas.addEventListener('mousemove', manejarHover3D);
    canvas.addEventListener('dblclick', manejarDobleClick3D);

    document.addEventListener('click', function(e) {
        if (suppressUnpin3d) {
            suppressUnpin3d = false;
            return;
        }
        if (!tooltipPinned3d) return;
        const tooltip = document.getElementById('device-tooltip');
        if (tooltip && tooltip.contains(e.target)) return;
        if (e.target === canvas) {
            desanclarTooltip3D();
        }
    });
}

function obtenerInterseccion3D(e, objetos) {
    const canvas = renderer3d.domElement;
    const rect = canvas.getBoundingClientRect();
    const mouse = new THREE.Vector2(
        ((e.clientX - rect.left) / rect.width) * 2 - 1,
        -((e.clientY - rect.top) / rect.height) * 2 + 1
    );
    raycaster3d.setFromCamera(mouse, camera3d);
    const hits = raycaster3d.intersectObjects(objetos, false);
    return hits.length ? hits[0] : null;
}

function manejarClick3D(e) {
    const hit = obtenerInterseccion3D(e, pickMeshes3d.filter(function(m) {
        return m.visible && m.parent && m.parent.visible;
    }));

    if (!hit) return;

    const deviceId = hit.object.userData.deviceId;
    const marker = markers3d.find(function(m) { return m.device.id === deviceId; });
    if (!marker) return;

    if (tooltipPinned3d && pinnedMarker3d === marker) {
        desanclarTooltip3D();
        return;
    }

    tooltipPinned3d = true;
    pinnedMarker3d = marker;
    suppressUnpin3d = true; // evitar que el mismo click lo desancle
    mostrarTooltip3D(marker, true);
}

function manejarHover3D(e) {
    if (tooltipPinned3d) return;

    const hit = obtenerInterseccion3D(e, pickMeshes3d.filter(function(m) {
        return m.visible && m.parent && m.parent.visible;
    }));

    if (!hit) {
        if (hovered3d) {
            hovered3d = null;
            ocultarTooltip3D();
            renderer3d.domElement.style.cursor = '';
        }
        return;
    }

    const deviceId = hit.object.userData.deviceId;
    const marker = markers3d.find(function(m) { return m.device.id === deviceId; });
    if (!marker) return;

    renderer3d.domElement.style.cursor = 'pointer';
    hovered3d = marker;
    mostrarTooltip3D(marker, false);
}

function manejarDobleClick3D(e) {
    if (!plano3dPerms.canCreate) return;

    const hits = [];
    const canvas = renderer3d.domElement;
    const rect = canvas.getBoundingClientRect();
    const mouse = new THREE.Vector2(
        ((e.clientX - rect.left) / rect.width) * 2 - 1,
        -((e.clientY - rect.top) / rect.height) * 2 + 1
    );
    raycaster3d.setFromCamera(mouse, camera3d);

    floorTopMeshes3d.forEach(function(top) {
        if (!top.parent.visible) return;
        const h = raycaster3d.intersectObject(top, false);
        if (h.length) hits.push(h[0]);
    });

    if (!hits.length) return;
    hits.sort(function(a, b) { return a.distance - b.distance; });
    const hit = hits[0];

    // Convertir punto local del piso a % del plano
    const local = hit.object.worldToLocal(hit.point.clone());
    const px = Math.min(Math.max(((local.x / PLANO3D_W) + 0.5) * 100, 0), 100);
    const py = Math.min(Math.max(((-local.y / PLANO3D_H) + 0.5) * 100, 0), 100);

    abrirModalCrear(px.toFixed(2), py.toFixed(2));

    const pisoSelect = document.getElementById('device-piso');
    if (pisoSelect && hit.object.userData.piso) {
        pisoSelect.value = hit.object.userData.piso;
    }
}

// ----------------------------------------
// Tooltip
// ----------------------------------------
function mostrarTooltip3D(marker, pinned) {
    const device = marker.device;
    const tooltip = document.getElementById('device-tooltip');

    let content = '<h6>' + escapeHtml3D(device.nombre) + '</h6>'
        + '<p><strong>Tipo:</strong> ' + escapeHtml3D(device.tipo_label) + '</p>'
        + '<p><strong>Oficina:</strong> ' + escapeHtml3D(device.oficina) + (device.piso ? ' - ' + escapeHtml3D(device.piso) : '') + '</p>';

    if (device.ip) {
        content += '<p><strong>IP:</strong> ' + escapeHtml3D(device.ip) + '</p>';
    }

    if (device.tiene_credenciales) {
        content += '<p class="has-credentials"><i class="fas fa-key"></i> Tiene credenciales</p>';
    } else {
        content += '<p class="no-credentials"><i class="fas fa-key"></i> Sin credenciales</p>';
    }

    if (pinned) {
        content += '<div class="actions">';
        if (plano3dPerms.canEdit) {
            content += '<button class="btn btn-xs btn-primary" onclick="abrirModalEditar(' + device.id + ')"><i class="fas fa-edit"></i></button> ';
        }
        content += '<button class="btn btn-xs btn-info" onclick="showDeviceDetails3D(' + device.id + ')"><i class="fas fa-info-circle"></i></button> ';
        if (plano3dPerms.canCredentials && device.tiene_credenciales) {
            content += '<button class="btn btn-xs btn-success" onclick="showCredentials3D(' + device.id + ')"><i class="fas fa-key"></i></button>';
        }
        content += '</div>';
    }

    tooltip.innerHTML = content;
    tooltip.style.display = 'block';
    tooltip.classList.toggle('pinned', pinned);

    posicionarTooltip3D(marker);
}

function posicionarTooltip3D(marker) {
    const tooltip = document.getElementById('device-tooltip');
    const container = document.getElementById('plano3d-container');
    if (!tooltip || !container) return;

    const world = new THREE.Vector3();
    marker.group.getWorldPosition(world);
    world.y += 11;

    const ndc = world.project(camera3d);
    const rect = container.getBoundingClientRect();
    let left = (ndc.x * 0.5 + 0.5) * rect.width;
    let top = (-ndc.y * 0.5 + 0.5) * rect.height - tooltip.offsetHeight - 8;

    left = Math.min(Math.max(left, 6), Math.max(6, rect.width - tooltip.offsetWidth - 6));
    top = Math.min(Math.max(top, 6), Math.max(6, rect.height - tooltip.offsetHeight - 6));

    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function ocultarTooltip3D() {
    const tooltip = document.getElementById('device-tooltip');
    if (!tooltip) return;
    tooltip.style.display = 'none';
    tooltip.classList.remove('pinned');
}

function desanclarTooltip3D() {
    tooltipPinned3d = false;
    pinnedMarker3d = null;
    ocultarTooltip3D();
}

function escapeHtml3D(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ----------------------------------------
// Controles de capas / pisos / vista
// ----------------------------------------
function construirTogglesPisos3D() {
    const container = document.getElementById('pisos-control');
    FLOORS_3D.forEach(function(floor) {
        const div = document.createElement('div');
        div.className = 'custom-control custom-switch';
        div.innerHTML = '<input type="checkbox" class="custom-control-input" id="floor3d-' + floor.key + '" checked>'
            + '<label class="custom-control-label" for="floor3d-' + floor.key + '">' + floor.label + '</label>';
        container.appendChild(div);

        div.querySelector('input').addEventListener('change', function() {
            floorGroups3d[floor.key].visible = this.checked;
            actualizarVisibilidad3D();
        });
    });
}

function toggleLayerControl3D() {
    const content = document.getElementById('layerControlContent3d');
    const toggle = document.getElementById('layerControlToggle3d');
    if (!content || !toggle) return;
    const isHidden = content.style.display === 'none';
    content.style.display = isHidden ? 'block' : 'none';
    toggle.textContent = isHidden ? '▼' : '▲';
}

function toggleSwitch3D(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.checked = !el.checked;
    el.dispatchEvent(new Event('change'));
}

function toggleLayerGroup3D(groupId) {
    const content = document.getElementById('layer3d-group-content-' + groupId);
    const icon = document.getElementById('layer3d-group-icon-' + groupId);
    if (!content || !icon) return;
    const isOpen = content.classList.contains('is-open');
    content.classList.toggle('is-open', !isOpen);
    icon.classList.toggle('fa-chevron-down', isOpen);
    icon.classList.toggle('fa-chevron-up', !isOpen);
}

function selectAllLayers3D() {
    document.querySelectorAll('#customLayerControl3d .layer-toggle-3d').forEach(function(t) {
        t.checked = true;
    });
    actualizarVisibilidad3D();
}

function deselectAllLayers3D() {
    document.querySelectorAll('#customLayerControl3d .layer-toggle-3d').forEach(function(t) {
        t.checked = false;
    });
    actualizarVisibilidad3D();
}

function resetVista3D() {
    if (!camera3d || !controls3d) return;
    camera3d.position.set(95, 105, 150);
    controls3d.target.set(0, FLOOR3D_HEIGHT * 1.6, 0);
    controls3d.update();
}

function toggleAutoRotate() {
    if (!controls3d) return;
    controls3d.autoRotate = !controls3d.autoRotate;
    document.getElementById('btn-autorotate').classList.toggle('active', controls3d.autoRotate);
}

// ----------------------------------------
// Detalles / credenciales / guardado
// ----------------------------------------
function showModal3D(title, html) {
    document.getElementById('infoModalTitle').textContent = title;
    document.getElementById('infoModalBody').innerHTML = html;
    if (window.jQuery) {
        window.jQuery('#infoModal').modal('show');
    }
}

function showDeviceDetails3D(deviceId) {
    const device = dispositivos3d.find(function(d) { return d.id === deviceId; });
    if (!device) return;

    let details = '<h5>' + escapeHtml3D(device.nombre) + '</h5>'
        + '<table class="table table-sm">'
        + '<tr><td><strong>Tipo:</strong></td><td>' + escapeHtml3D(device.tipo_label) + '</td></tr>'
        + '<tr><td><strong>Oficina:</strong></td><td>' + escapeHtml3D(device.oficina) + '</td></tr>';

    if (device.piso) details += '<tr><td><strong>Piso:</strong></td><td>' + escapeHtml3D(device.piso) + '</td></tr>';
    if (device.ip) details += '<tr><td><strong>IP:</strong></td><td>' + escapeHtml3D(device.ip) + '</td></tr>';
    if (device.mac) details += '<tr><td><strong>MAC:</strong></td><td>' + escapeHtml3D(device.mac) + '</td></tr>';
    if (device.marca) details += '<tr><td><strong>Marca:</strong></td><td>' + escapeHtml3D(device.marca) + '</td></tr>';
    if (device.modelo) details += '<tr><td><strong>Modelo:</strong></td><td>' + escapeHtml3D(device.modelo) + '</td></tr>';
    if (device.serie) details += '<tr><td><strong>Serie:</strong></td><td>' + escapeHtml3D(device.serie) + '</td></tr>';
    if (device.sistema_operativo) details += '<tr><td><strong>SO:</strong></td><td>' + escapeHtml3D(device.sistema_operativo) + '</td></tr>';
    if (device.puertos) details += '<tr><td><strong>Puertos:</strong></td><td>' + escapeHtml3D(device.puertos) + '</td></tr>';

    details += '<tr><td><strong>Estado:</strong></td><td><span class="status-badge ' + (device.activo ? 'active' : 'inactive') + '">' + (device.activo ? 'Activo' : 'Inactivo') + '</span></td></tr>'
        + '<tr><td><strong>Creado:</strong></td><td>' + escapeHtml3D(device.created_at) + '</td></tr>'
        + '<tr><td><strong>Actualizado:</strong></td><td>' + escapeHtml3D(device.updated_at) + '</td></tr>';

    if (device.observaciones) {
        details += '<tr><td><strong>Observaciones:</strong></td><td>' + escapeHtml3D(device.observaciones) + '</td></tr>';
    }

    details += '</table>';
    showModal3D('Detalles del Dispositivo', details);
}

function showCredentials3D(deviceId) {
    fetch('/api/plano-edificio/devices/' + deviceId + '/credentials')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showModal3D('Credenciales del Dispositivo',
                    '<table class="table table-sm">'
                    + '<tr><td><strong>Usuario:</strong></td><td>' + escapeHtml3D(data.data.username) + '</td></tr>'
                    + '<tr><td><strong>Contraseña:</strong></td><td><code>' + escapeHtml3D(data.data.password) + '</code></td></tr>'
                    + '</table>');
            } else {
                showToast('Error al obtener credenciales', 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            showToast('Error al obtener credenciales', 'error');
        });
}

let isSavingDevice3D = false;

// El modal de dispositivo (partial) llama a saveDevice() al enviar el formulario
function saveDevice() {
    if (isSavingDevice3D) return;

    const form = document.getElementById('deviceForm');
    const formData = new FormData(form);
    const deviceId = document.getElementById('device-id').value;

    const data = {};
    formData.forEach(function(value, key) {
        if (value !== '') data[key] = value;
    });
    data.activo = document.getElementById('device-activo').checked;

    const url = deviceId ? '/api/plano-edificio/devices/' + deviceId : '/api/plano-edificio/devices';
    const method = deviceId ? 'PUT' : 'POST';

    isSavingDevice3D = true;
    const saveBtn = form.querySelector('button[type="submit"]');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    }

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(function(response) {
        return response.json().then(function(payload) {
            if (!response.ok) {
                throw { message: payload && payload.message || 'Error al guardar dispositivo', errors: payload && payload.errors };
            }
            return payload;
        });
    })
    .then(function(data) {
        if (data && data.success) {
            showToast(data.message || 'Dispositivo guardado correctamente', 'success');
            if (window.jQuery) {
                window.jQuery('#deviceModal').modal('hide');
            }
            cargarDispositivos3D();
            return;
        }
        throw { message: data && data.message || 'Error al guardar dispositivo', errors: data && data.errors };
    })
    .catch(function(error) {
        console.error('Error:', error);
        let message = error.message || 'Error al guardar dispositivo';
        if (error.errors) {
            const firstKey = Object.keys(error.errors)[0];
            const firstMsg = Array.isArray(error.errors[firstKey]) ? error.errors[firstKey][0] : error.errors[firstKey];
            if (firstMsg) message = firstMsg;
        }
        showToast(message, 'error');
    })
    .finally(function() {
        isSavingDevice3D = false;
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar';
        }
    });
}

// ----------------------------------------
// Loader
// ----------------------------------------
function ocultarLoader3DSiListo() {
    if (!textureLista3d || !dispositivosListos3d) return;
    const loader = document.getElementById('plano3d-loader');
    if (loader) loader.style.display = 'none';
}
</script>
