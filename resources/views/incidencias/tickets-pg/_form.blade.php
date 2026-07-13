@php
    $ticketActual = $ticket ?? null;
    $codigoActual = old('codigo_interno', $ticketActual?->codigo_interno ?? $codigoSugerido ?? 'PG/--/---');

    $categoriasLista = $categorias ?? config('ticketera_categorias.categorias');
    $subsistemasLista = $subsistemas ?? config('ticketera_categorias.subsistemas');
    $camposCategoria = $camposPorCategoria ?? config('ticketera_categorias.campos', []);
    $subsistemaCategoria = $subsistemaPorCategoria ?? config('ticketera_categorias.subsistema_por_categoria', []);
    $subsistemasAgrupados = collect($subsistemasLista)->groupBy(fn ($item) => trim(explode(' - ', $item)[0]));

    $categoriaSeleccionada = old('tipo_equipo', $ticketActual?->tipo_equipo ?? 'Tetra');
    $subsistemaSeleccionado = old('subsistema', $ticketActual?->subsistema ?? 'Sist. TETRA - Comunicación - Por Terminales TETRA');

    $recursoSel = old('recurso_id', $ticketActual?->recurso_id);
    $equipoSel = old('equipo_id', $ticketActual?->equipo_id);
    $terminalSel = old('tipo_terminal_id', $ticketActual?->tipo_terminal_id);
    $oficinaSel = old('oficina', $ticketActual?->oficina);
    $modeloSel = old('modelo_equipo', $ticketActual?->modelo_equipo);
    $movilSel = old('movil', $ticketActual?->movil);
    $fechaInicioSel = old('fecha_inicio_falla', $ticketActual?->fecha_inicio_falla?->format('Y-m-d\TH:i'));
    $fechaFinSel = old('fecha_fin_falla', $ticketActual?->fecha_fin_falla?->format('Y-m-d\TH:i'));
    $camarasSeleccionadas = collect(old('camaras', collect($ticketActual?->camaras_afectadas ?? [])->pluck('id')->all()))
        ->map(fn ($id) => (int) $id)->all();
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Código interno</label>
            <input type="text" class="form-control" value="{{ $codigoActual }}" readonly id="codigo_interno_preview">
            <small class="form-text text-muted">Se confirma al guardar. No depende de la ticketera.</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Categoría *</label>
            <select name="tipo_equipo" id="tipo_equipo" class="form-control @error('tipo_equipo') is-invalid @enderror" required>
                @foreach($categoriasLista as $categoria)
                    <option value="{{ $categoria }}" @selected($categoriaSeleccionada === $categoria)>{{ $categoria }}</option>
                @endforeach
            </select>
            @error('tipo_equipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Prioridad *</label>
            <select name="prioridad" id="prioridad" class="form-control" required>
                @foreach($prioridades as $prioridad)
                    <option value="{{ $prioridad }}" @selected(old('prioridad', $ticketActual?->prioridad ?? 'Alto') === $prioridad)>{{ $prioridad }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Bloque TETRA (móvil de flota + TEI + modelo de terminal) ── --}}
<div class="row bloque-categoria" data-bloque="tetra" id="bloque-tetra">
    <div class="col-md-4">
        <div class="form-group">
            <label>Móvil (recurso de flota)</label>
            <select name="recurso_id" id="recurso_id" class="form-control" data-selected="{{ $recursoSel }}">
                <option value="">— Seleccionar —</option>
                @foreach($recursos as $recurso)
                    <option value="{{ $recurso->id }}" @selected($recursoSel == $recurso->id)>{{ $recurso->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>TEI (equipo asignado)</label>
            <select name="equipo_id" id="equipo_id" class="form-control" data-selected="{{ $equipoSel }}">
                <option value="">—</option>
            </select>
            <small class="form-text text-muted">Se completa según el móvil elegido.</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Modelo terminal</label>
            <select name="tipo_terminal_id" id="tipo_terminal_id" class="form-control">
                <option value="">— Seleccionar —</option>
                @foreach($tipoTerminales as $terminal)
                    <option value="{{ $terminal->id }}" @selected($terminalSel == $terminal->id)>{{ trim($terminal->marca . ' ' . $terminal->modelo) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── Bloque CÁMARAS (multiselect; cantidad de ítems = cámaras elegidas) ── --}}
<div class="form-group bloque-categoria" data-bloque="camaras" id="bloque-camaras">
    <label>Cámaras afectadas <small class="text-muted">(la cantidad de ítems se toma de las cámaras seleccionadas)</small></label>
    <select name="camaras[]" id="camaras" class="form-control" multiple>
        @foreach($camaras as $camara)
            <option value="{{ $camara->id }}" data-nombre="{{ $camara->nombre }}" data-tipo="{{ $camara->tipoCamara?->tipo }}" data-ip="{{ $camara->ip }}" @selected(in_array($camara->id, $camarasSeleccionadas, true))>{{ $camara->nombre }}@if($camara->tipoCamara?->tipo) ({{ $camara->tipoCamara->tipo }})@endif{{ $camara->ip ? ' — ' . $camara->ip : '' }}</option>
        @endforeach
    </select>
</div>

{{-- ── Bloque OFICINA (aire acondicionado) ── --}}
<div class="form-group bloque-categoria" data-bloque="oficina" id="bloque-oficina">
    <label>Oficina</label>
    <select name="oficina" id="oficina" class="form-control">
        <option value="">— Seleccionar —</option>
        @foreach($oficinas as $oficina)
            <option value="{{ $oficina }}" @selected($oficinaSel === $oficina)>{{ $oficina }}</option>
        @endforeach
    </select>
</div>

{{-- ── Bloque GENÉRICO (equipo/modelo y móvil como texto libre) ── --}}
<div class="row bloque-categoria" data-bloque="generico" id="bloque-generico">
    <div class="col-md-6">
        <div class="form-group">
            <label>Equipo / Modelo</label>
            <input type="text" name="modelo_equipo" id="modelo_equipo" class="form-control @error('modelo_equipo') is-invalid @enderror" value="{{ $modeloSel }}" placeholder="Notebook / UPS / Switch / etc.">
            @error('modelo_equipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Móvil / Identificador</label>
            <input type="text" name="movil" id="movil" class="form-control" value="{{ $movilSel }}" placeholder="Opcional">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label>Problema detectado *</label>
            <input type="text" name="problema_detectado" id="problema_detectado" class="form-control @error('problema_detectado') is-invalid @enderror" value="{{ old('problema_detectado', $ticketActual?->problema_detectado) }}" placeholder="fallas en modulaciones / sin GPS / no enciende" required>
            @error('problema_detectado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Periodo facturado</label>
            <input type="text" name="periodo_facturado" class="form-control" value="{{ old('periodo_facturado', $ticketActual?->periodo_facturado) }}" placeholder="P01">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Dependencia</label>
    <input type="text" name="dependencia" id="dependencia" class="form-control" value="{{ old('dependencia', $ticketActual?->dependencia) }}" placeholder="División 911">
</div>

{{-- ── Fechas de falla (siempre visibles; fin vacío = sin resolución) ── --}}
<div class="row" id="bloque-fechas">
    <div class="col-md-6">
        <div class="form-group">
            <label>Inicio de la falla</label>
            <input type="datetime-local" name="fecha_inicio_falla" id="fecha_inicio_falla" class="form-control @error('fecha_inicio_falla') is-invalid @enderror" value="{{ $fechaInicioSel }}">
            @error('fecha_inicio_falla')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Fin de la falla <small class="text-muted">(dejar vacío si no se corrigió)</small></label>
            <input type="datetime-local" name="fecha_fin_falla" id="fecha_fin_falla" class="form-control @error('fecha_fin_falla') is-invalid @enderror" value="{{ $fechaFinSel }}">
            @error('fecha_fin_falla')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Subsistema * <small class="text-muted">(define el % de falla / cálculo de multa)</small></label>
    <select name="subsistema" id="subsistema" class="form-control @error('subsistema') is-invalid @enderror" required>
        @foreach($subsistemasAgrupados as $nivel1 => $items)
            <optgroup label="{{ $nivel1 }}">
                @foreach($items as $subsistema)
                    <option value="{{ $subsistema }}" @selected($subsistemaSeleccionado === $subsistema)>{{ $subsistema }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    @error('subsistema')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Observaciones</label>
    <textarea name="observaciones" id="observaciones" class="form-control" rows="2">{{ old('observaciones', $ticketActual?->observaciones) }}</textarea>
</div>

<div class="form-group">
    <label>Asunto</label>
    <input type="text" name="asunto" id="asunto" class="form-control" value="{{ old('asunto', $ticketActual?->asunto) }}">
</div>

<div class="form-group">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="mb-0">Texto a enviar</label>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="redactarTicketPg()">
            <i class="fas fa-magic"></i> Redactar rápido
        </button>
    </div>
    <textarea name="texto_enviado" id="texto_enviado" class="form-control @error('texto_enviado') is-invalid @enderror" rows="7">{{ old('texto_enviado', $ticketActual?->texto_enviado) }}</textarea>
    @error('texto_enviado')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-text text-muted">El texto se autogenera pero es totalmente editable.</small>
</div>

<div class="custom-control custom-checkbox mb-3">
    <input type="checkbox" name="aplica_calculo" value="1" class="custom-control-input" id="aplica_calculo" @checked(old('aplica_calculo', $ticketActual?->aplica_calculo ?? true))>
    <label class="custom-control-label" for="aplica_calculo">Aplica a cálculo / multa</label>
</div>

@push('scripts')
<script>
    const CAMPOS_POR_CATEGORIA = @json($camposCategoria);
    const SUBSISTEMA_POR_CATEGORIA = @json($subsistemaCategoria);
    const RECURSOS_EQUIPOS = @json($recursosEquipos);

    function valor(id) {
        const elemento = document.getElementById(id);
        return elemento ? elemento.value.trim() : '';
    }

    function bloqueDeCategoria(categoria) {
        return CAMPOS_POR_CATEGORIA[categoria] || 'generico';
    }

    function actualizarBloques() {
        const bloque = bloqueDeCategoria(valor('tipo_equipo'));
        document.querySelectorAll('.bloque-categoria').forEach(function (el) {
            el.classList.toggle('d-none', el.getAttribute('data-bloque') !== bloque);
        });
    }

    function poblarEquipos(recursoId, equipoPreseleccionado) {
        const select = document.getElementById('equipo_id');
        if (!select) {
            return;
        }
        select.innerHTML = '<option value="">—</option>';
        const equipos = RECURSOS_EQUIPOS[recursoId] || [];
        equipos.forEach(function (equipo) {
            const option = document.createElement('option');
            option.value = equipo.equipo_id;
            option.textContent = equipo.tei || ('Equipo ' + equipo.equipo_id);
            option.setAttribute('data-modelo-id', equipo.tipo_terminal_id || '');
            select.appendChild(option);
        });

        let seleccionado = equipoPreseleccionado;
        if (!seleccionado && equipos.length > 0) {
            seleccionado = equipos[0].equipo_id;
        }
        if (seleccionado) {
            select.value = seleccionado;
        }
        aplicarModeloDelEquipo();
    }

    function aplicarModeloDelEquipo() {
        const opcion = document.querySelector('#equipo_id option:checked');
        const modeloId = opcion ? opcion.getAttribute('data-modelo-id') : '';
        if (modeloId) {
            $('#tipo_terminal_id').val(modeloId).trigger('change');
        }
    }

    function formatearFecha(valorFecha) {
        if (!valorFecha) {
            return '';
        }
        const fecha = new Date(valorFecha);
        if (isNaN(fecha.getTime())) {
            return valorFecha;
        }
        const p = (n) => String(n).padStart(2, '0');
        return `${p(fecha.getDate())}/${p(fecha.getMonth() + 1)}/${fecha.getFullYear()} ${p(fecha.getHours())}:${p(fecha.getMinutes())}`;
    }

    function textoDeFechas() {
        const inicio = formatearFecha(valor('fecha_inicio_falla'));
        if (inicio === '') {
            return '';
        }
        const fin = formatearFecha(valor('fecha_fin_falla'));
        return ` La falla se registro el ${inicio}` + (fin ? ` y fue resuelta el ${fin}.` : ' y continua sin resolucion.');
    }

    function textoDeObservaciones() {
        const observaciones = valor('observaciones');
        return observaciones !== '' ? ` Observaciones: ${observaciones}.` : '';
    }

    function camarasSeleccionadas() {
        return Array.from(document.querySelectorAll('#camaras option:checked')).map(function (opcion) {
            const nombre = opcion.getAttribute('data-nombre') || opcion.textContent.trim();
            const tipo = opcion.getAttribute('data-tipo');
            return tipo ? `${nombre} (${tipo})` : nombre;
        });
    }

    function redactarTicketPg() {
        const codigo = valor('codigo_interno_preview');
        const problema = valor('problema_detectado') || 'inconvenientes en su funcionamiento';
        const bloque = bloqueDeCategoria(valor('tipo_equipo'));
        const cierre = ' Se requiere verificacion tecnica, diagnostico y resolucion del inconveniente informado.';

        let asunto = '';
        let texto = '';

        if (bloque === 'camaras') {
            const listado = camarasSeleccionadas();
            const cantidad = listado.length;
            const sustantivo = cantidad === 1 ? 'la camara' : `las ${cantidad} camaras`;
            const verbo = cantidad === 1 ? 'la cual se encuentra' : 'las cuales se encuentran';
            const problemaAsunto = valor('problema_detectado') || 'Falla';
            const problemaTexto = problema ? problema.charAt(0).toLowerCase() + problema.slice(1) : problema;
            asunto = cantidad > 1 ? `${problemaAsunto} - ${cantidad} camaras` : `${problemaAsunto} - camara`;
            texto = `${codigo} Se solicita la revision de ${sustantivo}: ${listado.join(', ')}, ${verbo} ${problemaTexto}.`;
        } else if (bloque === 'tetra') {
            const modeloOpcion = document.querySelector('#tipo_terminal_id option:checked');
            const modelo = modeloOpcion && modeloOpcion.value ? modeloOpcion.textContent.trim() : 'terminal TETRA';
            const movilOpcion = document.querySelector('#recurso_id option:checked');
            const movil = movilOpcion && movilOpcion.value ? movilOpcion.textContent.trim() : '';
            const tei = valor('equipo_id') ? (document.querySelector('#equipo_id option:checked')?.textContent.trim() || '') : '';
            const dependencia = valor('dependencia');
            asunto = movil !== '' ? `Revision ${modelo} - Movil ${movil}` : `Revision ${modelo}`;
            texto = `${codigo} Se solicita la revision del equipo ${modelo}`;
            if (tei !== '') { texto += ` (TEI ${tei})`; }
            if (movil !== '') { texto += ` del movil ${movil}`; }
            if (dependencia !== '') { texto += ` de ${dependencia}`; }
            texto += `, el cual presenta ${problema}.`;
        } else if (bloque === 'oficina') {
            const oficinaOpcion = document.querySelector('#oficina option:checked');
            const oficina = oficinaOpcion && oficinaOpcion.value ? oficinaOpcion.textContent.trim() : '';
            asunto = oficina !== '' ? `Aire acondicionado - ${oficina}` : 'Aire acondicionado';
            texto = `${codigo} Se informa una falla en el equipo de climatizacion`;
            if (oficina !== '') { texto += ` de la oficina ${oficina}`; }
            texto += `, el cual presenta ${problema}.`;
        } else {
            const modelo = valor('modelo_equipo') || valor('tipo_equipo') || 'equipo';
            const movil = valor('movil');
            const dependencia = valor('dependencia');
            asunto = `Revision ${modelo}`;
            if (movil !== '') { asunto += ` - Movil ${movil}`; }
            texto = `${codigo} Se solicita la revision del equipo ${modelo}`;
            if (movil !== '') { texto += ` del movil ${movil}`; }
            if (dependencia !== '') { texto += ` de ${dependencia}`; }
            texto += `, el cual presenta ${problema}.`;
        }

        texto += textoDeFechas() + cierre + textoDeObservaciones();

        document.getElementById('asunto').value = asunto;
        document.getElementById('texto_enviado').value = texto.replace(/\s+/g, ' ').trim();
    }

    $(function () {
        $('#tipo_equipo, #prioridad, #subsistema, #recurso_id, #tipo_terminal_id, #oficina').select2({ width: '100%' });
        $('#camaras').select2({ width: '100%', placeholder: 'Elegí una o varias cámaras', closeOnSelect: false });

        actualizarBloques();

        const recursoInicial = document.getElementById('recurso_id');
        if (recursoInicial && recursoInicial.value) {
            poblarEquipos(recursoInicial.value, recursoInicial.getAttribute('data-selected'));
        }

        $('#tipo_equipo').on('change', function () {
            actualizarBloques();
            const subsistema = SUBSISTEMA_POR_CATEGORIA[this.value];
            if (subsistema) {
                $('#subsistema').val(subsistema).trigger('change');
            }
        });

        $('#recurso_id').on('change', function () {
            poblarEquipos(this.value, null);
        });

        $('#equipo_id').on('change', aplicarModeloDelEquipo);
    });
</script>
@endpush
