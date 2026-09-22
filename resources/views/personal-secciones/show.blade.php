@extends('layouts.app')

@php
    $v = fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? $valor : '—';
    $fecha = fn ($valor) => $valor ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '—';
@endphp

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">{{ $personal->apellido }}, {{ $personal->nombre }}</h3>
            <a href="{{ url()->previous(route('personal-secciones.index')) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @if($personal->trashed())
                <div class="alert alert-dark"><i class="fas fa-user-slash"></i> Este funcionario está dado de baja de la Policía (registro eliminado localmente).</div>
            @endif

            @unless($detalle)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No se pudo traer el detalle completo desde Personal 911 en este momento. Se muestran solo los datos que ya tenemos sincronizados localmente.
                </div>
            @endunless

            @php
                $datosPersonales = [
                    'Apellido' => $v($detalle->Ape_Func ?? $personal->apellido),
                    'Nombre' => $v($detalle->Nom_Func ?? $personal->nombre),
                    'Documento' => $v($detalle->Doc_Func ?? $personal->dni),
                    'Fecha de Nac.' => $fecha($personal->fecha_nacimiento),
                    'Edad' => $personal->edad !== null ? $personal->edad.' años' : '—',
                    'Grupo Sang.' => $v($detalle->Nom_GrupoSang ?? null),
                    'Sexo' => $v($detalle->Nombre_SexoFunc ?? null),
                    'Estado Civil' => $v($detalle->Nom_ECivil ?? $personal->estado_civil),
                    'Domicilio Actual' => $v($detalle->Dom_Func ?? $personal->direccion),
                    'Teléfono 1' => $v($detalle->Telefono1_Func ?? null),
                    'Teléfono 2' => $v($detalle->Telefono2_Func ?? null),
                    'C.U.I.L. N°' => $v($detalle->Cuil_Func ?? null),
                    'Email' => $v($detalle->Email_Func ?? $personal->email),
                ];
                $datosLaborales = [
                    'Fecha Ingreso' => isset($detalle->FecIng_Func) ? $fecha($detalle->FecIng_Func) : '—',
                    'Legajo Personal' => $v($personal->lp),
                    'Legajo Contable' => $v($detalle->LgjC_Func ?? null),
                    'Lugar (Sección)' => $v($seccion->seccion ?? ($detalle->Nom_Lugar ?? null)),
                    'Jerarquía' => $v($personal->jerarquia),
                    'Función' => $v($personal->funcion_personal911),
                    'Función D.P.3' => $v($detalle->funcion_dp3 ?? null),
                    'Cuerpo' => $v($detalle->Nom_Cuerpo ?? null),
                    'Tipo de Arma' => $v($detalle->Nombre_TipoArma ?? null),
                    'N° Arma' => $v($personal->numeracion_arma),
                    'Domicilio Laboral' => $v($detalle->Nombre_DomLab ?? null),
                ];
                $estadoActual = [
                    'Fecha' => $fecha($personal->fecha_situacion_personal911),
                    'Situación' => $v($personal->situacion_personal911),
                    'Norma Res. / Dec.' => $v($detalle->Obs_Estado ?? null),
                ];
                $ingresoDivision = [
                    'Fecha' => isset($detalle->Fec_Ing911) ? $fecha($detalle->Fec_Ing911) : '—',
                    'Norma Res. / Dec.' => $v($detalle->Norma_Ing911 ?? null),
                ];
            @endphp

            @php
                $renderPlanilla = function (array $datos) {
                    $filas = array_chunk($datos, 2, true);
                    $html = '<table class="table table-sm table-striped mb-0"><tbody>';
                    foreach ($filas as $par) {
                        $html .= '<tr>';
                        foreach ($par as $etiqueta => $valor) {
                            $html .= '<th class="text-muted font-weight-normal" style="width:15%">'.e($etiqueta).'</th>';
                            $html .= '<td style="width:35%">'.e($valor).'</td>';
                        }
                        if (count($par) === 1) {
                            $html .= '<th></th><td></td>';
                        }
                        $html .= '</tr>';
                    }
                    return $html.'</tbody></table>';
                };
            @endphp

            <div class="card mb-3">
                <div class="card-header"><strong><i class="fas fa-id-card mr-1"></i> Datos Personales</strong></div>
                <div class="table-responsive">{!! $renderPlanilla($datosPersonales) !!}</div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong><i class="fas fa-briefcase mr-1"></i> Datos Laborales</strong></div>
                <div class="table-responsive">{!! $renderPlanilla($datosLaborales) !!}</div>
                @if($personal->observaciones_personal911)
                    <div class="card-body pt-0">
                        <strong class="text-muted d-block mb-1">Observaciones</strong>
                        <p class="mb-0" style="white-space: pre-line">{{ $personal->observaciones_personal911 }}</p>
                    </div>
                @endif
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3 h-100">
                        <div class="card-header"><strong><i class="fas fa-clipboard-check mr-1"></i> Estado Actual</strong></div>
                        <div class="table-responsive">{!! $renderPlanilla($estadoActual) !!}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3 h-100">
                        <div class="card-header"><strong><i class="fas fa-flag mr-1"></i> Ingreso a la División 911 y V.V.</strong></div>
                        <div class="table-responsive">{!! $renderPlanilla($ingresoDivision) !!}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong><i class="fas fa-sticky-note mr-1"></i> Anotaciones</strong></div>
                <div class="card-body">
                    @forelse ($notas as $nota)
                        @php($esAutor = $nota->esAutor(auth()->user()))
                        <div class="border-left pl-3 mb-3" style="border-color:#dee2e6 !important">
                            <div class="text-muted small d-flex flex-wrap align-items-center" style="gap:.4rem">
                                <span><i class="far fa-clock mr-1"></i>{{ $nota->created_at->format('d/m/Y H:i') }}</span>
                                <span><i class="far fa-user ml-1 mr-1"></i>{{ $nota->autor?->name }} {{ $nota->autor?->apellido }}</span>
                                @if($esAutor)
                                    @if($nota->esPrivada())
                                        <span class="badge badge-secondary"><i class="fas fa-lock"></i> Privada</span>
                                    @else
                                        <span class="badge badge-info"><i class="fas fa-share-alt"></i> Compartida</span>
                                    @endif
                                @else
                                    <span class="badge badge-light">Compartida contigo</span>
                                @endif
                            </div>
                            <p class="mb-0" style="white-space: pre-line">{{ $nota->texto }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Todavía no hay anotaciones para este funcionario, o las que hay no fueron compartidas con vos.</p>
                    @endforelse

                    @can('crear-personal-seccion-nota')
                        <hr>
                        <form action="{{ route('personal-secciones.notas.store', $personal->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea name="texto" class="form-control" rows="2" minlength="3" maxlength="1000"
                                          placeholder="Agregar una anotación (queda privada, solo vos la ves)..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar anotación
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </section>
@endsection
