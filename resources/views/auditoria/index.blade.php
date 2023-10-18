@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Auditoría</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="">
                            <div class="">
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $auditorias->total() }}</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('auditoria.index') }}" method="get" onsubmit="return showLoad()">
                                <div class="input-group mt-4">
                                    <input type="text" name="texto" class="form-control" placeholder="Buscar"
                                        value="{{ $texto }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-info">Buscar</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-hover mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="color:#fff; width: 5%;">Registro</th>
                                        <th style="color:#fff; width: 10%;">Fecha</th>
                                        <th style="color:#fff; width: 5%;">Usuario</th>
                                        <th style="color:#fff;">Item modificado</th>
                                        <th style="color:#fff;">Tabla modificada</th>
                                        <th style="color:#fff;">Acción</th>
                                        <th style="color:#fff;">Cambios</th>
                                    </thead>
                                    <tbody>
                                        @if (count($auditorias) <= 0)
                                            <tr>
                                                <td colspan="8">No se encontraron resultados</td>
                                            </tr>
                                        @else
                                            @foreach ($auditorias as $auditoria)
                                                <tr>
                                                    <td>{{ $auditoria->id }}</td>
                                                    <td>{{ $auditoria->created_at }}</td>
                                                    <td>{{ $auditoria->user->apellido . ' ' . $auditoria->user->name }}</td>
                                                    @switch($auditoria->nombre_tabla)
                                                        @case('user')
                                                            @if (!is_null($auditoria->usuarioModificado))
                                                                <td>{{ $auditoria->usuarioModificado->name }}</td>
                                                            @else
                                                                <td>-</td>
                                                            @endif
                                                        @break

                                                        @case('flota_general')
                                                            @if (!is_null($auditoria->flotaModificada))
                                                                <td>{{ $auditoria->flotaModificada->equipo->tei }}</td>
                                                            @else
                                                                <td>-</td>
                                                            @endif
                                                        @break

                                                        @case('historico')
                                                            @if (!is_null($auditoria->historicoModificado))
                                                                <td>{{ $auditoria->historicoModificado->equipo->tei }}</td>
                                                            @else
                                                                <td>-</td>
                                                            @endif
                                                        @break

                                                        @case('recursos')
                                                            @if (!is_null($auditoria->recursoModificado))
                                                                <td>{{ $auditoria->recursoModificado->nombre }}</td>
                                                            @else
                                                                <td>-</td>
                                                            @endif
                                                        @break

                                                        @default
                                                            <td>NN</td>
                                                    @endswitch

                                                    <td>{{ $auditoria->nombre_tabla }}</td>
                                                    <td>{{ $auditoria->accion }}</td>
                                                    <td>{{ $auditoria->cambios }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {!! $auditorias->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
