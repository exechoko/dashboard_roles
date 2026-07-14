@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Importar Retenciones de Armas</h3>
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

                            <form action="{{ route('armas.retenciones.importar.post') }}" method="POST" enctype="multipart/form-data">
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
                                    <h6><i class="fas fa-info-circle"></i> Formato del archivo Excel</h6>
                                    <p class="mb-2">El archivo debe contener las siguientes columnas:</p>
                                    <ul class="mb-0">
                                        <li><strong>nombre</strong> - Nombre del funcionario</li>
                                        <li><strong>apellido</strong> - Apellido del funcionario</li>
                                        <li><strong>legajo</strong> - Legajo policial (LP)</li>
                                        <li><strong>jerarquia</strong> - Jerarquía del funcionario</li>
                                        <li><strong>numeracion_arma</strong> - Número del arma</li>
                                        <li><strong>nro_chaleco</strong> - Número de chaleco (opcional)</li>
                                        <li><strong>motivo</strong> - Motivo de la retención</li>
                                        <li><strong>fecha_posesion</strong> - Fecha de posesión</li>
                                        <li><strong>dias_restantes</strong> - Días restantes (opcional)</li>
                                        <li><strong>fecha_elevacion</strong> - Fecha de elevación (opcional)</li>
                                        <li><strong>fecha_devolucion</strong> - Fecha de devolución (opcional)</li>
                                        <li><strong>observaciones</strong> - Observaciones (opcional)</li>
                                    </ul>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Notas importantes</h6>
                                    <ul class="mb-0">
                                        <li>Si el funcionario no existe, se creará automáticamente</li>
                                        <li>El motivo debe coincidir con los motivos activos en el sistema</li>
                                        <li>Las fechas deben estar en formato YYYY-MM-DD o DD/MM/YYYY</li>
                                        <li>El tipo de retención se asignará automáticamente según el motivo</li>
                                    </ul>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Importar
                                    </button>
                                    <a href="{{ route('armas.retenciones.index') }}" class="btn btn-secondary">
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
                            <h4>Ejemplo de Formato</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>nombre</th>
                                            <th>apellido</th>
                                            <th>legajo</th>
                                            <th>jerarquia</th>
                                            <th>numeracion_arma</th>
                                            <th>nro_chaleco</th>
                                            <th>motivo</th>
                                            <th>fecha_posesion</th>
                                            <th>observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Juan</td>
                                            <td>Pérez</td>
                                            <td>12345</td>
                                            <td>Sargento</td>
                                            <td>ABC123</td>
                                            <td>CH001</td>
                                            <td>862</td>
                                            <td>2024-01-15</td>
                                            <td>Retención preventiva</td>
                                        </tr>
                                        <tr>
                                            <td>María</td>
                                            <td>González</td>
                                            <td>67890</td>
                                            <td>Oficial</td>
                                            <td>XYZ789</td>
                                            <td></td>
                                            <td>A.R.T</td>
                                            <td>15/01/2024</td>
                                            <td></td>
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
