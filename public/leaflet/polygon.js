(function(L) {
    "use strict";
    
    L.Polyline.prototype.contains = function (p) {
        //"use strict";
        var rectangularBounds = this.getBounds();  // It appears that this is O(1): the LatLngBounds is updated as points are added to the polygon when it is created.
        var wn;
        if (rectangularBounds.contains(p)) {
            wn = this.getWindingNumber(p);
            return (wn !== 0);
        } else {
            return false;
        }
    };

    
    L.LatLng.prototype.isLeft = function (p1, p2) {
        return ((p1.lng - this.lng) * (p2.lat - this.lat) -
                (p2.lng - this.lng) * (p1.lat - this.lat));
    };

    
    L.Polyline.prototype.getWindingNumber = function (p) { // Note that L.Polygon extends L.Polyline
        var i,
            isLeftTest,
            n,
            vertices,
            wn; // the winding number counter
        function flatten(a) {
            var flat;
            flat = ((Array.isArray ? Array.isArray(a) : L.Util.isArray(a)) ? a.reduce(function (accumulator, v, i, array) {
                    return accumulator.concat(Array.isArray(v) ? flatten(v) : v);
                }, [])
                : a);
            return flat;
        }

        vertices = this.getLatLngs();
        vertices = flatten(vertices); // Flatten array of LatLngs since multi-polylines return nested array.
        // Filter out duplicate vertices.  
        vertices = vertices.filter(function (v, i, array) { // remove adjacent duplicates
            if (i > 0 && v.lat === array[i-1].lat && v.lng === array[i-1].lng) {
                return false;
            } else {
                return true;
            }
        });
        n = vertices.length;
        // Note that per the algorithm, the vertices (V) must be "a vertex points of a polygon V[n+1] with V[n]=V[0]"
        if (n > 0 && !(vertices[n-1].lat === vertices[0].lat && vertices[n-1].lng === vertices[0].lng)) {
            vertices.push(vertices[0]);
        }
        n = vertices.length - 1;
        wn = 0;
        for (i=0; i < n; i++) {
            isLeftTest = vertices[i].isLeft(vertices[i+1], p);
            if (isLeftTest === 0) { // If the point is on a line, we are done.
                wn = 1;
                break;
            } else {
                if (isLeftTest !== 0) { // If not a vertex or on line (the C++ version does not check for this)
                    if (vertices[i].lat <= p.lat) {
                        if (vertices[i+1].lat > p.lat) { // An upward crossing
                            if (isLeftTest > 0) { // P left of edge
                                wn++; // have a valid up intersect
                            }
                        }
                    } else {
                        if (vertices[i+1].lat <= p.lat) {// A downward crossing
                            if (isLeftTest < 0) { // P right of edge
                                wn--; // have a valid down intersect
                            }
                        }
                    }
                } else {
                    wn++;
                }
            }
        }
        return wn;
    };

})(L);