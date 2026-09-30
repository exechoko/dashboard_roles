{{-- Estilos compartidos de la Plataforma de Descargas. Usa las variables de tema
     (--bg-*, --text-*, --border-color, --accent-*) para respetar claro/oscuro. --}}
<style>
    .descargas-subnav {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-bottom: 1.25rem;
    }

    .descargas-subnav .nav-link {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .9rem;
        border-radius: .5rem;
        color: var(--text-secondary);
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        font-size: .875rem;
        font-weight: 500;
        transition: all .15s ease;
    }

    .descargas-subnav .nav-link:hover {
        color: var(--accent-primary);
        border-color: var(--accent-primary);
    }

    .descargas-subnav .nav-link.active {
        color: #fff;
        background-color: var(--accent-primary);
        border-color: var(--accent-primary);
    }

    .descargas-subnav .nav-link .badge {
        font-size: .7rem;
    }

    .descargas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 1rem;
    }

    .descarga-card {
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .descarga-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 .75rem 1.5rem var(--shadow) !important;
    }

    .descarga-card__check {
        position: absolute;
        top: .5rem;
        left: .5rem;
        z-index: 2;
    }

    .descarga-card__destacado {
        position: absolute;
        top: .5rem;
        right: .5rem;
        z-index: 2;
        width: 1.75rem;
        height: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: var(--accent-warning);
        color: #fff;
        font-size: .75rem;
        box-shadow: 0 2px 6px var(--shadow);
    }

    .descarga-card__preview {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 150px;
        background-color: var(--bg-tertiary);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-secondary);
    }

    .descarga-card__preview i {
        font-size: 3rem;
    }

    .descarga-card__preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .descarga-card__body {
        padding: .85rem 1rem .5rem;
    }

    .descarga-card__nombre {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: .4rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .descarga-card__nombre:hover {
        color: var(--accent-primary);
    }

    .descarga-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-bottom: .5rem;
    }

    .descarga-badge-categoria {
        background-color: var(--categoria-color, var(--accent-secondary));
        color: #fff;
    }

    .descarga-card__datos {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        font-size: .78rem;
        margin-bottom: 0;
    }

    .descarga-card__descripcion {
        font-size: .78rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: .4rem 0 0;
    }

    .descarga-card__acciones {
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .descargas-empty {
        text-align: center;
        padding: 3.5rem 1rem;
        color: var(--text-secondary);
    }

    .descargas-empty i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: .5;
    }

    .descargas-stat-icon {
        width: 3.25rem;
        height: 3.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: .75rem;
        font-size: 1.4rem;
    }
</style>
