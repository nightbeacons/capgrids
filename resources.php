<?php
######################################################################
# resources.php
#
# Accepts a query string of the form:
#
#               kml.php?id=SEATTLE&mygrid=42&myquadrant=A
#          or
#               kml.php?lat=48.625&lon=-123.456
#
# and outputs a KML file of the appropriate grid.
#
# In the second case, where a lon and lat is given, provide the KML grid, 
# and add a placemark for the given lon/lat
#
# The Google Maps query string uses the "t" parameter to specify
# the type of map to display.
#
#       t=m		Display the default street map
#	t=h		Display the satellite  map
#	t=p		Display the terrain map
#       t=f		Display the Google Earth map (very slow)
######################################################################

include_once("includes/gridFunctions.php");

global $mapType;

$lonlatflag=0;
$quadrantDisplay="";

	if (isset($_GET['id'])) {
	$sectional=$_GET['id'];
	}	
if (isset($_GET['mygrid'])) $mygrid=$_GET['mygrid'];
if (isset($_GET['myquadrant'])) $myquadrant=$_GET['myquadrant'];

	if (isset($_GET['lon'])) {
	$pointLongitude=$_GET['lon'];
	$lonlatflag=1;
	}
	if (isset($_GET['lat'])) $pointLatitude=$_GET['lat'];

if ($myquadrant != "E") $quadrantDisplay= "-" . $myquadrant;

# Determine the raw lon/lat of the top-left grid corner
$gridLon=$gridLat=$longitude=$latitude=0;

	if ($lonlatflag) {
	$result1 = lonlat2grid($pointLongitude, $pointLatitude);
	$sectional = $result1['sectional'];
	$mygrid= $result1['grid'];
	$myquadrant = $result1['quadrant'];
	} 
$result = grid2lonlat($sectional,$mygrid, $myquadrant, "raw");

$abbrev = $coordinates[$sectional]['Abbrev'];

$gridLon = $result['NW']['lon'];
$gridLat = $result['NW']['lat'];

$gridLabel = "$abbrev - $mygrid";
$filenameHeader = "Content-Disposition: attachment; filename=\"" .$abbrev . "_" . $mygrid . "-" . $myquadrant . ".kml\"";

$sectionalName=ucwords(strtolower(preg_replace("/_/"," ", $sectional)));

#echo "Lon: $gridLon    Lat: $gridLat    $abbrev - $mygrid \n";


?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />

<script src="js/jquery-1.9.1.js"></script>
<script src="js/jquery-ui.js"></script>
<script>

 $(function() {
$( "#slider" ).slider({
range: "min",
min: 0,
max: 100,
value: 80,
change: changeOpacity,
slide: changeOpacity 
});
//$( "#amount" ).val( $( "#slider" ).slider( "value" ) );
});


function changeOpacity(){
  var op = $("#slider").slider("value");
  var op1 = ($("#slider").slider("value"))/100;
  var op2 = 1 - op1;
  $( "#top").css( "opacity", op1 );
  $( "#bottom").css( "opacity", op2 );
}
</script>


<link rel="stylesheet" href="css/jquery-ui.css" />
<link rel="stylesheet" href="css/style.css" />

  <style type="text/css">
    <!--

.maindiv {
	position: relative;
	width: 475px;
	margin-left: auto;
	margin-right: auto;
	}
div.clearOverlay {
        background: transparent;
        position: absolute;
        z-index: 99;
        width:475px;
        height:380px;
        top:0px;
        left:0px;
}
#top {
	position: absolute;
	top: 0px;
	left: 0px;
        z-index: 5;
	opacity: 0.8;
	}

#bottom {
        position: absolute;
        top: 0px;
        left: 0px;
        z-index: 4;
        opacity: 0.2;
        }


#slider {
	position: relative;
	width: 475px;
	top: 390px;
	// background-color: blue;
	}

#text {
	position: relative;
	top: 410px;
	left: 60px;
        font-family: Arial, Helvetica;
        font-size: 10pt;
	line-height: 20pt;
	}	
    -->
  </style>

</head>
<body>
<?php
echo "<h2 class=\"main\">Google Mapping for $abbrev $mygrid$quadrantDisplay</h2>\n";


$kmlURL = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?id=" . $sectional . "&mygrid=" . $mygrid . "&myquadrant=" . $myquadrant;
$zoom=11;
if ($myquadrant == "E") $zoom=10;
$kmlURLencoded = rawurlencode($kmlURL);

$fplURL = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "fpl.php?id=" . $sectional . "&mygrid=" . $mygrid ;

$TerrainMapiframeBase= "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['terrain'] . "&amp;output=embed&amp;";
$TerrainMapiframeSrc  = $TerrainMapiframeBase .  "&amp;z=" . $zoom;

// $TerrainMapiframeSrc = "http://www.painefieldcap.org/g2/kmlLoader.php?id=" . $sectional . "&mygrid=" . $mygrid . "&myquadrant=" . $myquadrant . "&MapTypeId=TERRAIN&embed=1";
$TerrainMapiframeSrc = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kmlLoader.php?id=" . $sectional . "&mygrid=" . $mygrid . "&myquadrant=" . $myquadrant . "&MapTypeId=TERRAIN&embed=1";

$SatMapiframeBase= "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['satellite'] . "&amp;output=embed&amp;";
$SatMapiframeSrc  = $SatMapiframeBase .  "&amp;z=" . $zoom;
//$SatMapiframeSrc  = "http://www.painefieldcap.org/g2/kmlLoader.php?id=" . $sectional . "&mygrid=" . $mygrid . "&myquadrant=" . $myquadrant . "&MapTypeId=HYBRID&embed=1";
$SatMapiframeSrc  = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kmlLoader.php?id=" . $sectional . "&mygrid=" . $mygrid . "&myquadrant=" . $myquadrant . "&MapTypeId=HYBRID&embed=1";

# $iframeHref = $TerrainMapiframeBase .  "&amp;z=" . ($zoom + 1);
#echo "<iframe id=\"top\" width=\"475\" height=\"380\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $iframeSrc . "\"></iframe>";

echo "<div class=\"maindiv\">";
echo "<div class=\"clearOverlay\"></div>\n";
echo "<iframe id=\"top\" width=\"475\" style=\"opacity: 0.8;\" height=\"380\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $TerrainMapiframeSrc . "\"></iframe>";
echo "<iframe id=\"bottom\" width=\"475\" style=\"opacity: 0.2;\" height=\"380\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $SatMapiframeSrc . "\"></iframe>";
echo "<div id=\"slider\" title=\" Adjust Map Tranparency \"></div>\n";

echo "<div id=\"text\">
&raquo; <a href=\"$kmlURL\">Download Google Earth Overlay for $abbrev $mygrid$quadrantDisplay</a><br>
&raquo; <a href=\"$fplURL\">Download G1000/G695 Flight Plan file for all quadrants in $abbrev $mygrid</a><br>\n";
#echo "<input type=\"text\" id=\"amount\" style=\"border: 0; color: #f6931f; font-weight: bold;z-index:-1;\" />\n";
echo "</div>

</div>\n";

?>
</body>
</html>

