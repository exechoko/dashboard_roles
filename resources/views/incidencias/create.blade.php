@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-plus-circle"></i> Nuevo Período de Facturación</h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('incidencias.periodos.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="section-body">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header"><h4>Datos del Período</h4></div>
                    <div class="card-body">
                        <form action="{{ route('incidencias.periodos.store') }}" method="POST">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Número de Período <span class="text-danger">*</span></label>
                                    <input type="number" name="numero" class="form-control @error('numero') is-invalid @enderror"
                                        value="{{ old('numero', $siguienteNumero) }}" min="1" required>
                                    @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Fecha Inicio <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio"
                                        class="form-control @error('fecha_inicio') is-invalid @enderror"
                                        value="{{ old('fecha_inicio') }}" required>
                                    @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Fecha Fin <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_fin" id="fecha_fin"
                                        class="form-control @error('fecha_fin') is-invalid @enderror"
                                        value="{{ old('fecha_fin') }}" required>
                                    @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6 class="text-muted">Cantidad de unidades por sistema (para calcular indisponibilidad)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label><i class="fas fa-satellite-dish text-primary"></i> Terminales TETRA</label>
                                    <input type="number" name="n_total_tetra" class="form-control"
                                        value="{{ old('n_total_tetra', 622) }}" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label><i class="fas fa-video text-success"></i> Cámaras CCTV</label>
                                    <input type="number" name="n_total_camaras" class="form-control"
                                        value="{{ old('n_total_camaras', 336) }}" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label><i class="fas fa-desktop text-warning"></i> Puestos CeCoCo</label>
                                    <input type="number" name="n_total_puestos_cecoco" class="form-control"
                                        value="{{ old('n_total_puestos_cecoco', 0) }}" min="0">
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6 class="text-muted">Datos de la Factura</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>N° Factura</label>
                                    <input type="text" name="factura_numero" class="form-control"
                                        value="{{ old('factura_numero') }}" placeholder="00002-00009073">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Monto Factura ($)</label>
                                    <input type="number" name="factura_monto" class="form-control"
                                        value="{{ old('factura_monto') }}" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>N° Expediente</label>
                                    <input type="text" name="expediente_numero" class="form-control"
                                        value="{{ old('expediente_numero') }}" placeholder="3394198">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>R.U.</label>
                                    <input type="text" name="ru_numero" class="form-control"
                                        value="{{ old('ru_numero') }}" placeholder="20278/26">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('incidencias.periodos.index') }}" class="btn btn-light">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Período</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
