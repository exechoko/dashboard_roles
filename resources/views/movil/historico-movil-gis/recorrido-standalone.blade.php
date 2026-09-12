<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recorrido &mdash; {{ $historial->recurso }}</title>
<style>{!! file_get_contents(public_path('leaflet/lib/leaflet-dist/leaflet.css')) !!}</style>
<style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f5f7; }
    header { background: linear-gradient(135deg,#1a1a2e,#16213e); color: #fff; padding: 12px 16px; }
    header h1 { font-size: 15px; margin: 0 0 2px; }
    header .sub { font-size: 12px; opacity: .85; }
    #map { width: 100%; height: 70vh; }
    .player {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 14px; background: #fff; border-top: 1px solid #ddd;
    }
    .player button {
        width: 40px; height: 40px; border-radius: 50%; border: 1px solid #ccc;
        background: #fff; font-size: 16px;
    }
    .player input[type=range] { flex: 1; }
    #info { padding: 6px 14px 14px; font-size: 13px; color: #333; background: #fff; }
    .leyenda { padding: 8px 14px; font-size: 11px; color: #555; background: #fff; border-top: 1px solid #eee; }
</style>
</head>
<body>

<header>
    <h1>Recorrido de {{ $historial->recurso }}</h1>
    <div class="sub">{{ $historial->fecha_inicio }} &mdash; {{ $historial->fecha_fin }} &middot; {{ count($registros) }} posiciones</div>
</header>

<div id="map"></div>

<div class="player">
    <button id="btnPrev">&#9664;</button>
    <button id="btnPlay">&#9654;</button>
    <button id="btnNext">&#9654;&#9654;</button>
    <input type="range" id="range" min="0" max="0" value="0">
</div>
<div id="info">&mdash; Toc&aacute; play o arrastr&aacute; la barra &mdash;</div>
<div class="leyenda">
    &#128993; En movimiento &nbsp; &#128309; Detenido &nbsp; &#9888;&#65039; Exceso de velocidad
</div>

<script>{!! file_get_contents(public_path('leaflet/lib/leaflet-dist/leaflet.js')) !!}</script>
<script>
(function () {
    var registros = @json($registros);

    var puntos = [];
    registros.forEach(function (r, idx) {
        if (r.lat !== null && r.lat !== undefined && r.lng !== null && r.lng !== undefined) {
            puntos.push({ idx: idx, lat: r.lat, lng: r.lng });
        }
    });

    var map = L.map('map');
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var latlngs = puntos.map(function (p) { return [p.lat, p.lng]; });
    var ruta = L.polyline(latlngs, { color: '#888', dashArray: '6,6', weight: 3 }).addTo(map);
    var trail = L.polyline([], { color: '#6777ef', weight: 4 }).addTo(map);
    var carIcon = L.divIcon({
        className: '',
        html: '<div style="width:22px;height:22px;border-radius:50%;background:#6777ef;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></div>',
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });
    var carMarker = L.marker(latlngs[0] || [0, 0], { icon: carIcon });

    registros.forEach(function (r) {
        if (r.tiempo_detenido && r.lat !== null && r.lng !== null) {
            var colores = { yellow: '#e6b800', orange: '#e07800', red: '#d9302f' };
            L.circleMarker([r.lat, r.lng], {
                radius: 6,
                color: colores[r.color_tiempo] || '#e6b800',
                fillColor: colores[r.color_tiempo] || '#e6b800',
                fillOpacity: .8,
                weight: 1
            }).bindPopup('Detenido ' + r.tiempo_detenido).addTo(map);
        }
    });

    if (latlngs.length > 0) {
        carMarker.addTo(map);
        map.fitBounds(latlngs, { padding: [24, 24] });
    } else {
        map.setView([-31.75899, -60.47825], 12);
    }

    var range = document.getElementById('range');
    range.max = Math.max(puntos.length - 1, 0);

    var pos = 0, playing = false, timer = null;

    function irA(i) {
        if (puntos.length === 0) return;
        pos = Math.max(0, Math.min(i, puntos.length - 1));
        var slice = latlngs.slice(0, pos + 1);
        trail.setLatLngs(slice);
        carMarker.setLatLng(latlngs[pos]);
        map.panTo(latlngs[pos]);
        range.value = pos;

        var reg = registros[puntos[pos].idx];
        document.getElementById('info').innerHTML =
            reg.fecha + ' &middot; ' + reg.velocidad + ' km/h &middot; ' + (reg.direccion || '') +
            ' &middot; <strong>' + reg.estado + '</strong>' +
            (reg.exceso_velocidad ? ' &middot; <span style="color:#b91c1c">EXCESO</span>' : '');
    }

    function pausar() {
        playing = false;
        document.getElementById('btnPlay').innerHTML = '&#9654;';
        if (timer) { clearInterval(timer); timer = null; }
    }

    document.getElementById('btnPlay').addEventListener('click', function () {
        if (playing) { pausar(); return; }
        if (puntos.length === 0) return;
        playing = true;
        document.getElementById('btnPlay').innerHTML = '&#10074;&#10074;';
        timer = setInterval(function () {
            if (pos >= puntos.length - 1) { pausar(); return; }
            irA(pos + 1);
        }, 350);
    });
    document.getElementById('btnPrev').addEventListener('click', function () { pausar(); irA(pos - 1); });
    document.getElementById('btnNext').addEventListener('click', function () { pausar(); irA(pos + 1); });
    range.addEventListener('input', function (e) { pausar(); irA(parseInt(e.target.value, 10)); });

    if (puntos.length > 0) {
        irA(0);
    }
})();
</script>
</body>
</html>
