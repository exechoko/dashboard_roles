@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Mapa</h3>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div id="map" style="height: 600px;"></div>
            </div>
        </div>
    </section>

    <script>
        var markers = [
            ["Cria. 1°", -31.7297377, -60.5353802],
            ["Cria. 2°", -31.7370856, -60.5298358],
            ["Cria. 3°", -31.7573272, -60.4970351]
        ];

        var zoom = 13;

        var mymap = L.map('map').setView(new L.LatLng(-31.736579, -60.524606), zoom);

        var Icono = L.icon({
            //iconUrl: "https://w7.pngwing.com/pngs/825/135/png-transparent-red-location-icon-google-maps-pin-google-map-maker-google-s-heart-map-location.png",
            iconUrl: "/img/marker.png",
            iconSize: [40, 40],
            iconAnchor: [15, 40],
            shadowSize: [35, 50],
            shadowAnchor: [0, 55],
            popupAnchor: [0, -40]
        });

        L.tileLayer(
            'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZW1kZXYiLCJhIjoiY2xlajRsbWpiMDdhNDNvbno1dmQzNW5xbSJ9.WOvCqAED6IUBsKukVJTJkg', {
                //attribution: 'Map data © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> ',
                maxZoom: 18,
                //id: 'mapbox/streets-v11',
                //id: 'mapbox/outdoors-v11',
                id: 'mapbox/light-v10',
                //id: 'mapbox/dark-v10',
                //id: 'mapbox/satellite-v9',
                //id: 'mapbox/satellite-streets-v11',
            }).addTo(mymap);

        for (var i = 0; i < markers.length; i++) {
            marker = new L.marker([markers[i][1], markers[i][2]], {icon: Icono})
                .bindPopup(markers[i][0])
                .addTo(mymap);
        }

        /*var marker = L.marker([-31.733611, -60.520276],{
            title: "Exe",
            icon: Icono
        }).addTo(mymap);*/
        //mymap.setView(new L.LatLng(-31.736579, -60.524606), 13);
    </script>
@endsection
