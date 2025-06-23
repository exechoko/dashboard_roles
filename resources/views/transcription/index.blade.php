@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <!-- Cabecera de la aplicación -->
                    <div class="card gradient-bg mb-4">
                        <div class="card-body text-center py-4">
                            <h1 class="display-5 fw-bold"><i class="fas fa-microphone-alt me-3"></i>Transcripción de Audio
                            </h1>
                            <p class="lead mb-0">Convierte tus archivos de audio en texto con precisión</p>
                        </div>
                    </div>

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
                                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Resultados de Transcripción</h4>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="resultsTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="transcription-tab" data-bs-toggle="tab"
                                                data-bs-target="#transcription" type="button" role="tab">
                                                <i class="fas fa-align-left me-1"></i> Texto Completo
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="structured-tab" data-bs-toggle="tab"
                                                data-bs-target="#structured" type="button" role="tab">
                                                <i class="fas fa-cube me-1"></i> Resultados Estructurados
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="tab-content mt-3" id="resultsTabContent">
                                        <div class="tab-pane fade show active" id="transcription" role="tabpanel">
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
                                        <div class="tab-pane fade" id="structured" role="tabpanel">
                                            <div id="structuredResults" class="mt-2">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Los resultados estructurados se mostrarán aquí después de la
                                                    transcripción.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                            if (data.transcripto && data.transcripcion) {
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
                            const transcriptionObj = JSON.parse(data.transcription);

                            // Construir texto de transcripción a partir de los diálogos
                            transcriptionText = transcriptionObj.dialogos.map(dialogo => {
                                const roleClass = dialogo.rol === 'AGENTE_911' ? 'role-agente' :
                                    dialogo.rol === 'DENUNCIANTE' ? 'role-denunciante' : '';
                                return `<span class="timestamp">[${dialogo.timestamp}]</span>
                                        <span class="${roleClass}">${dialogo.rol}:</span>
                                        ${dialogo.texto}`;
                            }).join('\n\n');
                        } catch (e) {
                            console.error('Error parsing transcription JSON:', e);
                            transcriptionText = data.transcription;
                        }
                        // Usar un div en lugar de textarea para mostrar texto formateado
                        const transcriptionResult = document.getElementById('transcriptionResult');
                        transcriptionResult.innerHTML = transcriptionText;
                        transcriptionResult.classList.add('transcription-text');
                    } else {
                        transcriptionText = data.transcripcion || data.texto_completo || "No se pudo obtener la transcripción";
                    }

                    document.getElementById('transcriptionResult').value = transcriptionText;

                    // Habilitar botones de copia y guardado
                    copyBtn.disabled = false;
                    saveBtn.disabled = false;

                    // Mostrar datos estructurados
                    let structuredHTML = '';

                    if (data.datos_extraidos) {
                        const de = data.datos_extraidos;

                        structuredHTML = `
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <i class="fas fa-info-circle me-2"></i> Datos Extraídos
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    ${de.nombres && de.nombres.length > 0 ? `
                                                        <div class="col-md-6">
                                                            <h6><i class="fas fa-user me-2"></i> Nombres</h6>
                                                            <ul class="list-group">
                                                                ${de.nombres.map(name => `<li class="list-group-item">${name}</li>`).join('')}
                                                            </ul>
                                                        </div>
                                                    ` : ''}

                                                    ${de.direcciones && de.direcciones.length > 0 ? `
                                                        <div class="col-md-6">
                                                            <h6><i class="fas fa-map-marker-alt me-2"></i> Direcciones</h6>
                                                            <ul class="list-group">
                                                                ${de.direcciones.map(addr => `<li class="list-group-item">${addr}</li>`).join('')}
                                                            </ul>
                                                        </div>
                                                    ` : ''}

                                                    ${de.telefonos && de.telefonos.length > 0 ? `
                                                        <div class="col-md-6 mt-3">
                                                            <h6><i class="fas fa-phone me-2"></i> Teléfonos</h6>
                                                            <ul class="list-group">
                                                                ${de.telefonos.map(phone => `<li class="list-group-item">${phone}</li>`).join('')}
                                                            </ul>
                                                        </div>
                                                    ` : ''}

                                                    ${de.documentos && de.documentos.length > 0 ? `
                                                        <div class="col-md-6 mt-3">
                                                            <h6><i class="fas fa-id-card me-2"></i> Documentos</h6>
                                                            <ul class="list-group">
                                                                ${de.documentos.map(doc => `<li class="list-group-item">${doc}</li>`).join('')}
                                                            </ul>
                                                        </div>
                                                    ` : ''}
                                                </div>

                                                ${de.otros && de.otros.length > 0 ? `
                                                    <div class="mt-3">
                                                        <h6><i class="fas fa-tags me-2"></i> Otros datos relevantes</h6>
                                                        <ul class="list-group">
                                                            ${de.otros.map(other => `<li class="list-group-item">${other}</li>`).join('')}
                                                        </ul>
                                                    </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                    }

                    if (data.resumen) {
                        structuredHTML += `
                                        <div class="card mt-3">
                                            <div class="card-header bg-info text-white">
                                                <i class="fas fa-file-alt me-2"></i> Resumen
                                            </div>
                                            <div class="card-body">
                                                <p>${data.resumen}</p>
                                            </div>
                                        </div>
                                    `;
                    }

                    document.getElementById('structuredResults').innerHTML = structuredHTML || `
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No se encontraron datos estructurados en la transcripción.
                                    </div>
                                `;

                    // Mostrar detalles del procesamiento
                    showDetailedResults(data);
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
                                                <div class="card-body text-center">
                                                    <i class="fas fa-file-audio fa-3x text-primary mb-3"></i>
                                                    <h5>${data.nombre_archivo || "Archivo de audio"}</h5>
                                                    <p class="text-muted">${data.ruta_archivo || "Sin ruta especificada"}</p>
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
                                                                Recibido
                                                                <span class="badge bg-${data.recibido ? 'success' : 'danger'}">
                                                                    ${data.recibido ? 'Sí' : 'No'}
                                                                </span>
                                                            </li>
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
            });
        </script>

        <style>
            .transcription-text {
                font-family: 'Courier New', monospace;
                line-height: 1.6;
                white-space: pre-wrap;
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }

            .timestamp {
                color: #6c757d;
                font-weight: bold;
            }

            .role-agente {
                color: #0d6efd;
                font-weight: bold;
            }

            .role-denunciante {
                color: #dc3545;
                font-weight: bold;
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

            .transcription-text {
                min-height: 200px;
                resize: vertical;
                font-family: monospace;
            }

            .structured-results {
                max-height: 300px;
                overflow-y: auto;
            }

            .progress-container {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
            }
        </style>
    </section>
@endsection
