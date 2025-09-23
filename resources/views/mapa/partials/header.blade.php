<div class="section-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
    <div class="w-100">
        {{-- Selector de cámaras --}}
        <div class="form-group">
            <select name="camara_select" id="camara_select" class="form-control select2 mb-3">
                <option value="">Buscar cámara</option>
                @foreach ($camaras as $camara)
                    <option value="{{ $camara['numero'] }}" data-lat="{{ $camara['latitud'] }}" data-lng="{{ $camara['longitud'] }}">
                        {{ $camara['titulo'] }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Estadísticas de cámaras por tipo --}}
        <div class="mt-2">
            <h6>Cámaras por tipo</h6>
            <div class="d-flex flex-wrap">
                <span class="badge badge-info p-2 m-1">Fijas {{ $fijas }}</span>
                <span class="badge badge-warning p-2 m-1">Fijas FR {{ $fijasFR }}</span>
                <span class="badge badge-danger p-2 m-1">Fijas LPR {{ $fijasLPR }}</span>
                <span class="badge badge-success p-2 m-1">Domos {{ $domos }}</span>
                <span class="badge badge-primary p-2 m-1">Domos Duales {{ $domosDuales }}</span>
                <span class="badge badge-info p-2 m-1">BDE (Totems) {{ $bde }}</span>
                <span class="badge badge-dark p-2 m-1">Cámaras {{ $total }}</span>
                <span class="badge badge-dark p-2 m-1">Canales {{ $canales }}</span>
            </div>
        </div>

        {{-- Estadísticas de cámaras por ciudad --}}
        <div class="mt-2">
            <h6>Cámaras por ciudad</h6>
            <div class="d-flex flex-wrap">
                <span class="badge badge-info p-2 m-1">Paraná: {{ $camarasParana }}</span>
                <span class="badge badge-warning p-2 m-1">Cnia. Avellaneda: {{ $camarasCniaAvellaneda }}</span>
                <span class="badge badge-danger p-2 m-1">San Benito: {{ $camarasSanBenito }}</span>
                <span class="badge badge-success p-2 m-1">Oro Verde: {{ $camarasOroVerde }}</span>
            </div>
        </div>

        {{-- Estadísticas de sitios por ciudad --}}
        <div class="mt-2">
            <h6>Sitios</h6>
            <div class="d-flex flex-wrap">
                <span class="badge badge-info p-2 m-1">Paraná: {{ $sitiosParana }}</span>
                <span class="badge badge-warning p-2 m-1">Cnia. Avellaneda: {{ $sitiosCniaAvellaneda }}</span>
                <span class="badge badge-danger p-2 m-1">San Benito: {{ $sitiosSanBenito }}</span>
                <span class="badge badge-success p-2 m-1">Oro Verde: {{ $sitiosOroVerde }}</span>
                <span class="badge badge-dark p-2 m-1">Total sitios: {{ $cantidadSitios }}</span>
            </div>
        </div>

        {{-- Botón de exportación --}}
        <div class="text-right">
            <form action="{{ route('mapa.exportar') }}" method="GET" style="display: inline;">
                <button type="submit" class="btn btn-primary">Exportar Listado Cámaras</button>
            </form>
        </div>
    </div>
</div>
