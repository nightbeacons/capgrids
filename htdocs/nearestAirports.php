<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="/css/style.css" />
<?php
include_once("includes/gridFunctions.php");
include_once("includes/simple_html_dom.php");
include_once("/var/www/capgrids/pwf/apt.php");
include_once("includes/lastMod.php");

//$cmd = "stat --format=%Z " .  $_SERVER['DOCUMENT_ROOT'] . "/data";
//$dataLastModified = date("j-M-Y", trim(`$cmd`));

$SELF= $_SERVER['PHP_SELF'];

$db =  new mysqli($dbserver, $dbuser, $dbpass, $dbname);
  if (mysqli_connect_errno()){
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
  }

$airports = array();
$numAirports = 5;
$sectional = $_GET['id'];
$mygrid = $_GET['mygrid'];
$myquadrant = $_GET['myquadrant'];
$embed = (isset($_GET['embed']) ? $_GET['embed'] : 0);

$result = grid2lonlat($sectional,$mygrid, $myquadrant, "raw");
$abbrev = $coordinates[$sectional]['Abbrev'];
$gridLabel = "$abbrev $mygrid";
  if ($myquadrant != "E") {
  $gridLabel .= "-" . $myquadrant;
  }

$latGridCenter = ($result['NW']['lat'] + $result['SW']['lat'])/2;
$lonGridCenter = ($result['NW']['lon'] + $result['NE']['lon'])/2;

$airports = getNearestAirports($latGridCenter, $lonGridCenter, 100, $numAirports);

echo "<link href=\"https://fonts.googleapis.com/css?family=Roboto+Condensed\" rel=\"stylesheet\">
<style type=\"text/css\">\n";
  if ($embed) {
  echo "div.nearestApt {
          font-size: 8.5pt;
          font-color: black;
          margin:0;
          padding:0;
          line-height:1;
       }
       a.nearestApt {
          text-decoration: none;
          color: black;
        }
        p.noprint {
          display:none;
        }
        h2.nearest {
        font-size:12pt;
        margin-top: 0;
        color:black;
        }";
  }

echo "</style>\n";
echo "</head>
<body style=\"margin:0;\">";

$html = "<h2 class=\"main nearest\">Nearest Airports to Center of $gridLabel</h2>";

  if ($embed){
     //  Code for PRINT version
    $html .= "<table border=\"1\" cellpadding=\"2\" cellspacing=\"0\">
      <tr><th><div class=\"nearestApt\">Code</div></th><th><div class=\"nearestApt\">Name</div></th><th><div class=\"nearestApt\">Distance</div></th></tr>\n";

       foreach($airports as $oneAirport){

            $html .= "<tr><td><div class=\"nearestApt\">" . $oneAirport['aptCode'] . "</div></td><td><div class=\"nearestApt\">" . strtoupper($oneAirport['name']) . "<br><small><i>" . strtoupper($oneAirport['city'] . ", " . $oneAirport['stateAbbrev']) . "</i></small></div></td><td><div class=\"nearestApt\">" . $oneAirport['distance'] . "&nbsp;nm " . $oneAirport['compass'] . "</div></td></tr>\n";
       }
    $html .= "</table>";

  } else {

    // Code for DISPLAY version
    $html .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">
      <tr><th><div class=\"nearestApt\">Code</div></th><th><div class=\"nearestApt\">Name</div></th><th class=\"noprint\"><div class=\"nearestApt\">City</div></th><th><div class=\"nearestApt\">Distance</div></th></tr>\n";
       foreach($airports as $oneAirport){
            $html .= "<tr><td><div class=\"nearestApt\"><a class=\"nearestApt\" href=\"https://www.airnav.com/airport/" . $oneAirport['aptCode'] . "\" target=\"_blank\">" . $oneAirport['aptCode'] . "</a></div></td><td><div class=\"nearestApt\">" . strtoupper($oneAirport['name']) . "</div></td><td><div class=\"nearestApt\">" . strtoupper($oneAirport['city'] . ", " . $oneAirport['stateAbbrev']) . "</div></td><td><div class=\"nearestApt\">" . $oneAirport['distance'] . "&nbsp;nm " . $oneAirport['compass'] . "</div></td></tr>\n";
       }
    $html .=  "</table><p style=\"display:block;margin-top:0;text-align:right;\"><i>Airport data current as of $dataLastModified</i></p>";
  }

echo $html;
echo "</body>\n";



/**
 * Get the "limit" Nearest Airports to given lat/lon
 * using Haversine formula
 *   Input:  - Lat and Lon of grid center, in decimal degrees (XXX.yyyyyy)
 *           - Maximum distance from grid center, in NM
 *           - Maximum number of rows to return
 *   Output: Array of matching airports
 */

function getNearestAirports($latGridCenter, $lonGridCenter, $maxDistanceFromAirportNM, $limit=5){
global $db;

$airports=array();
//$bearing=array();
//$compass=array();
//$distance = 30 / 60  ;
$NM_conversion = 3440;    // Radius of Earth in NM
//$latitudeBottom  = $latGridCenter - $distance;
//$latitudeTop     = $latGridCenter + $distance;
//$longitudeBottom = $lonGridCenter - $distance; 
//$longitudeTop    = $lonGridCenter + $distance;


//$query = "SELECT name, city, aptCode, decLatitude, decLongitude from apt_data 
//          WHERE (decLatitude BETWEEN $latitudeBottom AND $latitudeTop) 
//          AND (decLongitude BETWEEN $longitudeBottom AND $longitudeTop)";

//  Longitude of airport is X(coordinates)
//  Latitude of airport is Y(coordinates)
$haversine_query = "SELECT name, city, stateAbbrev, aptCode, X(coordinates),Y(coordinates),
                   ($NM_conversion *  acos (
                     cos( radians($latGridCenter))
                   * cos( radians( Y(coordinates)))
                   * cos( radians($lonGridCenter) - radians( X(coordinates)))
                   + sin( radians($latGridCenter))
                   * sin( radians( Y(coordinates)))
                   ) ) AS distance
                  FROM apt_data
                  HAVING distance < $maxDistanceFromAirportNM
                  ORDER BY distance ASC
                  LIMIT $limit";

//echo "<pre>$haversine_query\n</pre>";
$r1 = $db->query($haversine_query);

$idx=0;
  while ($myrow=$r1->fetch_array(MYSQLI_ASSOC)){
    $oneAirport=array();
    $airportDecLatitude        = $myrow['Y(coordinates)'];
    $airportDecLongitude       = $myrow['X(coordinates)'];
    $oneAirport['aptCode']     = $myrow['aptCode'];
    $oneAirport['name']        = $myrow['name'] . " Airport";
    $oneAirport['distance']    = round($myrow['distance'], 1);
    // $dist = gc_distance($lat, $lon, $latGridCenter, $lonGridCenter);
    $oneAirport['city']        = $myrow['city'];
    $oneAirport['stateAbbrev'] = $myrow['stateAbbrev'];
    // For bearing, pass the lat + long of grid center, then the lat + lon of target airport
    $oneAirport['bearing']     = getRhumbLineBearing($latGridCenter, $lonGridCenter, $airportDecLatitude, $airportDecLongitude);
    $oneAirport['compass']     = getCompassDirection($oneAirport['bearing']);
    $airports[] = $oneAirport;
  }

return($airports);
}

