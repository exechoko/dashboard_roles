<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#06101f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ingresar · C.A.R. 911 Móvil</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('img/pwa-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pwa-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">

    <style>
        :root {
            --auth-bg: #06101f;
            --auth-surface: rgba(8, 27, 50, .86);
            --auth-border: rgba(0, 229, 255, .24);
            --auth-text: #eaf6ff;
            --auth-muted: rgba(234, 246, 255, .6);
            --auth-cyan: #00e5ff;
            --auth-violet: #8b5cf6;
            --auth-danger: #ff355d;
            --auth-success: #00f2a6;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            overflow-x: hidden;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: max(1.5rem, env(safe-area-inset-top)) 1.25rem max(1.5rem, env(safe-area-inset-bottom));
            background:
                radial-gradient(60% 40% at 15% 0%, rgba(0, 229, 255, .16), transparent 60%),
                radial-gradient(55% 35% at 100% 100%, rgba(139, 92, 246, .16), transparent 60%),
                var(--auth-bg);
            color: var(--auth-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .m-login {
            width: 100%;
            max-width: 380px;
            background: var(--auth-surface);
            border: 1px solid var(--auth-border);
            border-radius: 22px;
            padding: 2rem 1.6rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .45), inset 0 0 40px rgba(0, 229, 255, .03);
            backdrop-filter: blur(6px);
        }

        .m-login__logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .m-login__logo img {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            box-shadow: 0 0 24px rgba(0, 229, 255, .25);
        }

        .m-login__title {
            text-align: center;
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: -.02em;
            margin: 0 0 .3rem;
        }

        .m-login__subtitle {
            text-align: center;
            color: var(--auth-muted);
            font-size: .85rem;
            line-height: 1.5;
            margin: 0 0 1.4rem;
        }

        .m-login__status {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(0, 242, 166, .08);
            border: 1px solid rgba(0, 242, 166, .18);
            color: var(--auth-muted);
            margin-bottom: 1.4rem;
            font-size: .8rem;
        }

        .m-login__status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--auth-success);
            box-shadow: 0 0 12px rgba(0, 242, 166, .8);
            flex: 0 0 auto;
        }

        .m-login__field { margin-bottom: 1.1rem; }

        .m-login__field label {
            display: block;
            color: rgba(234, 246, 255, .82);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .m-login__field input {
            width: 100%;
            height: 50px;
            font-size: 16px;
            padding: 0 16px;
            background: rgba(3, 12, 24, .74);
            border: 1px solid var(--auth-border);
            border-radius: 14px;
            color: #ffffff;
        }

        .m-login__field input::placeholder { color: rgba(234, 246, 255, .38); }

        .m-login__field input:focus {
            outline: none;
            border-color: var(--auth-cyan);
            box-shadow: 0 0 0 3px rgba(0, 229, 255, .16);
        }

        .m-login__password-wrap { position: relative; }

        .m-login__password-wrap input { padding-right: 52px; }

        .m-login__toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(0, 0, 0, .4);
            border: 1px solid rgba(0, 229, 255, .28);
            color: #ffffff;
            font-size: 15px;
        }

        .m-login__submit {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--auth-cyan), var(--auth-violet));
            color: #ffffff;
            font-weight: 800;
            font-size: .95rem;
            letter-spacing: .03em;
            box-shadow: 0 0 26px rgba(0, 229, 255, .28);
            margin-top: .3rem;
        }

        .m-login__submit:active { transform: translateY(1px); }

        .m-login__help {
            text-align: center;
            color: rgba(234, 246, 255, .48);
            font-size: .78rem;
            line-height: 1.6;
            margin-top: 1.3rem;
        }

        .m-login__errors {
            border: 1px solid rgba(255, 53, 93, .3);
            border-radius: 14px;
            background: rgba(255, 53, 93, .12);
            color: #ffdce4;
            padding: 12px 14px;
            font-size: .82rem;
            margin-bottom: 1.2rem;
        }

        .m-login__errors div + div { margin-top: 4px; }
    </style>
</head>

<body>
    <div class="m-login">
        <div class="m-login__logo">
            <img src="{{ asset('img/logo.png') }}" alt="C.A.R. 911">
        </div>
        <h1 class="m-login__title">C.A.R. 911 Móvil</h1>
        <p class="m-login__subtitle">Consulta de flota, cámaras, mapa y eventos CECOCO desde el celular.</p>

        <div class="m-login__status">Canal cifrado listo para iniciar sesión</div>

        @if ($errors->any())
            <div class="m-login__errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('movil.login') }}">
            @csrf

            <div class="m-login__field">
                <label for="email">Correo institucional</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="operador@car911.local" required autofocus autocomplete="username">
            </div>

            <div class="m-login__field">
                <label for="password">Contraseña</label>
                <div class="m-login__password-wrap">
                    <input type="password" id="password" name="password"
                        placeholder="Ingresá tu clave de acceso" required autocomplete="current-password">
                    <button type="button" class="m-login__toggle" id="movilTogglePassword" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye" id="movilPasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="m-login__submit">Acceder al sistema</button>

            <p class="m-login__help">
                Si no contás con credenciales, solicitá autorización al área responsable del C.A.R. 911.
            </p>
        </form>
    </div>

    <script>
        document.getElementById('movilTogglePassword').addEventListener('click', function () {
            var field = document.getElementById('password');
            var icon = document.getElementById('movilPasswordIcon');
            var isPassword = field.getAttribute('type') === 'password';
            field.setAttribute('type', isPassword ? 'text' : 'password');
            icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    </script>
</body>

</html>
