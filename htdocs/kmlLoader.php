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
include_once("includes/gridFunctions.php");
include_once("/var/www/capgrids/pwf/keys.php");
$gridBoxAry = grid2lonlat($_GET['id'], $_GET['mygrid'], $_GET['myquadrant'], 'raw');
$avgLat = ($gridBoxAry['NW']['lat'] + $gridBoxAry['SW']['lat']) / 2;
$avgLon = ($gridBoxAry['NW']['lon'] + $gridBoxAry['NE']['lon']) / 2;
$MapTypeId=$_GET['MapTypeId'];

echo "
function initMap() {
  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 11,
    zoomControl: false,
    scaleControl: false,
    streetViewControl: false,
    mapTypeId: google.maps.MapTypeId." . $MapTypeId . ",
 //   center: {lat: " . $avgLat . ", lng: " . $avgLon . "}
  });

  var ctaLayer = new google.maps.KmlLayer({
";

$qs = $_SERVER['QUERY_STRING'];
// $url = "http://www.painefieldcap.org/g2/kml.php?" . $qs;
$url = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?" . $qs;
echo "    url: '" . $url . "',";
?>
    map: map
  });
}

    </script>
<?php
$mapUrl = "https://maps.googleapis.com/maps/api/js?key=" . $api_key . "&signed_in=true&callback=initMap";
echo "<script async defer src=\"" . $mapUrl . "&signed_in=true&callback=initMap\">\n</script>\n";



// echo "http://www.painefieldcap.org/g2/kml.php?" . $qs;
echo "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?" . $qs;
?>
  </body>
</html>
