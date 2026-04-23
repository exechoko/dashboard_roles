@extends('layouts.app')

@section('content')
    <div class="section">
        <div class="section-header">
            <h1>Personal Efectivo</h1>
        </div>

        <div class="section-body">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">

                            <h4 class="mb-4">Informe de Personal - Sección Técnica</h4>

                            <div class="row">

                                {{-- 🔵 IZQUIERDA --}}
                                <div class="col-md-6">

                                    <h5>Funcionarios</h5>

                                        @can('crear-personal')
                                        <button class="btn btn-sm btn-primary mb-3"
                                            data-toggle="modal" data-target="#modalPersonal">
                                            + Agregar Funcionario
                                        </button>
                                        @endcan

                                    <div id="funcionarios-list"></div>

                                    <button class="btn btn-success mt-3" onclick="generarMensaje()">
                                        Generar Mensaje
                                    </button>

                                </div>

                                {{-- 🔵 DERECHA --}}
                                <div class="col-md-6">

                                    <h5>Mensaje Generado</h5>

                                    <textarea id="mensaje" class="form-control mb-3"
                                        style="height: 50vh; resize: vertical; overflow:auto;"></textarea>

                                    <button id="whatsapp-web-btn" class="btn btn-success me-2"
                                        style="display:none;" onclick="enviarWhatsAppWeb()">
                                        WhatsApp Web
                                    </button>

                                    <button id="whatsapp-desktop-btn" class="btn btn-secondary"
                                        style="display:none;" onclick="enviarWhatsAppDesktop()">
                                        WhatsApp Desktop
                                    </button>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🪟 MODAL --}}
    @can('crear-personal')
        <div id="modalPersonal" class="modal fade" data-backdrop="false" role="dialog">
            <div class="modal-dialog modal-md">
                <div class="modal-content">

                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">Nuevo Funcionario</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body" style="min-height: 200px">

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" id="nombre" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" id="apellido" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>LP</label>
                            <input type="text" id="lp" class="form-control" maxlength="5">
                        </div>

                        <div class="form-group">
                            <label>Jerarquía</label>
                            <input type="text" id="jerarquia" class="form-control">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" onclick="guardarFuncionario()">Guardar</button>
                    </div>

                </div>
            </div>
        </div>
    @endcan
    @php
        $puedeEditar = auth()->user()->can('editar-personal');
        $puedeBorrar = auth()->user()->can('borrar-personal');
    @endphp

    @push('scripts')
    <script>
        const puedeEditar = {{ $puedeEditar ? 'true' : 'false' }};
        const puedeBorrar = {{ $puedeBorrar ? 'true' : 'false' }};
    </script>

    <script>
        let editandoId = null;

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

        // 🔵 CARGAR FUNCIONARIOS
        window.cargarFuncionarios = async function () {

            const res = await fetch('/tareas/personal-efectivo', {
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();

            const div = document.getElementById("funcionarios-list");
            div.innerHTML = "";

            data.forEach(p => {

                const f = `${p.jerarquia} ${p.apellido}, ${p.nombre}, L.P. Nº ${p.lp}`;

                const container = document.createElement("div");
                container.className = "funcionario-box mb-3";

                const title = document.createElement("div");
                title.innerHTML = `<strong>${f}</strong>`;
                container.appendChild(title);

                const btnContainer = document.createElement("div");
                btnContainer.className = "d-flex gap-2 mb-2";

                if (puedeEditar) {
                    const btnEdit = document.createElement("button");
                    btnEdit.className = "btn btn-sm btn-warning mr-2";
                    btnEdit.textContent = "Editar";
                    btnEdit.onclick = () => editarFuncionario(p);
                    btnContainer.appendChild(btnEdit);
                }

                if (puedeBorrar) {
                    const btnDelete = document.createElement("button");
                    btnDelete.className = "btn btn-sm btn-danger";
                    btnDelete.textContent = "Eliminar";
                    btnDelete.onclick = () => eliminarFuncionario(p.id);
                    btnContainer.appendChild(btnDelete);
                }

                container.appendChild(btnContainer);

                horarios.forEach(h => {

                    let clase = "";
                    if (h.tipo === "manana") clase = "turno-manana";
                    else if (h.tipo === "tarde") clase = "turno-tarde";
                    else if (h.tipo === "mixto") clase = "turno-mixto";
                    else if (h.tipo === "12h") clase = "turno-12h";

                    const item = document.createElement("div");
                    item.className = `turno-box ${clase}`;

                    item.innerHTML = `
                        <label class="mb-0">
                            <input type="checkbox" class="asignacion"
                            data-funcionario="${f}" value="${h.nombre}">
                            ${h.nombre}
                        </label>
                        <span class="contador" id="count-${h.nombre.replace(/\s/g, '')}">0</span>
                    `;

                    container.appendChild(item);
                });

                div.appendChild(container);
            });
        };

        // EDITAR
        window.editarFuncionario = function (p) {
            editandoId = p.id;

            document.getElementById("nombre").value = p.nombre;
            document.getElementById("apellido").value = p.apellido;
            document.getElementById("lp").value = p.lp;
            document.getElementById("jerarquia").value = p.jerarquia;

            $('#modalPersonal').modal('show');
        };

        // GUARDAR
        window.guardarFuncionario = async function () {

            const data = {
                nombre: nombre.value,
                apellido: apellido.value,
                lp: lp.value,
                jerarquia: jerarquia.value
            };

            let url = '/tareas/personal-efectivo';
            let method = 'POST';

            if (editandoId !== null) {
                url = `/tareas/personal-efectivo/${editandoId}`;
                method = 'PUT';
            }

            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                cargarFuncionarios();
                $('#modalPersonal').modal('hide');

                nombre.value = "";
                apellido.value = "";
                lp.value = "";
                jerarquia.value = "";

                editandoId = null;
            } else {
                alert("Error al guardar");
            }
        };

        // ELIMINAR
        window.eliminarFuncionario = async function (id) {

            if (!confirm("¿Eliminar funcionario?")) return;

            const res = await fetch(`/tareas/personal-efectivo/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (res.ok) {
                cargarFuncionarios();
            } else {
                alert("Error al eliminar");
            }
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

            let msg = `Buenos días:\nFuerza efectiva del Personal de la Sección Técnica ${new Date().toLocaleDateString('es-AR')}:\n\n`;

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