@extends('layouts.app')

@section('content')
    @php
        $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    @endphp

    <section class="section">
        <div class="section-header">
            <h1 class="section-title">Entregas de Combustible</h1>
            <p class="section-subtitle">Diesel solicitado por soporte para grupos electrógenos y generadores</p>
        </div>

        <div class="section-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total litros {{ $meses[$mes] }} {{ $anio }}</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->litros }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-info bg-info">
                            <i class="fas fa-fill-drip"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Bidones solicitados</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->bidones }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-warning bg-warning">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Entregas del período</h4>
                            </div>
                            <div class="card-body">{{ (int) $totales->entregas }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-mobile-optimized">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row w-100">
                        <h4 class="card-title mb-2 mb-md-0">Listado de Entregas</h4>
                        <div class="d-flex flex-column flex-md-row" style="gap: .5rem;">
                            <button type="button" class="btn btn-success btn-lg-mobile" data-toggle="modal" data-target="#modalWhatsappCombustible">
                                <i class="fab fa-whatsapp"></i> Solicitar por WhatsApp
                            </button>
                            @can('crear-entrega-combustible')
                                <a href="{{ route('entrega-combustible.create') }}" class="btn btn-primary btn-lg-mobile">
                                    <i class="fas fa-plus"></i> Nueva Entrega
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('entrega-combustible.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <input type="text" name="ticket" class="form-control" placeholder="Ticket" value="{{ request('ticket') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha" class="form-control" value="{{ request('fecha') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="empresa_soporte" class="form-control" placeholder="Empresa" value="{{ request('empresa_soporte') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="mes" class="form-control">
                                    @foreach($meses as $numeroMes => $nombreMes)
                                        <option value="{{ $numeroMes }}" {{ $mes === $numeroMes ? 'selected' : '' }}>{{ $nombreMes }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2020" max="2100">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('entrega-combustible.index') }}" class="btn btn-secondary btn-block mt-1">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped mobile-table">
                            <thead>
                                <tr>
                                    <th>N° Acta</th>
                                    <th>Fecha</th>
                                    <th>Ticket</th>
                                    <th>Remito</th>
                                    <th>Empresa</th>
                                    <th>Receptor</th>
                                    <th>Cantidad</th>
                                    <th>Acta Firmada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entregas as $entrega)
                                    <tr>
                                        <td>{{ $entrega->id }}</td>
                                        <td>{{ $entrega->fecha_entrega->format('d/m/Y') }}<br><small class="text-muted">{{ substr($entrega->hora_entrega, 0, 5) }}</small></td>
                                        <td>{{ $entrega->ticket }}</td>
                                        <td>{{ $entrega->remito ?: '—' }}</td>
                                        <td>{{ $entrega->empresa_soporte }}</td>
                                        <td>{{ $entrega->personal_receptor }}</td>
                                        <td><span class="badge badge-info">{{ $entrega->cantidad_litros }} litros</span><br><small>{{ $entrega->cantidad_bidones }} bidones x {{ $entrega->litros_por_bidon }} L</small></td>
                                        <td>
                                            @if($entrega->ruta_acta_firmada)
                                                <a href="{{ asset($entrega->ruta_acta_firmada) }}" target="_blank" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Ver
                                                </a>
                                            @else
                                                <span class="badge badge-warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                @can('ver-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.show', $entrega) }}" class="btn btn-warning btn-sm" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('editar-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.edit', $entrega) }}" class="btn btn-info btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('crear-entrega-combustible')
                                                    <a href="{{ route('entrega-combustible.documento', $entrega) }}" class="btn btn-secondary btn-sm" title="Generar Word">
                                                        <i class="fas fa-file-word"></i>
                                                    </a>
                                                @endcan
                                                @if($entrega->ruta_archivo)
                                                    <a href="{{ route('entrega-combustible.descargar', $entrega) }}" class="btn btn-primary btn-sm" title="Descargar acta generada">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                @can('borrar-entrega-combustible')
                                                    <form action="{{ route('entrega-combustible.destroy', $entrega) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta entrega?')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">No se encontraron entregas de combustible</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $entregas->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalWhatsappCombustible" tabindex="-1" role="dialog" aria-labelledby="modalWhatsappCombustibleLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalWhatsappCombustibleLabel">
                        <i class="fab fa-whatsapp"></i> Solicitar combustible al 2° Jefe Patrulla
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Se abrirá WhatsApp Web/App con el mensaje pre-armado al número <strong>+54 9 3434 60-2014</strong>.</p>

                    <div class="form-group">
                        <label for="wa_saludo">Saludo</label>
                        <select id="wa_saludo" class="form-control">
                            <option value="Hola buenos días">Hola buenos días</option>
                            <option value="Hola buenas tardes">Hola buenas tardes</option>
                            <option value="Hola buenas noches">Hola buenas noches</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="wa_empresa">Empresa de soporte</label>
                        <input type="text" id="wa_empresa" class="form-control" value="Patagonia Green">
                    </div>

                    <div class="form-group">
                        <label for="wa_litros">Cantidad de litros solicitados</label>
                        <input type="number" id="wa_litros" class="form-control" value="40" min="1" max="9999">
                    </div>

                    <div class="form-group">
                        <label for="wa_mensaje">Mensaje (editable)</label>
                        <textarea id="wa_mensaje" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <a id="wa_enviar" href="#" target="_blank" rel="noopener" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i> Abrir WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('modalWhatsappCombustible');
        if (!modal) {
            return;
        }
        var saludo = document.getElementById('wa_saludo');
        var empresa = document.getElementById('wa_empresa');
        var litros = document.getElementById('wa_litros');
        var mensaje = document.getElementById('wa_mensaje');
        var enviar = document.getElementById('wa_enviar');
        var telefono = '5493434602014';
        var manual = false;

        function sugerirSaludo() {
            var h = new Date().getHours();
            if (h < 12) {
                saludo.value = 'Hola buenos días';
            } else if (h < 20) {
                saludo.value = 'Hola buenas tardes';
            } else {
                saludo.value = 'Hola buenas noches';
            }
        }

        function regenerar() {
            if (manual) {
                actualizarLink();
                return;
            }
            mensaje.value = saludo.value + ' Jefe. Desde la empresa de soporte ' + empresa.value +
                ' nos solicitaron ' + litros.value + ' litros de combustible para los generadores ' +
                'de la división y antenas radiobases';
            actualizarLink();
        }

        function actualizarLink() {
            enviar.href = 'https://wa.me/' + telefono + '?text=' + encodeURIComponent(mensaje.value);
        }

        if (typeof $ !== 'undefined') {
            $(modal).on('show.bs.modal', function () {
                manual = false;
                sugerirSaludo();
                regenerar();
            });
        } else {
            sugerirSaludo();
            regenerar();
        }

        saludo.addEventListener('change', regenerar);
        empresa.addEventListener('input', regenerar);
        litros.addEventListener('input', regenerar);
        mensaje.addEventListener('input', function () {
            manual = true;
            actualizarLink();
        });
    })();
</script>
@endpush
