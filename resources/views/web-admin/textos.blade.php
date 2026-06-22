@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h3 class="page__heading">Administrar Web — Textos</h3>
            <div>
                @can('editar-web-historia')
                    <a href="{{ route('web-historia.index') }}" class="btn btn-info">
                        <i class="fas fa-stream"></i> Cards de Historia
                    </a>
                @endcan
                @can('editar-web-tecnologia')
                    <a href="{{ route('web-tecnologia.index') }}" class="btn btn-info">
                        <i class="fas fa-microchip"></i> Cards de Tecnología
                    </a>
                @endcan
                @can('editar-web-contadores')
                    <a href="{{ route('web-admin.contadores.edit') }}" class="btn btn-light">
                        <i class="fas fa-sort-numeric-up"></i> Contadores
                    </a>
                @endcan
            </div>
        </div>

        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revise los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <span class="badge badge-light">{{ $error }}</span>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-info"></i>
                Editá los textos de la web. Al guardar, los cambios se publican automáticamente en
                <strong>div911.stper.com.ar</strong> (refrescá con Ctrl+F5).
            </div>

            <div class="card textos-indice">
                <div class="card-body py-2">
                    <span class="indice-label mr-2"><i class="fas fa-list-ol"></i> Ir a:</span>
                    @foreach ($catalogoPorGrupo as $grupoKey => $grupo)
                        <a href="#grupo-{{ $grupoKey }}" class="js-indice mb-1">
                            {{ $grupo['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <form action="{{ route('web-admin.textos.update') }}" method="POST">
                @csrf
                @method('PUT')

                @foreach ($catalogoPorGrupo as $grupoKey => $grupo)
                    <div class="card" id="grupo-{{ $grupoKey }}">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">
                                <i class="fas fa-file-alt text-muted mr-1"></i> {{ $grupo['label'] }}
                            </h4>
                            <div class="d-flex align-items-center">
                                @isset($grupo['pagina'])
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-2 js-preview"
                                            data-pagina="{{ $grupo['pagina'] }}"
                                            data-url="{{ rtrim(config('landing.url'), '/') . '/' . $grupo['pagina'] }}"
                                            data-title="{{ $grupo['label'] }}">
                                        <i class="fas fa-eye"></i> Vista previa
                                    </button>
                                    <span class="badge badge-info" title="Archivo de la página">
                                        <i class="fas fa-link"></i> {{ $grupo['pagina'] }}
                                    </span>
                                @endisset
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach ($grupo['textos'] as $clave => $meta)
                                <div class="form-group">
                                    <label for="t_{{ $clave }}">{{ $meta['label'] }}</label>
                                    @php $valor = old('textos.' . $clave, $valores[$clave] ?? ($meta['default'] ?? '')); @endphp
                                    @if (($meta['tipo'] ?? 'text') === 'html')
                                        <div class="wysiwyg @error('textos.' . $clave) wysiwyg--invalid @enderror" data-wysiwyg>
                                            <div class="wysiwyg__toolbar btn-group btn-group-sm" role="toolbar">
                                                <button type="button" class="btn btn-light" data-cmd="bold" title="Negrita"><i class="fas fa-bold"></i></button>
                                                <button type="button" class="btn btn-light" data-cmd="italic" title="Cursiva"><i class="fas fa-italic"></i></button>
                                                <button type="button" class="btn btn-light" data-cmd="insertUnorderedList" title="Lista"><i class="fas fa-list-ul"></i></button>
                                                <button type="button" class="btn btn-light" data-cmd="createLink" title="Insertar enlace"><i class="fas fa-link"></i></button>
                                                <button type="button" class="btn btn-light" data-cmd="unlink" title="Quitar enlace"><i class="fas fa-unlink"></i></button>
                                                <button type="button" class="btn btn-light" data-cmd="removeFormat" title="Quitar formato"><i class="fas fa-eraser"></i></button>
                                            </div>
                                            <div class="wysiwyg__area form-control" contenteditable="true" data-wysiwyg-area>{!! $valor !!}</div>
                                            <input type="hidden" name="textos[{{ $clave }}]" data-wysiwyg-input>
                                        </div>
                                    @elseif (($meta['tipo'] ?? 'text') === 'textarea')
                                        <textarea name="textos[{{ $clave }}]" id="t_{{ $clave }}" rows="4"
                                                  class="form-control @error('textos.' . $clave) is-invalid @enderror">{{ $valor }}</textarea>
                                    @else
                                        <input type="text" name="textos[{{ $clave }}]" id="t_{{ $clave }}"
                                               class="form-control @error('textos.' . $clave) is-invalid @enderror"
                                               value="{{ $valor }}">
                                    @endif
                                    @error('textos.' . $clave) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar y publicar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <div class="modal fade" id="modalPreview" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Vista previa — <span id="previewTitulo"></span></h5>
                    <div>
                        <a href="#" id="previewAbrir" target="_blank" rel="noopener" class="btn btn-sm btn-light mr-1" title="Ver versión publicada (pestaña nueva)">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <button type="button" id="previewRecargar" class="btn btn-sm btn-light mr-2" title="Actualizar la vista previa con los cambios actuales">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <iframe id="previewIframe" src="about:blank" title="Vista previa de la página"
                            style="width:100%;height:78vh;border:0;"></iframe>
                </div>
                <div class="modal-footer py-2">
                    <small class="text-muted mr-auto">
                        <i class="fas fa-info-circle"></i> Muestra <strong>tus cambios actuales sin guardar</strong> — así se verá al publicar.
                        Usá <i class="fas fa-sync-alt"></i> para refrescar tras seguir editando.
                    </small>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <button type="button" id="btnSubir" class="btn btn-primary btn-subir" title="Subir arriba" aria-label="Subir arriba">
        <i class="fas fa-arrow-up"></i>
    </button>
@endsection

@push('scripts')
    <style>
        .wysiwyg__toolbar { margin-bottom: .4rem; }
        .wysiwyg__area {
            height: auto;
            min-height: 120px;
            max-height: 420px;
            overflow-y: auto;
            line-height: 1.6;
        }
        .wysiwyg__area:focus {
            outline: 0;
            border-color: #80bdff;
            box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
        }
        .wysiwyg__area ul, .wysiwyg__area ol { padding-left: 1.5rem; margin-bottom: .5rem; }
        .wysiwyg__area p { margin-bottom: .5rem; }
        .wysiwyg--invalid .wysiwyg__area { border-color: #dc3545; }

        .textos-indice {
            position: sticky;
            top: 55px;
            z-index: 1020;
            box-shadow: 0 2px 6px rgba(0,0,0,.08);
        }
        .textos-indice .indice-label { color: var(--text-secondary, #6c757d); }
        .textos-indice .js-indice {
            display: inline-block;
            cursor: pointer;
            font-weight: 500;
            font-size: 85%;
            line-height: 1;
            padding: .35em .7em;
            margin-right: .25rem;
            border-radius: 10rem;
            text-decoration: none;
            color: var(--text-primary, #212529);
            background: var(--bg-tertiary, #e9ecef);
            border: 1px solid var(--border-color, #ced4da);
            transition: background-color .12s ease, color .12s ease, border-color .12s ease;
        }
        .textos-indice .js-indice:hover,
        .textos-indice .js-indice:focus {
            color: #fff;
            background: var(--accent-primary, #007bff);
            border-color: var(--accent-primary, #007bff);
            text-decoration: none;
        }
        /* Compensa la navbar fija + el índice sticky al saltar a una sección */
        .section-body .card[id^="grupo-"] { scroll-margin-top: 120px; }

        /* Apilado encima del botón de cambio de tema (.theme-toggle: bottom 30px, right 30px, 50px) */
        .btn-subir {
            position: fixed;
            right: 30px;
            bottom: 92px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            padding: 0;
            display: none;
            z-index: 1030;
            box-shadow: 0 3px 10px rgba(0,0,0,.25);
        }
        .btn-subir.is-visible { display: block; }
    </style>
    <script>
        (function () {
            function aplicar(area, cmd) {
                area.focus();
                if (cmd === 'createLink') {
                    var url = window.prompt('URL del enlace (https://...)');
                    if (!url) { return; }
                    document.execCommand('createLink', false, url);
                    return;
                }
                document.execCommand(cmd, false, null);
            }

            var form = document.querySelector('form[action="{{ route('web-admin.textos.update') }}"]');

            function sincronizarEditores() {
                document.querySelectorAll('[data-wysiwyg]').forEach(function (caja) {
                    var area = caja.querySelector('[data-wysiwyg-area]');
                    var input = caja.querySelector('[data-wysiwyg-input]');
                    if (area && input) { input.value = area.innerHTML; }
                });
            }

            document.querySelectorAll('[data-wysiwyg]').forEach(function (caja) {
                var area = caja.querySelector('[data-wysiwyg-area]');
                var input = caja.querySelector('[data-wysiwyg-input]');
                if (!area || !input) { return; }

                input.value = area.innerHTML;

                caja.querySelectorAll('[data-cmd]').forEach(function (boton) {
                    boton.addEventListener('click', function () {
                        aplicar(area, boton.getAttribute('data-cmd'));
                        input.value = area.innerHTML;
                    });
                });

                area.addEventListener('input', function () { input.value = area.innerHTML; });
            });

            if (form) {
                form.addEventListener('submit', sincronizarEditores);
            }

            // --- Vista previa en modal (refleja los cambios actuales SIN guardar) ---
            var iframe = document.getElementById('previewIframe');
            var titulo = document.getElementById('previewTitulo');
            var abrir = document.getElementById('previewAbrir');
            var recargar = document.getElementById('previewRecargar');
            var paginaActual = '';

            function generarPreview() {
                if (!form || !paginaActual) { return; }
                sincronizarEditores();

                var datos = new FormData(form);
                datos.delete('_method'); // la ruta de preview es POST, no PUT
                datos.append('pagina', paginaActual);

                iframe.srcdoc = '<p style="font-family:sans-serif;padding:1rem;">Generando vista previa…</p>';

                fetch('{{ route('web-admin.textos.preview') }}', {
                    method: 'POST',
                    body: datos,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.text();
                })
                .then(function (html) { iframe.srcdoc = html; })
                .catch(function (e) {
                    iframe.srcdoc = '<p style="font-family:sans-serif;color:#c00;padding:1rem;">No se pudo generar la vista previa: ' + e.message + '</p>';
                });
            }

            document.querySelectorAll('.js-preview').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    paginaActual = boton.getAttribute('data-pagina') || '';
                    titulo.textContent = boton.getAttribute('data-title') || '';
                    if (abrir) { abrir.href = boton.getAttribute('data-url') || '#'; }
                    generarPreview();
                    if (window.jQuery) { window.jQuery('#modalPreview').modal('show'); }
                });
            });

            if (recargar) {
                recargar.addEventListener('click', generarPreview);
            }
            if (window.jQuery) {
                window.jQuery('#modalPreview').on('hidden.bs.modal', function () {
                    iframe.srcdoc = '';
                });
            }

            // --- Scroll suave del índice ---
            document.querySelectorAll('.js-indice').forEach(function (enlace) {
                enlace.addEventListener('click', function (e) {
                    var destino = document.querySelector(enlace.getAttribute('href'));
                    if (destino) {
                        e.preventDefault();
                        destino.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            // --- Botón subir arriba ---
            var btnSubir = document.getElementById('btnSubir');
            if (btnSubir) {
                window.addEventListener('scroll', function () {
                    btnSubir.classList.toggle('is-visible', window.pageYOffset > 400);
                });
                btnSubir.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        })();
    </script>
@endpush
