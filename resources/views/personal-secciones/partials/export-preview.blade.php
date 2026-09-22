<p class="text-muted small mb-3">
    Tildá las columnas adicionales que necesites para armar la planilla. Podés copiar la tabla completa
    (botón "Copiar tabla") y pegarla directo en otra planilla, o descargar el archivo Excel con lo que hayas elegido.
</p>

<div class="d-flex flex-wrap mb-3" style="gap: .25rem 1rem">
    @foreach ($columnasExtraDisponibles as $clave => $etiqueta)
        <div class="form-check form-check-inline">
            <input class="form-check-input col-extra-toggle" type="checkbox" value="{{ $clave }}" id="col-extra-{{ $clave }}">
            <label class="form-check-label" for="col-extra-{{ $clave }}">{{ $etiqueta }}</label>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-muted small">{{ $filas->count() }} funcionario(s) en esta vista previa</span>
    <div>
        <button type="button" id="btnCopiarTablaExport" class="btn btn-outline-secondary btn-sm mr-2">
            <i class="fas fa-copy"></i> Copiar tabla
        </button>
        <a href="#" id="btnDescargarExcel" class="btn btn-primary btn-sm">
            <i class="fas fa-download"></i> Descargar Excel
        </a>
    </div>
</div>

<div class="table-responsive" style="max-height: 55vh;">
    <table class="table table-sm table-bordered table-striped mb-0" id="tablaPreviewExport">
        <thead>
            <tr>
                <th>Sección</th>
                <th>Jerarquía</th>
                <th>Apellido</th>
                <th>Nombre</th>
                <th>L.P.</th>
                <th>Función</th>
                <th>Estado</th>
                <th>Fecha de ingreso a la división</th>
                <th>Fecha baja sección</th>
                @foreach ($columnasExtraDisponibles as $clave => $etiqueta)
                    <th class="col-extra col-extra-{{ $clave }}" hidden>{{ $etiqueta }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    <td>{{ $fila['seccion'] }}</td>
                    <td>{{ $fila['jerarquia'] }}</td>
                    <td>{{ $fila['apellido'] }}</td>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['lp'] }}</td>
                    <td>{{ $fila['funcion'] }}</td>
                    <td>{{ $fila['estado'] }}</td>
                    <td>{{ $fila['ingreso_division_911'] }}</td>
                    <td>{{ $fila['fecha_baja'] }}</td>
                    @foreach ($columnasExtraDisponibles as $clave => $etiqueta)
                        <td class="col-extra col-extra-{{ $clave }}" hidden>{{ $fila['extra'][$clave] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 9 + count($columnasExtraDisponibles) }}" class="text-center text-muted py-3">
                        No hay funcionarios para exportar con estos filtros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
