#!/usr/bin/php
<?php
/**
 * @file
 * File: generateSectionalOverlays.php.
 *
 * Fetch and generate Sectional Overlays.
 *
 * Detecting all-white pngs -- see http://www.imagemagick.org/discourse-server/viewtopic.php?t=30614
 */


// include_once "/var/www/capgrids/includes/coordinates2.php";
include_once "/var/www/capgrids/pwf/apt.php";


$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

$fh = fopen("/var/www/capgrids/htdocs/includes/latestSectionals.php", "w");
$count=0;
$query = "SELECT FullName, Abbrev, editionNumber, DATE_FORMAT(editionDate, '%e-%M-%Y') AS editionDate from coordinates
   WHERE editionDate = (select distinct editionDate from coordinates  order by editionDate desc limit 1)
   ORDER BY FullName";

$r1 = $db->query($query);

  while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
    if ($count ==0){
      fwrite($fh, "<p class=\"sectionalUpdateHead\">Sectionals updated " . $myrow['editionDate'] . ":</p>\n<ul>\n");
    } 
  fwrite($fh, "<li class=\"sectionalUpdateRow\"><a class=\"sectionalUpdateLink\" href=\"/overlays/" . $myrow['Abbrev'] . "_grid.kmz\">" . $myrow['FullName'] . "</a>  " . $myrow['editionNumber'] . "</li>\n"); 
  $count++;
  }
fwrite($fh, "</ul>\n");
fclose($fh);
