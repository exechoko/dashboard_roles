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

        function renderCamarasLibreNms(datos) {
            var total = document.getElementById('camaras-librenms-total');
            var boton = document.getElementById('btn-ver-camaras-librenms');
            var detalle = document.getElementById('camaras-librenms-detalle');
            var actualizado = document.getElementById('camaras-librenms-actualizado');

            if (!total) return;

            if (!datos || !datos.disponible) {
                total.textContent = 'sin lectura reciente';
                total.className = 'badge badge-warning';
                if (boton) boton.disabled = true;
                return;
            }

            var caidas = datos.caidas || 0;
            total.textContent = caidas > 0 ? (caidas + ' caída' + (caidas === 1 ? '' : 's') + ' de ' + datos.total) : 'las ' + datos.total + ' online';
            total.className = caidas > 0 ? 'badge badge-danger' : 'badge badge-success';

            if (boton) boton.disabled = caidas === 0;

            if (!detalle) return;

            if (caidas === 0) {
                detalle.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-2"></i>Todas las cámaras respondiendo.</div>';
            } else {
                detalle.innerHTML = (datos.offline || []).map(function(cam) {
                    return '<div class="d-flex justify-content-between align-items-center border-bottom py-2">'
                        + '<div><i class="fas fa-video-slash mr-2 text-danger"></i><strong>' + escapar(cam.nombre) + '</strong>'
                        + (cam.ip ? ' <small class="text-muted">' + escapar(cam.ip) + '</small>' : '') + '</div>'
                        + '<small class="text-muted">sin responder hace ' + escapar(cam.caida_hace || '?') + '</small>'
                        + '</div>';
                }).join('');
            }

            if (actualizado && datos.consultado_en) {
                actualizado.textContent = 'Lectura LibreNMS: ' + new Date(datos.consultado_en).toLocaleString('es-AR');
            }
        }

        function verificar() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    renderConflictosInventario(d.inventario_conflictos);
                    renderDiscrepanciasInventario(d.inventario_discrepancias);
                    renderCamarasLibreNms(d.camaras_librenms);

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

                    // Cola de backups/restore de la BD
                    var backupsDot = document.getElementById('backups-worker-dot');
                    var backupsLabel = document.getElementById('backups-worker-label');
                    if (backupsDot && backupsLabel) {
                        if (d.backups_worker_activo) {
                            backupsDot.style.background = '#22c55e';
                            backupsLabel.className = 'badge badge-success';
                            backupsLabel.textContent = 'Activo';
                        } else if (d.backups_pendientes > 0) {
                            backupsDot.style.background = '#ef4444';
                            backupsLabel.className = 'badge badge-danger';
                            backupsLabel.textContent = 'Detenido';
                        } else {
                            backupsDot.style.background = '#6b7280';
                            backupsLabel.className = 'badge badge-secondary';
                            backupsLabel.textContent = 'Sin trabajos';
                        }
                    }
                    var backupsPend = document.getElementById('backups-pendientes');
                    if (backupsPend) { backupsPend.textContent = d.backups_pendientes; backupsPend.className = d.backups_pendientes > 0 ? 'badge badge-warning' : 'badge badge-secondary'; }
                    var backupsProc = document.getElementById('backups-procesando');
                    if (backupsProc) { backupsProc.textContent = d.backups_procesando; backupsProc.className = d.backups_procesando > 0 ? 'badge badge-info' : 'badge badge-secondary'; }

                    // Cola de la Plataforma de Descargas
                    var descargasDot = document.getElementById('descargas-worker-dot');
                    var descargasLabel = document.getElementById('descargas-worker-label');
                    if (descargasDot && descargasLabel) {
                        if (d.descargas_worker_activo) {
                            descargasDot.style.background = '#22c55e';
                            descargasLabel.className = 'badge badge-success';
                            descargasLabel.textContent = 'Activo';
                        } else if (d.descargas_pendientes > 0) {
                            descargasDot.style.background = '#ef4444';
                            descargasLabel.className = 'badge badge-danger';
                            descargasLabel.textContent = 'Detenido';
                        } else {
                            descargasDot.style.background = '#6b7280';
                            descargasLabel.className = 'badge badge-secondary';
                            descargasLabel.textContent = 'Sin trabajos';
                        }
                    }
                    var descargasPend = document.getElementById('descargas-pendientes');
                    if (descargasPend) { descargasPend.textContent = d.descargas_pendientes; descargasPend.className = d.descargas_pendientes > 0 ? 'badge badge-warning' : 'badge badge-secondary'; }
                    var descargasProc = document.getElementById('descargas-procesando');
                    if (descargasProc) { descargasProc.textContent = d.descargas_procesando; descargasProc.className = d.descargas_procesando > 0 ? 'badge badge-info' : 'badge badge-secondary'; }

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
