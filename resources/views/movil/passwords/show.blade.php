@extends('layouts.movil')

@section('title', $passwordVault->system_name)
@section('back', route('movil.password-vault.index'))

@section('content')
    <div class="m-detail">
        <div class="m-card__title" style="margin-bottom:.6rem; display:flex; align-items:center; justify-content:space-between; gap:.5rem;">
            <span>
                <i class="{{ $systemTypes[$passwordVault->system_type]['icon'] ?? 'fas fa-key' }}"></i>
                {{ $passwordVault->system_name }}
            </span>
            @can('editar-clave')
                <button type="button" id="mFavoriteBtn" class="m-topbar__theme" style="padding:0;" data-id="{{ $passwordVault->id }}" aria-label="Marcar favorito">
                    <i class="fas fa-star" id="mFavoriteIcon" style="color:{{ $passwordVault->favorite ? 'var(--m-warning)' : 'var(--m-muted)' }};"></i>
                </button>
            @endcan
        </div>

        <dl style="margin:0;">
            <div class="m-detail__row"><dt>Tipo</dt><dd>{{ $systemTypes[$passwordVault->system_type]['label'] ?? $passwordVault->system_type }}</dd></div>
            <div class="m-detail__row"><dt>Usuario</dt><dd>{{ $passwordVault->username }}</dd></div>
            @if ($passwordVault->url)
                <div class="m-detail__row"><dt>URL</dt><dd><a href="{{ $passwordVault->url }}" target="_blank" rel="noopener">{{ $passwordVault->url }}</a></dd></div>
            @endif
            @if ($passwordVault->system_type === 'vpn')
                @if ($passwordVault->vpn_host)
                    <div class="m-detail__row"><dt>Host VPN</dt><dd>{{ $passwordVault->vpn_host }}</dd></div>
                @endif
                @if ($passwordVault->vpn_type)
                    <div class="m-detail__row"><dt>Tipo de VPN</dt><dd>{{ $passwordVault->vpn_type }}</dd></div>
                @endif
            @endif
            @if ($passwordVault->user_id !== auth()->id())
                <div class="m-detail__row"><dt>Compartida por</dt><dd>{{ $passwordVault->owner->name ?? '—' }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="m-detail">
        <label style="display:block; font-size:.78rem; color:var(--m-muted); margin-bottom:.4rem;">Contraseña</label>
        <div style="display:flex; gap:.5rem;">
            <input type="password" id="mPasswordField" value="{{ $passwordVault->password }}" readonly
                   style="flex:1; min-width:0; font-size:16px; padding:.55rem .7rem; border:1px solid var(--m-border); border-radius:.5rem; background:var(--m-surface); color:var(--m-text);">
            <button type="button" id="mTogglePassword" class="m-btn m-btn--outline" aria-label="Mostrar/ocultar contraseña">
                <i class="fas fa-eye" id="mTogglePasswordIcon"></i>
            </button>
            <button type="button" id="mCopyPassword" class="m-btn" aria-label="Copiar contraseña">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </div>

    @if ($passwordVault->system_type === 'vpn' && $passwordVault->vpn_preshared_key)
        <div class="m-detail">
            <label style="display:block; font-size:.78rem; color:var(--m-muted); margin-bottom:.4rem;">Clave precompartida (PSK)</label>
            <div style="display:flex; gap:.5rem;">
                <input type="password" id="mPskField" value="{{ $passwordVault->vpn_preshared_key }}" readonly
                       style="flex:1; min-width:0; font-size:16px; padding:.55rem .7rem; border:1px solid var(--m-border); border-radius:.5rem; background:var(--m-surface); color:var(--m-text);">
                <button type="button" id="mTogglePsk" class="m-btn m-btn--outline" aria-label="Mostrar/ocultar PSK">
                    <i class="fas fa-eye" id="mTogglePskIcon"></i>
                </button>
                <button type="button" id="mCopyPsk" class="m-btn" aria-label="Copiar PSK">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    @endif

    @if ($passwordVault->notes)
        <div class="m-detail">
            <label style="display:block; font-size:.78rem; color:var(--m-muted); margin-bottom:.4rem;">Notas</label>
            <div style="white-space:pre-line;">{{ $passwordVault->notes }}</div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    function setupSecretField(fieldId, toggleId, toggleIconId, copyId) {
        var field = document.getElementById(fieldId);
        var toggle = document.getElementById(toggleId);
        var icon = document.getElementById(toggleIconId);
        var copy = document.getElementById(copyId);

        if (!field) return;

        toggle.addEventListener('click', function () {
            var isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });

        copy.addEventListener('click', function () {
            var texto = field.value;
            var copiar = navigator.clipboard && navigator.clipboard.writeText
                ? navigator.clipboard.writeText(texto)
                : Promise.reject();

            copiar.catch(function () {
                field.type = 'text';
                field.select();
                document.execCommand('copy');
                field.type = 'password';
            }).finally(function () {
                var original = copy.innerHTML;
                copy.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(function () { copy.innerHTML = original; }, 1500);
            });
        });
    }

    setupSecretField('mPasswordField', 'mTogglePassword', 'mTogglePasswordIcon', 'mCopyPassword');
    setupSecretField('mPskField', 'mTogglePsk', 'mTogglePskIcon', 'mCopyPsk');

    var favoriteBtn = document.getElementById('mFavoriteBtn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function () {
            var icon = document.getElementById('mFavoriteIcon');
            var id = favoriteBtn.dataset.id;

            fetch('/password-vault/' + id + '/toggle-favorite', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    icon.style.color = data.favorite ? 'var(--m-warning)' : 'var(--m-muted)';
                })
                .catch(function () {});
        });
    }
})();
</script>
@endpush
