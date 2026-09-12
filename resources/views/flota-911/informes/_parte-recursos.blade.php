{{-- Tabla plana de recursos del parte. Espera $recursos y $tipo (para el buscador AJAX de personal). --}}
<div class="table-responsive">
    <table class="table table-modern mb-0">
        <thead>
            <tr>
                <th style="width:150px">Recurso</th>
                <th style="width:150px">Estado del día</th>
                <th style="width:70px">Zona</th>
                <th style="width:110px">HT</th>
                <th style="width:180px">Motivo (si no circula)</th>
                <th>Dotación</th>
                <th style="width:200px">Chofer</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recursos as $recurso)
            @php
                $idx = "recursos[{$recurso->id}]";
                $estadoDiario = $recurso->estadoDiario->first();
                $estadoDia = $estadoDiario?->estado_dia ?? 'circula';
                $vehiculoActual = $recurso->vehiculoActual();
                $choferDotacion = $recurso->dotaciones->firstWhere('es_chofer', true);
            @endphp
            <tr>
                <td>
                    <input type="hidden" name="{{ $idx }}[id]" value="{{ $recurso->id }}">
                    <strong>{{ $recurso->nombre }}</strong><br>
                    @if($vehiculoActual)
                        <span class="tei-badge">{{ $vehiculoActual->dominio ?? '—' }}</span>
                    @else
                        <span class="badge badge-light text-muted">Sin ficha</span>
                    @endif
                </td>
                <td>
                    <select name="{{ $idx }}[estado_dia]" class="form-control form-control-sm estado-dia-select"
                        data-recurso="{{ $recurso->id }}">
                        @foreach(\App\Models\RecursoEstadoDiario::$estados as $key => $label)
                            <option value="{{ $key }}" {{ $estadoDia === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="{{ $idx }}[zona]" class="form-control form-control-sm">
                        <option value="">—</option>
                        @for($z = 1; $z <= 4; $z++)
                            <option value="{{ $z }}" {{ (string)($estadoDiario?->zona) === (string)$z ? 'selected' : '' }}>{{ $z }}</option>
                        @endfor
                    </select>
                </td>
                <td>
                    <input type="text" name="{{ $idx }}[ht]" class="form-control form-control-sm"
                        value="{{ $estadoDiario?->ht }}" maxlength="50" placeholder="HT / MP">
                </td>
                <td>
                    <input type="text" name="{{ $idx }}[motivo]" class="form-control form-control-sm"
                        value="{{ $estadoDiario?->motivo }}" maxlength="500" placeholder="Motivo..."
                        {{ $estadoDia === 'circula' ? 'style=display:none' : '' }}
                        id="motivo{{ $recurso->id }}">
                </td>
                <td>
                    <select name="{{ $idx }}[dotacion][]" class="form-control select2-personal"
                        multiple data-placeholder="Buscar personal..." id="dotacion{{ $recurso->id }}">
                        @foreach($recurso->dotaciones as $d)
                            @if($d->personal)
                                <option value="{{ $d->personal_id }}" selected>{{ $d->personal->getNombreCompletoAttribute() }}</option>
                            @endif
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="{{ $idx }}[chofer_id]" class="form-control select2-chofer" data-placeholder="Chofer...">
                        <option value="">— Sin chofer —</option>
                        @if($choferDotacion?->personal)
                            <option value="{{ $choferDotacion->personal_id }}" selected>{{ $choferDotacion->personal->getNombreCompletoAttribute() }}</option>
                        @endif
                    </select>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">No hay recursos para este parte.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
