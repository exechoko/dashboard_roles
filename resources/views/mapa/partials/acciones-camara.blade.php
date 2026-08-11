{{-- mapa/partials/acciones-camara.blade.php --}}
{{-- Acciones de cámara compartidas entre la vista 2D (Leaflet) y la vista 3D (MapLibre) --}}
<script>
function editCamera(camaraId) {
    @can('editar-camara')
        window.location.href = '/camaras/' + camaraId + '/edit';
    @endcan
}

@can('ver-stream-camara')
function openCameraStream(camaraId, cameraNombre, canales) {
    canales = parseInt(canales) || 1;

    var isMobile   = window.innerWidth < 576;
    var sideBySide = canales >= 2 && !isMobile;

    var panel = document.getElementById('mapaStreamPanel');
    panel.style.maxWidth = sideBySide ? '860px' : '480px';

    document.getElementById('mapaStreamTitle').textContent =
        cameraNombre + (canales > 1 ? ' (' + canales + ' canales)' : ' — En Vivo');

    var container = document.getElementById('mapaStreamContainer');
    container.innerHTML = '';
    container.style.cssText = 'display:flex; flex-wrap:wrap; gap:4px; width:100%;';

    for (var ch = 1; ch <= canales; ch++) {
        var col = document.createElement('div');
        col.style.cssText = 'flex:1 1 ' + (sideBySide ? 'calc(50% - 2px)' : '100%') + '; min-width:0;';

        if (canales > 1) {
            var lbl = document.createElement('div');
            lbl.style.cssText = 'color:#aaa; font-size:10px; text-align:center; margin-bottom:2px;';
            lbl.textContent = 'Canal ' + ch;
            col.appendChild(lbl);
        }

        var wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative; background:#000; border-radius:3px; overflow:hidden; line-height:0;';

        var spinner = document.createElement('div');
        spinner.style.cssText = 'position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff;';
        spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        wrap.appendChild(spinner);

        var img = document.createElement('img');
        img.src   = '/camaras/' + camaraId + '/stream?channel=' + ch;
        img.alt   = 'Canal ' + ch;
        img.style.cssText = 'width:100%; display:block; max-height:' + (isMobile ? '45vw' : '280px') + '; object-fit:contain; opacity:0; transition:opacity .2s;';
        img.onload  = function(i, s) { return function() { i.style.opacity = '1'; s.style.display = 'none'; }; }(img, spinner);
        img.onerror = function(s)    { return function() {
            s.innerHTML = '<small style="color:#ffc107;padding:10px;display:block;text-align:center;"><i class="fas fa-exclamation-triangle"></i><br>Sin señal</small>';
        }; }(spinner);
        wrap.appendChild(img);
        col.appendChild(wrap);
        container.appendChild(col);
    }

    panel.style.display = 'flex';
}

function closeCameraStream() {
    document.getElementById('mapaStreamContainer').innerHTML = '';
    document.getElementById('mapaStreamPanel').style.display = 'none';
}
@endcan

function openGoogleMaps(latitud, longitud) {
    // Abre Google Maps en una nueva pestaña con la ubicación especificada
    window.open('https://www.google.com/maps?q=' + latitud + ',' + longitud, '_blank');
}

@can('ver-stream-camara')
function openCameraStreamFromButton(button) {
    openCameraStream(
        button.dataset.camaraId,
        button.dataset.camaraTitulo,
        button.dataset.camaraCanales
    );
}
@endcan

function openStreetView(latitud, longitud) {
    // Abre Google Maps en una nueva pestaña con el enlace directo a Street View
    window.open('https://www.google.com/maps?q=&layer=c&cbll=' + latitud + ',' + longitud, '_blank');
}

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
