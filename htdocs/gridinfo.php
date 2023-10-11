<?php
include_once("includes/coordinates.php");
include_once "/var/www/capgrids/pwf/apt.php";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}
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
$sectional=trim(filter_var($_GET['id'], FILTER_SANITIZE_STRING));
$query = "SELECT FullName, Abbrev, editionNumber, DATE_FORMAT(editionDate, '%e-%M-%Y') AS editionDate from coordinates WHERE chartName='" . $sectional . "'";
  $r1 = $db->query($query);
  while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
    $sectionalName=$myrow['FullName'];
    $abbrev=trim($myrow['Abbrev']);
    $editionDate = trim($myrow['editionDate']);
  }
$FullName_no_spaces = str_replace(" ", "_", $sectionalName);
$mapDownload = "/gridded/sectional/" . $FullName_no_spaces . ".png";
$fp = fopen($_SERVER['DOCUMENT_ROOT'] . $mapDownload, 'r');
$fstat = fstat($fp);
fclose($fp);
$file_size = floor($fstat['size'] / (1024 * 1024) + 0.5);

echo "<hr width=\"50%\"><h3 style=\"color:#CC3300; font-family: Arial; margin-bottom:0; font-size: 20px; font-weight: 600;text-align:center;\">Search Grids for the $sectionalName Sectional</h3>\n";
// echo "<p style=\"font-size:15px;font-family: Arial;text-align:center;font-style:italic;margin-top:0;\">Sectional charts updated $editionDate</p>\n";

$kmz = "overlays/" . $abbrev . "_grid.kmz";

$cap_es_url = "http://www.cap-es.net/CAPGrids/grid_" . strtolower($coordinates[$sectional]['Abbrev']) . ".html";

$fpl = "fpl/" . $coordinates[$sectional]['Abbrev'] . "_FPL.zip";

echo "	<ul>\n";
echo "	<li><a href=\"$kmz\">Google Earth grid overlay for $sectionalName</a> <i>(with latest sectional)</i></li>\n";
//echo "	<li><a href=\"http://www.gelib.com/maps/_NL/aeronautical-charts-united-states.kml\">Google Earth overlays for Sectional and Terminal charts</a></li>\n";
echo "	<li><b>NEW: </b><a href=\"$mapDownload\" target=\"_blank\" download>Download <b><i>current</i></b> gridded $sectionalName sectional</a> <i><small>(PNG, $file_size MB)</small></i>\n";
echo "	<li><a href=\"$cap_es_url\" target=\"_blank\">CAP-ES Grid Page for $sectionalName</a></li>\n";
echo "	<li><a href=\"$fpl\">Download Garmin 695 Flight Plan package for $sectionalName sectional</a></li>\n";
echo "	</ul>\n";

?>
