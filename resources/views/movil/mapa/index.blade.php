@extends('layouts.movil')

@section('title', 'Mapa')
@section('back', route('movil.index'))

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
        integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/MarkerCluster.Default.css">
@endsection

@section('content')
    <div id="m-map"></div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
        integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.1/leaflet.markercluster.js"></script>
    <script>
        (function () {
            var mapa = L.map('m-map').setView([-31.75899, -60.47825], 13);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            var clusters = L.markerClusterGroup();

            fetch('{{ route('movil.mapa.camaras-json') }}')
                .then(function (r) { return r.json(); })
                .then(function (geojson) {
                    (geojson.features || []).forEach(function (feature) {
                        var p = feature.properties || {};
                        var coords = feature.geometry.coordinates;
                        var marker = L.marker([coords[1], coords[0]]);
                        var detalleUrl = '{{ url('/movil/camaras') }}/' + p.id;
                        marker.bindPopup(
                            '<strong>' + (p.titulo || '') + '</strong><br>' +
                            (p.tipo_camara || '') + '<br>' +
                            (p.sitio || '') +
                            '<br><a href="' + detalleUrl + '">Ver ficha</a>'
                        );
                        clusters.addLayer(marker);
                    });
                    mapa.addLayer(clusters);
                });
        })();
    </script>
@endsection
