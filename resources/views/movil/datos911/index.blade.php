@extends('layouts.movil')

@section('title', 'Datos del 911')
@section('back', route('movil.index'))

@section('content')
    <div class="m-card__subtitle" style="margin-bottom:.8rem; display:flex; justify-content:space-between; align-items:center;">
        <span>Resumen operativo</span>
        <small id="mDatos911Actualizado"></small>
    </div>

    <div class="m-empty" id="mDatos911Error" hidden>No se pudieron cargar los datos.</div>

    @can('ver-camara')
        <div class="m-section-title"><span class="material-symbols-outlined">videocam</span> Cámaras</div>
        <div class="m-stats">
            <div class="m-stat">
                <i class="fas fa-video"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mCamarasTotal">—</div>
                    <div class="m-stat__label">Cámaras</div>
                </div>
            </div>
            <div class="m-stat m-stat--success">
                <i class="fas fa-map-marker-alt"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mSitiosActivos">—</div>
                    <div class="m-stat__label">Sitios activos</div>
                </div>
            </div>
            <div class="m-stat m-stat--warning">
                <i class="fas fa-map-marker-alt"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mSitiosInactivos">—</div>
                    <div class="m-stat__label">Sitios inactivos</div>
                </div>
            </div>
        </div>
        <details class="m-detail" id="mCamarasPorTipoWrap" hidden>
            <summary>Cámaras por tipo</summary>
            <dl id="mCamarasPorTipo" style="margin:.6rem 0 0;"></dl>
        </details>
    @endcan

    @can('ver-personal')
        <div class="m-section-title"><span class="material-symbols-outlined">group</span> Personal</div>
        <div class="m-stats">
            <div class="m-stat">
                <i class="fas fa-user-check"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mFuncionariosActivos">—</div>
                    <div class="m-stat__label">Funcionarios activos</div>
                </div>
            </div>
            <div class="m-stat m-stat--warning">
                <i class="fas fa-calendar-times"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mFuncionariosLicencia">—</div>
                    <div class="m-stat__label">De licencia</div>
                </div>
            </div>
        </div>
    @endcan

    @canany(['ver-equipo', 'ver-antena'])
        <div class="m-section-title"><span class="material-symbols-outlined">satellite_alt</span> TETRA</div>

        @can('ver-equipo')
            <div class="m-stats">
                <div class="m-stat">
                    <i class="fas fa-satellite-dish"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mEquiposFuncionales">—</div>
                        <div class="m-stat__label">Equipos funcionales</div>
                    </div>
                </div>
                <div class="m-stat m-stat--warning">
                    <i class="fas fa-box"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mEquiposStock">—</div>
                        <div class="m-stat__label">En stock</div>
                    </div>
                </div>
            </div>
            <details class="m-detail" id="mEquiposFuncionalesPorTipoWrap" hidden>
                <summary>Funcionales por tipo</summary>
                <dl id="mEquiposFuncionalesPorTipo" style="margin:.6rem 0 0;"></dl>
            </details>
            <details class="m-detail" id="mEquiposStockPorTipoWrap" hidden>
                <summary>En stock por tipo</summary>
                <dl id="mEquiposStockPorTipo" style="margin:.6rem 0 0;"></dl>
            </details>
        @endcan

        @can('ver-antena')
            <div class="m-stats">
                <div class="m-stat">
                    <i class="fas fa-broadcast-tower"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mSbsTotal">—</div>
                        <div class="m-stat__label">Antenas SBS</div>
                    </div>
                </div>
                <div class="m-stat m-stat--success">
                    <i class="fas fa-broadcast-tower"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mSbsActivas">—</div>
                        <div class="m-stat__label">Activas</div>
                    </div>
                </div>
                <div class="m-stat m-stat--warning">
                    <i class="fas fa-broadcast-tower"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mSbsInactivas">—</div>
                        <div class="m-stat__label">Inactivas</div>
                    </div>
                </div>
            </div>
        @endcan
    @endcanany

    @can('ver-vehiculo')
        <div class="m-section-title"><span class="material-symbols-outlined">local_shipping</span> Patrullaje</div>
        <div class="m-stats">
            <div class="m-stat">
                <i class="fas fa-truck-pickup"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mPatrullajeCamionetas">—</div>
                    <div class="m-stat__label">Camionetas</div>
                </div>
            </div>
            <div class="m-stat">
                <i class="fas fa-car"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mPatrullajeAutos">—</div>
                    <div class="m-stat__label">Autos</div>
                </div>
            </div>
            <div class="m-stat">
                <i class="fas fa-motorcycle"></i>
                <div class="m-stat__body">
                    <div class="m-stat__value" id="mPatrullajeMotos">—</div>
                    <div class="m-stat__label">Motos (motopatrullas)</div>
                </div>
            </div>
        </div>
    @endcan

    @canany(['ver-reporte-llamadas-central-telefonica', 'ver-analitica-eventos-cecoco'])
        <div class="m-section-title"><span class="material-symbols-outlined">support_agent</span> CeCoCo</div>
        <div class="m-field" style="margin-bottom:.8rem;">
            <label for="mCecocoFechaInput">Fecha</label>
            <input type="date" id="mCecocoFechaInput" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="m-stats">
            @can('ver-reporte-llamadas-central-telefonica')
                <div class="m-stat">
                    <i class="fas fa-phone-alt"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mLlamadasHoy">—</div>
                        <div class="m-stat__label">Llamadas recibidas</div>
                    </div>
                </div>
            @endcan
            @can('ver-analitica-eventos-cecoco')
                <div class="m-stat m-stat--warning">
                    <i class="fas fa-user-lock"></i>
                    <div class="m-stat__body">
                        <div class="m-stat__value" id="mDetencionesHoy">—</div>
                        <div class="m-stat__label">Detenciones (aprox.)</div>
                    </div>
                </div>
            @endcan
        </div>
    @endcanany
@endsection

@section('scripts')
    <script>
        (function () {
            function escapar(texto) {
                var div = document.createElement('div');
                div.textContent = texto == null ? '' : String(texto);
                return div.innerHTML;
            }

            function pintar(id, valor) {
                var el = document.getElementById(id);
                if (el) {
                    el.textContent = valor === null || typeof valor === 'undefined' ? '—' : valor;
                }
            }

            function pintarPorTipo(wrapId, dlId, filas) {
                var wrap = document.getElementById(wrapId);
                var dl = document.getElementById(dlId);
                if (!wrap || !dl) {
                    return;
                }
                if (!filas || !filas.length) {
                    wrap.hidden = true;
                    return;
                }
                dl.innerHTML = filas.map(function (fila) {
                    return '<div class="m-detail__row"><dt>' + escapar(fila.tipo) + '</dt><dd>' + escapar(fila.total) + '</dd></div>';
                }).join('');
                wrap.hidden = false;
            }

            fetch('{{ route('movil.datos-911.datos-json') }}')
                .then(function (r) {
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status);
                    }
                    return r.json();
                })
                .then(function (d) {
                    if (d.camaras) {
                        pintar('mCamarasTotal', d.camaras.total);
                        pintar('mSitiosActivos', d.camaras.sitios_activos);
                        pintar('mSitiosInactivos', d.camaras.sitios_inactivos);
                        pintarPorTipo('mCamarasPorTipoWrap', 'mCamarasPorTipo', d.camaras.por_tipo);
                    }

                    if (d.personal) {
                        pintar('mFuncionariosActivos', d.personal.activos);
                        pintar('mFuncionariosLicencia', d.personal.de_licencia);
                    }

                    if (d.tetra) {
                        if (d.tetra.equipos) {
                            pintar('mEquiposFuncionales', d.tetra.equipos.funcionales_total);
                            pintar('mEquiposStock', d.tetra.equipos.en_stock_total);
                            pintarPorTipo('mEquiposFuncionalesPorTipoWrap', 'mEquiposFuncionalesPorTipo', d.tetra.equipos.funcionales_por_tipo);
                            pintarPorTipo('mEquiposStockPorTipoWrap', 'mEquiposStockPorTipo', d.tetra.equipos.en_stock_por_tipo);
                        }
                        if (d.tetra.sbs) {
                            pintar('mSbsTotal', d.tetra.sbs.total);
                            pintar('mSbsActivas', d.tetra.sbs.activas);
                            pintar('mSbsInactivas', d.tetra.sbs.inactivas);
                        }
                    }

                    if (d.patrullaje) {
                        pintar('mPatrullajeCamionetas', d.patrullaje.camionetas);
                        pintar('mPatrullajeAutos', d.patrullaje.autos);
                        pintar('mPatrullajeMotos', d.patrullaje.motos);
                    }

                    if (d.cecoco) {
                        pintarCecoco(d.cecoco);
                    }

                    var hora = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('mDatos911Actualizado').textContent = 'Actualizado: ' + hora;
                })
                .catch(function () {
                    document.getElementById('mDatos911Error').hidden = false;
                });

            function pintarCecoco(cecoco) {
                pintar('mLlamadasHoy', cecoco.llamadas_ultimo_dia);
                pintar('mDetencionesHoy', cecoco.detenciones_ultimo_dia);
            }

            var inputFecha = document.getElementById('mCecocoFechaInput');
            if (inputFecha) {
                inputFecha.addEventListener('change', function () {
                    if (!inputFecha.value) {
                        return;
                    }
                    pintar('mLlamadasHoy', '...');
                    pintar('mDetencionesHoy', '...');

                    fetch('{{ route('movil.datos-911.cecoco-json') }}?fecha=' + encodeURIComponent(inputFecha.value))
                        .then(function (r) {
                            if (!r.ok) {
                                throw new Error('HTTP ' + r.status);
                            }
                            return r.json();
                        })
                        .then(pintarCecoco)
                        .catch(function () {
                            pintar('mLlamadasHoy', '—');
                            pintar('mDetencionesHoy', '—');
                        });
                });
            }
        })();
    </script>
@endsection
