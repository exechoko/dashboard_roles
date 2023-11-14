<link href="{{ asset('leaflet/geocoder/geocoder.css') }}" rel="stylesheet">
<link href="{{ asset('leaflet/lib/leaflet-dist/leaflet.css') }}" rel="stylesheet">
<link href="{{ asset('leaflet/src/Icon.Label.css') }}" rel="stylesheet">
<link rel="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css" rel="stylesheet">
<style>
    .marker {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-direction: row;
        flex-direction: row;
        -ms-flex-pack: center;
        justify-content: center;
        -ms-flex-align: center;
        align-items: center;
        position: relative;
        border-radius: 100%;
        font-size: 16px;
        font-weight: bold;
        box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);

    }


    .marker-edit {
        width: 1rem;
        height: 1rem;
        left: -0.5rem;
        top: -0.5rem;
        border: thin solid blue;
        border-radius: 100% !important;
        background: white;
    }

    .marker-head {
        width: 2rem;
        height: 2rem;
        left: -1rem;
        top: -1rem;
        border: thick solid blue;
        border-radius: 100% !important;
        background: white;
        padding: 5px
    }





    .hide-marker {
        display: none;
    }


    #modal-marker {
        display: none;
        width: 200px;
        background: white;
        box-shadow: 2px 2px 4px rgba(0, 0, 0, .3);
        border-radius: 5px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000000000000000000000
    }


    .label-marker {
        position: absolute;
        z-index: 100000000;
        border: solid thin lightgray;
        box-shadow: 2px 2px rgba(0, 0, 0, .2);
        background: white;
        color: black;
        font-weight: normal;
        /*display: none;*/
    }



    .leaflet-marker-icon {
        background: transparent;
        border: none;
    }

    .linea {
        width: 50px;
        height: 50px;
        color: white;
        font-size: 30px;
        font-weight: bold;
    }

    .name-station {
        font-size: 30px;
        font-weight: bold;
        white-space: nowrap;
    }

    .comb-desde-hasta {
        font-size: 12px;
    }

    .line {
        height: 1px;
    }
</style>
<script src="{{ asset('leaflet/leaflet-0.7.3/leaflet.min.js') }}"></script>
<script src="{{ asset('leaflet/src/Icon.Label.js') }}"></script>
<script src="{{ asset('leaflet/geocoder/esri-leaflet.js') }}"></script>
<script src="{{ asset('leaflet/geocoder/esri-leaflet-geocoder.min.js') }}"></script>
<script src="{{ asset('leaflet/geocoder/gpx.min.js') }}"></script>

<script src="http://maps.googleapis.com/maps/api/js?v=3&sensor=false&libraries=places&key=API_KEY"></script>
@include('partials.polygon')
<script>
    var
        DEFAULT_LAT = -34.6055323,
        DEFAULT_LNG = -58.3832132,
        MARKERS = [],
        POLYLINES = [],
        ROUTE,
        CONFIG = {
            color: '#000000',
            stroke: 1,
            opacity: 1
        },
        SWAL_POLYGON = null,
        DRAG = false,
        INTERVAL


    function getCoord(calle, num, cdad,
    callback) { //traduce una direccion a coordenaadas maps (ingresar direccion, ciudad);
        var num = num;
        var y = ' y ';
        if ($.isNumeric(num)) {
            y = ' ';
        }
        var address = calle + y + num + ', ' + cdad;

        var loc = null;
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            'address': address
        }, function(results, status) {
            if (status == 'OK') {
                ////console.log('geocoder:', results)
                callback({
                    lat: results[0].geometry.location.lat(),
                    lng: results[0].geometry.location.lng()
                });
            } else {
                //console.log("Geocoding no tuvo éxito debido a: " + status);
                callback(null);
            }
        });


    }

    function getLatLng(marker, route) {
        if (route !== undefined) {
            return {
                lat: parseFloat(marker.lat),
                lng: parseFloat(marker.lng)
            }
        }
        try {
            return {
                lat: marker._latlng.lat,
                lng: marker._latlng.lng
            }
        } catch (e) {
            return null
        }

    }

    function searchPlace(partido, barrio,
    callback) { //traduce una direccion a coordenaadas maps (ingresar direccion, ciudad);
        var address = partido
        if (barrio != null) {
            address += ', ' + barrio
        }


        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            'address': address
        }, function(results, status) {
            if (status == 'OK') {

                callback({
                    lat: results[0].geometry.location.lat(),
                    lng: results[0].geometry.location.lng()
                });
            } else {
                //console.log("Geocoding no tuvo éxito debido a: " + status);
                callback(null, null);
            }
        });


    }


    function getAddress(marker, obj) {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            'latLng': marker.getPosition()
        }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                var address = results[0]['formatted_address'];
                //console.log(address)
                obj.html(address)
            }
        })
    }

    function resetRoute(route) {
        if (route != null) {
            route.resetRoute()
        }
        route = null
    }


    function initMap(lat, lng, callback) { //inicializa el mapa
        //console.log('init map')

        map = L.map('map', {
            zoomControl: false
        }).setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        // searchControl = new L.esri.Controls.Geosearch({position:'topright'}).addTo(map);
        // var geocodeService = new L.esri.Services.Geocoding();

        // var results = new L.LayerGroup().addTo(map);

        // searchControl.on('results', function (data) {
        //   results.clearLayers();
        //   for (var i = data.results.length - 1; i >= 0; i--) {
        //     results.addLayer(L.marker(data.results[i].latlng));
        //   }
        // });

        map.on('zoomend', function() {

        }).on('mousedown', function(e) {
            //console.log('MAP', e.originalEvent.which)

            // if(e.originalEvent.which == 3){
            //  $('.label-marker').remove()
            //  return;
            // }
        }).on('moveend', function(e) {

            //ROUTE.updatePositions()
        })
        if (callback != null) {


            map.on('click', function(e) {


                if (!DRAG) {
                    //console.log('map click')
                    callback(e)
                }
                DRAG = false
            })

        }



        try {
            mapInit(map)
        } catch (e) {}




    }

    function setZoom(markers) {
        //console.log('ZOOM', markers)
        var zoommarkers = [],
            zoomZona = new L.featureGroup()
        $.each(markers, function(i, m) {
            var marker = new L.Marker([m._latlng.lat, m._latlng.lng])
            zoommarkers.push(marker)
            zoomZona.addLayer(marker)
        })

        zoomZona.addTo(map);

        map.fitBounds(zoomZona.getBounds())

        for (var m = 0; m < zoommarkers.length; m++) {
            map.removeLayer(zoommarkers[m])
        }


    }

    function getIcon(label, markerClass, style, hover) {
        var size = hover !== undefined ? hover : 1
        var anchor = markerClass == 'marker-estacion' ? 5 * size : 1


        return L.divIcon({
            iconAnchor: [anchor, anchor],
            labelAnchor: [0, 0],
            popupAnchor: [0, 0],
            //zIndexOffset: markerClass == 'marker-estacion' ? 50000000 : ((style == 'marker-tramo' || style == 'marker-parada') ? 20000000 : 10000),
            zIndexOffset: (markerClass == 'marker-origen' || markerClass == 'marker-destino') ? 1000 : 50000000,
            html: '<div class="marker ' + markerClass + '" ' + style + '>' + label + '</div>'
        })
    }

    function addMarker(latlng, title, drag, markerClass, parada) { //agrega un marcador en el mapa
        var lat = latlng.lat,
            lng = latlng.lng
        marker = new L.Marker(
            [lat, lng], {
                icon: getIcon(title, markerClass),
                title: title,
                draggable: drag,
                index: POLYLINES.length,
                parada: parada !== undefined ? parada : false
            })
        if (markerClass != 'hide-marker') {
            marker.on('dragstart', function() {
                    DRAG = true
                })
                .on('drag', function(e) {

                    var index = e.target.options.index
                    ////console.log('index', index)
                    POLYLINES[index][0] = e.target._latlng.lat
                    POLYLINES[index][1] = e.target._latlng.lng
                    setPolyLine()

                })
        }

        // .on('mousedown', function(e){

        //   if(e.originalEvent.which == 3) {

        //     markerAlert(e)
        //   }
        // })
        POLYLINES.push([lat, lng])
        MARKERS.push(marker)
        marker.addTo(map)
        setPolyLine()
    }


    function addSingleMarker(latlng, title, drag, markerClass, style) { //agrega un marcador en el mapa
        var lat = latlng.lat,
            lng = latlng.lng
        marker = new L.Marker(
                [lat, lng], {
                    icon: getIcon(title, markerClass, style),
                    title: title,
                    draggable: drag
                })
            .on('dragstart', function() {

            })
            .on('drag', function(e) {

            })

        if (markerClass == 'marker-destino') {
            marker.on('click', function(e) {
                //console.log(e)
                addMarker({
                    lat: e.latlng.lat,
                    lng: e.latlng.lng
                }, '', false, 'marker-tramo')
            })
        }
        //console.log('MMM', marker)
        marker.addTo(map)
        return marker
    }


    // function resetRoute(){
    //    removeMarkers()
    //    POLYLINES = []
    //    if(ROUTE != null){
    //      map.removeLayer(ROUTE)
    //      ROUTE = null;
    //    }


    // }

    function setPolyLine() {
        if (ROUTE != null) {
            map.removeLayer(ROUTE)
        }

        ////console.log('polylines', POLYLINES)

        ROUTE = L.polyline(POLYLINES, {
            color: CONFIG.color,
            weight: CONFIG.stroke,
            opacity: CONFIG.opacity
        }).addTo(map)

        //ROUTE.addTo(map)
    }




    function removeMarkers() { //elimina marcadores del mapa

        for (var m = 0; m < MARKERS.length; m++) {
            map.removeLayer(MARKERS[m])
        }

        MARKERS = []
    }

    function ctrlZ(route) {
        if (route == null) {
            return
        }
        route.fin = false
        var last = route.markers.length - 1
        if (last < 0) {
            return;
        }

        map.removeLayer(route.markers[last])
        route.markers.splice(last, 1)
        route.polylines.splice(route.polylines.length - 1, 1)
        route.setPolyLine()
    }

    function setParada() {
        MARKERS[MARKERS.length - 1].setIcon(getIcon('', 'marker-parada'))
        MARKERS[MARKERS.length - 1].options.parada = true
    }

    function markerAlert(m) {
        var index = parseInt(m.target.options.index)
        //     modalMarker = $('#modal-marker')
        // setZoom([m.target])
        MARKERS[index].setIcon(getIcon('', 'marker-alert'))

        map.removeLayer(MARKERS[index])
        MARKERS.splice(index, 1)
        setPolyLine()

        //modalMarker.fadeIn(150)
    }


    function ruta(map, ramal, edit) {
        this.edit = edit !== undefined ? true : false
        this.linea = ramal.name
        this.id = ramal.id
        this.map = map
        this.fin = false
        this.color = ramal.color
        this.stroke = 5
        this.opacity = 1
        this.markers = []
        this.positionMarkers = []
        this.polylines = []
        this.points = []
        this.route = null
        this.estacion = false
        this.estaciones = []
        this.frames = []
        this.animateMarkers = [];
        this.frameIndex = 0;
        this.isAnimate = false
        this.animateMarker
        this.insert = null
        this.update = false
        this.routes = []
        this.position = 0
        this.hidden = false
        this.animateStyle = null
        this.pause = false
        this.endTravel = true
        this.stop = true
        this.resume = false
        this.legFrames = 5


        this.setColor = function(color) {
            this.color = color
        }

        this.setPolyLine = function() {
            if (this.route != null) {
                this.map.removeLayer(this.route)
            }



            this.route = L.polyline(this.polylines, {
                color: this.color,
                weight: this.stroke,
                opacity: this.hidden ? 0 : this.opacity
            }).addTo(this.map)

            //ROUTE.addTo(map)
        }


        this.getStyle = function(e) {
            var parent = this
            if (e == null) {
                return ''
            }
            return 'style="\
                              border: solid thick ' + parent.color + ';\
                              background: white\
                            "'
        }

        this.setAnimateStyle = function(linea) {
            this.animateStyle = 'style="\
                    border: solid thick ' + linea.color_b + ';\
                    background:' + linea.color_a + ';\
                    color:' + linea.color_c + ';\
                  "'
        }


        this.addMarker = function(latlng, title, drag, markerClass, parada) { //agrega un marcador en el mapa


            var lat = latlng.lat,
                lng = latlng.lng,
                parent = this,
                style = this.getStyle(parada),
                marker = new L.Marker(
                    [lat, lng], {
                        icon: getIcon(title, markerClass, style), //
                        title: title,
                        draggable: drag,
                        index: this.polylines.length,
                        parada: parada
                    }
                )
            if (markerClass != 'hide-marker') {
                marker.on('dragstart', function(e) {

                        DRAG = true
                    })
                    .on('drag', function(e) {
                        if (!parent.edit) {
                            return
                        }
                        var index = e.target.options.index
                        ////console.log('index', index)
                        parent.polylines[index][0] = e.target._latlng.lat
                        parent.polylines[index][1] = e.target._latlng.lng
                        parent.setPolyLine()

                    })
                    .on('click', function(e) {
                        if (!parent.edit) {
                            return
                        }
                        var marker = e.target

                        parent.setParada(marker.options)

                    })

            }

            if (this.insert != null) {
                this.insertTo(marker)
            } else {
                this.polylines.push([lat, lng])
                this.markers.push(marker)
                marker.addTo(this.map)
                this.setPolyLine()
            }

            return marker
        }
        this.insertTo = function(marker) {
            var pos = this.insert.position,
                parent = this,
                newPolylines = [],
                newMarkers = [],
                index = 0
            $.each(parent.polylines, function(i, p) {
                if (i == pos) {
                    if (parent.insert.inTo == 'before') {

                        newPolylines.push([marker._latlng.lat, marker._latlng.lng])
                        marker.options.index = index++
                        newMarkers.push(marker)

                        newPolylines.push(p)
                        parent.markers[i].options.index = index++
                        newMarkers.push(parent.markers[i])
                    } else {
                        newPolylines.push(p)
                        parent.markers[i].options.index = index++
                        newMarkers.push(parent.markers[i])

                        newPolylines.push([marker._latlng.lat, marker._latlng.lng])
                        marker.options.index = index++
                        newMarkers.push(marker)

                    }
                } else {
                    newPolylines.push(p)
                    parent.markers[i].options.index = index++
                    newMarkers.push(parent.markers[i])
                }

            })
            parent.polylines = newPolylines
            parent.markers = newMarkers
            parent.insert = null
            marker.addTo(this.map)
            this.setPolyLine()
        }

        this.insertTozz = function(marker) {
            var pos = this.insert.position,
                parent = this,
                points = parent.points
            newPoints = []
            $.each(points, function(i, p) {
                if (parent.insert.inTo == 'before') {
                    if (i == pos - 1) {
                        newPoints.push({
                            lat: marker._latlng.lat,
                            lng: marker._latlng.lng,
                            parada: false
                        })
                    } else {
                        newPoints.push(p)
                    }
                } else {
                    if (i == pos + 1) {
                        newPoints.push({
                            lat: marker._latlng.lat,
                            lng: marker._latlng.lng,
                            parada: false
                        })
                    } else {
                        newPoints.push(p)
                    }
                }
            })
            parent.points = newPoints
            this.set(newPoints)
        }

        this.deletePoint = function(pos) {
            //console.log(this.points)
            this.points.splice(pos, 1)
            this.update = true
            this.set(this.points)
        }

        this.deletePoints = function(indexes) {
            var parent = this,
                points = []
            $.each(parent.points, function(i, p) {
                if (!indexes.includes(i)) {
                    points.push(p)
                }
            })
            console.log('DELETE', points)
            if (points.length == 0) {
                parent.resetRoute()
                return
            }
            parent.update = true
            parent.set(points)
        }
        this.singleMarker = function(latlng, title, drag, markerClass, style) { //agrega un marcador en el mapa
            var lat = latlng.lat,
                lng = latlng.lng,
                parent = this,
                marker = new L.Marker(
                    [lat, lng], {
                        icon: getIcon(title, markerClass, style),
                        title: title,
                        draggable: drag,
                        parada: markerClass.includes('parada')
                    })
                .on('dragstart', function() {

                })
                .on('drag', function(e) {

                })
            // .on('contextmenu', function(e){

            //   //e.originalEvent.stopPropagation()
            //   var marker = e.target

            //   ROUTE.setEstacion(marker.options.index)
            //   return
            // })

            if (markerClass == 'marker-destino') {
                marker.on('click', function(e) {
                    //console.log(e)
                    parent.addMarker({
                        lat: e.latlng.lat,
                        lng: e.latlng.lng
                    }, '', false, 'marker-tramo')
                    parent.fin = true
                })
            }


            //console.log('MMM', marker)
            marker.addTo(parent.map)
            return marker
        }


        this.setParada = function(options) {
            var url = "{{ route('route.edit-parada', ['index' => 'pos', 'parada' => 'stop']) }}"
            url = url.replace('pos', options.index)
            url = url.replace('stop', options.parada ? 1 : 0)

            modalParada.openModal(url)
        }
        this.updatePoint = function(isParada, index) {
            //console.log('UPDATE', isParada)
            this.markers[index].setIcon(getIcon('', isParada ? 'marker-parada' : 'marker-edit', ''))
            this.markers[index].options.parada = isParada

        }
        this.addParada = function() {
            if (this.markers.length == 0) {
                return
            }
            var m = this.markers[this.markers.length - 1]
            m.setIcon(getIcon('', 'marker-parada'))
            m.options.parada = true
        }
        this.insertPoint = function(isAfter, index) {
            this.insert = {
                inTo: isAfter ? 'after' : 'before',
                position: index
            }

        }
        this.getRoute = function() {
            var points = []
            $.each(this.markers, function(i, marker) {
                points.push({
                    lat: marker._latlng.lat,
                    lng: marker._latlng.lng,
                    parada: marker.options.parada
                })
            })

            return points
        }
        this.removeMarkers = function() {
            for (var m = 0; m < this.markers.length; m++) {
                this.map.removeLayer(this.markers[m])
            }

            this.markers = []
        }
        this.resetRoute = function() {
            this.removeMarkers()
            this.polylines = []
            if (this.route != null) {
                this.map.removeLayer(this.route)
                this.route = null;
            }
        }
        this.set = function(points) {

            var mks = [],
                origin = null,
                destination = null,
                parent = this
            this.points = points
            this.fin = false

            if (points.length != 0) {

                this.resetRoute()

                $.each(points, function(i, m) {
                    var parada = (i == 0 || i == points.length - 1) ? true : (m.parada !== undefined ? m
                        .parada : false)
                    var mclass = parent.hidden ? 'hide-marker' : (parada ? 'marker-parada' : parent.edit ?
                        'marker-tramo' : 'hide-marker')

                    //console.log('antes add', mclass)
                    parent.addMarker({
                        lat: m.lat,
                        lng: m.lng
                    }, '', parent.edit, mclass, parada)
                    // if(){
                    //   estacion = Object.assign(estacion, {index:i, num: parent.estaciones.length, linea_id: parent.id})
                    //   parent.estaciones.push(estacion)
                    // }
                })
                //console.log('MARKERS', this.markers)

            }

            if (this.edit) {
                if (!this.update) {
                    //parent.map.setView(parent.markers[parent.markers.length - 1]._latlng, 17)

                } else {
                    this.update = false
                }

            }

            setTimeout(function() {
                preload()
                parent.zoom()
            }, 1000)

        }
        this.getMarker = function(index) {
            return this.markers[index]
        }


        this.getDistance = function() {
            var d = 0

            for (var i = 1; i < this.markers.length; i++) {
                var a = this.markers[i - 1],
                    b = this.markers[i]

                d += getDistance([a._latlng.lat, a._latlng.lng], [b._latlng.lat, b._latlng.lng])

            }
            d = d / 1000
            return d.toFixed(2)
        }
        this.zoom = function() {
            if (this.markers.length == 0) {
                return
            }

            var zoommarkers = [],
                zoomZona = new L.featureGroup(),
                parent = this
            $.each(parent.markers, function(i, m) {
                var marker = new L.Marker([m._latlng.lat, m._latlng.lng])
                zoommarkers.push(marker)
                zoomZona.addLayer(marker)
            })

            zoomZona.addTo(parent.map);

            parent.map.fitBounds(zoomZona.getBounds())

            for (var m = 0; m < zoommarkers.length; m++) {
                parent.map.removeLayer(zoommarkers[m])
            }
        }


        this.animateTramo = function() {
            var m = $('#point')
            var size = parseInt(m.width() / 2)
            var parent = this
            var vel = 1
            var desde = parent.animateMarkers[parent.frameIndex]


            parent.frameIndex++
            var hasta = parent.animateMarkers[parent.frameIndex]
            if (parent.frameIndex > parent.animateMarkers.length - 1) {
                return
            }



            parent.map.setView(hasta._latlng, 17)
            var frame = parent.getLayers(hasta)
            var D = getDistance([desde._latlng.lat, desde._latlng.lng], [hasta._latlng.lat, hasta._latlng.lng])


            var aceleracion = 5 / 10000;
            var time = D / vel //Math.round(Math.sqrt((2 * D) / aceleracion));
            //console.log('TIEMPO', parent.frameIndex, time, D)
            m.animate({
                top: frame.top - size,
                left: frame.left - size
            }, time, function() {
                parent.animateTramo()
            })
        }



        this.getLayers = function(m) {
            var layerPoint = this.map.latLngToLayerPoint(m.getLatLng()),
                containerPoint = ROUTE.map.layerPointToContainerPoint(layerPoint)
            return {
                left: containerPoint.x,
                top: containerPoint.y
            }
        }

        this.hideAll = function() {
            var parent = this
            $.each(parent.markers, function(i, m) {
                m.setIcon(getIcon('', 'hide-marker'))
            })
            parent.route.setStyle({
                opacity: 0
            })
        }

        this.showAll = function() {
            var parent = this
            $.each(parent.markers, function(i, m) {
                var markerClass = (m.options.parada ? 'marker-parada' : parent.edit ? 'marker-tramo' :
                    'hide-marker')
                m.setIcon(getIcon('', markerClass, parent.getStyle(m.options.parada)))
            })
            parent.route.setStyle({
                opacity: 1
            })
        }







        this.setRoutes = function(routes) {
            this.resetRoute()
            this.routes = routes
            this.position = 0
            this.hidden = true
            this.set(routes[0])
            this.animateRoute(true, true)
        }


        this.animateRoute = function(points, init) {
            var parent = this
            if (init != undefined) {
                parent.endTravel = false
                //parent.stop = false
                parent.frameIndex = parent.position != 0 ? parent.position : 0
                parent.resetRoute()
                parent.points = points
            }
            if (parent.endTravel) {
                parent.map.removeLayer(parent.animateMarker)
                parent.animateMarker = null
                return
            }
            if (parent.frameIndex >= parent.points.length) {
                return
            }

            if (parent.pause) {
                if (parent.isParada()) {
                    console.log('PARO')
                    return
                }
            }




            var before = getLatLng(parent.points[this.frameIndex], true)

            if (init !== undefined) {
                if (!parent.resume) {
                    parent.animateMarker = parent.singleMarker(getLatLng(parent.points[this.frameIndex], true),
                        '<i class="fas fa-bus"></i>', false, 'animate-marker', parent.animateStyle)
                }
            }


            this.frameIndex++
            var after = getLatLng(parent.points[this.frameIndex], true);
            if (after) {
                parent.animationMarker(before, after)
                var bounds = L.latLngBounds([before, after]);
                parent.map.fitBounds(bounds, {
                    animate: true,
                    duration: 3
                });
            }


        }

        this.isOut = function(marker) {
            var l = this.getLayers(marker)
            var w = window.innerWidth
            var h = window.innerHeight
            var out = true
            if (l.top > 100 && l.top < h - 100 && l.left > 100 && l.left < w - 100) {
                out = false
            }

            return out
        }

        this.animationMarker = function(pointA, pointB) {
            var D = getDistance([pointA.lat, pointA.lng], [pointB.lat, pointB.lng])

            var frames = Math.round(D) * this.legFrames //100; // Número de frames para la animación
            //console.log('FRAMES', frames)
            //var vel = .5

            var duration = frames ///100// D/vel//2000; // Duración total de la animación en milisegundos
            var currentFrame = 0;

            var startLatLng = pointA;
            var endLatLng = pointB;

            if (this.stop) {
                return
            }

            this.updateMarker(pointA, pointB, frames, duration, currentFrame);
        }
        this.updateMarker = function(startLatLng, endLatLng, frames, duration, currentFrame) {
            var parent = this
            var fraction = frames != 0 ? currentFrame / frames : 0;
            var D = getDistance([startLatLng.lat, startLatLng.lng], [endLatLng.lat, endLatLng.lng])
            //var vel = .5
            var duration = D //D/vel

            var interpolatedLatLng = L.latLng([
                startLatLng.lat + fraction * (endLatLng.lat - startLatLng.lat),
                startLatLng.lng + fraction * (endLatLng.lng - startLatLng.lng)
            ]);

            parent.animateMarker.setLatLng(interpolatedLatLng);

            currentFrame++;
            //console.log('DURATION', Math.round(duration/frames) - 10*fraction)



            if (currentFrame <= frames) {
                setTimeout(function() {
                    //console.log('time out')
                    parent.updateMarker(startLatLng, endLatLng, frames, duration, currentFrame)
                }, (duration / frames)); //(Math.round(duration/frames) - 10*fraction)//duration / frames
            } else {

                parent.animateRoute()
            }
        }

        this.acelerar = function(value) {
            var f = (11 - value)
            console.log('VEL', f)
            this.legFrames = f
        }

        this.isParada = function() {

            return this.points[this.frameIndex].parada !== undefined ? true : false


        }
    }








    function pointExists(points, p) {
        var e = false
        $.each(points, function(i, point) {
            if (point.lat == p.lat && point.lng == p.lng) {
                e = true
            }
        })
        return e
    }

    function mergePoints(points, pts) {
        $.each(pts, function(i, p) {
            if (!pointExists(points, p)) {
                points.push(p)
            }
        })
        return points
    }

    function getIndex(routes, id) {
        for (var i = 0; i < routes.length; i++) {
            if (routes[i].id == id) {
                return i
            }
        }
        return null
    }

    function drawTraza(linea, map, routes) {
        var markers = []
        var points = []
        $.each(linea.tramos, function(i, tramo) {
            if (tramo.points != null) {
                points = mergePoints(points, JSON.parse(tramo.points))
            }
        })

        var route = new ruta(map, linea)

        route.set(points)
        markers = route.markers
        if (routes !== undefined) {
            var index = getIndex(routes, linea.id)
            if (index != null) {
                routes[index] = route
            } else {
                routes.push(route)
            }

        }

        $('.marker-tramo').hide()
        if (routes !== undefined) {
            return
        }
        setZoom(markers)
    }


    function getEstacionesLinea(linea) {
        var estaciones = []
        $.each(linea.tramos, function(i, tramo) {
            var points = JSON.parse(tramo.points)
            $.each(points, function(p, pt) {
                if (pt.estacion != null) {
                    estaciones.push(pt)
                }
            })
        })

        return estaciones
    }


    function getCombinaciones(lineas, data) {
        var comb = [],
            estacion = data.estacion

        $.each(lineas, function(i, l) {
            if (l.id != data.linea_id) {

                var estaciones = getEstacionesLinea(l)

                $.each(estaciones, function(j, e) {
                    var d = getDistance([data.lat, data.lng], [e.lat, e.lng])
                    ////console.log('DIST', estacion.lat, estacion.lng, e.lat, e.lng, d)
                    if (d <= 200) {
                        comb.push({
                            linea: l.linea,
                            origin: l.origen,
                            destination: l.destino,
                            estacion: e.estacion.name,
                            color: l.color
                        })
                    }
                })
            }
        })

        return comb
    }


    function getDistance(origin, destination) {
        // return distance in meters
        var lon1 = toRadian(origin[1]),
            lat1 = toRadian(origin[0]),
            lon2 = toRadian(destination[1]),
            lat2 = toRadian(destination[0]);

        var deltaLat = lat2 - lat1;
        var deltaLon = lon2 - lon1;

        var a = Math.pow(Math.sin(deltaLat / 2), 2) + Math.cos(lat1) * Math.cos(lat2) * Math.pow(Math.sin(deltaLon / 2),
            2);
        var c = 2 * Math.asin(Math.sqrt(a));
        var EARTH_RADIUS = 6371;
        return c * EARTH_RADIUS * 1000;
    }

    function toRadian(degree) {
        return degree * Math.PI / 180;
    }

    // $(document).contextmenu(function() {
    //     return false;
    // });

    // function setCombinaciones(linea, lineas){

    //    var estaciones = getEstacionesLinea(linea),


    //    $.each(estaciones, function(i, e){
    //       //console.log('COMBINACION', e.estacion.name, combinaciones(linea, lineas, e))

    //    })
    // }

    function Polygon(map, color) {
        this.map = map
        this.stroke = 5
        this.opacity = 1
        this.color = color
        this.polygon = null
        this.polylines = []
        this.markers = []
        this.selections = []
        this.recyclers = []
        this.addMarker = function(latlng) { //agrega un marcador en el mapa
            var lat = latlng.lat;
            var lng = latlng.lng;
            var parent = this


            var icon = L.divIcon({

                iconAnchor: [0.75, 0.75],
                labelAnchor: [0, 0],
                popupAnchor: [0, 0],
                zIndexOffset: 10000,
                html: '<div class="marker marker-polygon"></div>'
            });


            parent.polylines.push([lat, lng]);
            var marker = new L.Marker(
                    [lat, lng], {
                        icon: icon, //new SweetIcon(),
                        title: 'title',
                        draggable: true,
                        index: parent.polylines.length - 1
                    })
                .on('dragstart', function() {

                })
                .on('drag', function(e) {

                    var index = e.target.options.index;
                    ////console.log('drag', POLYLINES)
                    parent.polylines[index][0] = e.target._latlng.lat;
                    parent.polylines[index][1] = e.target._latlng.lng;
                    parent.set('drag');


                })
                .on('click', function(e) {

                })

            parent.markers.push(marker)
            marker.addTo(parent.map)

            parent.set();

        }


        this.set = function() { //genera un poligono y lo inicializa
            var parent = this
            if (parent.polygon != null) {
                parent.map.removeLayer(parent.polygon);

            }

            parent.polygon = L.polygon(parent.polylines, {
                color: color,
                stroke: 1,
            });
            parent.polygon.on('click', function(evt) {
                parent.polygon = evt.target;
                parent.selections = []
                parent.recyclers = []
                $.each(ROUTE.markers, function(i, marker) {
                    if (parent.polygon.contains(marker.getLatLng())) {
                        parent.selections.push(i);
                        parent.recyclers.push(marker)
                    }
                })

                // Swal.fire({
                //   title: 'Eliminar Puntos',
                //   text: parent.selections.join(','),
                //   icon: "warning",
                //   showCancelButton: true,
                //   confirmButtonColor: "#006600",
                //   confirmButtonText: "SI",
                //   cancelButtonColor: "#ff0000",
                //   cancelButtonText: "NO"
                //   //closeOnConfirm: false
                // }).then((will) => {
                //   if(will.isConfirmed){
                //     ROUTE.deletePoints(parent.selections)
                //     parent.remove()
                //   }else{
                //      parent.remove()
                //   }

                // })

                SWAL_POLYGON = Swal.fire({
                    title: 'Agrupar Puntos',
                    text: parent.selections.join(','),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#006600',
                    cancelButtonColor: '#FF0000',
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Eliminar',
                    showCloseButton: true, // Botón 3 (cerrar)
                    footer: '<button class="btn btn-dark" id="close-button">Cancelar</button>'
                })

                $('#close-button').click(function() {
                    parent.remove()
                    SWAL_POLYGON.close()
                })

                SWAL_POLYGON.then((result) => {
                    console.log('CONFIG', result)
                    if (result.isConfirmed) {
                        SWAL_POLYGON.close()
                        swalSiNo('Guardar Puntos', '¿Guardar grupo?', function() {
                            console.log('SELECTIONS', parent.selections)
                            RECYCLER = parent.recyclers
                            $('#restore-recycler').show()
                            parent.remove()
                        })
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        SWAL_POLYGON.close()
                        swalSiNo('Eliminar Puntos', '¿Eliminar grupo?', function() {
                            console.log('SELECTIONS', parent.selections)
                            ROUTE.deletePoints(parent.selections)
                            parent.remove()
                        })
                    }
                });


            })
            parent.polygon.addTo(parent.map);

        }

        this.remove = function() {
            var parent = this
            $.each(parent.markers, function(i, m) {
                parent.map.removeLayer(m)
            })
            parent.map.removeLayer(parent.polygon)

            POLYGON = null

        }
    }
</script>
