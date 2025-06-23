<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcripción de Audio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #6777ef;
            --primary-dark: #35199a;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .section {
            padding: 20px 0;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 600;
        }

        .upload-area {
            border: 2px dashed var(--primary);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .upload-area:hover {
            border-color: var(--primary-dark);
            background-color: #e9ecef;
        }

        .upload-area.dragover {
            border-color: var(--success);
            background-color: #d4edda;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }

        .result-card {
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-badge {
            font-size: 0.8em;
        }

        .data-section {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .file-info {
            display: none;
            margin-top: 15px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            border: none;
        }

        .btn-success {
            background: linear-gradient(45deg, var(--success), #1e7e34);
            border: none;
        }

        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            padding: 20px 0;
        }

        .nav-tabs .nav-link {
            color: var(--secondary);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            font-weight: 600;
            border-bottom: 3px solid var(--primary);
        }

        .audio-preview {
            display: none;
            margin-top: 15px;
        }

        .audio-player {
            width: 100%;
            margin: 10px 0;
        }

        .playback-controls {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .transcription-text {
            min-height: 300px;
            font-size: 15px;
            line-height: 1.6;
        }

        .gradient-bg {
            background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
            color: white;
        }
    </style>
</head>
<body>
    <section class="section">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card gradient-bg mb-4">
                        <div class="card-body text-center py-4">
                            <h1 class="display-5 fw-bold"><i class="fas fa-microphone-alt me-3"></i>Transcripción de Audio</h1>
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
                                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('audioFile').click()">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h5>Arrastra tu archivo de audio aquí o haz clic para seleccionar</h5>
                                        <p class="text-muted">Formatos soportados: MP3, WAV, M4A, OGG (máximo 50MB)</p>
                                        <input type="file" id="audioFile" class="d-none" accept="audio/*">
                                    </div>

                                    <!-- Información del archivo seleccionado -->
                                    <div class="file-info" id="fileInfo">
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
                                    <div class="audio-preview mt-3" id="audioPreview">
                                        <h6><i class="fas fa-music me-2"></i> Vista Previa</h6>
                                        <audio id="audioPlayer" class="audio-player" controls></audio>
                                        <div class="playback-controls">
                                            <button class="btn btn-sm btn-success" id="playBtn">
                                                <i class="fas fa-play me-1"></i> Reproducir
                                            </button>
                                            <button class="btn btn-sm btn-danger" id="stopBtn">
                                                <i class="fas fa-stop me-1"></i> Detener
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div class="progress-container" id="progressContainer">
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
                                            <textarea id="transcriptionResult" class="form-control transcription-text"
                                                      placeholder="El texto transcrito aparecerá aquí..." readonly></textarea>
                                            <div class="mt-3 d-flex justify-content-between">
                                                <button id="copyBtn" class="btn btn-outline-primary">
                                                    <i class="fas fa-copy me-2"></i> Copiar Texto
                                                </button>
                                                <button id="saveBtn" class="btn btn-outline-success">
                                                    <i class="fas fa-save me-2"></i> Guardar como TXT
                                                </button>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="structured" role="tabpanel">
                                            <div id="structuredResults" class="mt-2">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Los resultados estructurados se mostrarán aquí después de la transcripción.
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
                            <div id="detailedResults"></div>
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
    </section>

    <script>
        class AudioTranscription {
            constructor() {
                this.currentFile = null;
                this.currentUploadData = null;
                this.initializeEventListeners();
                this.setupDragAndDrop();
                this.csrfToken = "{{ csrf_token() }}"; // Simulando token CSRF
            }

            initializeEventListeners() {
                document.getElementById('audioFile').addEventListener('change', this.handleFileSelect.bind(this));
                document.getElementById('uploadBtn').addEventListener('click', this.handleUpload.bind(this));
                document.getElementById('cancelBtn').addEventListener('click', this.handleCancel.bind(this));
                document.getElementById('playBtn').addEventListener('click', this.playAudio.bind(this));
                document.getElementById('stopBtn').addEventListener('click', this.stopAudio.bind(this));
                document.getElementById('copyBtn').addEventListener('click', this.copyTranscription.bind(this));
                document.getElementById('saveBtn').addEventListener('click', this.saveTranscription.bind(this));
            }

            setupDragAndDrop() {
                const uploadArea = document.getElementById('uploadArea');

                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        this.processFile(files[0]);
                    }
                });
            }

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.processFile(file);
                }
            }

            processFile(file) {
                // Validar tipo de archivo
                const validTypes = ['audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/ogg'];
                if (!validTypes.includes(file.type)) {
                    this.showError('Tipo de archivo no soportado. Por favor selecciona un archivo de audio válido.');
                    return;
                }

                // Validar tamaño (50MB)
                if (file.size > 50 * 1024 * 1024) {
                    this.showError('El archivo es demasiado grande. El tamaño máximo es 50MB.');
                    return;
                }

                this.currentFile = file;
                document.getElementById('fileName').textContent = `${file.name} (${this.formatFileSize(file.size)})`;
                document.getElementById('fileInfo').style.display = 'block';

                // Mostrar vista previa del audio
                const audioPlayer = document.getElementById('audioPlayer');
                audioPlayer.src = URL.createObjectURL(file);
                document.getElementById('audioPreview').style.display = 'block';
            }

            async handleUpload() {
                if (!this.currentFile) return;

                try {
                    this.showProgress('Preparando archivo...', 10);

                    // Simulamos un retraso para mostrar la interfaz de progreso
                    await new Promise(resolve => setTimeout(resolve, 800));

                    this.showProgress('Subiendo archivo...', 30);

                    // Simulación de subida de archivo
                    await this.simulateProgress(30, 80, 1000);

                    this.showProgress('Transcribiendo audio...', 80);

                    // Simulación de transcripción
                    await this.simulateProgress(80, 100, 2000);

                    this.showProgress('Procesando resultados...', 100);

                    // Mostrar resultados simulados
                    setTimeout(() => {
                        this.hideProgress();
                        this.showSuccess('Transcripción completada con éxito!');
                        this.displayResults(this.generateSampleResults());
                    }, 1000);

                } catch (error) {
                    this.hideProgress();
                    this.showError(error.message);
                }
            }

            async simulateProgress(start, end, duration) {
                return new Promise(resolve => {
                    const steps = 20;
                    const increment = (end - start) / steps;
                    const stepTime = duration / steps;

                    let current = start;
                    const interval = setInterval(() => {
                        current += increment;
                        this.showProgress('Procesando...', Math.min(current, end));

                        if (current >= end) {
                            clearInterval(interval);
                            resolve();
                        }
                    }, stepTime);
                });
            }

            displayResults(data) {
                // Mostrar texto completo
                document.getElementById('transcriptionResult').value = data.transcripcion || data.texto_completo;

                // Mostrar resultados estructurados
                const structuredResults = document.getElementById('structuredResults');
                structuredResults.innerHTML = this.createStructuredResults(data);

                // Mostrar detalles del procesamiento
                const detailedResults = document.getElementById('detailedResults');
                detailedResults.innerHTML = this.createDetailedResults(data);

                // Habilitar botones de copia y guardado
                document.getElementById('copyBtn').disabled = false;
                document.getElementById('saveBtn').disabled = false;
            }

            createStructuredResults(data) {
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-file-alt me-2"></i> Resumen
                                </div>
                                <div class="card-body">
                                    <p>${data.resumen || "Resumen no disponible"}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-chart-pie me-2"></i> Estadísticas
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Duración del audio
                                            <span class="badge bg-primary rounded-pill">${data.duracion || "3:45"}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Palabras transcritas
                                            <span class="badge bg-primary rounded-pill">${data.palabras || "1,248"}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Confianza
                                            <span class="badge bg-success rounded-pill">${data.confianza || "92.5%"}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (data.datos_extraidos) {
                    html += `
                        <div class="card mt-3">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-database me-2"></i> Datos Extraídos
                            </div>
                            <div class="card-body">
                                ${this.createExtractedDataSection(data.datos_extraidos)}
                            </div>
                        </div>
                    `;
                }

                return html;
            }

            createExtractedDataSection(data) {
                let html = '<div class="row">';

                if (data.nombres && data.nombres.length > 0) {
                    html += `
                        <div class="col-md-6">
                            <h6><i class="fas fa-user me-2"></i> Nombres</h6>
                            <ul class="list-group">
                                ${data.nombres.map(name => `<li class="list-group-item">${name}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                if (data.direcciones && data.direcciones.length > 0) {
                    html += `
                        <div class="col-md-6">
                            <h6><i class="fas fa-map-marker-alt me-2"></i> Direcciones</h6>
                            <ul class="list-group">
                                ${data.direcciones.map(addr => `<li class="list-group-item">${addr}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                if (data.telefonos && data.telefonos.length > 0) {
                    html += `
                        <div class="col-md-6 mt-3">
                            <h6><i class="fas fa-phone me-2"></i> Teléfonos</h6>
                            <ul class="list-group">
                                ${data.telefonos.map(phone => `<li class="list-group-item">${phone}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                html += '</div>';
                return html;
            }

            createDetailedResults(data) {
                return `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-audio fa-3x text-primary mb-3"></i>
                                    <h5>${data.nombre_archivo || "audio_sample.mp3"}</h5>
                                    <p class="text-muted">${this.formatFileSize(data.tamano || 1256789)}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5><i class="fas fa-tachometer-alt me-2"></i> Rendimiento</h5>
                                    <div class="mt-3">
                                        <h6>Velocidad de procesamiento</h6>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                                        </div>

                                        <h6>Precisión</h6>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 92%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5><i class="fas fa-history me-2"></i> Historial</h5>
                                    <ul class="list-group list-group-flush mt-3">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Inicio de proceso</span>
                                            <small>${new Date().toLocaleTimeString()}</small>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Transcripción completada</span>
                                            <small>${new Date(Date.now() + 5000).toLocaleTimeString()}</small>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Análisis IA</span>
                                            <small>${new Date(Date.now() + 8000).toLocaleTimeString()}</small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            generateSampleResults() {
                return {
                    nombre_archivo: "audio_muestra.mp3",
                    duracion: "4:22",
                    palabras: 1287,
                    confianza: "94.2%",
                    resumen: "Esta es una conversación entre Juan Pérez y María García sobre la planificación de un evento corporativo que se llevará a cabo el próximo mes. Discuten los detalles del lugar, el presupuesto y los invitados clave.",
                    transcripcion: "Hola María, ¿cómo estás? He estado revisando los detalles para el evento del próximo mes. Creo que debemos confirmar el lugar lo antes posible. El hotel Grand Palace tiene disponibilidad para esa fecha, pero necesitamos confirmar antes del viernes. El costo sería de aproximadamente $5,000 dólares por todo el día, incluyendo el salón principal y dos salas más pequeñas para talleres. También debemos considerar el catering, que sería adicional. ¿Qué opinas?",
                    datos_extraidos: {
                        nombres: ["Juan Pérez", "María García"],
                        telefonos: ["+1 555-1234", "+1 555-5678"],
                        direcciones: ["Hotel Grand Palace, 123 Calle Principal"],
                        fechas: ["Próximo mes", "Viernes"]
                    }
                };
            }

            playAudio() {
                const audioPlayer = document.getElementById('audioPlayer');
                audioPlayer.play();
            }

            stopAudio() {
                const audioPlayer = document.getElementById('audioPlayer');
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
            }

            copyTranscription() {
                const textarea = document.getElementById('transcriptionResult');
                textarea.select();
                document.execCommand('copy');

                // Mostrar feedback visual
                const originalText = this.copyBtn.innerHTML;
                this.copyBtn.innerHTML = '<i class="fas fa-check me-2"></i> Texto copiado!';
                setTimeout(() => {
                    this.copyBtn.innerHTML = originalText;
                }, 2000);
            }

            saveTranscription() {
                const text = document.getElementById('transcriptionResult').value;
                const blob = new Blob([text], {type: 'text/plain'});
                const url = URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.href = url;
                a.download = 'transcripcion.txt';
                document.body.appendChild(a);
                a.click();

                // Limpieza
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }, 100);
            }

            showProgress(text, percent) {
                document.getElementById('progressText').textContent = text;
                document.getElementById('progressPercent').textContent = percent + '%';
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressContainer').style.display = 'block';
            }

            hideProgress() {
                document.getElementById('progressContainer').style.display = 'none';
            }

            showError(message) {
                document.getElementById('errorMessage').textContent = message;
                new bootstrap.Modal(document.getElementById('errorModal')).show();
            }

            showSuccess(message) {
                // Crear una alerta de éxito temporal
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.card-body').appendChild(alert);

                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
            }

            handleCancel() {
                this.resetForm();
            }

            resetForm() {
                this.currentFile = null;
                document.getElementById('audioFile').value = '';
                document.getElementById('fileInfo').style.display = 'none';
                document.getElementById('audioPreview').style.display = 'none';
                this.hideProgress();
            }

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        }

        // Inicializar la aplicación cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            window.audioTranscriber = new AudioTranscription();
        });
    </script>
</body>
</html>
