@extends('layouts.auth_app')
@section('title')
    C.A.R. 911 - Login
@endsection

@section('content')
    <style>
        .login-card {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            margin-bottom: 0;
        }

        .login-card .card-header {
            display: block;
            background: transparent !important;
            border: 0 !important;
            padding: 0 0 24px;
        }

        .login-card .card-header h4 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }

        .login-card .card-header p {
            color: rgba(234, 246, 255, 0.68);
            margin: 0;
            line-height: 1.55;
        }

        .login-card .card-body {
            padding: 0 !important;
        }

        .login-card label {
            color: rgba(234, 246, 255, 0.88);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .login-card .form-control {
            height: 52px;
            background: rgba(3, 12, 24, 0.74) !important;
            border: 1px solid rgba(0, 229, 255, 0.22) !important;
            border-radius: 16px !important;
            color: #ffffff !important;
            padding: 0 16px;
            box-shadow: inset 0 0 22px rgba(0, 229, 255, 0.04);
        }

        .login-card .form-control::placeholder {
            color: rgba(234, 246, 255, 0.42);
        }

        .login-card .form-control:focus {
            border-color: #00e5ff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 229, 255, 0.18), inset 0 0 22px rgba(0, 229, 255, 0.06) !important;
        }

        .login-card .form-group {
            margin-bottom: 22px;
        }

        .login-card .btn-primary {
            height: 54px;
            border: 0 !important;
            border-radius: 16px !important;
            background: linear-gradient(135deg, #00e5ff, #8b5cf6) !important;
            color: #ffffff !important;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            box-shadow: 0 0 28px rgba(0, 229, 255, 0.32) !important;
        }

        .login-card .btn-primary:hover,
        .login-card .btn-primary:focus {
            transform: translateY(-1px);
            box-shadow: 0 0 36px rgba(0, 229, 255, 0.44) !important;
        }

        .login-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(0, 242, 166, 0.08);
            border: 1px solid rgba(0, 242, 166, 0.18);
            color: rgba(234, 246, 255, 0.78);
            margin-bottom: 24px;
            font-size: 0.86rem;
        }

        .login-status::before {
            content: '';
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #00f2a6;
            box-shadow: 0 0 14px rgba(0, 242, 166, 0.78);
            flex: 0 0 auto;
        }

        .login-card .alert {
            border: 1px solid rgba(255, 53, 93, 0.3);
            border-radius: 16px;
            background: rgba(255, 53, 93, 0.12);
            color: #ffdce4;
        }

        .login-card .alert-success {
            border-color: rgba(0, 242, 166, 0.26);
            background: rgba(0, 242, 166, 0.1);
            color: #d7fff2;
        }

        .login-card .alert ul {
            padding: 12px 16px 12px 32px;
            margin: 0;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.42);
            border: 1px solid rgba(0, 229, 255, 0.28);
            cursor: pointer;
            color: #ffffff;
            z-index: 10;
            font-size: 16px;
            line-height: 1;
            box-shadow: 0 0 12px rgba(0, 229, 255, 0.16);
        }

        .password-toggle-btn:hover {
            color: #ffffff;
            background: rgba(0, 229, 255, 0.26);
            border-color: rgba(0, 229, 255, 0.48);
        }

        .password-toggle input[type="password"],
        .password-toggle input[type="text"] {
            padding-right: 56px;
        }

        .login-help {
            color: rgba(234, 246, 255, 0.54);
            font-size: 0.82rem;
            line-height: 1.6;
            margin-top: 18px;
            text-align: center;
        }
    </style>

    <div class="card card-primary login-card">
        <div class="card-header">
            <h4>Ingreso seguro</h4>
            <p>Autenticación para operadores y administradores del sistema.</p>
        </div>

        <div class="card-body">
            <div class="login-status">Canal cifrado listo para iniciar sesión</div>

            @if(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                @if ($errors->any())
                    <div class="alert alert-danger p-0">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="form-group">
                    <label for="email">Correo institucional</label>
                    <input aria-describedby="emailHelpBlock" id="email" type="email"
                        class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                        placeholder="operador@car911.local" tabindex="1"
                        value="{{ (Cookie::get('email') !== null) ? Cookie::get('email') : old('email') }}" autofocus
                        required>
                    <div class="invalid-feedback">
                        {{ $errors->first('email') }}
                    </div>
                </div>

                <div class="form-group">
                    <div class="d-block">
                        <label for="password" class="control-label">Contraseña</label>
                        <div class="float-right">
                            <a href="{{ route('password.request') }}" class="text-small">
                            </a>
                        </div>
                    </div>
                    <div class="password-toggle">
                        <input aria-describedby="passwordHelpBlock" id="password" type="password"
                            value="{{ (Cookie::get('password') !== null) ? Cookie::get('password') : null }}"
                            placeholder="Ingrese su clave de acceso"
                            class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                            tabindex="2" required>
                        <button type="button" class="password-toggle-btn" id="togglePassword">
                            <i class="fas fa-eye" id="passwordIcon" aria-hidden="true"></i>
                        </button>
                        @if($errors->has('password'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>
                </div>

                <!--div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="remember" class="custom-control-input" tabindex="3"
                                   id="remember"{{ (Cookie::get('remember') !== null) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="remember">Remember Me</label>
                        </div>
                    </div-->

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                        Acceder al sistema
                    </button>
                </div>

                <div class="login-help">
                    Si no cuenta con credenciales, solicite autorización al área responsable del C.A.R. 911.
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            togglePassword.addEventListener('click', function (e) {
                e.preventDefault();

                // Cambiar el tipo de input entre password y text
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);

                // Cambiar el ícono
                passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
        });
    </script>
@endsection
