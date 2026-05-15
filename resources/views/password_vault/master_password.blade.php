@extends('layouts.app')

@section('title', 'Acceso al Gestor de Contraseñas')

@push('style')
<style>
    .vault-lock-page {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1rem;
    }

    .vault-lock-card {
        width: 100%;
        max-width: 400px;
    }

    .vault-lock-card .card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 16px 48px rgba(0,0,0,.14);
        overflow: hidden;
    }

    /* ── Cabecera ── */
    .vault-header {
        background: linear-gradient(150deg, #1a2e6e 0%, #4e73df 100%);
        padding: 2rem 2rem 2.25rem;
        text-align: center;
    }

    /* Círculo del candado */
    .vault-lock-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .vault-lock-circle i {
        font-size: 1.6rem;
        color: #fff;
        line-height: 1;
    }

    /* Avatar */
    .vault-avatar {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,.45);
        object-fit: cover;
        display: block;
        margin: 0 auto .6rem;
    }

    .vault-initials {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,.45);
        background: rgba(255,255,255,.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: .6rem;
    }

    .vault-user-name {
        color: #fff;
        font-size: .97rem;
        font-weight: 600;
        margin: 0 0 .15rem;
        line-height: 1.3;
    }

    .vault-section-label {
        color: rgba(255,255,255,.65);
        font-size: .78rem;
        margin: 0;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    /* ── Cuerpo ── */
    .vault-body {
        padding: 2rem 2rem 1.75rem;
    }

    .vault-pw-label {
        font-size: .78rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: .4rem;
        display: block;
    }

    /* Input wrapper */
    .vault-input-wrap {
        position: relative;
    }

    .vault-input-wrap .form-control {
        border-radius: 10px;
        border: 1.5px solid #dee2e6;
        padding: .65rem 2.75rem .65rem 1rem;
        font-size: .97rem;
        height: auto;
        transition: border-color .2s, box-shadow .2s;
    }

    .vault-input-wrap .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78,115,223,.15);
    }

    .vault-input-wrap .form-control.is-invalid {
        border-color: #dc3545;
    }

    .vault-pw-toggle {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        color: #adb5bd;
        cursor: pointer;
        transition: color .15s;
        padding: 0;
    }

    .vault-pw-toggle:hover { color: #4e73df; }

    /* Alerta error */
    .vault-alert-error {
        display: flex;
        align-items: center;
        gap: .5rem;
        background: #fff5f5;
        color: #c53030;
        border-radius: 10px;
        padding: .6rem .9rem;
        font-size: .875rem;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(197,48,48,.2);
    }

    [data-theme="dark"] .vault-alert-error {
        background: rgba(220,53,69,.12);
        color: #f87171;
        border-color: rgba(220,53,69,.25);
    }

    /* Botón */
    .vault-btn {
        border-radius: 10px;
        padding: .7rem 1rem;
        font-weight: 600;
        font-size: .95rem;
        letter-spacing: .02em;
        background: linear-gradient(135deg, #4e73df 0%, #1a2e6e 100%);
        border: none;
        transition: opacity .2s, transform .1s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        width: 100%;
    }

    .vault-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
    .vault-btn:active:not(:disabled) { transform: translateY(0); }
    .vault-btn:disabled { opacity: .7; }

    /* Link volver */
    .vault-back {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .82rem;
        color: #adb5bd;
        text-decoration: none;
        transition: color .15s;
        margin-top: 1rem;
    }

    .vault-back:hover { color: #4e73df; text-decoration: none; }

    /* Mobile */
    @media (max-width: 480px) {
        .vault-lock-page { padding: 1rem .5rem; }
        .vault-header { padding: 1.75rem 1.5rem 2rem; }
        .vault-body { padding: 1.5rem 1.5rem 1.25rem; }
    }
</style>
@endpush

@section('content')
<div class="vault-lock-page">
    <div class="vault-lock-card">
        <div class="card">

            {{-- Cabecera --}}
            <div class="vault-header">
                <div class="vault-lock-circle">
                    <i class="fas fa-lock"></i>
                </div>

                <br>

                @if(auth()->user()->photo)
                    <img src="{{ asset(auth()->user()->photo) }}"
                         alt="Avatar"
                         class="vault-avatar">
                @else
                    <div class="vault-initials">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif

                <p class="vault-user-name">
                    {{ auth()->user()->name }} {{ auth()->user()->apellido }}
                </p>
                <p class="vault-section-label">Gestor de Contraseñas</p>
            </div>

            {{-- Formulario --}}
            <div class="vault-body">

                @if ($errors->any())
                    <div class="vault-alert-error">
                        <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                        <span>{{ $errors->first('master_password') }}</span>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('password-vault.verify-master-password') }}"
                      id="vaultLockForm">
                    @csrf

                    <div class="form-group mb-4">
                        <label class="vault-pw-label" for="vaultMasterPw">
                            Contraseña maestra
                        </label>
                        <div class="vault-input-wrap">
                            <input
                                type="password"
                                class="form-control @error('master_password') is-invalid @enderror"
                                id="vaultMasterPw"
                                name="master_password"
                                placeholder="••••••••"
                                autofocus
                                autocomplete="current-password"
                            >
                            <button type="button"
                                    class="vault-pw-toggle"
                                    id="pwToggleBtn"
                                    tabindex="-1"
                                    title="Mostrar/ocultar">
                                <i class="fas fa-eye" id="pwToggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                            class="btn btn-primary vault-btn"
                            id="vaultSubmitBtn">
                        <i class="fas fa-unlock-alt"></i>
                        <span>Acceder al gestor</span>
                    </button>
                </form>

                <div class="text-center">
                    <a href="javascript:history.back()" class="vault-back">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver atrás</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input  = document.getElementById('vaultMasterPw');
    var toggle = document.getElementById('pwToggleBtn');
    var icon   = document.getElementById('pwToggleIcon');
    var form   = document.getElementById('vaultLockForm');
    var btn    = document.getElementById('vaultSubmitBtn');

    toggle.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
        input.focus();
    });

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Verificando...</span>';
    });
})();
</script>
@endpush
