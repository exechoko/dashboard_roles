{{-- JS compartido por parte-moviles y parte-motos. Espera $tipo ('moviles' | 'motos'). --}}
<script>
(function() {
    const TIPO = @json($tipo);
    const inputFecha = document.getElementById('inputFecha');
    const inputHorario = document.getElementById('inputHorario');
    const inputInicio = document.getElementById('inputFechaInicio');
    const inputFin = document.getElementById('inputFechaFin');
    const inputGuardia = document.getElementById('inputGuardia');

    function pad(n) { return String(n).padStart(2, '0'); }

    function actualizarFechasPorHorario() {
        const fecha = inputFecha.value;
        const horario = inputHorario.value;
        if (!fecha || !horario) { return; }
        if (horario === '06_18') {
            inputInicio.value = fecha + 'T06:15';
            inputFin.value = fecha + 'T18:15';
        } else {
            const fin = new Date(fecha + 'T00:00');
            fin.setDate(fin.getDate() + 1);
            const finStr = fin.getFullYear() + '-' + pad(fin.getMonth() + 1) + '-' + pad(fin.getDate());
            inputInicio.value = fecha + 'T18:15';
            inputFin.value = finStr + 'T06:15';
        }
    }

    inputFecha.addEventListener('change', actualizarFechasPorHorario);
    inputHorario.addEventListener('change', actualizarFechasPorHorario);

    document.getElementById('btnCargarTurno').addEventListener('click', function() {
        const params = new URLSearchParams({
            fecha: inputFecha.value,
            guardia: inputGuardia.value,
            horario: inputHorario.value,
            fecha_inicio: inputInicio.value,
            fecha_fin: inputFin.value,
        });
        window.location.search = params.toString();
    });

    document.getElementById('btnPreArmar').addEventListener('click', function() {
        const guardia = inputGuardia.value;
        if (!guardia) {
            iziToast.warning({ title: 'Falta la guardia', message: 'Seleccione la guardia antes de pre-armar.', position: 'topRight' });
            return;
        }
        const btn = this;
        btn.disabled = true;
        fetch('{{ route('flota-911.informes.parte-diario.pre-armar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tipo: TIPO, guardia: guardia, fecha_inicio: inputInicio.value }),
        })
        .then(function(r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function(data) {
            const gi = document.querySelector('textarea[name="guardia_interna"]');
            if (gi && !gi.value.trim() && data.guardia_interna) { gi.value = data.guardia_interna; }
            const lo = document.querySelector('textarea[name="licencia_ordinaria"]');
            if (lo && !lo.value.trim() && data.licencia_ordinaria) { lo.value = data.licencia_ordinaria; }
            Object.entries(data.novedades || {}).forEach(function(entry) {
                const el = document.querySelector('textarea[name="novedades[' + entry[0] + ']"]');
                if (el && !el.value.trim()) { el.value = entry[1]; }
            });
            iziToast.success({ title: 'Pre-armado', message: 'Se completaron guardia interna, licencias y novedades de sala.', position: 'topRight' });
        })
        .catch(function() {
            iziToast.error({ title: 'Error', message: 'No se pudo pre-armar el parte.', position: 'topRight' });
        })
        .finally(function() { btn.disabled = false; });
    });

    function setSelect2(el, valor) {
        if (!el) { return; }
        el.value = valor == null ? '' : String(valor);
        if (window.jQuery) { window.jQuery(el).trigger('change'); }
    }

    function rellenarDesdeUltimaGuardia(data) {
        Object.entries(data.novedades || {}).forEach(function(entry) {
            var ta = document.querySelector('textarea[name="novedades[' + entry[0] + ']"]');
            if (ta) { ta.value = entry[1] || ''; }
        });

        ['guardia_interna', 'licencia_ordinaria', 'novedades_pie'].forEach(function(campo) {
            var ta = document.querySelector('textarea[name="' + campo + '"]');
            if (ta && data[campo] != null) { ta.value = data[campo]; }
        });

        (data.asignaciones || []).forEach(function(asig) {
            var filas = document.querySelectorAll('input[name^="asignaciones["][name$="][asignacion_texto]"]');
            var puesto = false;
            for (var i = 0; i < filas.length && !puesto; i++) {
                var base = filas[i].name.replace('[asignacion_texto]', '');
                var grupo = (document.querySelector('[name="' + base + '[grupo]"]') || {}).value || '';
                var nombre = (document.querySelector('[name="' + base + '[nombre]"]') || {}).value || '';
                if (grupo === (asig.grupo || '') && nombre === (asig.nombre || '')) {
                    filas[i].value = asig.asignacion_texto || '';
                    puesto = true;
                }
            }
            for (var j = 0; j < filas.length && !puesto; j++) {
                var b2 = filas[j].name.replace('[asignacion_texto]', '');
                var gInput = document.querySelector('input[name="' + b2 + '[grupo]"]');
                var nInput = document.querySelector('input[name="' + b2 + '[nombre]"]');
                if (gInput && nInput && !nInput.value && !filas[j].value) {
                    gInput.value = asig.grupo || '';
                    nInput.value = asig.nombre || '';
                    filas[j].value = asig.asignacion_texto || '';
                    puesto = true;
                }
            }
        });

        Object.entries(data.recursos || {}).forEach(function(rEntry) {
            var rid = rEntry[0], r = rEntry[1];

            var estado = document.querySelector('select[name="recursos[' + rid + '][estado_dia]"]');
            if (estado && r.estado_dia) {
                estado.value = r.estado_dia;
                var motivo = document.getElementById('motivo' + rid);
                if (motivo) { motivo.style.display = r.estado_dia === 'circula' ? 'none' : ''; }
            }

            var zona = document.querySelector('select[name="recursos[' + rid + '][zona]"]');
            if (zona) { zona.value = r.zona || ''; }

            var ht = document.querySelector('input[name="recursos[' + rid + '][ht]"]');
            if (ht) { ht.value = r.ht || ''; }

            var motivoInput = document.getElementById('motivo' + rid);
            if (motivoInput) { motivoInput.value = r.motivo || ''; }

            var dot = document.getElementById('dotacion' + rid);
            if (dot && window.jQuery) {
                (r.dotacion_detalle || []).forEach(function(p) {
                    if (!dot.querySelector('option[value="' + p.id + '"]')) {
                        dot.appendChild(new Option(p.text, p.id, true, true));
                    }
                });
                window.jQuery(dot).val((r.dotacion || []).map(String)).trigger('change');
            }

            var choferSel = document.querySelector('select[name="recursos[' + rid + '][chofer_id]"]');
            if (choferSel && r.chofer_detalle && !choferSel.querySelector('option[value="' + r.chofer_detalle.id + '"]')) {
                choferSel.appendChild(new Option(r.chofer_detalle.text, r.chofer_detalle.id, true, true));
            }
            setSelect2(choferSel, r.chofer_id || '');
        });
    }

    document.getElementById('btnDesdeGuardia').addEventListener('click', function() {
        var guardia = inputGuardia.value;
        if (!guardia) {
            iziToast.warning({ title: 'Falta la guardia', message: 'Seleccione la guardia antes de traer el último parte.', position: 'topRight' });
            return;
        }
        var btn = this;
        btn.disabled = true;
        fetch('{{ route('flota-911.informes.parte-diario.desde-guardia') }}?tipo=' + TIPO + '&guardia=' + encodeURIComponent(guardia), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function(r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function(data) {
            if (!data.encontrado) {
                iziToast.info({ title: 'Sin antecedentes', message: 'No hay partes previos de esta guardia.', position: 'topRight' });
                return;
            }
            rellenarDesdeUltimaGuardia(data);
            iziToast.success({
                title: 'Cargado',
                message: 'Datos del parte del ' + (data.referencia && data.referencia.fecha ? data.referencia.fecha : 'último turno') + ' (' + (data.referencia ? data.referencia.guardia_label : '') + ').',
                position: 'topRight',
            });
        })
        .catch(function() {
            iziToast.error({ title: 'Error', message: 'No se pudo traer el último parte de la guardia.', position: 'topRight' });
        })
        .finally(function() { btn.disabled = false; });
    });
})();

document.querySelectorAll('.estado-dia-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var motivo = document.getElementById('motivo' + this.dataset.recurso);
        if (motivo) { motivo.style.display = this.value === 'circula' ? 'none' : ''; }
    });
});

$(document).ready(function() {
    var personalAjax = {
        url: '{{ route('flota-911.informes.parte-diario.personal.buscar') }}',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return { tipo: @json($tipo), q: params.term || '' };
        },
        processResults: function(data) {
            return { results: data.results };
        },
        cache: true,
    };

    $('.select2-personal').select2({ width: '100%', language: 'es', ajax: personalAjax, minimumInputLength: 0 });
    $('.select2-chofer').select2({ width: '100%', language: 'es', allowClear: true, ajax: personalAjax, minimumInputLength: 0 });

    $('.select2-novedad-personal').select2({ width: '100%', language: 'es', placeholder: 'Agregar funcionario...' });
    $('.select2-novedad-personal').on('select2:select', function(e) {
        var corto = (e.params.data.element && e.params.data.element.getAttribute('data-corto')) || e.params.data.text;
        var ta = document.querySelector('textarea[name="novedades[' + $(this).data('rubro') + ']"]');
        if (ta && corto) {
            var actual = ta.value.trim();
            if (actual.indexOf(corto) === -1) {
                ta.value = actual ? actual + '; ' + corto : corto;
            }
        }
        $(this).val(null).trigger('change');
    });

    $('.select2-personal').on('select2:selecting', function(e) {
        const nuevoId = String(e.params.args.data.id);
        const yaSeleccionado = $('.select2-personal').not(this).toArray().some(function(sel) {
            return ($(sel).val() || []).includes(nuevoId);
        });
        if (yaSeleccionado) {
            e.preventDefault();
            iziToast.warning({
                title: 'Funcionario duplicado',
                message: 'Este funcionario ya figura en la dotación de otro recurso.',
                position: 'topRight',
                timeout: 3500,
            });
        }
    });
});
</script>
