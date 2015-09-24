<?php
######################################################################
# fpl.php
#
# Accepts a query string of the form:
#
#               fpl.php?id=SEATTLE&mygrid=42&myquadrant=A
#
# and outputs an FPL (Flight Plan)  file for all four quadrants of the
# selected grid, plus the entire grid.
#
#     Waypoint nomenclature
#
#     NW         N          NE
#     +----------+----------+
#     |          |          |
#     |   1      |   2      |
#     |          |          |
#     |          |          |
#  W  +--------- C ---------+ E
#     |          |          |
#     |   3      |   4      |
#     |          |          |
#     |          |          |
#     +----------+----------+
#     SW        S           SE
#
#  Thus, top-right waypoint of the "B" grid is <grid_ident>NE
#  Numbers refer to the flight-plan-index number
#
#
######################################################################

include_once("includes/gridFunctions.php");

$TEST = 0;

$header="Content-type: application/octet-stream";
$buffer="";

$creationDate = gmdate("Y-m-d\TH:i:s\Z");

$FPLheader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<flight-plan xmlns=\"http://www8.garmin.com/xmlschemas/FlightPlan/v1\">
  <author>
     <author-name>CAP Gridmaster</author-name>
     <link>www.painefieldcap.org/gridmaster/</link>
  </author>
  <created>$creationDate</created>\n";

$offset = "0.000000000001";
$myquadrant="E";
$allWaypoints = $waypoints = array();

$allQuadrants=array("E","A","B","C","D");

# Define coordinates for each quadrant, going clockwise
$quadrantMap = array("A" => array("NW", "N", "C", "W", "NW"),
                     "B" => array("N", "NE", "E", "C", "N"),
                     "C" => array("W", "C", "S", "SW", "W"),
                     "D" => array("C", "E", "SE", "S", "C"),
                     "E" => array("NW", "NE", "SE", "SW", "NW")
                    ); 


foreach($allQuadrants as $myquadrant){

$quadrantDisplay="";
$FNquadrantDisplay="";
$lonlatflag=0;
	if (isset($_GET['id'])) {
	$sectional=$_GET['id'];
	}	
if (isset($_GET['mygrid'])) $mygrid=$_GET['mygrid'];
# if (isset($_GET['myquadrant'])) $myquadrant=$_GET['myquadrant'];

	if ($myquadrant != "E") {
	$quadrantDisplay="-" . $myquadrant;
	$FNquadrantDisplay= " " . $myquadrant;
	}

# Determine the raw lon/lat of the top-left grid corner
$gridLon=$gridLat=$longitude=$latitude=0;

$result = grid2lonlat($sectional,$mygrid, $myquadrant, "raw");

$abbrev = $coordinates[strtoupper($sectional)]['Abbrev'];

$gridLon = $result['NW']['lon'];  # Returns coords of NW corner of grid or quarter-grid
$gridLat = $result['NW']['lat'];  # 

$gridLabel = "$abbrev  $mygrid$quadrantDisplay";
$mygridLeadZeros = sprintf("%03s", $mygrid); 
$filenameHeader = "Content-Disposition: attachment; filename=\"" .$abbrev . " " . $mygridLeadZeros . ".fpl\"";

#$identifierBase = $abbrev . $mygridLeadZeros . trim($FNquadrantDisplay);
$identifierBase = $abbrev . $mygridLeadZeros;


#echo "Lon: $gridLon    Lat: $gridLat    $abbrev - $mygrid \n";


if ($myquadrant == "E") {
$nw_coord = array('lat' => $gridLat, 'lon' => $gridLon);
$ne_coord = array('lat' => $gridLat, 'lon' => ($gridLon + 0.25));
$se_coord = array('lat' => ($gridLat - 0.25), 'lon' => ($gridLon + 0.25));
$sw_coord = array('lat' => ($gridLat - 0.25), 'lon' => $gridLon);
} else {
$nw_coord = array('lat' => $gridLat, 'lon' => $gridLon);
$ne_coord = array('lat' => $gridLat, 'lon' => ($gridLon + 0.125));
$se_coord = array('lat' => ($gridLat - 0.125), 'lon' => ($gridLon + 0.125));
$sw_coord = array('lat' => ($gridLat - 0.125), 'lon' => $gridLon);
}

# Place waypoints for all grids into master array

$allWaypoints[$myquadrant]['NW'] =  $nw_coord;
$allWaypoints[$myquadrant]['NE'] =  $ne_coord;
$allWaypoints[$myquadrant]['SE'] =  $se_coord;
$allWaypoints[$myquadrant]['SW'] =  $sw_coord;

}    // End of foreach($allQuadrants as $myquadrant)

# Select the unique waypoints needed for the four grid quadrants

$waypoints['NW'] = $allWaypoints['A']['NW'];
$waypoints['N']  = $allWaypoints['A']['NE'];
$waypoints['NE'] = $allWaypoints['B']['NE'];

$waypoints['W'] = $allWaypoints['A']['SW'];
$waypoints['C'] = $allWaypoints['A']['SE'];
$waypoints['E'] = $allWaypoints['B']['SE'];

$waypoints['SW'] = $allWaypoints['C']['SW'];
$waypoints['S']  = $allWaypoints['C']['SE'];
$waypoints['SE'] = $allWaypoints['D']['SE'];


# Create the waypoint table

$buffer = "<waypoint-table>\n";
	foreach($waypoints as $key => $oneWaypoint){
	$buffer .= "    <waypoint>
      <identifier>" . $identifierBase . $key . "</identifier>
      <type>USER WAYPOINT</type>
      <country-code>K1</country-code>
      <lat>" . $oneWaypoint['lat'] . "</lat>
      <lon>" . $oneWaypoint['lon'] . "</lon>
      <comment></comment>
    </waypoint>\n";

	}	
$buffer .= "  </waypoint-table>\n";


# Create one route for each quadrant, and one for the entire grid

$flightPlanIndex=0;

	foreach ($quadrantMap as $key => $oneQuad){
	$whichQuadrant = " " . $key;
                if ($key=="E") $whichQuadrant="";
	$flightPlanIndex++;
	$buffer .= "  <route>
    <route-name>" . $abbrev . " " . $mygridLeadZeros . $whichQuadrant . "</route-name>
    <flight-plan-index>" . $flightPlanIndex . "</flight-plan-index>\n";

		foreach($oneQuad as $quadCorner){
		$buffer .= "    <route-point>
      <waypoint-identifier>" . $identifierBase . $quadCorner . "</waypoint-identifier>
      <waypoint-type>USER WAYPOINT</waypoint-type>
      <waypoint-country-code>K1</waypoint-country-code>
    </route-point>\n";
		}	

	$buffer .= "  </route>\n";
	}

$buffer .= "</flight-plan>\n";


header($header);
header($filenameHeader);
echo "$FPLheader";

echo "$buffer";

# ===================================================================================================

?>
