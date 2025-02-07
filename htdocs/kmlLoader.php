<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/gridFunctions.php");
include_once("/var/www/capgrids/pwf/keys.php");
$gridBoxAry = grid2lonlat($_GET['id'], $_GET['mygrid'], $_GET['myquadrant'], 'raw');
$avgLat = ($gridBoxAry['NW']['lat'] + $gridBoxAry['SW']['lat']) / 2;
$avgLon = ($gridBoxAry['NW']['lon'] + $gridBoxAry['NE']['lon']) / 2;
$MapTypeId=$_GET['MapTypeId'];
$qs = $_SERVER['QUERY_STRING'];

//$url = "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?" . $qs;
//$url = "https://dev.capgrids.com/kml/149/D/TERRAIN/1/SEATTLE.kml";
$url = "https://www.capgrids.com/kml/" . $_GET['mygrid'] . "/" . $_GET['myquadrant'] . "/" . $_GET['MapTypeId'] . "/" . $_GET['embed'] . "/" . $_GET['id'] . ".kml";
//$url = "https://dev.capgrids.com/SEATTLE.kml";

$initMap = "
function initMap() {
  const map = new google.maps.Map(document.getElementById('map'), {
    zoom: 11,
    zoomControl: false,
    scaleControl: false,
    disableDefaultUI: true,
    streetViewControl: false,
    mapTypeControl: false,
    mapTypeId: google.maps.MapTypeId." . $MapTypeId . ",
 //   center: {lat: " . $avgLat . ", lng: " . $avgLon . "}
  });

  const ctaLayer = new google.maps.KmlLayer({
    url: \"" . $url . "\",
    map: map,
  });
}\n"; 

?>
<!DOCTYPE html>
<html lang="en">
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
    <script>
    <?php echo $initMap; ?>
    </script>
  </head>
  <body>
    <div id="map" style="height:100%;"></div>
<?php
$mapUrl = "https://maps.googleapis.com/maps/api/js?key=" . $googleAPIkey . "&loading=async&callback=initMap";
echo "<script defer src=\"" . $mapUrl . "\">\n</script>\n";

//echo "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?" . $qs;
?>
  </body>
</html>
