{{-- Lista de recursos con switch de inclusión. Espera $recursos (colección de Recurso). --}}
<div class="table-responsive">
    <table class="table table-modern mb-0">
        <thead>
            <tr>
                <th>Recurso</th>
                <th style="width:200px">Aparece en el parte diario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recursos as $recurso)
            <tr>
                <td>
                    <strong>{{ $recurso->nombre }}</strong>
                    @if($recurso->vehiculo)
                        <span class="tei-badge ml-1">{{ $recurso->vehiculo->dominio ?? '—' }}</span>
                    @endif
                </td>
                <td>
                    <label class="custom-switch mt-2">
                        <input type="checkbox" name="incluidos[]" value="{{ $recurso->id }}"
                            class="custom-switch-input"
                            {{ $recurso->incluir_en_parte_diario ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                    </label>
                </td>
            </tr>
            @empty
            <tr><td colspan="2" class="text-center py-4 text-muted">No hay recursos para este listado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
