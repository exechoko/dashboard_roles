@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Transcripción de Audio a Texto</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Subir Archivo de Audio</h4>
                                        </div>
                                        <div class="card-body">
                                            <div id="drop-area" class="dropzone p-4 border-dashed rounded text-center"
                                                style="border: 2px dashed #6777ef; cursor: pointer;">
                                                <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                                                <h5>Arrastra y suelta tu archivo de audio aquí</h5>
                                                <p class="text-muted">o</p>
                                                <button id="browse-btn" class="btn btn-primary">
                                                    <i class="fas fa-folder-open mr-2"></i>Seleccionar archivo
                                                </button>
                                                <input type="file" id="audio-file" accept=".mp3,.wav,.m4a,.ogg" hidden>
                                                <div class="mt-3">
                                                    <small class="text-muted">
                                                        Formatos soportados: MP3, WAV, M4A, OGG (Máx. 10MB)
                                                    </small>
                                                </div>
                                            </div>

                                            <div id="audio-preview" class="mt-3 d-none">
                                                <p><strong>Archivo cargado:</strong> <span id="file-name"></span></p>
                                                <audio id="audio-player" controls
                                                    style="width: 100%; max-width: 400px;"></audio>
                                                <div class="mt-2">
                                                    <button id="play-btn" class="btn btn-sm btn-success mr-2">
                                                        <i class="fas fa-play"></i> Reproducir
                                                    </button>
                                                    <button id="stop-btn" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-stop"></i> Detener
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="progress-container" class="mt-4 d-none">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>Progreso de transcripción:</span>
                                                    <span id="progress-percent">0%</span>
                                                </div>
                                                <div class="progress">
                                                    <div id="progress-bar"
                                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                                        role="progressbar" style="width: 0%; background-color: #6777ef;">
                                                    </div>
                                                </div>
                                                <div id="status-message" class="text-center mt-2"></div>
                                            </div>

                                            <div class="mt-4">
                                                <button id="transcribe-btn" class="btn btn-success btn-block" disabled
                                                    style="background: linear-gradient(45deg,#6777ef, #35199a); border: none;">
                                                    <i class="fas fa-microphone-alt mr-2"></i>Transcribir audio
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Resultado de la transcripción</h4>
                                        </div>
                                        <div class="card-body">
                                            <textarea id="transcription-result" class="form-control" rows="15"
                                                placeholder="El texto transcrito aparecerá aquí..." readonly></textarea>
                                            <button id="copy-btn" class="btn btn-outline-primary btn-block mt-3" disabled>
                                                <i class="fas fa-copy mr-2"></i>Copiar texto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript">
        $(document).ready(function () {
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('audio-file');
            const browseBtn = document.getElementById('browse-btn');
            const transcribeBtn = document.getElementById('transcribe-btn');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressPercent = document.getElementById('progress-percent');
            const statusMessage = document.getElementById('status-message');
            const transcriptionResult = document.getElementById('transcription-result');
            const copyBtn = document.getElementById('copy-btn');
            const audioPreview = document.getElementById('audio-preview');
            const fileNameLabel = document.getElementById('file-name');
            const audioPlayer = document.getElementById('audio-player');
            const playBtn = document.getElementById('play-btn');
            const stopBtn = document.getElementById('stop-btn');

            let selectedFile = null;

            function resetUI() {
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                statusMessage.textContent = '';
                transcriptionResult.value = '';
                progressContainer.classList.add('d-none');
            }

            function validateFile(file) {
                const allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/x-m4a', 'audio/ogg'];
                const maxSize = 10 * 1024 * 1024; // 10MB

                if (!allowedTypes.includes(file.type)) {
                    showError("Formato no soportado. Suba un archivo MP3, WAV, M4A u OGG.");
                    return false;
                }

                if (file.size > maxSize) {
                    showError("El archivo supera los 10MB permitidos.");
                    return false;
                }

                return true;
            }

            function showError(message) {
                statusMessage.textContent = message;
                statusMessage.classList.remove('text-success');
                statusMessage.classList.add('text-danger');
                transcribeBtn.disabled = true;
            }

            function clearError() {
                statusMessage.textContent = '';
                statusMessage.classList.remove('text-danger');
            }

            browseBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function () {
                resetUI();
                if (this.files.length > 0) {
                    const file = this.files[0];
                    if (validateFile(file)) {
                        selectedFile = file;
                        clearError();
                        transcribeBtn.disabled = false;
                        statusMessage.textContent = `Archivo seleccionado: ${file.name}`;
                        statusMessage.classList.remove('text-danger');
                        statusMessage.classList.add('text-success');
                        fileNameLabel.textContent = file.name;
                        audioPlayer.src = URL.createObjectURL(file);
                        audioPreview.classList.remove('d-none');
                    } else {
                        audioPreview.classList.add('d-none');
                        audioPlayer.src = '';
                        selectedFile = null;
                        this.value = ''; // Reset input
                    }
                }
            });

            playBtn.addEventListener('click', () => {
                if (audioPlayer.src) audioPlayer.play();
            });

            stopBtn.addEventListener('click', () => {
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
            });

            // Drag & Drop
            dropArea.addEventListener('dragover', e => {
                e.preventDefault();
                dropArea.classList.add('bg-light');
            });

            dropArea.addEventListener('dragleave', () => {
                dropArea.classList.remove('bg-light');
            });

            dropArea.addEventListener('drop', e => {
                e.preventDefault();
                dropArea.classList.remove('bg-light');
                resetUI();

                if (e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    if (validateFile(file)) {
                        selectedFile = file;
                        fileInput.files = e.dataTransfer.files;
                        clearError();
                        transcribeBtn.disabled = false;
                        statusMessage.textContent = `Archivo seleccionado: ${file.name}`;
                        statusMessage.classList.remove('text-danger');
                        statusMessage.classList.add('text-success');
                        fileNameLabel.textContent = file.name;
                        audioPlayer.src = URL.createObjectURL(file);
                        audioPreview.classList.remove('d-none');
                    } else {
                        selectedFile = null;
                        audioPreview.classList.add('d-none');
                        audioPlayer.src = '';
                    }
                }
            });

            /*transcribeBtn.addEventListener('click', function () {
                if (!selectedFile) {
                    showError("Debe seleccionar un archivo de audio válido.");
                    return;
                }

                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                statusMessage.textContent = "Iniciando transcripción...";

                // Simulación de progreso
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 10;
                    if (progress <= 100) {
                        progressBar.style.width = `${progress}%`;
                        progressPercent.textContent = `${progress}%`;
                    }
                    if (progress === 100) {
                        clearInterval(interval);
                        transcriptionResult.value = "Texto transcrito simulado desde el archivo de audio.";
                        statusMessage.textContent = "Transcripción finalizada.";
                        copyBtn.disabled = false;
                    }
                }, 300);
            });*/

            transcribeBtn.addEventListener('click', function () {
                if (!selectedFile) {
                    showError("Debe seleccionar un archivo de audio válido.");
                    return;
                }

                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                statusMessage.textContent = "Enviando archivo para transcripción...";

                let formData = new FormData();
                formData.append('audio', selectedFile);

                // Progreso falso visual
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    if (progress <= 95) {
                        progressBar.style.width = `${progress}%`;
                        progressPercent.textContent = `${progress}%`;
                    }
                }, 500);

                fetch('/transcribir', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        progressPercent.textContent = '100%';

                        if (data.success) {
                            transcriptionResult.value = data.text;
                            statusMessage.textContent = "Transcripción completada.";
                            copyBtn.disabled = false;
                        } else {
                            transcriptionResult.value = '';
                            statusMessage.textContent = "Error en la transcripción: " + (data.message || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        clearInterval(interval);
                        transcriptionResult.value = '';
                        statusMessage.textContent = "Error al conectar con el servidor.";
                    });
            });


            copyBtn.addEventListener('click', () => {
                transcriptionResult.select();
                document.execCommand('copy');
                copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Texto copiado';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="fas fa-copy mr-2"></i>Copiar texto';
                }, 2000);
            });
        });
    </script>
@endsection
