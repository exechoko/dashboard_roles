@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Importar Chalecos</h3>
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

                            <form action="{{ route('armas.armeria.chalecos.importar.post') }}" method="POST" enctype="multipart/form-data">
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
                                    <h6><i class="fas fa-info-circle"></i> Formato esperado (PLANILLA RELEVAMIENTO CHALECOS)</h6>
                                    <p class="mb-0">El archivo debe tener el encabezado ORDEN / MOVIL / MARCA / MODELO/PROTECCION / TALLE / SERIE / EN SERVICIO en la fila 6.</p>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Notas importantes</h6>
                                    <ul class="mb-0">
                                        <li>Los chalecos cuya serie ya exista en el sistema se omiten (no se duplican).</li>
                                        <li>Si la columna "EN SERVICIO" contiene "REPARACIÓN" se carga como En Reparación; si contiene "BAJA" como De Baja; caso contrario En Servicio.</li>
                                        <li>Si el texto menciona "Jefatura" o "Central" se ubica en Armería Jefatura Central; caso contrario en Armería División 911.</li>
                                    </ul>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Importar
                                    </button>
                                    <a href="{{ route('armas.armeria.chalecos.index') }}" class="btn btn-secondary">
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
                                            <th>ORDEN</th>
                                            <th>MOVIL</th>
                                            <th>MARCA</th>
                                            <th>MODELO/PROTECCION</th>
                                            <th>TALLE</th>
                                            <th>SERIE</th>
                                            <th>EN SERVICIO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>-</td>
                                            <td>ABPC S.A</td>
                                            <td>RB3/3</td>
                                            <td>L</td>
                                            <td>3023</td>
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
