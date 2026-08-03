<style>
    /* Paleta de Armería: colores semánticos distintos a los badges bootstrap
       genéricos, para diferenciar claramente los tipos de movimiento. */
    .badge-armeria-en-servicio { background-color: #16a34a; color: #fff; }
    .badge-armeria-en-reparacion { background-color: #d97706; color: #fff; }
    .badge-armeria-de-baja { background-color: #475569; color: #fff; }
    .badge-armeria-division { background-color: #0d9488; color: #fff; }
    .badge-armeria-jefatura { background-color: #4f46e5; color: #fff; }

    .text-armeria-teal { color: #0d9488; }
    .text-armeria-indigo { color: #4f46e5; }
    .text-armeria-green { color: #16a34a; }
    .text-armeria-amber { color: #d97706; }
    .text-armeria-red { color: #dc2626; }
    .text-armeria-slate { color: #64748b; }

    .armeria-timeline {
        position: relative;
        padding-left: 34px;
    }
    .armeria-timeline::before {
        content: '';
        position: absolute;
        left: 14px;
        top: 4px;
        bottom: 4px;
        width: 2px;
        background: linear-gradient(180deg, #0d9488, #4f46e5, #16a34a, #d97706, #dc2626);
        opacity: .25;
        border-radius: 2px;
    }
    .armeria-timeline-item {
        position: relative;
        padding-bottom: 22px;
    }
    .armeria-timeline-item:last-child {
        padding-bottom: 0;
    }
    .armeria-timeline-marker {
        position: absolute;
        left: -34px;
        top: 2px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .8rem;
        box-shadow: 0 0 0 3px #fff, 0 1px 3px rgba(0,0,0,.15);
    }
    .armeria-timeline-marker.armeria-teal { background-color: #0d9488; }
    .armeria-timeline-marker.armeria-indigo { background-color: #4f46e5; }
    .armeria-timeline-marker.armeria-green { background-color: #16a34a; }
    .armeria-timeline-marker.armeria-amber { background-color: #d97706; }
    .armeria-timeline-marker.armeria-red { background-color: #dc2626; }
    .armeria-timeline-marker.armeria-slate { background-color: #64748b; }

    .armeria-timeline-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left-width: 3px;
        border-radius: .375rem;
        padding: .6rem .9rem;
    }
    .armeria-timeline-content.armeria-teal { border-left-color: #0d9488; }
    .armeria-timeline-content.armeria-indigo { border-left-color: #4f46e5; }
    .armeria-timeline-content.armeria-green { border-left-color: #16a34a; }
    .armeria-timeline-content.armeria-amber { border-left-color: #d97706; }
    .armeria-timeline-content.armeria-red { border-left-color: #dc2626; }
    .armeria-timeline-content.armeria-slate { border-left-color: #64748b; }

    .armeria-adjunto-card {
        border: 1px solid #e2e8f0;
        border-radius: .375rem;
        padding: .5rem;
        text-align: center;
    }
    .armeria-adjunto-card img {
        max-height: 100px;
        object-fit: cover;
        width: 100%;
        border-radius: .25rem;
    }

    .armeria-stat-card {
        border-radius: .5rem;
        color: #fff;
        padding: 1rem;
    }
    .armeria-stat-card.bg-teal { background: linear-gradient(135deg, #0d9488, #0f766e); }
    .armeria-stat-card.bg-indigo { background: linear-gradient(135deg, #4f46e5, #4338ca); }
    .armeria-stat-card.bg-amber { background: linear-gradient(135deg, #d97706, #b45309); }
    .armeria-stat-card.bg-slate { background: linear-gradient(135deg, #475569, #334155); }

    .btn-armeria-amber, .btn-armeria-amber:hover { background-color: #d97706; border-color: #d97706; color: #fff; }
    .btn-armeria-indigo, .btn-armeria-indigo:hover { background-color: #4f46e5; border-color: #4f46e5; color: #fff; }
    .btn-armeria-green, .btn-armeria-green:hover { background-color: #16a34a; border-color: #16a34a; color: #fff; }
</style>
