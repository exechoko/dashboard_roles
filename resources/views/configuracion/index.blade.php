@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Configuración del Sistema</h3>
        </div>

        <div class="section-body">
            <div class="row">
                @can('ver-auditoria')
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                                <h5>Auditoría</h5>
                                <p class="text-muted">Historial de cambios realizados en el sistema.</p>
                                <a href="{{ route('auditoria.index') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ir a Auditoría
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('ver-configuracion-env')
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-sliders-h fa-3x text-info mb-3"></i>
                                <h5>Variables de Entorno</h5>
                                <p class="text-muted">Credenciales y parámetros de los servicios integrados.</p>
                                <a href="{{ route('configuracion.env') }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ir a Variables de Entorno
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('ver-configuracion-ia')
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-brain fa-3x text-success mb-3"></i>
                                <h5>IA y API Keys</h5>
                                <p class="text-muted">Modelos y servidores de inferencia de cada servicio de IA.</p>
                                <a href="{{ route('configuracion.ia') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ir a IA y API Keys
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('ver-configuracion-workers')
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-cogs fa-3x text-warning mb-3"></i>
                                <h5>Workers y Colas</h5>
                                <p class="text-muted">Configuración de colas y estado de los workers.</p>
                                <a href="{{ route('configuracion.workers') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ir a Workers y Colas
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('ver-configuracion-backup')
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-danger mb-3"></i>
                                <h5>Backups de Base de Datos</h5>
                                <p class="text-muted">Generar, descargar y restaurar backups de la base de datos.</p>
                                <a href="{{ route('configuracion.backups') }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ir a Backups
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </section>
@endsection
