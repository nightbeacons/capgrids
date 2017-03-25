<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <title>KML Layers</title>
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
      #map {
        height: 100%;
      }
    </style>
  </head>
  <body>
    <div id="map"></div>
    <script>
<?php
$MapTypeId=$_GET['MapTypeId'];

echo "
function initMap() {
  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 11,
    mapTypeId: google.maps.MapTypeId." . $MapTypeId . ",
    center: {lat: 47.762, lng: -122.206}
  });

  var ctaLayer = new google.maps.KmlLayer({
";

$qs = $_SERVER['QUERY_STRING'];
$url = "http://www.painefieldcap.org/g2/kml.php?" . $qs;
echo "    url: '" . $url . "',";
?>
    map: map
  });
}

    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBwA88JVm20c5Hv-sHjytdIZKhhAvYyYWU&signed_in=true&callback=initMap">
    </script>
<?php
echo "http://www.painefieldcap.org/g2/kml.php?" . $qs;
?>
  </body>
</html>
