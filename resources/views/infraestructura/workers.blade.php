@extends('layouts.app')

@section('css')
<style>
    /* Card "Estado de procesos y Base de datos" */
    .estado-procesos-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .estado-procesos-header {
        background: linear-gradient(135deg, #1a3a2a, #166534);
        color: #fff;
    }

    .estado-procesos-grid {
        gap: 1.5rem;
    }

    /* Bloque que envuelve un grupo (Geocodificación, Tamaño BD, etc.) */
    .estado-procesos-bloque {
        padding-left: 0.75rem;
        margin-left: 0.25rem;
        border-left: 1px solid #dee2e6;
    }

    .estado-procesos-titulo {
        color: #6c757d;
    }

    .restauraciones-icono {
        font-size: 1rem;
        line-height: 1;
    }

    .inventario-conflictos-btn:not(:disabled) {
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.12);
    }

    .conflicto-funcionario {
        padding: 0.4rem 0.55rem;
        margin-bottom: 0.35rem;
        border-left: 3px solid #ef4444;
        background: #f8fafc;
        border-radius: 0 4px 4px 0;
    }

    [data-theme="dark"] .conflicto-funcionario {
        color: #e2e8f0;
        background: #1e293b;
    }

    /* ── Botón refresh restauraciones: animaciones por estado ───────────── */
    .btn-refresh-restauraciones {
        transition: background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease, transform 0.15s ease;
    }
    .btn-refresh-restauraciones.estado-consultando {
        color: #fff;
        background-color: #3b82f6;
        border-color: #3b82f6;
        animation: refresh-halo-azul 1.4s ease-in-out infinite;
    }
    .btn-refresh-restauraciones.estado-success {
        color: #fff;
        background-color: #22c55e;
        border-color: #22c55e;
        animation: refresh-flash-verde 1.5s ease-out 1;
    }
    .btn-refresh-restauraciones.estado-error {
        color: #fff;
        background-color: #ef4444;
        border-color: #ef4444;
        animation: refresh-shake-rojo 0.5s cubic-bezier(.36,.07,.19,.97) 1;
    }
    @keyframes refresh-halo-azul {
        0%   { box-shadow: 0 0 0 0   rgba(59,130,246,0.6); }
        70%  { box-shadow: 0 0 0 8px rgba(59,130,246,0);   }
        100% { box-shadow: 0 0 0 0   rgba(59,130,246,0);   }
    }
    @keyframes refresh-flash-verde {
        0%   { box-shadow: 0 0 0 0   rgba(34,197,94,0.7); transform: scale(1); }
        40%  { box-shadow: 0 0 0 12px rgba(34,197,94,0);  transform: scale(1.08); }
        100% { box-shadow: 0 0 0 0   rgba(34,197,94,0);   transform: scale(1); }
    }
    @keyframes refresh-shake-rojo {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-3px); }
        40%, 60% { transform: translateX(3px); }
    }
    /* Respeta a usuarios con prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .btn-refresh-restauraciones.estado-consultando,
        .btn-refresh-restauraciones.estado-success,
        .btn-refresh-restauraciones.estado-error {
            animation: none;
        }
    }

    /* Dark mode */
    [data-theme="dark"] .estado-procesos-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .estado-procesos-card .card-body {
        color: #e2e8f0;
    }

    [data-theme="dark"] .estado-procesos-bloque {
        border-left-color: rgba(255, 255, 255, 0.15);
    }

    [data-theme="dark"] .estado-procesos-titulo {
        color: #cbd5e1;
    }

    [data-theme="dark"] .estado-procesos-card .badge.badge-secondary {
        background-color: #475569;
        color: #e2e8f0;
    }

    /* Mobile: bloques apilados sin border-left a la izquierda (queda mal en una sola columna) */
    @media (max-width: 767px) {
        .estado-procesos-grid {
            gap: 0.85rem;
        }
        .estado-procesos-bloque {
            border-left: none;
            border-top: 1px solid #dee2e6;
            padding-left: 0;
            padding-top: 0.5rem;
            margin-left: 0;
            width: 100%;
        }
        [data-theme="dark"] .estado-procesos-bloque {
            border-top-color: rgba(255, 255, 255, 0.15);
        }
    }
</style>
@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Workers y Bases de Datos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm estado-procesos-card">
                        <div class="card-header d-flex align-items-center justify-content-between py-2 estado-procesos-header">
                            <span><i class="fas fa-cogs mr-2"></i><strong>Estado de procesos y Base de datos</strong></span>
                            <small id="workers-ultima-actualizacion" class="text-white-50"></small>
                        </div>
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap align-items-start estado-procesos-grid">

                                {{-- Estado del worker --}}
                                <div class="d-flex align-items-center mr-4">
                                    <span id="workers-dot" class="mr-2"
                                        style="width:14px;height:14px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                    <span><strong>Worker</strong> <span id="workers-label"
                                            class="badge badge-secondary">Verificando...</span></span>
                                </div>

                                {{-- Jobs pendientes --}}
                                <div class="d-flex align-items-center mr-4">
                                    <i class="fas fa-clock mr-2 text-warning"></i>
                                    <span><strong>Pendientes:</strong> <span id="workers-pendientes" class="badge badge-warning">—</span></span>
                                </div>

                                {{-- Jobs procesando --}}
                                <div class="d-flex align-items-center mr-4">
                                    <i class="fas fa-spinner mr-2 text-info"></i>
                                    <span><strong>Procesando:</strong> <span id="workers-procesando" class="badge badge-info">—</span></span>
                                </div>

                                {{-- Jobs fallidos --}}
                                <div class="d-flex align-items-center mr-4">
                                    <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                                    <span><strong>Fallidos:</strong> <span id="workers-fallidos" class="badge badge-danger">—</span></span>
                                </div>

                                @can('administrar-visor-mails')
                                    {{-- Cola de indexación de correos (mbox) --}}
                                    <div class="estado-procesos-bloque" title="Worker dedicado a indexar los backups .mbox del Visor de Correos (php artisan queue:work mbox --queue=mbox).">
                                        <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-envelope-open-text mr-1"></i><strong>Cola de Correos (mbox)</strong></small>
                                        <div class="d-flex align-items-center flex-wrap" style="gap:0.75rem;">
                                            <span class="d-flex align-items-center">
                                                <span id="mbox-worker-dot" class="mr-2"
                                                    style="width:10px;height:10px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                                                <span id="mbox-worker-label" class="badge badge-secondary">Verificando...</span>
                                            </span>
                                            <span><small>Pendientes:</small> <span id="mbox-pendientes" class="badge badge-secondary">—</span></span>
                                            <span><small>Procesando:</small> <span id="mbox-procesando" class="badge badge-secondary">—</span></span>
                                            <a href="{{ route('herramientas.mails.buzones.index') }}" class="btn btn-xs btn-outline-primary" title="Ver buzones">
                                                <i class="fas fa-inbox"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endcan

                                {{-- Geocodificación --}}
                                <div class="estado-procesos-bloque">
                                    <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-map-marker-alt mr-1"></i><strong>Geocodificación</strong></small>
                                    <div class="d-flex align-items-center flex-wrap" style="gap:0.75rem;">
                                        <span><small>Servicio:</small> <span id="workers-geo-servicio" class="badge badge-secondary" title="Estado del servidor de geocodificación">Verificando...</span></span>
                                        <span><small>Cacheadas:</small> <span id="workers-geo-cacheadas" class="badge badge-success">—</span></span>
                                        <span><small>Pendientes:</small> <span id="workers-geo-pendientes" class="badge badge-secondary">—</span></span>
                                    </div>
                                </div>

                                {{-- Tamaño BD restauraciones CECOCO --}}
                                <div class="estado-procesos-bloque" title="Tamaño de la base de datos de restauraciones de CECOCO. Se actualiza una vez por hora.">
                                    <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-database mr-1"></i><strong>Tamaño BD restauraciones</strong></small>
                                    <div class="d-flex align-items-center flex-wrap" style="gap:0.5rem;">
                                        <span id="workers-restauraciones-icono" class="restauraciones-icono" style="display:none;">
                                            <i class="fas fa-exclamation-triangle text-danger" title="Supera el umbral de 4000 MB"></i>
                                        </span>
                                        <span id="workers-restauraciones-mb" class="badge badge-secondary">—</span>
                                        <button type="button" class="btn btn-xs btn-outline-primary btn-refresh-restauraciones" id="btn-refresh-restauraciones" title="Consultar ahora">
                                            <i class="fas fa-sync-alt" id="icon-refresh-restauraciones"></i>
                                        </button>
                                     </div>
                                 </div>

                                 {{-- Tamaño BD restauraciones CECOCO GPS --}}
                                  <div class="estado-procesos-bloque" title="Tamaño de la base de datos de restauraciones de históricos GPS. Se actualiza una vez por hora.">
                                     <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-database mr-1"></i><strong>Tamaño BD restauraciones GPS</strong></small>
                                     <div class="d-flex align-items-center flex-wrap" style="gap:0.5rem;">
                                         <span id="workers-restauraciones-gps-icono" class="restauraciones-icono" style="display:none;">
                                             <i class="fas fa-exclamation-triangle text-danger" title="Supera el umbral de 4000 MB"></i>
                                         </span>
                                         <span id="workers-restauraciones-gps-mb" class="badge badge-secondary">—</span>
                                         <button type="button" class="btn btn-xs btn-outline-primary btn-refresh-restauraciones" id="btn-refresh-restauraciones-gps" title="Consultar ahora">
                                             <i class="fas fa-sync-alt" id="icon-refresh-restauraciones-gps"></i>
                                         </button>
                                         <button type="button" class="btn btn-xs btn-outline-info btn-ver-restauradas" id="btn-ver-restauradas-gps" title="Ver ficheros restaurados GPS">
                                             <i class="fas fa-check-double"></i>
                                         </button>
                                      </div>
                                  </div>

                                 @can('ver-menu-armamento')
                                     <div class="estado-procesos-bloque" title="Asignaciones duplicadas detectadas durante la última sincronización con Personal 911.">
                                         <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-shield-alt mr-1"></i><strong>Conflictos de inventario</strong></small>
                                         <div class="d-flex align-items-center flex-wrap" style="gap:0.5rem;">
                                              <span><small>Armas:</small> <span id="inventario-conflictos-armas" class="badge badge-secondary">—</span></span>
                                              <span><small>Chalecos:</small> <span id="inventario-conflictos-chalecos" class="badge badge-secondary">—</span></span>
                                              <button type="button" id="btn-ver-conflictos-inventario"
                                                 class="btn btn-xs btn-outline-danger inventario-conflictos-btn"
                                                 data-toggle="modal" data-target="#modal-conflictos-inventario" disabled>
                                                  <i class="fas fa-users mr-1"></i>Ver detalle
                                              </button>
                                              <span><small>Correcciones:</small> <span id="inventario-discrepancias-total" class="badge badge-secondary">—</span></span>
                                              <button type="button" id="btn-ver-discrepancias-inventario"
                                                  class="btn btn-xs btn-outline-warning inventario-conflictos-btn"
                                                  data-toggle="modal" data-target="#modal-discrepancias-inventario" disabled>
                                                  <i class="fas fa-lock mr-1"></i>Ver correcciones
                                              </button>
                                          </div>
                                      </div>
                                  @endcan

                            </div>

                            {{-- Desglose por tipo --}}
                            <div id="workers-desglose" class="mt-3" style="display:none;">
                                <small class="text-muted">Jobs en cola por tipo:</small>
                                <div id="workers-desglose-lista" class="d-flex flex-wrap mt-1" style="gap:0.5rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @can('ver-menu-armamento')
                <div class="modal fade" id="modal-conflictos-inventario" tabindex="-1" role="dialog" aria-labelledby="modalConflictosInventarioTitulo" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="modalConflictosInventarioTitulo">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Asignaciones duplicadas
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">Estos bienes aparecen asignados a más de un funcionario activo en Personal 911. No se asignan localmente hasta que se corrija la fuente.</p>
                                <div id="inventario-conflictos-detalle">
                                    <span class="text-muted">Cargando detalle...</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <small id="inventario-conflictos-actualizado" class="text-muted mr-auto"></small>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal-discrepancias-inventario" tabindex="-1" role="dialog" aria-labelledby="modalDiscrepanciasInventarioTitulo" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title" id="modalDiscrepanciasInventarioTitulo">
                                    <i class="fas fa-lock mr-2"></i>Correcciones locales protegidas
                                </h5>
                                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Cerrar">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">Estas correcciones fueron cargadas localmente y no se pisan con Personal 911 mientras la fuente conserve un dato distinto.</p>
                                <div id="inventario-discrepancias-detalle">
                                    <span class="text-muted">Cargando detalle...</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <small id="inventario-discrepancias-actualizado" class="text-muted mr-auto"></small>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Modal: Ficheros restaurados CECOCO --}}
            <div id="modal-restauradas" class="modal fade" data-backdrop="false"
                style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info">
                            <h4 class="modal-title text-white">Ficheros restaurados</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="min-height: 200px;">
                            <p class="text-muted small" id="restauradas-modal-subtitle">
                                <i class="fas fa-database mr-1"></i> Listado de ficheros restaurados —
                                <span id="restauradas-modal-origen">CECOCO</span>
                                <span id="restauradas-modal-total" class="badge badge-info ml-1">0</span>
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Fichero</th>
                                            <th>Fecha inicio</th>
                                            <th>Fecha fin</th>
                                            <th>Localización</th>
                                        </tr>
                                    </thead>
                                    <tbody id="restauradas-modal-body">
                                        <tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-danger" data-dismiss="modal">
                                <i class="fa fa-times"></i>
                                <span> Cerrar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (function workersMonitor() {
        var url = '{{ route("api.infraestructura.workers-status") }}';

        function escapar(texto) {
            var div = document.createElement('div');
            div.textContent = texto == null ? '' : String(texto);
            return div.innerHTML;
        }

        function renderConflictosInventario(datos) {
            var estado = datos || { total: 0, armas: 0, chalecos: 0, detalle: [] };
            var armas = document.getElementById('inventario-conflictos-armas');
            var chalecos = document.getElementById('inventario-conflictos-chalecos');
            var boton = document.getElementById('btn-ver-conflictos-inventario');
            var detalle = document.getElementById('inventario-conflictos-detalle');
            var actualizado = document.getElementById('inventario-conflictos-actualizado');

            if (armas) {
                armas.textContent = estado.armas || 0;
                armas.className = estado.armas > 0 ? 'badge badge-danger' : 'badge badge-success';
            }
            if (chalecos) {
                chalecos.textContent = estado.chalecos || 0;
                chalecos.className = estado.chalecos > 0 ? 'badge badge-danger' : 'badge badge-success';
            }
            if (boton) {
                boton.disabled = estado.total === 0;
                boton.className = estado.total > 0
                    ? 'btn btn-xs btn-danger inventario-conflictos-btn'
                    : 'btn btn-xs btn-outline-success inventario-conflictos-btn';
                boton.innerHTML = estado.total > 0
                    ? '<i class="fas fa-users mr-1"></i>Ver ' + estado.total + ' conflicto' + (estado.total === 1 ? '' : 's')
                    : '<i class="fas fa-check-circle mr-1"></i>Sin conflictos';
            }

            if (!detalle) return;

            if (!estado.detalle || estado.detalle.length === 0) {
                detalle.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-2"></i>No hay asignaciones duplicadas activas.</div>';
                if (actualizado) actualizado.textContent = '';
                return;
            }

            detalle.innerHTML = estado.detalle.map(function(conflicto) {
                var funcionarios = (conflicto.funcionarios || []).map(function(funcionario) {
                    return '<div class="conflicto-funcionario">'
                        + '<strong>' + escapar(funcionario.apellido) + ', ' + escapar(funcionario.nombre) + '</strong>'
                        + '<div class="small text-muted">' + escapar(funcionario.jerarquia || 'Sin jerarquía')
                        + ' · L.P. ' + escapar(funcionario.lp) + '</div></div>';
                }).join('');
                var etiqueta = conflicto.tipo === 'arma' ? 'Arma' : 'Chaleco';
                var icono = conflicto.tipo === 'arma' ? 'fa-crosshairs' : 'fa-shield-alt';

                return '<div class="card border-danger mb-3">'
                    + '<div class="card-header py-2 d-flex justify-content-between align-items-center">'
                    + '<strong><i class="fas ' + icono + ' mr-2 text-danger"></i>' + etiqueta + ' N° ' + escapar(conflicto.identificador) + '</strong>'
                    + '<span class="badge badge-danger">Duplicado</span></div>'
                    + '<div class="card-body py-2">' + funcionarios
                    + '<small class="text-muted">Detectado: ' + escapar(conflicto.detectado_en || '—')
                    + ' · Última verificación: ' + escapar(conflicto.ultima_deteccion_en || '—') + '</small>'
                    + '</div></div>';
            }).join('');

            if (actualizado) actualizado.textContent = 'Datos de la última sincronización con Personal 911';
        }

        function renderDiscrepanciasInventario(datos) {
            var estado = datos || { total: 0, armas: 0, chalecos: 0, detalle: [] };
            var total = document.getElementById('inventario-discrepancias-total');
            var boton = document.getElementById('btn-ver-discrepancias-inventario');
            var detalle = document.getElementById('inventario-discrepancias-detalle');
            var actualizado = document.getElementById('inventario-discrepancias-actualizado');

            if (total) {
                total.textContent = estado.total || 0;
                total.className = estado.total > 0 ? 'badge badge-warning' : 'badge badge-success';
            }
            if (boton) {
                boton.disabled = estado.total === 0;
                boton.className = estado.total > 0
                    ? 'btn btn-xs btn-warning inventario-conflictos-btn'
                    : 'btn btn-xs btn-outline-success inventario-conflictos-btn';
                boton.innerHTML = estado.total > 0
                    ? '<i class="fas fa-lock mr-1"></i>Ver ' + estado.total + ' corrección' + (estado.total === 1 ? '' : 'es')
                    : '<i class="fas fa-check-circle mr-1"></i>Sin correcciones';
            }

            if (!detalle) return;

            if (!estado.detalle || estado.detalle.length === 0) {
                detalle.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-2"></i>No hay correcciones locales pendientes de resolver en Personal 911.</div>';
                if (actualizado) actualizado.textContent = '';
                return;
            }

            detalle.innerHTML = estado.detalle.map(function(discrepancia) {
                var funcionario = discrepancia.funcionario || {};
                var etiqueta = discrepancia.tipo === 'arma' ? 'Arma' : 'Chaleco';
                var icono = discrepancia.tipo === 'arma' ? 'fa-crosshairs' : 'fa-shield-alt';

                return '<div class="card border-warning mb-3">'
                    + '<div class="card-header py-2 d-flex justify-content-between align-items-center">'
                    + '<strong><i class="fas ' + icono + ' mr-2 text-warning"></i>' + etiqueta + '</strong>'
                    + '<span class="badge badge-warning">Corrección protegida</span></div>'
                    + '<div class="card-body py-2">'
                    + '<strong>' + escapar(funcionario.apellido) + ', ' + escapar(funcionario.nombre) + '</strong>'
                    + '<div class="small text-muted mb-2">' + escapar(funcionario.jerarquia || 'Sin jerarquía')
                    + ' · L.P. ' + escapar(funcionario.lp) + '</div>'
                    + '<div class="row">'
                    + '<div class="col-md-6"><small class="text-muted">Dato local protegido</small><div>' + escapar(discrepancia.valor_local || 'Sin dato') + '</div></div>'
                    + '<div class="col-md-6"><small class="text-muted">Dato Personal 911</small><div>' + escapar(discrepancia.valor_importado || 'Sin dato') + '</div></div>'
                    + '</div>'
                    + (discrepancia.motivo ? '<div class="small mt-2"><strong>Motivo:</strong> ' + escapar(discrepancia.motivo) + '</div>' : '')
                    + '<small class="text-muted d-block mt-2">Detectado: ' + escapar(discrepancia.detectado_en || '—')
                    + ' · Última verificación: ' + escapar(discrepancia.ultima_deteccion_en || '—') + '</small>'
                    + '</div></div>';
            }).join('');

            if (actualizado) actualizado.textContent = 'Datos de la última sincronización con Personal 911';
        }

        function verificar() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    renderConflictosInventario(d.inventario_conflictos);
                    renderDiscrepanciasInventario(d.inventario_discrepancias);

                    // Tabla jobs no existe aún
                    if (d.error === 'tabla_jobs_inexistente') {
                        var dot = document.getElementById('workers-dot');
                        var lbl = document.getElementById('workers-label');
                        if (dot) dot.style.background = '#f59e0b';
                        if (lbl) { lbl.className = 'badge badge-warning'; lbl.title = d.mensaje; lbl.textContent = 'Sin configurar'; }
                        return;
                    }

                    // Worker activo
                    var dot   = document.getElementById('workers-dot');
                    var label = document.getElementById('workers-label');
                    if (d.worker_activo) {
                        dot.style.background = '#22c55e';
                        label.className = 'badge badge-success';
                        label.textContent = 'Activo';
                    } else {
                        dot.style.background = d.pendientes > 0 ? '#ef4444' : '#6b7280';
                        label.className = d.pendientes > 0 ? 'badge badge-danger' : 'badge badge-secondary';
                        label.textContent = d.pendientes > 0 ? 'Detenido' : 'Inactivo';
                    }

                    // Contadores
                    var elPend = document.getElementById('workers-pendientes');
                    if (elPend) { elPend.textContent = d.pendientes; elPend.className = d.pendientes > 0 ? 'badge badge-warning' : 'badge badge-secondary'; }

                    var elProc = document.getElementById('workers-procesando');
                    if (elProc) { elProc.textContent = d.procesando; elProc.className = d.procesando > 0 ? 'badge badge-info' : 'badge badge-secondary'; }

                    var elFall = document.getElementById('workers-fallidos');
                    if (elFall) { elFall.textContent = d.fallidos; elFall.className = d.fallidos > 0 ? 'badge badge-danger' : 'badge badge-secondary'; }

                    // Cola de correos (mbox)
                    var mboxDot = document.getElementById('mbox-worker-dot');
                    var mboxLabel = document.getElementById('mbox-worker-label');
                    if (mboxDot && mboxLabel) {
                        if (d.mbox_worker_activo) {
                            mboxDot.style.background = '#22c55e';
                            mboxLabel.className = 'badge badge-success';
                            mboxLabel.textContent = 'Activo';
                        } else if (d.mbox_pendientes > 0) {
                            mboxDot.style.background = '#ef4444';
                            mboxLabel.className = 'badge badge-danger';
                            mboxLabel.textContent = 'Detenido';
                        } else {
                            mboxDot.style.background = '#6b7280';
                            mboxLabel.className = 'badge badge-secondary';
                            mboxLabel.textContent = 'Sin trabajos';
                        }
                    }
                    var mboxPend = document.getElementById('mbox-pendientes');
                    if (mboxPend) { mboxPend.textContent = d.mbox_pendientes; mboxPend.className = d.mbox_pendientes > 0 ? 'badge badge-warning' : 'badge badge-secondary'; }
                    var mboxProc = document.getElementById('mbox-procesando');
                    if (mboxProc) { mboxProc.textContent = d.mbox_procesando; mboxProc.className = d.mbox_procesando > 0 ? 'badge badge-info' : 'badge badge-secondary'; }

                    // Geocodificación
                    var elGeoServicio = document.getElementById('workers-geo-servicio');
                    if (elGeoServicio) {
                        var motor = d.geo_servicio_motor || 'Geocodificación';
                        if (d.geo_servicio_online) {
                            elGeoServicio.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + motor + ': Online';
                            elGeoServicio.className = 'badge badge-success';
                        } else {
                            elGeoServicio.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' + motor + ': Offline';
                            elGeoServicio.className = 'badge badge-danger';
                        }
                    }

                    var elGeoCach = document.getElementById('workers-geo-cacheadas');
                    if (elGeoCach) elGeoCach.textContent = d.geo_cacheadas !== null ? d.geo_cacheadas : '—';

                    var elGeoPend = document.getElementById('workers-geo-pendientes');
                    if (elGeoPend) {
                        if (d.geo_pendientes === null) {
                            elGeoPend.textContent = 'calculando...';
                            elGeoPend.className = 'badge badge-secondary';
                        } else {
                            elGeoPend.textContent = d.geo_pendientes;
                            elGeoPend.className = d.geo_pendientes > 0 ? 'badge badge-warning' : 'badge badge-success';
                        }
                    }

                    // Tamaño BD restauraciones CECOCO (cache horario)
                    var elRest = document.getElementById('workers-restauraciones-mb');
                    var elRestIcon = document.getElementById('workers-restauraciones-icono');
                    if (elRest) {
                        var mb = d.restauraciones_mb;
                        var umbral = d.restauraciones_umbral_mb || 4000;
                        if (mb === null || typeof mb === 'undefined') {
                            elRest.textContent = 'sin datos';
                            elRest.className = 'badge badge-secondary';
                            elRest.title = 'Aún no se cacheó el valor (corre cada hora desde el schedule).';
                            if (elRestIcon) elRestIcon.style.display = 'none';
                        } else {
                            var mbFmt = Number(mb).toLocaleString('es-AR', { maximumFractionDigits: 0 });
                            elRest.textContent = mbFmt + ' MB';
                            var supera = mb > umbral;
                            elRest.className = supera ? 'badge badge-danger' : 'badge badge-success';
                            if (d.restauraciones_consultado_en) {
                                var fecha = new Date(d.restauraciones_consultado_en);
                                elRest.title = 'Consultado: ' + fecha.toLocaleString('es-AR') + (supera ? ' — Supera ' + umbral + ' MB' : '');
                            }
                            if (elRestIcon) elRestIcon.style.display = supera ? 'inline-block' : 'none';
                        }
                    }

                    var elRestGps = document.getElementById('workers-restauraciones-gps-mb');
                    var elRestGpsIcon = document.getElementById('workers-restauraciones-gps-icono');
                    if (elRestGps) {
                        var mbGps = d.restauraciones_gps_mb;
                        var umbralGps = d.restauraciones_gps_umbral_mb || 4000;
                        if (mbGps === null || typeof mbGps === 'undefined') {
                            elRestGps.textContent = 'sin datos';
                            elRestGps.className = 'badge badge-secondary';
                            elRestGps.title = 'Aún no se cacheó el valor (corre cada hora desde el schedule).';
                            if (elRestGpsIcon) elRestGpsIcon.style.display = 'none';
                        } else {
                            var mbGpsFmt = Number(mbGps).toLocaleString('es-AR', { maximumFractionDigits: 0 });
                            elRestGps.textContent = mbGpsFmt + ' MB';
                            var superaGps = mbGps > umbralGps;
                            elRestGps.className = superaGps ? 'badge badge-danger' : 'badge badge-success';
                            if (d.restauraciones_gps_consultado_en) {
                                var fechaGps = new Date(d.restauraciones_gps_consultado_en);
                                elRestGps.title = 'Consultado: ' + fechaGps.toLocaleString('es-AR') + (superaGps ? ' — Supera ' + umbralGps + ' MB' : '');
                            }
                            if (elRestGpsIcon) elRestGpsIcon.style.display = superaGps ? 'inline-block' : 'none';
                        }
                    }

                    // Desglose por tipo
                    var desgloseDiv  = document.getElementById('workers-desglose');
                    var desgloseList = document.getElementById('workers-desglose-lista');
                    if (d.jobs_por_tipo && d.jobs_por_tipo.length > 0) {
                        desgloseDiv.style.display = '';
                        desgloseList.innerHTML = '';
                        d.jobs_por_tipo.forEach(function(j) {
                            var span = document.createElement('span');
                            span.className = 'badge badge-light border';
                            span.innerHTML = '<strong>' + j.tipo + ':</strong> ' + j.total +
                                (j.procesando > 0 ? ' <span class="text-info">(' + j.procesando + ' procesando)</span>' : '');
                            desgloseList.appendChild(span);
                        });
                    } else {
                        desgloseDiv.style.display = 'none';
                    }

                    // Timestamp
                    var hora = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    var el = document.getElementById('workers-ultima-actualizacion');
                    if (el) el.textContent = 'Actualizado: ' + hora;

                    // Guardar ficheros restaurados GPS para el modal
                    _restauradasGpsData = d.restauraciones_gps_restauradas || [];
                })
                .catch(function() {
                    var dot = document.getElementById('workers-dot');
                    var lbl = document.getElementById('workers-label');
                    if (dot) dot.style.background = '#f59e0b';
                    if (lbl) { lbl.className = 'badge badge-warning'; lbl.textContent = 'Error'; }
                });
        }

        verificar();
        setInterval(verificar, 60000);

        function setEstadoBotonRestauraciones(estado, sufijo) {
            var btn = document.getElementById('btn-refresh-restauraciones' + (sufijo || ''));
            var icon = document.getElementById('icon-refresh-restauraciones' + (sufijo || ''));
            if (!btn || !icon) return;
            btn.classList.remove('estado-consultando', 'estado-success', 'estado-error');
            icon.className = '';
            switch (estado) {
                case 'consultando':
                    btn.classList.add('estado-consultando');
                    icon.className = 'fas fa-hourglass-half fa-spin';
                    btn.disabled = true;
                    break;
                case 'success':
                    btn.classList.add('estado-success');
                    icon.className = 'fas fa-check';
                    btn.disabled = false;
                    setTimeout(function() { setEstadoBotonRestauraciones('idle', sufijo); }, 1500);
                    break;
                case 'error':
                    btn.classList.add('estado-error');
                    icon.className = 'fas fa-exclamation-triangle';
                    btn.disabled = false;
                    setTimeout(function() { setEstadoBotonRestauraciones('idle', sufijo); }, 2000);
                    break;
                case 'idle':
                default:
                    icon.className = 'fas fa-sync-alt';
                    btn.disabled = false;
                    break;
            }
            icon.id = 'icon-refresh-restauraciones' + (sufijo || '');
        }

        function refreshRestauraciones(sufijo, ruta, campoConsultadoEn) {
            var btn = document.getElementById('btn-refresh-restauraciones' + (sufijo || ''));
            var el = document.getElementById('workers-restauraciones' + (sufijo || '') + '-mb');
            if (!btn || !el) return;
            setEstadoBotonRestauraciones('consultando', sufijo);
            el.textContent = '...';
            el.className = 'badge badge-secondary';

            fetch(ruta, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(function(r) {
                return r.json().then(function(d) { return { status: r.status, body: d }; });
            })
            .then(function(res) {
                var d = res.body;
                if (res.status === 429) {
                    el.textContent = 'esperá';
                    el.className = 'badge badge-warning';
                    el.title = 'Demasiadas consultas seguidas. Esperá un minuto.';
                    setEstadoBotonRestauraciones('error', sufijo);
                    return;
                }
                if (!d.ok) {
                    el.textContent = 'error';
                    el.className = 'badge badge-danger';
                    el.title = d.error || 'Error desconocido';
                    setEstadoBotonRestauraciones('error', sufijo);
                    return;
                }

                el.textContent = 'consultando...';
                el.className = 'badge badge-info';
                el.title = d.mensaje || 'Consulta encolada';

                // Polling progresivo: hasta 60s, cada 4s, hasta que consultado_en cambie.
                var baseline = d.consultado_en_anterior;
                var intentosMax = 15;
                var intento = 0;
                var intervalId = setInterval(function() {
                    intento++;
                    fetch('{{ route("api.infraestructura.workers-status") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            var nuevo = data[campoConsultadoEn];
                            if (nuevo && nuevo !== baseline) {
                                clearInterval(intervalId);
                                setEstadoBotonRestauraciones('success', sufijo);
                                verificar();
                            } else if (intento >= intentosMax) {
                                clearInterval(intervalId);
                                el.textContent = 'tarda más de lo normal';
                                el.className = 'badge badge-warning';
                                el.title = 'El worker puede estar detenido o la consulta está demorando. Revisá el log.';
                                setEstadoBotonRestauraciones('error', sufijo);
                            }
                        })
                        .catch(function() { /* siguiente intento */ });
                }, 4000);
            })
            .catch(function() {
                el.textContent = 'error';
                el.className = 'badge badge-danger';
                setEstadoBotonRestauraciones('error', sufijo);
            });
        }

        // Bind del botón (la función está dentro de la IIFE, no en scope global)
        var btnRefresh = document.getElementById('btn-refresh-restauraciones');
        if (btnRefresh) btnRefresh.addEventListener('click', function() {
            refreshRestauraciones('', '{{ route('api.infraestructura.refresh-restauraciones') }}', 'restauraciones_consultado_en');
        });
        var btnRefreshGps = document.getElementById('btn-refresh-restauraciones-gps');
        if (btnRefreshGps) btnRefreshGps.addEventListener('click', function() {
            refreshRestauraciones('-gps', '{{ route('api.infraestructura.refresh-restauraciones-gps') }}', 'restauraciones_gps_consultado_en');
        });

        // ── Datos de ficheros restaurados GPS (actualizados por el polling) ──
        var _restauradasGpsData = [];

        function escHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }

        function abrirModalRestauradas() {
            var datos = _restauradasGpsData;
            var tbody = document.getElementById('restauradas-modal-body');
            var totalSpan = document.getElementById('restauradas-modal-total');
            if (!tbody || !totalSpan) return;

            totalSpan.textContent = datos.length;

            if (!datos || datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay ficheros restaurados aún</td></tr>';
                $('#modal-restauradas').modal('show');
                return;
            }

            var html = '';
            datos.forEach(function(f) {
                var name = escHtml(f.nombre_fichero) || '—';
                var fi   = escHtml(f.fecha_inicio) || '—';
                var ff   = escHtml(f.fecha_fin) || '—';
                var loc  = escHtml(f.localizacion) || '—';
                html += '<tr>' +
                    '<td title="' + name + '">' + name + '</td>' +
                    '<td>' + fi + '</td>' +
                    '<td>' + ff + '</td>' +
                    '<td>' + loc + '</td>' +
                    '</tr>';
            });
            tbody.innerHTML = html;
            $('#modal-restauradas').modal('show');
        }

        var btnVerGps = document.getElementById('btn-ver-restauradas-gps');
        if (btnVerGps) btnVerGps.addEventListener('click', abrirModalRestauradas);
    })();
</script>
@endpush
