<?php
include_once("includes/coordinates.php");
?>
<style type="text/css">
li{
  font-family: Arial;
}

a{
 text-decoration: none;
}

a:hover{
 text-decoration: underline;
}
</style>
<?php
$sectional=$_GET['id'];

$sectionalName=ucwords(strtolower(preg_replace("/_/"," ", $sectional)));
echo "<hr width=\"50%\"><h3 style=\"color:#CC3300; font-family: Arial; font-size: 20px; font-weight: 600;text-align:center;\">Search Grids for the $sectionalName Sectional</h3>\n";

$kmz = "overlays/" . $coordinates[$sectional]['Abbrev'] . "_grid.kmz";

$cap_es_url = "http://www.cap-es.net/CAPGrids/grid_" . strtolower($coordinates[$sectional]['Abbrev']) . ".html";

$mapDownload = "http://www.cap-es.net/gridded/" . rawurlencode(ucwords(strtolower(preg_replace("/_/"," ", $sectional)))) . "%20Sectional.pdf";

$fpl = "fpl/" . $coordinates[$sectional]['Abbrev'] . "_FPL.zip";

echo "	<ul>\n";
echo "	<li><a href=\"$kmz\">Google Earth grid overlay for $sectionalName</a></li>\n";
echo "	<li><a href=\"http://www.gelib.com/maps/_NL/aeronautical-charts-united-states.kml\">Google Earth overlays for Sectional and Terminal charts</a></li>\n";
echo "	<li><a href=\"$mapDownload\" download>Download gridded $sectionalName sectional</a> <i>(PDF)</i>\n";
echo "	<li><a href=\"$cap_es_url\" target=\"_blank\">CAP-ES Grid Page for $sectionalName</a></li>\n";
echo "	<li><a href=\"$fpl\">Download Garmin 695 Flight Plan package for $sectionalName sectional</a></li>\n";
echo "	</ul>\n";

?>
