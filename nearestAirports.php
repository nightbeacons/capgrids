<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="/css/style.css" />
<?php
include_once("includes/gridFunctions.php");
include_once("includes/simple_html_dom.php");
$SELF= $_SERVER['PHP_SELF'];
$airports = array();

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

echo "<link href=\"https://fonts.googleapis.com/css?family=Roboto+Condensed\" rel=\"stylesheet\">
<style type=\"text/css\">\n";
  if ($embed) {
  echo "div.nearestApt {
          font-size: 10pt;
          font-color: black;
          margin:0;
          padding:0;
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

$latGridCenter = ($result['NW']['lat'] + $result['SW']['lat'])/2;
$lonGridCenter = ($result['NW']['lon'] + $result['NE']['lon'])/2;
echo "<h2 class=\"main nearest\">Nearest airports to center of $gridLabel</h2><p class=\"noprint\" style=\"margin-top:0\"><i>Source: <a href=\"http://www.airnav.com\" target=\"_blank\">Airnav.com</a></i></p>";
// echo "<p>Center at $latGridCenter x $lonGridCenter</p>";

// http://airnav.com/cgi-bin/airport-search?place=&airportid=&lat=44.654&NS=N&lon=122.765&EW=W&fieldtypes=a&fieldtypes=g&use=u&use=r&use=m&iap=0&length=&fuel=0&mindistance=0&maxdistance=20&distanceunits=nm
// $url = "http://www.airnav.com/cgi-bin/airport-search?place=&airportid=&lat=" . $latGridCenter . "&NS=N&lon=" . $lonGridCenter . "&EW=W&fieldtypes=a&fieldtypes=g&use=u&use=r&use=m&iap=0&length=&fuel=0&mindistance=0&maxdistance=50&distanceunits=nm";

$url = "http://www.airnav.com/cgi-bin/airport-search";

$fields_string="place=&airportid=&lat=" . $latGridCenter . "&NS=N&lon=" . $lonGridCenter . "&EW=W&fieldtypes=a&fieldtypes=g&use=u&use=r&use=m&iap=0&length=&fuel=0&mindistance=0&maxdistance=50&distanceunits=nm";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_POST, 1);
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_REFERER, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0");
$output = curl_exec($ch);
curl_close($ch);

$results = preg_replace("|.*?<H1>Airport Search Results</H1>.*?(<TABLE.*?</TABLE>).*|s", '$1', $output);
$html = str_get_html($results);
$cnt=0;
foreach($html->find('tr') as $row) {
  $airports[$cnt]['link'] = preg_replace('/.*<a href="(.*?)".*/', 'https://www.airnav.com$1', $row);
    if (strpos($airports[$cnt]['link'], "https://") !== FALSE) {
    $ary = preg_split("/<td.*?>/", $row);
    $airports[$cnt]['code'] = preg_replace("/<i>(.*?)&nbsp.*/", "$1", $ary[1]);
    $airports[$cnt]['city'] = preg_replace("/<i>(.*?)&nbsp.*/", "$1", $ary[2]);
    $airports[$cnt]['name'] = preg_replace("/<i>(.*?)&nbsp.*/", "$1", $ary[3]);
    $airports[$cnt]['distance'] =  preg_replace("/(.*?)<br>.*/",     "$1", $ary[4]);
    $cnt++;
    }
}
echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">
      <tr><th><div class=\"nearestApt\">Code</div></th><th><div class=\"nearestApt\">Name</div></th><th><div class=\"nearestApt\">City</div></th><th><div class=\"nearestApt\">Distance</div></th></tr>\n";
   for ($i=0; $i<=4; $i++){
   echo "<tr><td><div class=\"nearestApt\"><a class=\"nearestApt\" href=\"" . $airports[$i]['link'] . "\" target=\"_blank\">" . $airports[$i]['code'] . "</a></div></td><td><div class=\"nearestApt\">" . $airports[$i]['name'] . "</div></td><td><div class=\"nearestApt\">" . $airports[$i]['city'] . "</div></td><td><div class=\"nearestApt\">" . $airports[$i]['distance'] . "</div></td></tr>\n";
   }
echo "</table>";
?>
</body>
