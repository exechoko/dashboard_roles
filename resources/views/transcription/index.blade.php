@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Transcripción de Audio usando AWS</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="nav nav-pills" id="myTabTranscription" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="transcribir-tab3" data-toggle="tab" href="#transcribir3"
                                role="tab" aria-controls="home" aria-selected="true">Transcribir audio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="historial-tab3" data-toggle="tab" href="#historial3" role="tab"
                                aria-controls="profile" aria-selected="false">Historial</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <!-- TAB Transcripcion -->
                    <div class="tab-pane fade show active" id="transcribir3" role="tabpanel"
                        aria-labelledby="transcribir-tab3">
                        <div class="row">
                            <!-- Columna izquierda: Subida de archivos -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="mb-0"><i class="fas fa-upload me-2"></i>Subir Archivo de Audio</h4>
                                    </div>
                                    <div class="card-body">
                                        <!-- Área de subida de archivos -->
                                        <div class="upload-area" id="uploadArea"
                                            onclick="document.getElementById('audioFile').click()">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                            <h5>Arrastra tu archivo de audio aquí o haz clic para seleccionar</h5>
                                            <p class="text-muted">Formatos soportados: MP3, WAV, M4A, OGG (máximo 50MB)</p>
                                            <input type="file" id="audioFile" class="d-none" accept="audio/*">
                                        </div>

                                        <!-- Información del archivo seleccionado -->
                                        <div class="file-info" id="fileInfo" style="display: none;">
                                            <h6><i class="fas fa-file-audio"></i> Archivo seleccionado:</h6>
                                            <p id="fileName" class="mb-2"></p>
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-success flex-fill me-2" id="uploadBtn">
                                                    <i class="fas fa-upload me-2"></i> Subir y Transcribir
                                                </button>
                                                <button type="button" class="btn btn-secondary" id="cancelBtn">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Vista previa del audio -->
                                        <div class="audio-preview mt-3" id="audioPreview" style="display: none;">
                                            <h6><i class="fas fa-music me-2"></i> Vista Previa</h6>
                                            <audio id="audioPlayer" class="audio-player w-100" controls></audio>
                                            <div class="playback-controls mt-2 d-flex justify-content-center">
                                                <button class="btn btn-sm btn-success me-2" id="playBtn">
                                                    <i class="fas fa-play me-1"></i> Reproducir
                                                </button>
                                                <button class="btn btn-sm btn-danger" id="stopBtn">
                                                    <i class="fas fa-stop me-1"></i> Detener
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Barra de progreso -->
                                        <div class="progress-container mt-3" id="progressContainer" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span id="progressText">Subiendo archivo...</span>
                                                <span id="progressPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                    id="progressBar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha: Resultados -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Resultado de Transcripción
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="transcriptionResult" class="form-control transcription-text"
                                            style="height: 300px; overflow-y: auto;">
                                            <!-- El texto transcrito aparecerá aquí con formato -->
                                        </div>
                                        <div class="mt-3 d-flex justify-content-between">
                                            <button id="copyBtn" class="btn btn-outline-primary" disabled>
                                                <i class="fas fa-copy me-2"></i> Copiar Texto
                                            </button>
                                            <button id="saveBtn" class="btn btn-outline-success" disabled>
                                                <i class="fas fa-save me-2"></i> Guardar como TXT
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seccion resultados estructurados -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados estructurados</h4>
                            </div>
                            <div id="structuredResults" class="mt-2" style="max-height: 600px; overflow-y: auto;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Los resultados estructurados se mostrarán aquí después de la
                                    transcripción.
                                </div>
                            </div>
                        </div>

                        <!-- Sección de resultados detallados -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Detalles del Procesamiento</h4>
                            </div>
                            <div class="card-body">
                                <div id="detailedResults">
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Los detalles del procesamiento aparecerán aquí después de la
                                            transcripción.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAB Historial -->
                    <div class="tab-pane fade" id="historial3" role="tabpanel" aria-labelledby="historial-tab3">
                        <button class="btn btn-primary" id="cargar-historial">Cargar Historial</button>
                        <div id="historial-container"></div>
                    </div>

                </div>
            </div>
        </div>


        <!-- Modal de error -->
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i> Error
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="errorMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                $('#cargar-historial').on('click', function () {
                    $.ajax({
                        type: 'GET',
                        url: '{{ route('getHistorial') }}',
                        success: function (historial) {
                            $('#historial-container').html('');

                            $.each(historial, function (index, item) {
                                // Construir HTML de transcripción como chat
                                let transcripcionHTML = '<p class="text-muted">No disponible</p>';
                                if (item.transcription) {
                                    try {
                                        const tObj = typeof item.transcription === 'string'
                                            ? JSON.parse(item.transcription)
                                            : item.transcription;
                                        if (tObj && tObj.dialogos) {
                                            transcripcionHTML = `<div class="chat-container" style="display:flex;flex-direction:column;gap:8px;padding:10px;">` +
                                                tObj.dialogos.map(d => {
                                                    const isOp = d.rol === 'OPERADOR_911';
                                                    const bg = isOp ? '#d4edda' : '#cce5ff';
                                                    const borderColor = isOp ? '#28a745' : '#007bff';
                                                    const label = isOp ? 'Operador 911' : 'Denunciante';
                                                    const alignSelf = isOp ? 'flex-start' : 'flex-end';
                                                    const textAlign = isOp ? 'left' : 'right';
                                                    const borderRadius = isOp ? '14px 14px 14px 4px' : '14px 14px 4px 14px';
                                                    return `<div style="display:flex;flex-direction:column;align-items:${alignSelf};max-width:80%;align-self:${alignSelf};">
                                                        <small style="font-weight:bold;margin-bottom:3px;color:${borderColor};">${label}</small>
                                                        <div style="background:${bg};padding:8px 12px;border-radius:${borderRadius};border-left:${isOp ? '3px solid '+borderColor : 'none'};border-right:${isOp ? 'none' : '3px solid '+borderColor};text-align:${textAlign};">
                                                            ${d.texto}
                                                            <br><small style="color:#6c757d;font-size:0.7em;">[${d.timestamp}]</small>
                                                        </div>
                                                    </div>`;
                                                }).join('') +
                                                `</div>`;
                                        }
                                    } catch(e) {
                                        transcripcionHTML = `<pre style="font-size:0.8em;">${String(item.transcription)}</pre>`;
                                    }
                                }

                                // Convertir datos_extraidos a lista organizada
                                const datosArray = [];
                                if (item.datos_extraidos) {
                                    const de = item.datos_extraidos;
                                    if (de.denunciante_nombre) datosArray.push('Denunciante: ' + de.denunciante_nombre);
                                    if (de.denunciante_telefono) datosArray.push('Tel. Denunciante: ' + de.denunciante_telefono);
                                    if (de.denunciante_documento) datosArray.push('Doc. Denunciante: ' + de.denunciante_documento);
                                    (de.nombres_mencionados || de.nombres || []).forEach(v => { if (v) datosArray.push('Nombre: ' + v); });
                                    (de.direcciones || []).forEach(v => { if (v) datosArray.push('Dirección: ' + v); });
                                    (de.telefonos || []).forEach(v => { if (v) datosArray.push('Tel: ' + v); });
                                    (de.documentos || []).forEach(v => { if (v) datosArray.push('Doc: ' + v); });
                                    (de.vehiculos || []).forEach(v => { if (v) datosArray.push('Vehículo: ' + v); });
                                    (de.otros || []).forEach(v => { if (v) datosArray.push(v); });
                                }

                                // Formatear fechas
                                const recibidoFecha = item.recibido_fecha
                                    ? new Date(item.recibido_fecha * 1000).toLocaleString()
                                    : 'N/A';

                                const transcriptoFecha = item.transcripto_fecha
                                    ? new Date(item.transcripto_fecha * 1000).toLocaleString()
                                    : 'N/A';

                                // Crear elemento de card
                                const card = $(`
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title">Fecha: ${recibidoFecha} - ${item.nombre_archivo}</h5>
                                                    <p class="card-text">${item.resumen || 'Sin resumen'}</p>
                                                    <button class="btn btn-primary view-details" data-index="${index}">
                                                        Mostrar detalles
                                                    </button>
                                                </div>
                                            </div>
                                        `);

                                // Crear modal
                                const modal = $(`
                                            <div class="modal fade" id="modal-${index}" tabindex="-1" role="dialog">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">${item.nombre_archivo}</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ${item.resumen ? `<div class="alert alert-info mb-3"><strong>Resumen:</strong> ${item.resumen}</div>` : ''}
                                                            ${item.tipo_emergencia ? `<p class="mb-3"><span class="badge bg-warning text-dark me-2">Tipo de emergencia</span>${item.tipo_emergencia}</p>` : ''}

                                                            <h6>Transcripción:</h6>
                                                            <div class="border p-2 mb-3" style="max-height: 280px; overflow-y: auto;">
                                                                ${transcripcionHTML}
                                                            </div>

                                                            <h6>Datos extraídos:</h6>
                                                            <ul class="mb-3">
                                                                ${datosArray.length > 0
                                        ? datosArray.map(d => `<li>${d}</li>`).join('')
                                        : '<li>No hay datos extraídos</li>'}
                                                            </ul>

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p><strong>Ruta del archivo:</strong><br> ${item.ruta_archivo || 'N/A'}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Recibido:</strong> ${recibidoFecha}</p>
                                                                    <p><strong>Transcrito:</strong> ${transcriptoFecha}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `);

                                // Agregar al DOM
                                $('#historial-container').append(card);
                                $('body').append(modal); // IMPORTANTE: Añadir modales al body

                                // Configurar evento para abrir modal
                                card.find('.view-details').on('click', function () {
                                    $(`#modal-${index}`).modal('show');
                                });
                            });
                        },
                        error: function (xhr) {
                            console.error('Error cargando historial:', xhr.responseText);
                            alert('Error al cargar el historial');
                        }
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function () {
                const audioInput = document.getElementById('audioFile');
                const fileNameDisplay = document.getElementById('fileName');
                const fileInfo = document.getElementById('fileInfo');
                const audioPreview = document.getElementById('audioPreview');
                const audioPlayer = document.getElementById('audioPlayer');
                const uploadBtn = document.getElementById('uploadBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                const progressContainer = document.getElementById('progressContainer');
                const progressBar = document.getElementById('progressBar');
                const progressPercent = document.getElementById('progressPercent');
                const progressText = document.getElementById('progressText');
                const copyBtn = document.getElementById('copyBtn');
                const saveBtn = document.getElementById('saveBtn');
                const playBtn = document.getElementById('playBtn');
                const stopBtn = document.getElementById('stopBtn');

                let selectedFile = null;
                let uploadInProgress = false;

                // Configurar arrastrar y soltar
                const uploadArea = document.getElementById('uploadArea');

                uploadArea.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', function () {
                    this.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', function (e) {
                    e.preventDefault();
                    this.classList.remove('dragover');

                    if (e.dataTransfer.files.length) {
                        selectedFile = e.dataTransfer.files[0];
                        processSelectedFile();
                    }
                });

                audioInput.addEventListener('change', function (e) {
                    selectedFile = e.target.files[0];
                    processSelectedFile();
                });

                function processSelectedFile() {
                    if (!selectedFile) return;

                    // Validar tipo de archivo
                    const validTypes = ['audio/mpeg', 'audio/wav', 'audio/m4a', 'audio/ogg', 'audio/x-m4a'];
                    if (!validTypes.includes(selectedFile.type)) {
                        showError('Tipo de archivo no soportado. Por favor selecciona un archivo de audio válido.');
                        return;
                    }

                    // Validar tamaño (50MB)
                    if (selectedFile.size > 50 * 1024 * 1024) {
                        showError('El archivo es demasiado grande. El tamaño máximo es 50MB.');
                        return;
                    }

                    fileNameDisplay.textContent = `${selectedFile.name} (${formatFileSize(selectedFile.size)})`;
                    fileInfo.style.display = 'block';

                    // Vista previa del audio
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        audioPlayer.src = e.target.result;
                        audioPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(selectedFile);
                }

                cancelBtn.addEventListener('click', function () {
                    selectedFile = null;
                    fileInfo.style.display = 'none';
                    audioPreview.style.display = 'none';
                    audioInput.value = '';
                    audioPlayer.src = '';
                });

                uploadBtn.addEventListener('click', async function () {
                    if (!selectedFile || uploadInProgress) return;

                    uploadInProgress = true;
                    progressContainer.style.display = 'block';
                    progressBar.style.width = '0%';
                    progressPercent.textContent = '0%';
                    progressText.textContent = 'Generando URL de carga...';

                    try {
                        // 1. GENERAR URL DE CARGA
                        const response = await fetch('/generate-upload-url', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                fileName: selectedFile.name,
                                contentType: selectedFile.type
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Error generando URL de carga: ' + response.status);
                        }

                        const data = await response.json();
                        const { uploadUrl, key } = data;

                        // Extraer el nombre real del archivo del key
                        const fileNameParts = key.split('/');
                        const actualFileName = fileNameParts[fileNameParts.length - 1];
                        console.log('Nombre real del archivo:', actualFileName);

                        // 2. SUBIR A S3
                        progressText.textContent = 'Subiendo archivo...';
                        await uploadToPresignedUrl(uploadUrl, selectedFile);

                        // 3. CONSULTAR RESULTADO
                        progressText.textContent = 'Procesando audio...';
                        await waitForResult(actualFileName);

                    } catch (error) {
                        showError('Ocurrió un error durante la carga o transcripción: ' + error.message);
                        console.error(error);
                    } finally {
                        uploadInProgress = false;
                    }
                });

                async function uploadToPresignedUrl(uploadUrl, file) {
                    return new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open("PUT", uploadUrl, true);
                        xhr.setRequestHeader("Content-Type", file.type);

                        xhr.upload.onprogress = function (e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                progressBar.style.width = percent + "%";
                                progressPercent.textContent = percent + "%";
                            }
                        };

                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                resolve();
                            } else {
                                reject(new Error("Error al subir archivo a S3: " + xhr.statusText));
                            }
                        };

                        xhr.onerror = function () {
                            reject(new Error("Error de red al subir el archivo"));
                        };

                        xhr.send(file);
                    });
                }

                async function waitForResult(actualFileName, retries = 30, delay = 3000) {
                    let attempts = 0;
                    let transcriptionData = null;

                    while (attempts < retries) {
                        try {
                            const res = await fetch(`/get-results-by-filename?fileName=${encodeURIComponent(actualFileName)}`);

                            if (!res.ok) {
                                throw new Error('Error en la solicitud: ' + res.status);
                            }

                            const data = await res.json();
                            console.log('Respuesta parcial:', data);

                            // Verificar si ya tenemos datos de transcripción
                            if (data.transcripto && (data.transcription || data.transcripcion)) {
                                transcriptionData = data;
                                break;
                            }

                            // Verificar si tenemos datos en otros campos
                            if (data.texto_completo || data.datos_extraidos) {
                                transcriptionData = data;
                                break;
                            }

                        } catch (error) {
                            console.error('Error obteniendo resultados:', error);
                        }

                        // Actualizar progreso con tiempo estimado
                        const remainingTime = Math.round((retries - attempts) * delay / 1000);
                        progressText.textContent = `Procesando... Tiempo estimado: ${remainingTime} segundos`;

                        await new Promise(r => setTimeout(r, delay));
                        attempts++;
                    }

                    if (transcriptionData) {
                        showResults(transcriptionData);
                    } else {
                        showError('La transcripción demoró demasiado o falló. Por favor intenta nuevamente.');
                    }
                }

                function showResults(data) {
                    progressText.textContent = '¡Transcripción completa!';

                    // Manejar diferentes formatos de respuesta
                    let transcriptionText = "";

                    // Verificar si tenemos una transcripción estructurada
                    if (data.transcription) {
                        try {
                            // Manejar tanto string como objeto
                            const transcriptionObj = typeof data.transcription === 'string'
                                ? JSON.parse(data.transcription)
                                : data.transcription;

                            if (transcriptionObj && transcriptionObj.dialogos) {
                                // Construir HTML con formato de chat
                                transcriptionText = transcriptionObj.dialogos.map(dialogo => {
                                    const isOperador = dialogo.rol === 'OPERADOR_911';
                                    const alignClass = isOperador ? 'align-left' : 'align-right';
                                    const rolLabel = isOperador ? 'Operador 911' : 'Denunciante';

                                    return `<div class="message ${alignClass}">
                                        <div class="speaker">${rolLabel}</div>
                                        <div class="bubble">
                                            ${dialogo.texto}
                                            <span class="timestamp">[${dialogo.timestamp}]</span>
                                        </div>
                                    </div>`;
                                }).join('');

                                document.getElementById('transcriptionResult').innerHTML = transcriptionText;
                            } else {
                                document.getElementById('transcriptionResult').textContent = JSON.stringify(transcriptionObj, null, 2);
                            }

                        } catch (e) {
                            console.error('Error parsing transcription:', e);
                            document.getElementById('transcriptionResult').textContent = String(data.transcription);
                        }
                    } else {
                        transcriptionText = data.transcripcion || data.texto_completo || "No se pudo obtener la transcripción";
                        document.getElementById('transcriptionResult').textContent = transcriptionText;
                    }

                    // Habilitar botones de copia y guardado
                    copyBtn.disabled = false;
                    saveBtn.disabled = false;

                    // Mostrar datos estructurados
                    let structuredHTML = '';

                    // Primero mostrar el resumen si está disponible
                    if (data.resumen) {
                        structuredHTML += `
                                                                                                                                    <div class="card mb-3">
                                                                                                                                        <div class="card-header bg-info text-white">
                                                                                                                                            <i class="fas fa-file-alt me-2"></i> Resumen del Audio
                                                                                                                                        </div>
                                                                                                                                        <div class="card-body">
                                                                                                                                            <p class="lead">${data.resumen}</p>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                `;
                    }

                    // Mostrar tipo de emergencia si está disponible
                    if (data.tipo_emergencia) {
                        structuredHTML += `
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <i class="fas fa-exclamation-circle me-2"></i> Tipo de Emergencia
                                </div>
                                <div class="card-body">
                                    <p class="lead mb-0">${data.tipo_emergencia}</p>
                                </div>
                            </div>
                        `;
                    }

                    // Luego mostrar datos extraídos si están disponibles
                    if (data.datos_extraidos) {
                        const de = data.datos_extraidos;

                        // Datos del denunciante
                        const hasDenunciante = de.denunciante_nombre || de.denunciante_telefono || de.denunciante_documento;
                        const denuncianteHTML = hasDenunciante ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-user-circle me-2 text-primary"></i> Datos del Denunciante</h6>
                                <ul class="list-group">
                                    ${de.denunciante_nombre ? `<li class="list-group-item"><strong>Nombre:</strong> ${de.denunciante_nombre}</li>` : ''}
                                    ${de.denunciante_telefono ? `<li class="list-group-item"><strong>Teléfono:</strong> ${de.denunciante_telefono}</li>` : ''}
                                    ${de.denunciante_documento ? `<li class="list-group-item"><strong>Documento:</strong> ${de.denunciante_documento}</li>` : ''}
                                </ul>
                            </div>
                        ` : '';

                        const nombresMencionados = de.nombres_mencionados || de.nombres || [];
                        const nombresHTML = nombresMencionados.length > 0 ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-users me-2 text-success"></i> Nombres Mencionados</h6>
                                <ul class="list-group">
                                    ${nombresMencionados.map(name => `<li class="list-group-item">${name}</li>`).join('')}
                                </ul>
                            </div>
                        ` : '';

                        const direccionesHTML = de.direcciones && de.direcciones.length > 0 ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-map-marker-alt me-2 text-danger"></i> Direcciones</h6>
                                <ul class="list-group">
                                    ${de.direcciones.map(addr => `<li class="list-group-item">${addr}</li>`).join('')}
                                </ul>
                            </div>
                        ` : '';

                        const telefonosHTML = de.telefonos && de.telefonos.length > 0 ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-phone me-2 text-info"></i> Teléfonos</h6>
                                <ul class="list-group">
                                    ${de.telefonos.map(phone => `<li class="list-group-item">${phone}</li>`).join('')}
                                </ul>
                            </div>
                        ` : '';

                        const documentosHTML = de.documentos && de.documentos.length > 0 ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-id-card me-2 text-secondary"></i> Documentos</h6>
                                <ul class="list-group">
                                    ${de.documentos.map(doc => `<li class="list-group-item">${doc}</li>`).join('')}
                                </ul>
                            </div>
                        ` : '';

                        const vehiculosHTML = de.vehiculos && de.vehiculos.length > 0 ? `
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-car me-2 text-dark"></i> Vehículos</h6>
                                <ul class="list-group">
                                    ${de.vehiculos.map(v => `<li class="list-group-item">${v}</li>`).join('')}
                                </ul>
                            </div>
                        ` : '';

                        const otrosHTML = de.otros && de.otros.length > 0 ? `
                            <div class="col-12 mb-3">
                                <h6><i class="fas fa-tags me-2 text-warning"></i> Otros datos relevantes</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    ${de.otros.map(other => `<span class="badge bg-light text-dark border py-2 px-3">${other}</span>`).join('')}
                                </div>
                            </div>
                        ` : '';

                        if (denuncianteHTML || nombresHTML || direccionesHTML || telefonosHTML || documentosHTML || vehiculosHTML || otrosHTML) {
                            structuredHTML += `
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-info-circle me-2"></i> Datos Extraídos
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            ${denuncianteHTML}
                                            ${nombresHTML}
                                            ${direccionesHTML}
                                            ${telefonosHTML}
                                            ${documentosHTML}
                                            ${vehiculosHTML}
                                            ${otrosHTML}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    // Mostrar línea de tiempo de eventos si está disponible
                    if (data.eventos && data.eventos.length > 0) {
                        structuredHTML += `
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-stream me-2"></i> Línea de Tiempo
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        ${data.eventos.map(ev => `
                                            <li class="list-group-item d-flex align-items-start gap-3">
                                                <span class="badge bg-secondary text-white mt-1" style="min-width:52px;font-size:0.8em;">${ev.timestamp}</span>
                                                <span>${ev.evento}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>
                        `;
                    }

                    // Si no hay datos estructurados ni resumen
                    if (!structuredHTML) {
                        structuredHTML = `
                                                                                                                                    <div class="alert alert-warning">
                                                                                                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                                                                                                        No se encontraron datos estructurados en la transcripción.
                                                                                                                                    </div>
                                                                                                                                `;
                    }

                    document.getElementById('structuredResults').innerHTML = structuredHTML;

                    // Mostrar detalles del procesamiento
                    showDetailedResults(data);

                    // Activar manualmente la pestaña de resultados estructurados
                    setTimeout(() => {
                        const structuredTab = new bootstrap.Tab(document.getElementById('structured-tab'));
                        structuredTab.show();
                    }, 100);
                }

                function showDetailedResults(data) {
                    // Función para formatear timestamps UNIX
                    const formatUnixTime = (timestamp) => {
                        if (!timestamp) return 'N/A';
                        const date = new Date(timestamp * 1000);
                        return date.toLocaleString();
                    };

                    let html = `
                                                                                                                                <div class="row">
                                                                                                                                    <div class="col-md-4">
                                                                                                                                        <div class="card h-100">
                                                                                                                                            <div class="card-body">
                                                                                                                                                <h5><i class="fas fa-file-audio me-2"></i> Información del Archivo</h5>
                                                                                                                                                <div class="mt-3">
                                                                                                                                                    <p><strong>Nombre:</strong> ${data.nombre_archivo || "N/A"}</p>
                                                                                                                                                    <p><strong>Ruta:</strong> ${data.ruta_archivo || "N/A"}</p>
                                                                                                                                                    <p><strong>Recibido:</strong> ${data.recibido ? 'Sí' : 'No'}</p>
                                                                                                                                                </div>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                    <div class="col-md-4">
                                                                                                                                        <div class="card h-100">
                                                                                                                                            <div class="card-body">
                                                                                                                                                <h5><i class="fas fa-tachometer-alt me-2"></i> Estado del Procesamiento</h5>
                                                                                                                                                <div class="mt-3">
                                                                                                                                                    <ul class="list-group list-group-flush">
                                                                                                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                            Transcripción
                                                                                                                                                            <span class="badge bg-${data.transcripto ? 'success' : 'danger'}">
                                                                                                                                                                ${data.transcripto ? 'Completa' : 'Pendiente'}
                                                                                                                                                            </span>
                                                                                                                                                        </li>
                                                                                                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                            Procesamiento IA
                                                                                                                                                            <span class="badge bg-${data.procesamiento_ia ? 'success' : 'danger'}">
                                                                                                                                                                ${data.procesamiento_ia ? 'Completo' : 'Pendiente'}
                                                                                                                                                            </span>
                                                                                                                                                        </li>
                                                                                                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                            Reporte Generado
                                                                                                                                                            <span class="badge bg-${data.reporte_generado ? 'success' : 'danger'}">
                                                                                                                                                                ${data.reporte_generado ? 'Sí' : 'No'}
                                                                                                                                                            </span>
                                                                                                                                                        </li>
                                                                                                                                                    </ul>
                                                                                                                                                </div>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                    <div class="col-md-4">
                                                                                                                                        <div class="card h-100">
                                                                                                                                            <div class="card-body">
                                                                                                                                                <h5><i class="fas fa-history me-2"></i> Historial</h5>
                                                                                                                                                <ul class="list-group list-group-flush mt-3">
                                                                                                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                        <span>Recibido</span>
                                                                                                                                                        <small>${formatUnixTime(data.recibido_fecha)}</small>
                                                                                                                                                    </li>
                                                                                                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                        <span>Transcripción</span>
                                                                                                                                                        <small>${formatUnixTime(data.transcripto_fecha)}</small>
                                                                                                                                                    </li>
                                                                                                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                                                        <span>Procesamiento IA</span>
                                                                                                                                                        <small>${formatUnixTime(data.procesamiento_ia_fecha)}</small>
                                                                                                                                                    </li>
                                                                                                                                                </ul>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            `;

                    document.getElementById('detailedResults').innerHTML = html;
                }

                function showError(msg) {
                    document.getElementById('errorMessage').textContent = msg;
                    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
                    modal.show();

                    // Resetear progreso
                    progressContainer.style.display = 'none';
                    progressBar.style.width = '0%';
                    progressPercent.textContent = '0%';
                }

                // Copiar texto
                copyBtn.addEventListener('click', () => {
                    // Obtener texto sin formato
                    const text = document.getElementById('transcriptionResult').innerText;

                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="fas fa-check me-2"></i> ¡Copiado!';
                        setTimeout(() => {
                            copyBtn.innerHTML = originalText;
                        }, 2000);
                    });
                });

                // Guardar como archivo
                saveBtn.addEventListener('click', () => {
                    // Obtener texto sin formato
                    const text = document.getElementById('transcriptionResult').innerText;

                    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'transcripcion.txt';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });

                // Controles de audio
                playBtn.addEventListener('click', () => {
                    audioPlayer.play();
                });

                stopBtn.addEventListener('click', () => {
                    audioPlayer.pause();
                    audioPlayer.currentTime = 0;
                });

                // Formatear tamaño de archivo
                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
                }

                // Inicializar tabs de Bootstrap
                const tabEls = document.querySelectorAll('#resultsTab button[data-bs-toggle="tab"]');
                tabEls.forEach(tabEl => {
                    tabEl.addEventListener('click', function (event) {
                        event.preventDefault();
                        const target = this.getAttribute('data-bs-target');
                        const tabPane = document.querySelector(target);
                        if (tabPane) {
                            // Ocultar todos los paneles
                            document.querySelectorAll('.tab-pane').forEach(pane => {
                                pane.classList.remove('show', 'active');
                            });

                            // Mostrar el panel seleccionado
                            tabPane.classList.add('show', 'active');

                            // Actualizar tabs activos
                            tabEls.forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                        }
                    });
                });
            });
        </script>

        <style>
            :root {
                --operador-color: #d4edda;
                --operador-header: #28a745;
                --operador-border: #28a745;
                --denunciante-color: #cce5ff;
                --denunciante-header: #007bff;
                --denunciante-border: #007bff;
                --timestamp-color: #6c757d;
            }

            /* Estilos para modales */
            /* Solución definitiva para modales que cubren toda la pantalla */
            .modal {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1050;
                display: none;
                width: 100%;
                height: 100%;
                overflow: hidden;
                outline: 0;
            }

            .modal-dialog {
                position: relative;
                max-width: 800px;
                margin: 1.75rem auto;
                pointer-events: none;
            }

            .modal-content {
                position: relative;
                display: flex;
                flex-direction: column;
                width: 100%;
                pointer-events: auto;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid rgba(0, 0, 0, .2);
                border-radius: 0.3rem;
                outline: 0;
            }

            .modal-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1040;
                width: 100vw;
                height: 100vh;
                background-color: #000;
            }

            .modal-backdrop.fade {
                opacity: 0;
            }

            .modal-backdrop.show {
                opacity: 0.5;
            }

            .modal-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                padding: 1rem;
                border-bottom: 1px solid #dee2e6;
            }

            .modal-body {
                position: relative;
                flex: 1 1 auto;
                padding: 1rem;
            }

            .close {
                float: right;
                font-size: 1.5rem;
                font-weight: 700;
                line-height: 1;
                color: #000;
                text-shadow: 0 1px 0 #fff;
                opacity: .5;
            }

            /* Contenedor principal compacto */
            #transcriptionResult {
                display: flex;
                flex-direction: column;
                gap: 8px;
                /* Espacio vertical reducido */
                margin-top: 20px;
            }

            /* Mensaje individual compacto */
            .message {
                display: flex;
                flex-direction: column;
                max-width: 85%;
            }

            /* Alineación izquierda (Agente) */
            .align-left {
                align-items: flex-start;
                text-align: left;
                margin-right: auto;
            }

            /* Alineación derecha (Denunciante) */
            .align-right {
                align-items: flex-end;
                text-align: right;
                margin-left: auto;
            }

            /* Burbujas compactas */
            .bubble {
                padding: 8px 12px;
                /* Padding reducido */
                border-radius: 14px;
                position: relative;
                word-wrap: break-word;
                line-height: 1.4;
            }

            /* Burbuja operador - izquierda */
            .align-left .bubble {
                background: var(--operador-color);
                border-bottom-left-radius: 4px;
                border-left: 3px solid var(--operador-border);
            }

            /* Burbuja denunciante - derecha */
            .align-right .bubble {
                background: var(--denunciante-color);
                border-bottom-right-radius: 4px;
                border-right: 3px solid var(--denunciante-border);
            }

            /* Speaker más compacto */
            .speaker {
                font-weight: bold;
                font-size: 0.8em;
                margin-bottom: 3px;
                padding: 1px 6px;
                border-radius: 4px;
            }

            .align-left .speaker {
                color: var(--operador-header);
                background: rgba(45,106,45,0.1);
            }

            .align-right .speaker {
                color: var(--denunciante-header);
                background: rgba(26,74,138,0.1);
            }

            /* Timestamp debajo de la burbuja */
            .timestamp {
                display: block;
                font-size: 0.65em;
                color: var(--timestamp-color);
                margin-top: 3px;
                opacity: 0.85;
            }

            .gradient-bg {
                background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                color: white;
                border: none;
            }

            .upload-area {
                border: 2px dashed #0d6efd;
                border-radius: 10px;
                padding: 30px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
                background-color: #f8f9fa;
            }

            .upload-area:hover,
            .upload-area.dragover {
                background-color: #e9f0ff;
                border-color: #0b5ed7;
            }

            .file-info {
                background-color: #e9f7ef;
                border-radius: 8px;
                padding: 15px;
                margin-top: 15px;
            }

            .audio-preview {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
            }

            .progress-container {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
            }

            .card-header {
                font-weight: bold;
            }

            .nav-tabs .nav-link {
                cursor: pointer;
            }

            .nav-tabs .nav-link.active {
                background-color: #e9ecef;
                border-bottom: 3px solid #0d6efd;
            }
        </style>
    </section>
@endsection
