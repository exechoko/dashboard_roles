{{--
    Escáner de código de barras / QR / DataMatrix reutilizable, vía cámara.
    Usa @zxing/library (cargada bajo demanda, no en cada carga de página).

    Uso desde cualquier vista:
        mAbrirEscaner(function (texto) {
            // texto = contenido decodificado del código
        });
--}}
<div class="m-scanner-overlay" id="mScannerOverlay" hidden>
    <div class="m-scanner-overlay__header">
        <span>Escanear código</span>
        <button type="button" class="m-scanner-overlay__close" id="mScannerClose" aria-label="Cerrar">&times;</button>
    </div>
    <div class="m-scanner-overlay__video-wrap">
        <video class="m-scanner-overlay__video" id="mScannerVideo" playsinline muted autoplay></video>
        <div class="m-scanner-overlay__frame"></div>
        <div class="m-scanner-overlay__hint" id="mScannerHint">Apuntá al código de barras, QR o DataMatrix</div>
    </div>
</div>

<script>
    (function () {
        var overlay = document.getElementById('mScannerOverlay');
        var video = document.getElementById('mScannerVideo');
        var closeBtn = document.getElementById('mScannerClose');
        var hint = document.getElementById('mScannerHint');

        var reader = null;
        var callbackActual = null;
        var zxingCargado = typeof window.ZXing !== 'undefined';
        var zxingCargando = null;

        function cargarZXing() {
            if (zxingCargado) {
                return Promise.resolve();
            }
            if (zxingCargando) {
                return zxingCargando;
            }
            zxingCargando = new Promise(function (resolve, reject) {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
                script.onload = function () {
                    zxingCargado = true;
                    resolve();
                };
                script.onerror = function () {
                    reject(new Error('No se pudo cargar el lector de códigos.'));
                };
                document.head.appendChild(script);
            });
            return zxingCargando;
        }

        function mostrarMensaje(mensaje) {
            hint.textContent = mensaje;
        }

        function detener() {
            if (reader) {
                try {
                    reader.reset();
                } catch (e) {
                    // nada que hacer, la cámara ya puede estar liberada
                }
                reader = null;
            }
            video.srcObject = null;
            overlay.hidden = true;
            document.body.style.overflow = '';
        }

        closeBtn.addEventListener('click', detener);

        window.mAbrirEscaner = function (callback) {
            callbackActual = callback;
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            mostrarMensaje('Apuntá al código de barras, QR o DataMatrix');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                mostrarMensaje('Este navegador no permite acceder a la cámara.');
                return;
            }

            cargarZXing()
                .then(function () {
                    var hints = new Map();
                    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                        ZXing.BarcodeFormat.QR_CODE,
                        ZXing.BarcodeFormat.DATA_MATRIX,
                        ZXing.BarcodeFormat.CODE_128,
                        ZXing.BarcodeFormat.CODE_39,
                        ZXing.BarcodeFormat.EAN_13,
                        ZXing.BarcodeFormat.EAN_8,
                        ZXing.BarcodeFormat.UPC_A,
                        ZXing.BarcodeFormat.ITF,
                    ]);
                    reader = new ZXing.BrowserMultiFormatReader(hints);

                    return reader.decodeFromConstraints(
                        { video: { facingMode: 'environment' } },
                        video,
                        function (resultado) {
                            if (resultado) {
                                var texto = resultado.getText();
                                var cb = callbackActual;
                                detener();
                                if (cb) {
                                    cb(texto);
                                }
                            }
                            // Los "no encontrado en este frame" son normales
                            // mientras la cámara sigue buscando: se ignoran.
                        }
                    );
                })
                .catch(function (e) {
                    if (e.name === 'NotAllowedError') {
                        mostrarMensaje('Permiso de cámara denegado. Habilitalo en la configuración del navegador.');
                    } else if (e.name === 'NotFoundError') {
                        mostrarMensaje('No se encontró ninguna cámara en este dispositivo.');
                    } else {
                        mostrarMensaje(e.message || 'No se pudo iniciar la cámara.');
                    }
                });
        };
    })();
</script>
