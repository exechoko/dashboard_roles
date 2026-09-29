{{--
    Combobox con búsqueda AJAX y paginación
    ========================================

    Props:
        id          (string)  requerido — ID único del componente
        name        (string)  requerido — name del hidden input (lo que se envía al form)
        url         (string)  requerido — endpoint AJAX
        method      (string)  default 'GET' — método HTTP: 'GET' o 'POST'
        display     (string)  default 'nombre'  — campo a mostrar en la lista (soporta "{id} - {nombre}")
        valueField  (string)  default 'id'      — campo a guardar en el hidden input
        placeholder (string)  default 'Buscar…'
        perPage     (int)     default 15
        minChars    (int)     default 3
        selected    (string)  valor inicial del hidden input (opcional)
        selectedText(string)  texto inicial a mostrar en el input (opcional)
        label       (string)  label visible (opcional)
        disabled    (bool)    default false
        required    (bool)    default false
        extraParams (array)   parámetros extra enviados en cada request
        multiple    (bool)    default false — permite varias selecciones (chips + un hidden name[] por ítem)
        selectedItems (array) valores iniciales en modo multiple: [['id'=>1,'text'=>'Texto'], ...]

    Respuesta esperada del servidor (compatible con ->paginate() de Laravel):
        { "data": [...], "current_page": 1, "last_page": 5, "total": 72, "per_page": 15 }
        También acepta { "items": [...] } o un array plano [...].

    Uso:
        <x-combobox-ajax
            id="cliente_id"
            name="cliente_id"
            url="{{ route('clientes.search') }}"
            display="nombre"
            value-field="id"
            placeholder="Buscar cliente…"
            :per-page="20"
            label="Cliente"
        />

    Eventos / API JS:
        $('#cliente_id_wrap').on('combobox:select', function(e, item) { ... });
        ComboboxAjax.instances['cliente_id'].clear();
        ComboboxAjax.instances['cliente_id'].getValue();
--}}

@props([
    'id',
    'name',
    'url',
    'method'      => 'GET',
    'display'     => 'nombre',
    'valueField'  => 'id',
    'placeholder' => 'Buscar…',
    'perPage'     => 15,
    'minChars'    => 3,
    'selected'    => '',
    'selectedText'=> '',
    'label'       => null,
    'disabled'    => false,
    'required'    => false,
    'extraParams' => [],
    'multiple'    => false,
    'selectedItems' => [],
])

@php
    $resolvedValueField = $attributes->get('value-field')
        ?? $attributes->get('valueField')
        ?? $valueField
        ?? 'id';
@endphp

@once
<style>
.cb-ajax-wrap {
    position: relative;
}
.cb-ajax-dropdown-detached {
    position: absolute;
    z-index: 9999;
    background: var(--input-bg, #fff);
    color: var(--text-primary, #495057);
    border: 1px solid var(--input-border, #ced4da);
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 10px var(--shadow, rgba(0,0,0,.15));
    overflow: hidden;
}
.cb-ajax-dropdown {
    display: none;
    position: absolute;
    z-index: 1050;
    width: 100%;
    background: var(--input-bg, #fff);
    color: var(--text-primary, #495057);
    border: 1px solid var(--input-border, #ced4da);
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 10px var(--shadow, rgba(0,0,0,.15));
    overflow: hidden;
}
.cb-ajax-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 220px;
    overflow-y: auto;
}
.cb-ajax-item {
    padding: 7px 12px;
    cursor: pointer;
    font-size: 13px;
    border-bottom: 1px solid var(--border-color, #f2f2f2);
}
.cb-ajax-item:hover,
.cb-ajax-item.cb-active {
    background: #e9f0ff;
    color: #1f2d3d;
}
[data-theme="dark"] .cb-ajax-item:hover,
[data-theme="dark"] .cb-ajax-item.cb-active {
    background: var(--sidebar-active-bg, rgba(0,229,255,.18));
    color: var(--accent-primary, #00e5ff);
}
.cb-ajax-item.cb-selected {
    opacity: .5;
}
.cb-ajax-status {
    padding: 8px 12px;
    font-size: 12px;
    color: var(--text-secondary, #6c757d);
    text-align: center;
}
.cb-ajax-pagination {
    display: none;
    padding: 4px 8px;
    border-top: 1px solid var(--border-color, #ddd);
    background: #f5f5f5;
    text-align: center;
    line-height: 26px;
}
[data-theme="dark"] .cb-ajax-pagination {
    background: var(--bg-tertiary, #102843);
}
.cb-ajax-pagination .cb-page-info {
    font-size: 11px;
    color: var(--text-secondary, #777);
    margin: 0 6px;
}
.cb-ajax-pagination button {
    background: none;
    border: none;
    color: #337ab7;
    cursor: pointer;
    padding: 0 4px;
    font-size: 13px;
    line-height: 1.4;
    vertical-align: middle;
}
[data-theme="dark"] .cb-ajax-pagination button {
    color: var(--accent-primary, #00e5ff);
}
.cb-ajax-pagination button:disabled {
    color: #bbb;
    cursor: default;
}
[data-theme="dark"] .cb-ajax-pagination button:disabled {
    color: var(--text-secondary, #93a8c0);
    opacity: .5;
}
.cb-ajax-text,
.cb-ajax-item {
    text-transform: uppercase;
}
.cb-ajax-field {
    position: relative;
}
.cb-ajax-field .cb-ajax-text {
    padding-right: 34px;
}
.cb-ajax-field.cb-clearable .cb-ajax-text {
    padding-right: 58px;
}
.cb-ajax-clear {
    display: none;
    position: absolute;
    top: 0;
    right: 30px;
    bottom: 0;
    width: 24px;
    padding: 0;
    color: var(--text-secondary, #6c757d);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 12px;
}
.cb-ajax-clear:hover {
    color: var(--accent-danger, #dc3545);
}
.cb-ajax-clear:focus {
    outline: none;
}
.cb-ajax-field.cb-has-value .cb-ajax-clear {
    display: block;
}
.cb-ajax-open {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 32px;
    padding: 0;
    color: var(--text-secondary, #6c757d);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 12px;
}
.cb-ajax-open:hover:not(:disabled) {
    color: var(--accent-primary, #007bff);
}
.cb-ajax-open:focus {
    outline: none;
}
.cb-ajax-open:disabled {
    cursor: default;
    opacity: .5;
}
.cb-ajax-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}
.cb-ajax-chips:empty {
    display: none;
}
.cb-ajax-chip {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    padding: 2px 4px 2px 8px;
    font-size: 12px;
    line-height: 1.4;
    text-transform: uppercase;
    color: #1f2d3d;
    background: #e9f0ff;
    border: 1px solid #b8ccf5;
    border-radius: 4px;
}
.cb-ajax-chip-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cb-ajax-chip-remove {
    margin-left: 4px;
    padding: 0 4px;
    color: inherit;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    opacity: .6;
}
.cb-ajax-chip-remove:hover {
    opacity: 1;
}
[data-theme="dark"] .cb-ajax-chip {
    color: var(--accent-primary, #00e5ff);
    background: var(--surface-glow, rgba(0,229,255,.08));
    border-color: var(--input-border, rgba(0,229,255,.28));
}
</style>
</style>
@endonce

@if($label)
<label class="filter-label" for="{{ $id }}_text">
    {{ $label }}@if($required)<span class="text-danger">*</span>@endif
</label>
@endif

<div class="cb-ajax-wrap" id="{{ $id }}_wrap">

    <div class="cb-ajax-field @unless($multiple) cb-clearable @endunless">
        <input
            type="text"
            class="form-control cb-ajax-text"
            id="{{ $id }}_text"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            value="{{ $selectedText }}"
            @if($disabled) disabled @endif
        >
        @unless($multiple)
            <button
                class="cb-ajax-clear"
                type="button"
                tabindex="-1"
                aria-label="Borrar"
                @if($disabled) disabled @endif
            >
                <i class="fas fa-times"></i>
            </button>
        @endunless
        <button
            class="cb-ajax-open"
            type="button"
            tabindex="-1"
            aria-label="Mostrar todos"
            @if($disabled) disabled @endif
        >
            <i class="fas fa-chevron-down"></i>
        </button>
        {{ $append ?? '' }}
    </div>

    @if($multiple)
        <div class="cb-ajax-chips">
            @foreach($selectedItems as $selectedItem)
                <span class="cb-ajax-chip" data-value="{{ $selectedItem['id'] }}">
                    <span class="cb-ajax-chip-text">{{ $selectedItem['text'] }}</span>
                    <button type="button" class="cb-ajax-chip-remove" aria-label="Quitar">&times;</button>
                    <input type="hidden" name="{{ $name }}[]" value="{{ $selectedItem['id'] }}">
                </span>
            @endforeach
        </div>
    @else
        <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $selected }}" @if($required) data-required="true" @endif>
    @endif

    <div class="cb-ajax-dropdown">
        <div class="cb-ajax-status"></div>
        <ul class="cb-ajax-list"></ul>
        <div class="cb-ajax-pagination">
            <button class="cb-ajax-prev" type="button"><i class="fas fa-chevron-left"></i></button>
            <span class="cb-page-info"></span>
            <button class="cb-ajax-next" type="button"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

</div>

@once
@push('scripts')
<script>
window.ComboboxAjax = window.ComboboxAjax || { instances: {} };

(function($) {

    function keyIs(e, names, keyCodes) {
        var key = e.key;
        var code = e.which || e.keyCode;
        if (Array.isArray(names) && names.indexOf(key) !== -1) return true;
        if (Array.isArray(keyCodes) && keyCodes.indexOf(code) !== -1) return true;
        return false;
    }

    function ComboboxAjaxInstance(wrapId, config) {
        var self = this;

        self.wrap       = $('#' + wrapId);
        self.textInput  = self.wrap.find('.cb-ajax-text');
        self.hidden     = self.wrap.find('input[type=hidden]');
        self.chips      = self.wrap.find('.cb-ajax-chips');
        self.multiple   = !!config.multiple;
        self.inputName  = config.name;
        self.openBtn    = self.wrap.find('.cb-ajax-open');
        self.clearBtn   = self.wrap.find('.cb-ajax-clear');
        self.field      = self.wrap.find('.cb-ajax-field');
        self.dropdown   = self.wrap.find('.cb-ajax-dropdown');
        self.status     = self.wrap.find('.cb-ajax-status');
        self.list       = self.wrap.find('.cb-ajax-list');
        self.pagination = self.wrap.find('.cb-ajax-pagination');
        self.prevBtn    = self.wrap.find('.cb-ajax-prev');
        self.nextBtn    = self.wrap.find('.cb-ajax-next');
        self.pageInfo   = self.wrap.find('.cb-page-info');

        self.url         = config.url;
        self.method      = (config.method || 'GET').toUpperCase();
        self.displayTpl  = config.display    || 'nombre';
        self.display     = self.displayTpl;
        self.valueField  = config.valueField || 'id';
        self.perPage     = (config.perPage !== undefined && config.perPage !== null) ? config.perPage : 15;
        self.minChars    = (config.minChars !== undefined && config.minChars !== null) ? config.minChars : 3;

        self.currentPage   = 1;
        self.lastPage      = 1;
        self.currentSearch = '';
        self.xhr           = null;
        self.debounce      = null;
        self.focusedIndex  = -1;
        self.isLoading     = false;
        self.extraParams   = {};

        self._bindEvents();
        self._bindScroll();
        self._syncClear();
    }

    ComboboxAjaxInstance.prototype._bindEvents = function() {
        var self = this;

        self.textInput.on('keydown', function(e) {
            if (keyIs(e, ['ArrowDown'], [40]) && self.dropdown.is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                self._moveFocus(1);
                return;
            }

            if (keyIs(e, ['ArrowUp'], [38]) && self.dropdown.is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                self._moveFocus(-1);
                return;
            }

            if (keyIs(e, ['Enter'], [13]) && self.dropdown.is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                self._selectFocused();
                return;
            }

            if (keyIs(e, ['Escape', 'Esc'], [27]) && self.dropdown.is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                self.closeDropdown();
                return;
            }
        });

        self.textInput.on('input', function() {
            self._syncClear();
        });

        self.clearBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.clear();
            self.textInput.focus();
        });

        self.textInput.on('keyup', function(e) {
            if (keyIs(e, ['ArrowDown', 'ArrowUp', 'Enter', 'Escape', 'Esc', 'Tab'], [40, 38, 13, 27, 9])) { return; }

            if (!self.multiple && self.hidden.val() !== '') {
                self.hidden.val('').trigger('change');
            }

            var val = self.textInput.val().trim();
            self.currentSearch = val;
            self.currentPage   = 1;
            self.focusedIndex  = -1;
            self.isLoading     = false;

            clearTimeout(self.debounce);

            if (val.length < self.minChars) {
                self.list.empty();
                self._hidePagination();
                self._showStatus('Ingrese al menos ' + self.minChars + ' caracteres para buscar.');
                self.openDropdown();
                return;
            }

            self.debounce = setTimeout(function() { self._fetch(); }, 300);
        });

        self.openBtn.on('click', function(e) {
            e.stopPropagation();
            if (self.dropdown.is(':visible')) {
                self.closeDropdown();
            } else {
                self.currentSearch = '';
                self.currentPage   = 1;
                self._fetch();
                self.textInput.focus();
            }
        });

        self.chips.on('click', '.cb-ajax-chip-remove', function(e) {
            e.preventDefault();
            self._removeChip($(this).closest('.cb-ajax-chip'));
        });

        self.prevBtn.on('click', function(e) {
            e.stopPropagation();
            if (self.currentPage > 1) {
                self.currentPage--;
                self._fetch();
            }
        });

        self.nextBtn.on('click', function(e) {
            e.stopPropagation();
            if (self.currentPage < self.lastPage) {
                self.currentPage++;
                self._fetch();
            }
        });

        $(document).on('click.cb-ajax-' + self.wrap.attr('id'), function(e) {
            if (!self.wrap.is(e.target) && self.wrap.has(e.target).length === 0
                && !self.dropdown.is(e.target) && self.dropdown.has(e.target).length === 0) {
                self.closeDropdown();
            }
        });
    };

    ComboboxAjaxInstance.prototype._bindScroll = function() {
        var self = this;
        self.list.on('scroll', function() {
            var el       = self.list[0];
            var atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 20;
            if (atBottom && !self.isLoading && self.currentPage < self.lastPage) {
                self.currentPage++;
                self._fetch(true);
            }
        });
    };

    ComboboxAjaxInstance.prototype._invalidResponse = function(response) {
        this.isLoading = false;
        this.list.find('.cb-ajax-loading-more').remove();
        this.list.empty();
        this._hidePagination();
        this._showStatus('<i class="fas fa-exclamation-triangle"></i> Respuesta inválida del servidor.');
        this.openDropdown();
        this.wrap.trigger('combobox:error', [{ type: 'invalid_response', raw: response }]);
    };

    ComboboxAjaxInstance.prototype._fetch = function(append, done) {
        var self = this;

        if (self.isLoading) return;
        if (!append && self.xhr) self.xhr.abort();

        self.isLoading = true;

        if (!append) {
            self._showStatus('<i class="fas fa-spinner fa-spin"></i> Buscando…');
            self.list.empty();
            self._hidePagination();
            self.openDropdown();
        } else {
            self.list.append('<li class="cb-ajax-loading-more" style="text-align:center;padding:6px;color:#999;font-size:12px;"><i class="fas fa-spinner fa-spin"></i></li>');
        }

        var params = $.extend({
            search:   self.currentSearch,
            page:     self.currentPage,
            per_page: self.perPage
        }, self.extraParams);

        if (self.method === 'POST') {
            params._token = $('meta[name="csrf-token"]').attr('content');
        }

        self.xhr = $.ajax({
            url:    self.url,
            method: self.method,
            data:   params,
            success: function(response) {
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        self._invalidResponse(response);
                        return;
                    }
                }

                if (!(Array.isArray(response) || (response && typeof response === 'object'))) {
                    self._invalidResponse(response);
                    return;
                }

                self.list.find('.cb-ajax-loading-more').remove();
                self._render(response, append);
                self.isLoading = false;
                if (typeof done === 'function') {
                    done(response);
                }
            },
            error: function(xhr) {
                self.isLoading = false;
                self.list.find('.cb-ajax-loading-more').remove();
                if (xhr.statusText !== 'abort') {
                    self._showStatus('<i class="fas fa-exclamation-triangle"></i> Error al buscar. Intente nuevamente.');
                }
            }
        });
    };

    ComboboxAjaxInstance.prototype._render = function(response, append) {
        var self = this;

        var items       = Array.isArray(response)
                            ? response
                            : (response.data || response.items || []);
        var total       = response.total        || items.length;
        var lastPage    = response.last_page    || 1;
        var currentPage = response.current_page || 1;

        self.lastPage    = lastPage;
        self.currentPage = currentPage;

        if (!append) {
            self.focusedIndex = -1;
            self.list.empty();
            self._hideStatus();
        }

        if (items.length === 0 && !append) {
            self._showStatus('Sin resultados.');
            return;
        }

        $.each(items, function(i, item) {
            var li = $('<li class="cb-ajax-item">')
                .toggleClass('cb-selected', self.multiple && self._hasValue(item[self.valueField]))
                .text(self._label(item))
                .data('item', item);

            li.on('mousedown', function(e) {
                e.preventDefault();
                self._select($(this).data('item'));
            });

            self.list.append(li);
        });

        if (lastPage > 1) {
            self._showPagination(currentPage, lastPage, total);
        }
    };

    ComboboxAjaxInstance.prototype._select = function(item) {
        var self = this;
        if (self.multiple) {
            self._addChip(item[self.valueField], self._label(item));
            self.textInput.val('');
            self.currentSearch = '';
            self.closeDropdown();
            self.wrap.trigger('combobox:select', [item]);
            return;
        }
        self.textInput.val(self._label(item));
        self._syncClear();
        self.hidden.val(item[self.valueField]).trigger('change');
        self.closeDropdown();
        self.wrap.trigger('combobox:select', [item]);
    };

    ComboboxAjaxInstance.prototype._moveFocus = function(direction) {
        var self  = this;
        var items = self.list.find('.cb-ajax-item');
        if (!items.length) return;

        var nextIndex = self.focusedIndex + direction;

        if (direction > 0 && nextIndex >= items.length) {
            if (!self.isLoading && self.currentPage < self.lastPage) {
                var previousLength = items.length;
                self.currentPage++;
                self._fetch(true, function() {
                    var newItems = self.list.find('.cb-ajax-item');
                    self.focusedIndex = newItems.length > previousLength ? previousLength : newItems.length - 1;
                    self._applyFocus();
                });
            }
            return;
        }

        self.focusedIndex = Math.max(0, Math.min(items.length - 1, nextIndex));
        self._applyFocus();
    };

    ComboboxAjaxInstance.prototype._applyFocus = function() {
        var self  = this;
        var items = self.list.find('.cb-ajax-item');
        if (!items.length || self.focusedIndex < 0) return;

        items.removeClass('cb-active');
        var active = items.eq(self.focusedIndex).addClass('cb-active');

        var listEl = self.list[0];
        var itemEl = active[0];
        if (!listEl || !itemEl) return;

        var itemTop    = itemEl.offsetTop;
        var itemBottom = itemTop + itemEl.offsetHeight;
        var viewTop    = listEl.scrollTop;
        var viewBottom = viewTop + listEl.clientHeight;

        if (itemTop < viewTop) {
            listEl.scrollTop = itemTop;
        } else if (itemBottom > viewBottom) {
            listEl.scrollTop = itemBottom - listEl.clientHeight;
        }
    };

    ComboboxAjaxInstance.prototype._selectFocused = function() {
        var self  = this;
        var items = self.list.find('.cb-ajax-item');
        if (self.focusedIndex >= 0 && items.length > self.focusedIndex) {
            self._select(items.eq(self.focusedIndex).data('item'));
        }
    };

    ComboboxAjaxInstance.prototype._label = function(item) {
        var tpl = this.displayTpl;
        if (tpl.indexOf('{') === -1) {
            return item[tpl] !== undefined ? item[tpl] : '';
        }
        return tpl.replace(/\{(\w+)\}/g, function(_, key) {
            return item[key] !== undefined ? item[key] : '';
        });
    };

    ComboboxAjaxInstance.prototype._showStatus = function(html) {
        this.status.html(html).show();
    };

    ComboboxAjaxInstance.prototype._hideStatus = function() {
        this.status.hide().empty();
    };

    ComboboxAjaxInstance.prototype._showPagination = function(current, last, total) {
        this.pageInfo.text('Pág. ' + current + ' de ' + last + ' — ' + total + ' resultados');
        this.prevBtn.prop('disabled', current <= 1);
        this.nextBtn.prop('disabled', current >= last);
        this.pagination.css('display', 'block');
    };

    ComboboxAjaxInstance.prototype._hidePagination = function() {
        this.pagination.hide();
    };

    ComboboxAjaxInstance.prototype.openDropdown = function() {
        var self = this;
        var rect = self.wrap[0].getBoundingClientRect();
        var scrollTop  = window.pageYOffset || document.documentElement.scrollTop;
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        self.dropdown
            .detach()
            .addClass('cb-ajax-dropdown-detached')
            .css({
                top:   rect.bottom + scrollTop,
                left:  rect.left  + scrollLeft,
                width: rect.width
            })
            .appendTo('body')
            .show();

        self.openBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    };

    ComboboxAjaxInstance.prototype.closeDropdown = function() {
        var self = this;
        self.dropdown
            .hide()
            .detach()
            .removeClass('cb-ajax-dropdown-detached')
            .css({ top: '', left: '', width: '' })
            .appendTo(self.wrap);

        self.openBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    };

    ComboboxAjaxInstance.prototype._chipValues = function() {
        return this.chips.find('input[type=hidden]').map(function() { return String($(this).val()); }).get();
    };

    ComboboxAjaxInstance.prototype._hasValue = function(value) {
        return this._chipValues().indexOf(String(value)) !== -1;
    };

    ComboboxAjaxInstance.prototype._addChip = function(value, text) {
        if (this._hasValue(value)) { return; }
        var chip = $('<span class="cb-ajax-chip">').attr('data-value', value);
        chip.append($('<span class="cb-ajax-chip-text">').text(text));
        chip.append($('<button type="button" class="cb-ajax-chip-remove" aria-label="Quitar">').html('&times;'));
        chip.append($('<input type="hidden">').attr('name', this.inputName + '[]').val(value));
        this.chips.append(chip);
    };

    ComboboxAjaxInstance.prototype._removeChip = function(chip) {
        var value = chip.data('value');
        chip.remove();
        this.wrap.trigger('combobox:remove', [value]);
    };

    ComboboxAjaxInstance.prototype._syncClear = function() {
        this.field.toggleClass('cb-has-value', !this.multiple && this.textInput.val() !== '');
    };

    ComboboxAjaxInstance.prototype.getValue = function() {
        return this.multiple ? this._chipValues() : this.hidden.val();
    };

    ComboboxAjaxInstance.prototype.clear = function() {
        this.textInput.val('');
        this._syncClear();
        if (this.hidden.length && this.hidden.val() !== '') {
            this.hidden.val('').trigger('change');
        }
        this.chips.empty();
        this.closeDropdown();
        this.list.empty();
        this._hideStatus();
        this._hidePagination();
        this.wrap.trigger('combobox:clear');
    };

    ComboboxAjaxInstance.prototype.setValue = function(value, text) {
        if (this.multiple) {
            this._addChip(value, text !== undefined ? text : value);
            return;
        }
        this.hidden.val(value);
        this.textInput.val(text !== undefined ? text : value);
        this._syncClear();
    };

    ComboboxAjaxInstance.prototype.setDisabled = function(disabled) {
        this.textInput.prop('disabled', !!disabled);
        this.openBtn.prop('disabled', !!disabled);
        if (disabled) {
            this.closeDropdown();
        }
    };

    window.ComboboxAjax.Instance = ComboboxAjaxInstance;

})(jQuery);
</script>
@endpush
@endonce

@push('scripts')
<script>
(function($) {
    var instance = new ComboboxAjax.Instance(@json($id . '_wrap'), {
        url:        @json($url),
        method:     @json(strtoupper($method)),
        name:       @json($name),
        multiple:   @json((bool) $multiple),
        display:    @json($display),
        valueField: @json($resolvedValueField),
        perPage:    @json((int) $perPage),
        minChars:   @json((int) $minChars),
    });
    @if(!empty($extraParams))
    instance.extraParams = @json($extraParams);
    @endif
    ComboboxAjax.instances[@json($id)] = instance;
})(jQuery);
</script>
@endpush
