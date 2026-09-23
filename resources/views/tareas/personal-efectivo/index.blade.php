@extends('layouts.app')

@section('content')
    <div class="section">
        <div class="section-body">
            <div class="card shadow-sm border-0">
                <div class="card-header-modern">
                    <div class="card-header-left">
                        <div class="header-icon"><i class="fas fa-microchip"></i></div>
                        <div>
                            <h5 class="header-title">Personal Efectivo — {{ \App\Http\Controllers\PersonalController::SECCION_TECNICA }}</h5>
                            <small class="text-muted">
                                <span class="badge-total" id="total-funcionarios">0</span> funcionarios activos
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="row">
                        {{-- 🔵 IZQUIERDA: funcionarios y sus horarios --}}
                        <div class="col-lg-7">
                            <div id="funcionarios-list"></div>

                            <button class="btn btn-nuevo mt-2" onclick="generarMensaje()">
                                <i class="fas fa-comment-dots mr-1"></i> Generar Mensaje
                            </button>
                        </div>

                        {{-- 🔵 DERECHA: mensaje generado --}}
                        <div class="col-lg-5">
                            <label class="d-block mb-2">Mensaje generado</label>
                            <textarea id="mensaje" class="form-control mb-3"
                                style="height: 55vh; resize: vertical; overflow:auto;"></textarea>

                            <button id="whatsapp-web-btn" class="btn btn-success mr-2"
                                style="display:none;" onclick="enviarWhatsAppWeb()">
                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp Web
                            </button>

                            <button id="whatsapp-desktop-btn" class="btn btn-outline-success"
                                style="display:none;" onclick="enviarWhatsAppDesktop()">
                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp Desktop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .funcionario-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: .9rem 1rem;
            margin-bottom: 1rem;
            background: var(--bg-secondary);
        }
        .funcionario-card-header {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .75rem;
        }
        .jerarquia-chip {
            background: linear-gradient(135deg, #6777ef, #35199a);
            color: #fff;
            border-radius: 20px;
            padding: .15rem .7rem;
            font-size: .78rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .funcionario-nombre { color: var(--text-primary); font-weight: 700; }
        .badge-en-licencia {
            background: #f6c23e;
            color: #212529;
            border-radius: 20px;
            padding: .15rem .6rem;
            font-size: .75rem;
            font-weight: 600;
        }
        .horarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: .5rem;
        }
        .horario-chip {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .65rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 0;
            font-size: .83rem;
            color: var(--text-primary);
            cursor: pointer;
            background: var(--input-bg, transparent);
            transition: background .15s, border-color .15s;
        }
        .horario-chip:hover { background: rgba(103, 119, 239, .08); }
        .horario-chip:has(input:checked) {
            border-color: #6777ef;
            background: rgba(103, 119, 239, .12);
            font-weight: 600;
        }
        .horario-chip input { flex-shrink: 0; }
        .turno-manana { border-left: 4px solid #f6c23e; }
        .turno-tarde  { border-left: 4px solid #6777ef; }
        .turno-mixto  { border-left: 4px solid #36b9cc; }
        .turno-12h    { border-left: 4px solid #e74a3b; }
    </style>
    @endpush

    @push('scripts')
    <script>
        const horarios = [
            { nombre: "Personal turno de 12 horas (07:30 hs. a 19:30 hs.)", tipo: "12h" },
            { nombre: "Personal turno mañana (07:30 hs. a 13:00 hs.)", tipo: "manana" },
            { nombre: "Personal turno (07:30 hs. a 13:00 hs. y 17:30 hs. a 21:00 hs.)", tipo: "manana" },
            { nombre: "Personal turno tarde (17:30 hs. a 21:00 hs.)", tipo: "tarde" },
            { nombre: "Personal turno tarde (16:30 hs. a 21:00 hs.)", tipo: "tarde" },
            { nombre: "Personal turno (08:00 hs. a 12:00 hs.)", tipo: "manana" },
            { nombre: "Personal turno (08:00 hs. a 12:00 hs. y 18:00 hs. a 20:00 hs.)", tipo: "mixto" },
            { nombre: "Personal turno (09:00 hs. a 11:00 hs.)", tipo: "manana" }
        ];

        // 🔵 CARGAR FUNCIONARIOS (vienen de personal911, vía personal_secciones)
        window.cargarFuncionarios = async function () {

            const res = await fetch('/tareas/personal-efectivo', {
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();

            document.getElementById("total-funcionarios").textContent = data.length;

            const div = document.getElementById("funcionarios-list");
            div.innerHTML = "";

            data.forEach(p => {

                const f = `${p.jerarquia} ${p.apellido}, ${p.nombre}, L.P. Nº ${p.lp}`;

                const card = document.createElement("div");
                card.className = "funcionario-card";

                const header = document.createElement("div");
                header.className = "funcionario-card-header";
                header.innerHTML = `
                    <span class="jerarquia-chip">${p.jerarquia}</span>
                    <span class="funcionario-nombre">${p.apellido}, ${p.nombre}</span>
                    <span class="text-muted small">L.P. Nº ${p.lp}</span>
                    ${p.en_licencia ? '<span class="badge-en-licencia"><i class="fas fa-plane-departure mr-1"></i>En licencia</span>' : ''}
                `;
                card.appendChild(header);

                const grid = document.createElement("div");
                grid.className = "horarios-grid";

                horarios.forEach(h => {

                    let clase = "";
                    if (h.tipo === "manana") clase = "turno-manana";
                    else if (h.tipo === "tarde") clase = "turno-tarde";
                    else if (h.tipo === "mixto") clase = "turno-mixto";
                    else if (h.tipo === "12h") clase = "turno-12h";

                    const chip = document.createElement("label");
                    chip.className = `horario-chip ${clase}`;
                    chip.innerHTML = `
                        <input type="checkbox" class="asignacion"
                            data-funcionario="${f}" value="${h.nombre}">
                        <span>${h.nombre}</span>
                    `;

                    grid.appendChild(chip);
                });

                card.appendChild(grid);
                div.appendChild(card);
            });
        };

        // MENSAJE
        window.generarMensaje = function () {

            const checked = document.querySelectorAll(".asignacion:checked");

            if (checked.length === 0) {
                alert("Seleccionar al menos uno");
                return;
            }

            const funcionarios = [...new Set([...checked].map(c => c.dataset.funcionario))];

            const horarioCount = {};
            checked.forEach(c => {
                const h = c.value;
                if (!horarioCount[h]) horarioCount[h] = 0;
                horarioCount[h]++;
            });

            let msg = `Buenos días:\nFuerza efectiva del Personal de la Sección Técnica y Desarrollo ${new Date().toLocaleDateString('es-AR')}:\n\n`;

            msg += "Funcionarios:\n";
            funcionarios.forEach(f => msg += `• ${f}\n`);

            msg += "\nHorarios:\n";
            for (const [h, count] of Object.entries(horarioCount)) {
                msg += `${h}: ${count}\n`;
            }

            document.getElementById("mensaje").value = msg;

            document.getElementById("whatsapp-web-btn").style.display = "inline-block";
            document.getElementById("whatsapp-desktop-btn").style.display = "inline-block";
        };

        // WHATSAPP
        window.enviarWhatsAppWeb = function () {
            const txt = encodeURIComponent(document.getElementById("mensaje").value);
            window.open(`https://wa.me/5493434601937?text=${txt}`);
        };

        window.enviarWhatsAppDesktop = function () {
            const txt = encodeURIComponent(document.getElementById("mensaje").value);
            window.open(`whatsapp://send?phone=5493434601937&text=${txt}`);
        };

        document.addEventListener("DOMContentLoaded", cargarFuncionarios);
    </script>
    @endpush

@endsection
