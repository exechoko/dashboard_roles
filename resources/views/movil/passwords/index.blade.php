@extends('layouts.movil')

@section('title', 'Contraseñas')

@section('content')
    <form method="GET" action="{{ route('movil.password-vault.index') }}" class="m-filters">
        <div class="m-field">
            <input type="text" name="texto" value="{{ $texto }}" placeholder="Sistema, usuario o URL…">
        </div>

        <div class="m-filters__row">
            <div class="m-field">
                <label for="mPasswordsTipo">Tipo</label>
                <select id="mPasswordsTipo" name="tipo">
                    <option value="">Todos</option>
                    @foreach ($systemTypes as $key => $type)
                        <option value="{{ $key }}" @selected($tipo === $key)>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label style="display:flex; align-items:center; gap:.4rem; font-size:.85rem; color:var(--m-text);">
            <input type="checkbox" name="favoritos" value="1" @checked($soloFavoritos)>
            Solo favoritos
        </label>

        <button type="submit" class="m-btn"><i class="fas fa-search"></i> Buscar</button>
    </form>

    @if ($passwords->isEmpty())
        <div class="m-empty">
            <i class="fas fa-lock" style="font-size:1.6rem;"></i>
            <p>No se encontraron contraseñas.</p>
        </div>
    @else
        <div class="m-list">
            @foreach ($passwords as $password)
                <a href="{{ route('movil.password-vault.show', $password) }}" class="m-card">
                    <div class="m-card__title">
                        <i class="{{ $systemTypes[$password->system_type]['icon'] ?? 'fas fa-key' }}"></i>
                        {{ $password->system_name }}
                        @if ($password->favorite)
                            <i class="fas fa-star" style="color:var(--m-warning); font-size:.8rem;"></i>
                        @endif
                    </div>
                    <div class="m-card__subtitle">{{ $systemTypes[$password->system_type]['label'] ?? $password->system_type }} · {{ $password->username }}</div>
                    <div class="m-card__meta">
                        @if ($password->user_id !== auth()->id())
                            <span class="m-chip"><i class="fas fa-share-alt"></i> Compartida por {{ $password->owner->name ?? 'otro usuario' }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="m-pagination">
            {{ $passwords->links() }}
        </div>
    @endif
@endsection
