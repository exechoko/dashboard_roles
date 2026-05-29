<style>
    #sidebar-wrapper .sidebar-filter {
        padding: 0 20px 12px;
    }

    #sidebar-wrapper .sidebar-filter-field {
        position: relative;
        display: flex;
        align-items: center;
    }

    #sidebar-wrapper .sidebar-filter-icon {
        position: absolute;
        left: 12px;
        z-index: 1;
        color: #98a6ad;
        font-size: 12px;
        pointer-events: none;
    }

    #sidebar-filter-input {
        width: 100%;
        height: 36px;
        padding: 8px 34px;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 18px;
        background: #f7fafc;
        color: #34395e;
        font-size: 13px;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    #sidebar-filter-input:focus {
        border-color: #6777ef;
        background: #ffffff;
        box-shadow: 0 0 0 .2rem rgba(103, 119, 239, .12);
    }

    #sidebar-wrapper .sidebar-filter-clear {
        position: absolute;
        right: 7px;
        display: none;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #98a6ad;
        cursor: pointer;
        font-size: 11px;
    }

    #sidebar-wrapper .sidebar-filter-clear.is-visible {
        display: flex;
    }

    #sidebar-wrapper .sidebar-filter-clear:hover {
        color: #6777ef;
        background: rgba(103, 119, 239, .1);
    }

    #sidebar-wrapper .sidebar-filter-empty {
        display: none;
        padding: 8px 10px 0;
        color: #98a6ad;
        font-size: 12px;
    }

    #sidebar-wrapper .sidebar-filter-empty.is-visible {
        display: block;
    }

    #sidebar-wrapper .sidebar-filter-hidden {
        display: none !important;
    }

    body.sidebar-mini .main-sidebar .sidebar-filter {
        display: none;
    }

    [data-theme="dark"] #sidebar-filter-input {
        border-color: rgba(255, 255, 255, .12);
        background: rgba(255, 255, 255, .06);
        color: #f8fafc;
    }

    [data-theme="dark"] #sidebar-filter-input:focus {
        border-color: #6777ef;
        background: rgba(255, 255, 255, .1);
    }
</style>

<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <img class="navbar-brand-full app-header-logo" src="{{ asset('img/logo.ico') }}" width="45"
             alt="Logo 911">
        <a href="{{ url('/home') }}"></a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{{ url('/home') }}" class="small-sidebar-text">
            <img class="navbar-brand-full" src="{{ asset('img/logo.ico') }}" width="45px" alt=""/>
        </a>
    </div>
    <div class="sidebar-filter">
        <div class="sidebar-filter-field">
            <i class="fas fa-search sidebar-filter-icon" aria-hidden="true"></i>
            <input type="text" id="sidebar-filter-input" placeholder="Filtrar menu" autocomplete="off" aria-label="Filtrar menu">
            <button type="button" id="sidebar-filter-clear" class="sidebar-filter-clear" aria-label="Limpiar filtro">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div id="sidebar-filter-empty" class="sidebar-filter-empty">Sin resultados</div>
    </div>
    <ul class="sidebar-menu">
        @include('layouts.menu')
    </ul>
</aside>

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('sidebar-filter-input');
            const clearButton = document.getElementById('sidebar-filter-clear');
            const emptyMessage = document.getElementById('sidebar-filter-empty');
            const menu = document.querySelector('#sidebar-wrapper .sidebar-menu');

            if (!input || !clearButton || !emptyMessage || !menu) {
                return;
            }

            const normalizeText = function (value) {
                return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
            };

            const filterMenu = function () {
                const query = normalizeText(input.value);
                let visibleItems = 0;

                menu.querySelectorAll(':scope > li').forEach(function (item) {
                    const parentLink = item.querySelector(':scope > .nav-link');
                    const dropdown = item.querySelector(':scope > .dropdown-menu');
                    const parentMatches = parentLink && normalizeText(parentLink.textContent).includes(query);
                    let childMatches = 0;

                    if (dropdown) {
                        dropdown.querySelectorAll(':scope > li').forEach(function (child) {
                            const childMatchesQuery = normalizeText(child.textContent).includes(query);
                            const showChild = !query || parentMatches || childMatchesQuery;

                            child.classList.toggle('sidebar-filter-hidden', !showChild);

                            if (childMatchesQuery) {
                                childMatches++;
                            }
                        });
                    }

                    const showItem = !query || parentMatches || childMatches > 0;
                    item.classList.toggle('sidebar-filter-hidden', !showItem);

                    if (dropdown) {
                        if (query && showItem) {
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.style.display = item.classList.contains('active') ? 'block' : '';
                        }
                    }

                    if (showItem) {
                        visibleItems++;
                    }
                });

                clearButton.classList.toggle('is-visible', query.length > 0);
                emptyMessage.classList.toggle('is-visible', query.length > 0 && visibleItems === 0);
            };

            input.addEventListener('input', filterMenu);
            clearButton.addEventListener('click', function () {
                input.value = '';
                filterMenu();
                input.focus();
            });
        })();
    </script>
@endpush
