{{-- Mammoth.js para DOCX --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
{{-- Marked.js para Markdown --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>

<script>
$(function () {

    // ── Drop zone ──────────────────────────────────────────────
    document.querySelectorAll('.drop-zone').forEach(function (zone) {
        var input   = zone.querySelector('.drop-zone-input');
        var formId  = zone.closest('form').id;
        var suffix  = formId.includes('cecoco') ? 'cecoco' : 'instructivo';
        var preview = document.getElementById('preview-' + suffix);

        zone.addEventListener('click', function () { input.click(); });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('drop-zone--over');
        });

        ['dragleave', 'dragend'].forEach(function (ev) {
            zone.addEventListener(ev, function () { zone.classList.remove('drop-zone--over'); });
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('drop-zone--over');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                mostrarPreview(preview, e.dataTransfer.files);
            }
        });

        input.addEventListener('change', function () {
            mostrarPreview(preview, input.files);
        });
    });

    function mostrarPreview(container, files) {
        container.innerHTML = '';
        Array.from(files).forEach(function (f) {
            var ext  = f.name.split('.').pop().toLowerCase();
            var icon = iconPorExtension(ext);
            var div  = document.createElement('div');
            div.className = 'badge badge-light border mr-1 mb-1 p-2';
            div.style.fontSize = '0.85rem';
            div.innerHTML = '<i class="' + icon + ' mr-1"></i>' + f.name;
            container.appendChild(div);
        });
    }

    function iconPorExtension(ext) {
        var icons = { pdf: 'fas fa-file-pdf text-danger', docx: 'fas fa-file-word text-primary',
                      md: 'fas fa-file-code text-success', html: 'fas fa-file-code text-warning' };
        return icons[ext] || 'fas fa-file text-secondary';
    }

    // ── Confirmación de eliminación ────────────────────────────
    $(document).on('submit', '.form-eliminar', function (e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: '¿Eliminar documento?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });

    // ── Visor de documentos ────────────────────────────────────
    $(document).on('click', '.btn-ver', function () {
        var id     = $(this).data('id');
        var nombre = $(this).data('nombre');
        var ext    = $(this).data('ext').toLowerCase();
        var url    = '/manuales/ver/' + id;
        var dlUrl  = '/manuales/descargar/' + id;

        // Resetear modal
        $('#visor-nombre').text(nombre);
        $('#visor-descargar').attr('href', dlUrl);
        $('#visor-loading').show();
        $('#visor-pdf, #visor-iframe, #visor-md, #visor-docx, #visor-fallback').hide();
        $('#visor-pdf').attr('src', 'about:blank');
        $('#visor-iframe').attr('src', 'about:blank');
        $('#visor-md, #visor-docx').html('');

        // Icono
        var iconMap = { pdf: 'fas fa-file-pdf text-danger', docx: 'fas fa-file-word text-primary',
                        md: 'fas fa-file-code text-success', html: 'fas fa-file-code text-warning' };
        $('#visor-icono').attr('class', (iconMap[ext] || 'fas fa-file text-secondary') + ' mr-2');

        $('#modalVisor').modal('show');

        if (ext === 'pdf') {
            // Usar <embed> para evitar el bloqueo de Chrome en iframes con sandbox
            $('#visor-pdf').attr('src', url).show();
            setTimeout(function () { $('#visor-loading').hide(); }, 800);
        } else if (ext === 'html') {
            $('#visor-iframe').attr('src', url).on('load', function () {
                $('#visor-loading').hide();
                $('#visor-iframe').show();
            });
        } else if (ext === 'md') {
            fetch(url)
                .then(function (r) { return r.text(); })
                .then(function (texto) {
                    $('#visor-loading').hide();
                    $('#visor-md').html(marked.parse(texto)).show();
                })
                .catch(function () {
                    $('#visor-loading').hide();
                    $('#visor-fallback').show();
                });
        } else if (ext === 'docx') {
            fetch(url)
                .then(function (r) { return r.arrayBuffer(); })
                .then(function (buffer) {
                    return mammoth.convertToHtml({ arrayBuffer: buffer });
                })
                .then(function (result) {
                    $('#visor-loading').hide();
                    $('#visor-docx').html(result.value).show();
                })
                .catch(function () {
                    $('#visor-loading').hide();
                    $('#visor-fallback').show();
                });
        } else {
            $('#visor-loading').hide();
            $('#visor-fallback').show();
        }
    });

    // Limpiar visor al cerrar
    $('#modalVisor').on('hidden.bs.modal', function () {
        $('#visor-pdf').attr('src', 'about:blank');
        $('#visor-iframe').attr('src', 'about:blank');
        $('#visor-md, #visor-docx').html('');
    });
});
</script>
