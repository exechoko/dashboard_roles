@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-edit"></i> Editar Período {{ $periodo->label }}</h1>
        <div class="section-header-breadcrumb">
            <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light btn-sm">
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
                        <form action="{{ route('incidencias.periodos.update', $periodo->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Número de Período <span class="text-danger">*</span></label>
                                    <input type="number" name="numero" class="form-control @error('numero') is-invalid @enderror"
                                        value="{{ old('numero', $periodo->numero) }}" min="1" required>
                                    @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Fecha Inicio <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                                        value="{{ old('fecha_inicio', $periodo->fecha_inicio->format('Y-m-d')) }}" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Fecha Fin <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                                        value="{{ old('fecha_fin', $periodo->fecha_fin->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6 class="text-muted">Unidades por sistema</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Terminales TETRA</label>
                                    <input type="number" name="n_total_tetra" class="form-control"
                                        value="{{ old('n_total_tetra', $periodo->n_total_tetra) }}" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Cámaras CCTV</label>
                                    <input type="number" name="n_total_camaras" class="form-control"
                                        value="{{ old('n_total_camaras', $periodo->n_total_camaras) }}" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Puestos CCTV</label>
                                    <input type="number" name="n_total_puestos_cctv" class="form-control"
                                        value="{{ old('n_total_puestos_cctv', $periodo->n_total_puestos_cctv) }}" min="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Puestos CeCoCo</label>
                                    <input type="number" name="n_total_puestos_cecoco" class="form-control"
                                        value="{{ old('n_total_puestos_cecoco', $periodo->n_total_puestos_cecoco) }}" min="0">
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6 class="text-muted">Facturación</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>N° Factura</label>
                                    <input type="text" name="factura_numero" class="form-control" value="{{ old('factura_numero', $periodo->factura_numero) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Monto Factura ($)</label>
                                    <input type="number" name="factura_monto" class="form-control" step="0.01" value="{{ old('factura_monto', $periodo->factura_monto) }}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>N° Expediente</label>
                                    <input type="text" name="expediente_numero" class="form-control" value="{{ old('expediente_numero', $periodo->expediente_numero) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>R.U.</label>
                                    <input type="text" name="ru_numero" class="form-control" value="{{ old('ru_numero', $periodo->ru_numero) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones', $periodo->observaciones) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('incidencias.periodos.show', $periodo->id) }}" class="btn btn-light">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
