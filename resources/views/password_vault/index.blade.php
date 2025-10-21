@extends('layouts.app')

@section('title', 'Gestor de Contraseñas')

@push('style')
<style>
    .password-card {
        transition: all 0.3s;
        cursor: pointer;
    }
    .password-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .password-hidden {
        filter: blur(5px);
        user-select: none;
    }
    .favorite-star {
        color: #ffc107;
    }
</style>
@endpush

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-lock"></i> Gestor de Contraseñas</h1>
        <div class="section-header-button">
            <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Contraseña
            </a>
        </div>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
            <div class="breadcrumb-item">Contraseñas</div>
        </div>
    </div>

    <div class="section-body">
        {{-- Filtros --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('password-vault.index') }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Buscar</label>
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Buscar por nombre, usuario o URL..."
                                               value="{{ request('search') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tipo de Sistema</label>
                                        <select name="type" class="form-control">
                                            <option value="">Todos</option>
                                            @foreach($systemTypes as $key => $type)
                                                <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                                    {{ $type['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="favorites" name="favorites"
                                                   {{ request()->has('favorites') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="favorites">
                                                Solo Favoritos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                            <a href="{{ route('password-vault.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjetas de contraseñas --}}
        <div class="row">
            @forelse($passwords as $password)
            <div class="col-md-6 col-lg-4">
                <div class="card password-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="{{ $systemTypes[$password->system_type]['icon'] }} fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $password->system_name }}</h6>
                                    <small class="text-muted">{{ $systemTypes[$password->system_type]['label'] }}</small>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-link p-0 toggle-favorite"
                                        data-id="{{ $password->id }}">
                                    <i class="fas fa-star {{ $password->favorite ? 'favorite-star' : 'text-muted' }}"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Usuario</small>
                            <strong>{{ $password->username }}</strong>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Contraseña</small>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-sm password-field"
                                       value="{{ $password->password }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary copy-password"
                                            type="button" data-password="{{ $password->password }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if($password->url)
                        <div class="mb-2">
                            <small class="text-muted d-block">URL</small>
                            <a href="{{ $password->url }}" target="_blank" class="text-truncate d-block">
                                {{ $password->url }}
                            </a>
                        </div>
                        @endif

                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('password-vault.show', $password) }}"
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="{{ route('password-vault.edit', $password) }}"
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </div>
                                <form action="{{ route('password-vault.destroy', $password) }}"
                                      method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if($password->last_accessed_at)
                        <div class="mt-2">
                            <small class="text-muted">
                                Último acceso: {{ $password->last_accessed_at->diffForHumans() }}
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h5>No hay contraseñas guardadas</h5>
                        <p class="text-muted">Comienza agregando tu primera contraseña</p>
                        <a href="{{ route('password-vault.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Contraseña
                        </a>
                    </div>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        @if($passwords->hasPages())
        <div class="row">
            <div class="col-12">
                {{ $passwords->links() }}
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle mostrar/ocultar contraseña
        $('.toggle-password').click(function() {
            let input = $(this).closest('.input-group').find('.password-field');
            let icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Copiar contraseña
        $('.copy-password').click(function() {
            let password = $(this).data('password');
            navigator.clipboard.writeText(password).then(function() {
                iziToast.success({
                    title: 'Copiado',
                    message: 'Contraseña copiada al portapapeles',
                    position: 'topRight'
                });
            });
        });

        // Toggle favorito
        $('.toggle-favorite').click(function() {
            let btn = $(this);
            let id = btn.data('id');

            $.ajax({
                url: `/password-vault/${id}/toggle-favorite`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    let star = btn.find('i');
                    if (response.favorite) {
                        star.addClass('favorite-star').removeClass('text-muted');
                    } else {
                        star.removeClass('favorite-star').addClass('text-muted');
                    }
                }
            });
        });

        // Confirmar eliminación
        $('.delete-form').submit(function(e) {
            e.preventDefault();
            let form = this;

            swal({
                title: '¿Estás seguro?',
                text: 'Esta contraseña será eliminada permanentemente',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
