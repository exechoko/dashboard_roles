{{-- Card "Estado de procesos y Base de datos" (Workers/colas/BD). Compartida entre
     Infraestructura > Workers y Bases de Datos y el Dashboard (pestaña Novedades). --}}
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

            @can('ver-configuracion-backup')
                {{-- Cola de backups/restore de la BD --}}
                <div class="estado-procesos-bloque" title="Worker dedicado a backups/restore de la base de datos, Configuración del Sistema (php artisan queue:work backups --queue=backups).">
                    <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-database mr-1"></i><strong>Cola de Backups</strong></small>
                    <div class="d-flex align-items-center flex-wrap" style="gap:0.75rem;">
                        <span class="d-flex align-items-center">
                            <span id="backups-worker-dot" class="mr-2"
                                style="width:10px;height:10px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                            <span id="backups-worker-label" class="badge badge-secondary">Verificando...</span>
                        </span>
                        <span><small>Pendientes:</small> <span id="backups-pendientes" class="badge badge-secondary">—</span></span>
                        <span><small>Procesando:</small> <span id="backups-procesando" class="badge badge-secondary">—</span></span>
                        <a href="{{ route('configuracion.backups') }}" class="btn btn-xs btn-outline-primary" title="Ver backups">
                            <i class="fas fa-database"></i>
                        </a>
                    </div>
                </div>
            @endcan

            @can('administrar-plataforma-descargas')
                {{-- Cola de la Plataforma de Descargas --}}
                <div class="estado-procesos-bloque" title="Worker dedicado a la Plataforma de Descargas: mover archivos, comprimir ZIPs, generar QRs, notificaciones (php artisan queue:work descargas --queue=descargas).">
                    <small class="estado-procesos-titulo d-block mb-1"><i class="fas fa-download mr-1"></i><strong>Cola de Descargas</strong></small>
                    <div class="d-flex align-items-center flex-wrap" style="gap:0.75rem;">
                        <span class="d-flex align-items-center">
                            <span id="descargas-worker-dot" class="mr-2"
                                style="width:10px;height:10px;border-radius:50%;display:inline-block;background:#aaa;"></span>
                            <span id="descargas-worker-label" class="badge badge-secondary">Verificando...</span>
                        </span>
                        <span><small>Pendientes:</small> <span id="descargas-pendientes" class="badge badge-secondary">—</span></span>
                        <span><small>Procesando:</small> <span id="descargas-procesando" class="badge badge-secondary">—</span></span>
                        <a href="{{ route('descargas.admin.index') }}" class="btn btn-xs btn-outline-primary" title="Ver Descargas">
                            <i class="fas fa-download"></i>
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
