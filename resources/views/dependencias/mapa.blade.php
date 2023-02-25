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
        var mymap = L.map('map');
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
                id: 'mapbox/streets-v11',
            }).addTo(mymap);
        var marker = L.marker([-31.733611, -60.520276],{
            title: "Exe",
            icon: Icono
        }).addTo(mymap);
        mymap.setView(new L.LatLng(-31.736579, -60.524606), 13);
    </script>
@endsection
