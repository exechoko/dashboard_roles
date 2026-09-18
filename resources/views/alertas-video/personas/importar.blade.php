@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Importar Personas</h3>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Seleccionar Archivo Excel</h4>
                        </div>
                        <div class="card-body">
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            @endif

                            <form action="{{ route('alertas-video.personas.importar.post') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group">
                                    <label for="archivo">Archivo Excel <span class="text-danger">*</span></label>
                                    <input type="file" name="archivo" id="archivo" class="form-control-file @error('archivo') is-invalid @enderror" accept=".xlsx,.xls" required>
                                    @error('archivo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Formatos aceptados: .xlsx, .xls</small>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Formato esperado</h6>
                                    <p class="mb-0">
                                        El archivo debe tener una hoja llamada <strong>"Personas"</strong> con el encabezado
                                        Nro / D.N.I. / Apellido y Nombre / Direccion / Solicitado por / Fecha de carga /
                                        Funcionario que carga / Hecho relacionado / Activo / Identificado / Finalizado, en la fila 3.
                                    </p>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Notas importantes</h6>
                                    <ul class="mb-0">
                                        <li>Las personas con D.N.I. ya cargado se omiten (no se duplican).</li>
                                        <li>Si no hay D.N.I., se compara por Apellido y Nombre para evitar duplicados.</li>
                                        <li>Las fotos de rostro no vienen en la planilla: se cargan luego desde la ficha de cada persona.</li>
                                    </ul>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Importar
                                    </button>
                                    <a href="{{ route('alertas-video.personas.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Ejemplo de Formato (hoja "Personas")</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>D.N.I.</th>
                                            <th>Apellido y Nombre</th>
                                            <th>Hecho relacionado</th>
                                            <th>Activo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>35708801</td>
                                            <td>Montero Marianela Ayelen</td>
                                            <td>Solicitud de localización y restitución al hogar</td>
                                            <td>SI</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
