@extends('layouts.movil')

@section('title', 'Contraseña maestra')
@section('back', route('movil.index'))

@section('content')
    <div class="m-detail" style="text-align:center;">
        <i class="fas fa-lock" style="font-size:1.8rem; color:var(--m-accent);"></i>
        <p class="m-card__subtitle" style="margin:.6rem 0 0;">
            Ingresá tu contraseña maestra para entrar al gestor de contraseñas.
        </p>
    </div>

    @if ($errors->any())
        <div class="m-alert m-alert--danger">{{ $errors->first('master_password') }}</div>
    @endif

    <form method="POST" action="{{ route('movil.password-vault.verify-master-password') }}" id="mVaultLockForm">
        @csrf

        <div class="m-field" style="margin-bottom:1rem;">
            <label for="mVaultMasterPw">Contraseña maestra</label>
            <input type="password" id="mVaultMasterPw" name="master_password" placeholder="••••••••" autofocus autocomplete="current-password">
        </div>

        <button type="submit" class="m-btn" style="width:100%;">
            <i class="fas fa-unlock-alt"></i> Acceder al gestor
        </button>
    </form>
@endsection
