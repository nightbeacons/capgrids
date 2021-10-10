#!/usr/bin/php
<?php
include_once "/var/www/capgrids/pwf/aixm.php";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}


$output_file = "nav.dat";

$ndb = ndb_build();

echo $ndb;

/**
 * Ndb_build()
 * Create the "2" (NDB) records
 * Record format for NDB is:
 *  Column 1:   2
 *  Column 3:  latitude of NBB, in decimal degrees, right-alighed to column 15, formatted to 8 decimal places
 *  Column 16: longitude of NBB, in decimal degrees, right-aligned to col 29, formatted to 8 decimal places
 *  Column 32: elevation in MSL. Integer, right-aligned to Col 36
 *  Column 39: Frequency (kHz), integer. Right-aligned to Col 42
 *  Column 43: Range (NM), integer. Right-aligned to Col 46. (Extract from 'class' column) for XP in the US, always 50
 *  Column 50:  Always 0.0
 *  Column 54: NDB identifier, up to 4 chars. Left-aligned
 *  Column 59: NDB Name. Left-aligned. Always suffixed with " NDB".
 */
function ndb_build() {
  global $db;

  $ndb = "";
  $query = "SELECT id, name, type, class, frequency, power, decLongitude, decLatitude, elevation_10 from nav
            WHERE (type = 'NDB' OR type='NDB/DME')
            AND status regexp 'OPERATIONAL'
            AND public='Y'
            ORDER BY name ASC";
  $r1 = $db->query($query);

  while ($row = $r1->fetch_assoc()) {
    $latitude  = str_replace('+', ' ', sprintf("%11s", sprintf("%+012.8f", $row['decLatitude'])));
    $longitude = str_replace('+', ' ', sprintf("%+013.8f", $row['decLongitude']));
    $elevation = sprintf("%6s", (($row['elevation_10'] == '') ? 0 : (intval($row['elevation_10'] / 10))));
    $frequency = $row['frequency'];
    $range = 50;

    $ident = sprintf("%-4s", $row['id']);
    $name  = trim($row['name']) . " NDB";

    $ndb .= "2 $latitude $longitude $elevation   $frequency  $range    0.0 $ident $name\n";

  }
  return($ndb);

}
