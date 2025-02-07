<div>
    <div id="map" style="height: 400px;"></div>

    <script>
        function initMap() {
            let latInput = document.querySelector("input[name='latitude']");
            let lngInput = document.querySelector("input[name='longitude']");

            let lat = parseFloat(latInput.value) || 24.7136;
            let lng = parseFloat(lngInput.value) || 46.6753;

            let map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: lat, lng: lng },
                zoom: 10
            });

            let marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                draggable: true
            });

            google.maps.event.addListener(map, 'click', function(event) {
                let clickedLocation = event.latLng;
                marker.setPosition(clickedLocation);
                latInput.value = clickedLocation.lat();
                lngInput.value = clickedLocation.lng();
            });

            google.maps.event.addListener(marker, 'dragend', function(event) {
                latInput.value = event.latLng.lat();
                lngInput.value = event.latLng.lng();
            });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOwsG6thiGKjmTt2Ru6XRE7uwGsyw5Tv0&callback=initMap" async defer></script>
</div>
