@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading"><i class="fas fa-spinner mr-2"></i>Procesando Archivos</h3>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                    <h4>Subiendo y procesando archivos...</h4>
                    <p class="text-muted">Por favor, no cierres esta ventana hasta que el proceso haya finalizado.</p>
                </div>

                <div id="jobs-container">
                    @foreach($jobs as $index => $job)
                        <div class="job-item mb-3" data-job-id="{{ $job['job_id'] }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $job['nombre_archivo'] }}</strong>
                                <span class="badge badge-secondary job-status">Pendiente</span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated job-progress" 
                                     role="progressbar" 
                                     style="width: 0%" 
                                     aria-valuenow="0" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    0%
                                </div>
                            </div>
                            <small class="text-muted job-message mt-1">Esperando procesamiento...</small>
                        </div>
                    @endforeach
                </div>

                <div id="acciones-finales" class="text-center mt-4" style="display: none;">
                    <hr>
                    <div id="resumen-final" class="mb-3"></div>
                    <a href="{{ route('descargas.admin.archivos') }}" class="btn btn-primary">
                        <i class="fas fa-list"></i> Ver Lista de Archivos
                    </a>
                    <a href="{{ route('descargas.admin.create') }}" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Cargar Más Archivos
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
const jobs = @json($jobs);
const jobsCompletados = new Set();
const jobsFallidos = new Set();
let totalJobs = jobs.length;

function actualizarProgreso(jobId, data) {
    const jobElement = document.querySelector(`[data-job-id="${jobId}"]`);
    if (!jobElement) return;

    const progressBar = jobElement.querySelector('.job-progress');
    const statusBadge = jobElement.querySelector('.job-status');
    const message = jobElement.querySelector('.job-message');

    // Actualizar barra de progreso
    progressBar.style.width = data.progreso + '%';
    progressBar.setAttribute('aria-valuenow', data.progreso);
    progressBar.textContent = data.progreso + '%';

    // Actualizar estado
    switch(data.estado) {
        case 'pendiente':
            statusBadge.className = 'badge badge-secondary job-status';
            statusBadge.textContent = 'Pendiente';
            message.textContent = 'Esperando procesamiento...';
            break;
        
        case 'procesando':
            statusBadge.className = 'badge badge-primary job-status';
            statusBadge.textContent = 'Procesando';
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary job-progress';
            message.textContent = 'Procesando archivo...';
            break;
        
        case 'completado':
            statusBadge.className = 'badge badge-success job-status';
            statusBadge.textContent = 'Completado';
            progressBar.className = 'progress-bar bg-success job-progress';
            message.textContent = 'Archivo cargado exitosamente';
            jobsCompletados.add(jobId);
            break;
        
        case 'error':
            statusBadge.className = 'badge badge-danger job-status';
            statusBadge.textContent = 'Error';
            progressBar.className = 'progress-bar bg-danger job-progress';
            progressBar.style.width = '100%';
            progressBar.textContent = 'Error';
            message.textContent = 'Error: ' + (data.error || 'Error desconocido');
            jobsFallidos.add(jobId);
            break;
    }

    // Verificar si todos los jobs terminaron
    if (jobsCompletados.size + jobsFallidos.size === totalJobs) {
        mostrarResumenFinal();
    }
}

function mostrarResumenFinal() {
    const accionesFinales = document.getElementById('acciones-finales');
    const resumenFinal = document.getElementById('resumen-final');
    
    accionesFinales.style.display = 'block';
    
    let mensaje = '<h5>';
    if (jobsFallidos.size === 0) {
        mensaje += '<i class="fas fa-check-circle text-success"></i> ';
        mensaje += `Todos los archivos (${jobsCompletados.size}) se cargaron exitosamente`;
    } else if (jobsCompletados.size === 0) {
        mensaje += '<i class="fas fa-times-circle text-danger"></i> ';
        mensaje += `Todos los archivos (${jobsFallidos.size}) fallaron al cargarse`;
    } else {
        mensaje += '<i class="fas fa-exclamation-triangle text-warning"></i> ';
        mensaje += `${jobsCompletados.size} archivo(s) cargado(s), ${jobsFallidos.size} fallaron`;
    }
    mensaje += '</h5>';
    
    resumenFinal.innerHTML = mensaje;
}

function verificarProgreso() {
    jobs.forEach(job => {
        if (jobsCompletados.has(job.job_id) || jobsFallidos.has(job.job_id)) {
            return; // Ya terminó, no verificar más
        }

        fetch(`{{ url('descargas/admin/job-status') }}/${job.job_id}`)
            .then(response => response.json())
            .then(data => {
                actualizarProgreso(job.job_id, data);
            })
            .catch(error => {
                console.error('Error verificando progreso:', error);
            });
    });

    // Continuar verificando si no todos terminaron
    if (jobsCompletados.size + jobsFallidos.size < totalJobs) {
        setTimeout(verificarProgreso, 2000); // Verificar cada 2 segundos
    }
}

// Iniciar verificación
verificarProgreso();
</script>
@endpush
