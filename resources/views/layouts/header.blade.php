<form class="form-inline mr-auto" action="#">
    <ul class="navbar-nav mr-3">
        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>
</form>

<style>
    .banner-fecha-hora {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.35), rgba(16, 185, 129, 0.25));
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 6px 16px;
        color: #fff;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        margin: 0 auto;
    }

    .banner-fecha-hora__fila {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .banner-fecha-hora__izq {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .banner-fecha-hora__icono {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.12);
        flex: 0 0 auto;
    }

    .banner-fecha-hora__texto {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
        min-width: 0;
    }

    .banner-fecha-hora__fecha {
        font-size: 11px;
        opacity: 0.9;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .banner-fecha-hora__fecha-completa {
        display: inline;
    }

    .banner-fecha-hora__fecha-corta {
        display: none;
    }

    .banner-fecha-hora__hora {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .banner-fecha-hora__der {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }

    .banner-clima {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.12);
        min-height: 32px;
    }

    .banner-clima__temp {
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .banner-clima__desc {
        font-size: 10px;
        opacity: 0.9;
        white-space: nowrap;
    }

    /* Responsive: en móviles compactar */
    @media (max-width: 767px) {
        .banner-fecha-hora {
            padding: 4px 8px;
            border-radius: 8px;
            max-width: 70%;
            margin: 0;
            margin-right: -5px;
        }

        .banner-fecha-hora__fila {
            justify-content: center;
            gap: 8px;
        }

        .banner-fecha-hora__icono {
            width: 28px;
            height: 28px;
        }

        .banner-fecha-hora__fecha {
            font-size: 10px;
        }

        .banner-fecha-hora__hora {
            font-size: 14px;
        }

        .banner-fecha-hora__fecha-completa {
            display: none;
        }

        .banner-fecha-hora__fecha-corta {
            display: inline;
        }

        .banner-clima {
            padding: 2px 4px;
            gap: 2px;
            min-height: 24px;
        }

        .banner-clima__temp {
            font-size: 11px;
        }

        .banner-clima__desc {
            display: none;
        }

        .navbar-brand {
            display: none !important;
        }

        /* Show compact clock and date on mobile */
        .banner-fecha-hora__izq {
            display: flex !important;
            flex-direction: column;
            justify-content: center;
        }

        /* Hide "Hoy:" label on mobile to save space */
        .banner-divider {
            margin: 0 2px;
        }

        /* Adjust main navbar to prevent wrapping */
        .main-navbar {
            padding-right: 5px !important;
            padding-left: 5px !important;
        }
    }

</style>

<div class="banner-fecha-hora d-flex align-items-center">
    <div class="banner-fecha-hora__fila">
        <div class="banner-fecha-hora__izq">
            <div class="banner-fecha-hora__icono" aria-hidden="true">
                <i class="fas fa-clock"></i>
            </div>
            <div class="banner-fecha-hora__texto">
                <div class="banner-fecha-hora__fecha" id="banner-fecha">
                    <span class="banner-fecha-hora__fecha-completa">{{ \Carbon\Carbon::now()->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                    <span class="banner-fecha-hora__fecha-corta">{{ \Carbon\Carbon::now()->translatedFormat('D, d/m/Y') }}</span>
                </div>
                <div class="banner-fecha-hora__hora" id="banner-hora">
                    --:--:--
                </div>
            </div>
        </div>

        <div class="banner-fecha-hora__der">
            <div class="banner-clima" id="banner-clima" title="Clima actual">
                <i class="fas fa-cloud" id="clima-icono" aria-hidden="true"></i>
                <div style="display:flex; flex-direction:column; line-height:1.1;">
                    <div class="banner-clima__temp" id="clima-temp">--°C</div>
                    <div class="banner-clima__desc" id="clima-desc">Cargando clima…</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function actualizarReloj() {
            var ahora = new Date();
            var hora = pad2(ahora.getHours()) + ':' + pad2(ahora.getMinutes()) + ':' + pad2(ahora.getSeconds());

            var elHora = document.getElementById('banner-hora');
            if (elHora) {
                elHora.textContent = hora;
            }

            // Actualizar fecha completa
            var elFechaCompleta = document.querySelector('.banner-fecha-hora__fecha-completa');
            if (elFechaCompleta) {
                try {
                    var formatoCompleto = new Intl.DateTimeFormat('es-AR', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: '2-digit'
                    });
                    elFechaCompleta.textContent = formatoCompleto.format(ahora);
                } catch (error) {
                    // fallback
                }
            }

            // Actualizar fecha corta para móviles
            var elFechaCorta = document.querySelector('.banner-fecha-hora__fecha-corta');
            if (elFechaCorta) {
                try {
                    var formatoCorto = new Intl.DateTimeFormat('es-AR', {
                        weekday: 'short',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    });
                    elFechaCorta.textContent = formatoCorto.format(ahora);
                } catch (error) {
                    // fallback
                }
            }
        }

        function getClimaUiDeCodigo(codigo) {
            if (codigo === 0) return { icono: 'fa-sun', texto: 'Soleado' };
            if (codigo === 1) return { icono: 'fa-sun', texto: 'Mayormente despejado' };
            if (codigo === 2) return { icono: 'fa-cloud-sun', texto: 'Parcialmente nublado' };
            if (codigo === 3) return { icono: 'fa-cloud', texto: 'Nublado' };

            if (codigo === 45 || codigo === 48) return { icono: 'fa-smog', texto: 'Niebla' };

            if (codigo === 51 || codigo === 53 || codigo === 55) return { icono: 'fa-cloud-rain', texto: 'Llovizna' };
            if (codigo === 56 || codigo === 57) return { icono: 'fa-cloud-rain', texto: 'Llovizna helada' };

            if (codigo === 61 || codigo === 63 || codigo === 65) return { icono: 'fa-cloud-showers-heavy', texto: 'Lluvia' };
            if (codigo === 66 || codigo === 67) return { icono: 'fa-cloud-showers-heavy', texto: 'Lluvia helada' };

            if (codigo === 71 || codigo === 73 || codigo === 75) return { icono: 'fa-snowflake', texto: 'Nieve' };
            if (codigo === 77) return { icono: 'fa-snowflake', texto: 'Granizo' };

            if (codigo === 80 || codigo === 81 || codigo === 82) return { icono: 'fa-cloud-showers-heavy', texto: 'Chubascos' };
            if (codigo === 85 || codigo === 86) return { icono: 'fa-snowflake', texto: 'Chubascos de nieve' };

            if (codigo === 95) return { icono: 'fa-bolt', texto: 'Tormenta' };
            if (codigo === 96 || codigo === 99) return { icono: 'fa-bolt', texto: 'Tormenta con granizo' };

            return { icono: 'fa-cloud', texto: 'Clima' };
        }

        function setClimaEnUi(temperaturaC, codigoClima) {
            var elTemp = document.getElementById('clima-temp');
            var elDesc = document.getElementById('clima-desc');
            var elIcono = document.getElementById('clima-icono');

            if (!elTemp || !elDesc || !elIcono) {
                return;
            }

            var tempRedondeada = (typeof temperaturaC === 'number')
                ? Math.round(temperaturaC)
                : null;

            var ui = getClimaUiDeCodigo(codigoClima);

            elTemp.textContent = (tempRedondeada === null ? '--' : tempRedondeada) + '°C';
            elDesc.textContent = ui.texto;
            elIcono.className = 'fas ' + ui.icono;
        }

        async function obtenerClimaPorCoordenadas(lat, lon) {
            var url = 'https://api.open-meteo.com/v1/forecast'
                + '?latitude=' + encodeURIComponent(lat)
                + '&longitude=' + encodeURIComponent(lon)
                + '&current=temperature_2m,weather_code'
                + '&timezone=auto';

            var response = await fetch(url, { method: 'GET' });
            if (!response.ok) {
                throw new Error('Error al consultar clima: ' + response.status);
            }
            return await response.json();
        }

        async function actualizarClima() {
            var fallback = { lat: -31.73197, lon: -60.5238 }; // Coordenadas de ejemplo Pná, ER.

            function usarCoordenadas(lat, lon) {
                obtenerClimaPorCoordenadas(lat, lon)
                    .then(function (data) {
                        var temperatura = data && data.current ? data.current.temperature_2m : null;
                        var codigo = data && data.current ? data.current.weather_code : null;
                        setClimaEnUi(temperatura, codigo);
                    })
                    .catch(function () {
                        setClimaEnUi(null, null);
                        var elDesc = document.getElementById('clima-desc');
                        if (elDesc) {
                            elDesc.textContent = 'No disponible';
                        }
                    });
            }

            if (navigator.geolocation && window.isSecureContext) {
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        usarCoordenadas(pos.coords.latitude, pos.coords.longitude);
                    },
                    function () {
                        usarCoordenadas(fallback.lat, fallback.lon);
                    },
                    { enableHighAccuracy: false, timeout: 6000, maximumAge: 300000 }
                );
                return;
            }

            usarCoordenadas(fallback.lat, fallback.lon);
        }

        actualizarReloj();
        setInterval(actualizarReloj, 1000);
        actualizarClima();
        setInterval(actualizarClima, 10 * 60 * 1000);
    });
</script>

<ul class="navbar-nav navbar-right">

    @if(\Illuminate\Support\Facades\Auth::user())
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="Profile" src="{{ auth()->user()->photo ? asset(auth()->user()->photo) : asset('img/logo.png') }}"
                    class="rounded-circle mr-1 thumbnail-rounded user-thumbnail ">
                <div class="d-sm-none d-lg-inline-block">
                    {{\Illuminate\Support\Facades\Auth::user()->name}}
                </div>
            </a>

            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">
                    Perfil</div>
                <a class="dropdown-item has-icon edit-profile" href="#" data-id="{{ \Auth::id() }}">
                    <i class="fa fa-user"></i>Editar Perfil</a>
                <a class="dropdown-item has-icon" data-toggle="modal" data-target="#changePasswordModal" href="#"
                    data-id="{{ \Auth::id() }}"><i class="fa fa-lock"></i> Cambiar contraseña</a>
                <a class="dropdown-item has-icon" data-toggle="modal" data-target="#MasterPasswordModal" href="#">
                    <i class="fas fa-key"></i> Contraseña maestra
                    @if(auth()->user()->master_password)
                        <span class="badge badge-success badge-sm ml-1" style="font-size:9px;">ON</span>
                    @else
                        <span class="badge badge-secondary badge-sm ml-1" style="font-size:9px;">OFF</span>
                    @endif
                </a>
                <a href="{{ url('logout') }}" class="dropdown-item has-icon text-danger"
                    onclick="event.preventDefault(); localStorage.clear();  document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
                <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="d-none">
                    {{ csrf_field() }}
                </form>
            </div>
        </li>
    @else
        <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                {{-- <img alt="image" src="#" class="rounded-circle mr-1">--}}
                <div class="d-sm-none d-lg-inline-block">{{ __('messages.common.hello') }}</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">{{ __('messages.common.login') }}
                    / {{ __('messages.common.register') }}</div>
                <a href="{{ route('login') }}" class="dropdown-item has-icon">
                    <i class="fas fa-sign-in-alt"></i> {{ __('messages.common.login') }}
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('register') }}" class="dropdown-item has-icon">
                    <i class="fas fa-user-plus"></i> {{ __('messages.common.register') }}
                </a>
            </div>
        </li>
    @endif
</ul>