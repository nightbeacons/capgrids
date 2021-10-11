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
$vor = vor_build();

echo $vor;

/**
 * function ndb_build()
 * Create the "2" (NDB) records
 * Record format for NDB is:
 *  Column 1:   2
 *  Column 3:  latitude of NDB, in decimal degrees, right-alighed to column 15, formatted to 8 decimal places
 *  Column 16: longitude of NDB, in decimal degrees, right-aligned to col 29, formatted to 8 decimal places
 *  Column 32: elevation in MSL. Integer, right-aligned to Col 36
 *  Column 39: Frequency (kHz), integer. Right-aligned to Col 42
 *  Column 43: Range (NM), integer. Right-aligned to Col 46. (Extract from 'class' column) for XP in the US, always 50
 *  Column 50:  Always 0.0
 *  Column 54: NDB identifier, up to 4 chars. Left-aligned
 *  Column 59: NDB Name. Left-aligned. Always suffixed with " NDB".
 *
 *  Sample NDB data from earth_nav.dat
 * 2  43.00018300  141.65069700      0   376  50    0.0 NA   NAGANUMA NDB
 * 2  39.74333300  066.40500000      0   598  50    0.0 DY   NAGORNAYA NDB
 * 2  35.25939700  136.91635800     52   360 100    0.0 KC   NAGOYA NDB
 * 2  21.12268900  079.03911400      0   372  50    0.0 NP   NAGPUR NDB
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



/**
 * function vor_build()
 * Create the "3" records
 * Record type "3" handles VOR / VOR-DME / VORTAC 
 * Record format for VOR is:
 *  Column 1:  3 
 *  Column 3:  latitude of VOR (identical to NDB format)
 *  Column 16: longitude of VOR (identical to NDB format)
 *  Column 32: elevation in MSL. (identical to NDB format)
 *  Column 39: Frequency (MkHz) multiplied by 100, integer. Right-aligned to Col 42
 *  Column 43: Range (NM), integer. Maximum reception range, NM, right-aligned
 *             If empty, set to "L"
 *  Column 50: Slaved mag variation, up to 3 decimal places
 *  Column 54: VOR identifier, up to 4 chars. Left-aligned
 *  Column 59: VOR Name. Left-aligned. Always suffixed with " VOR, VORTAC or VOR-DME".
 *
 *  Sample VOR data from earth_nav.dat
 *      LAT          LON           ELEV FREQ  RANGE MVAR
 * 3  36.82511111 -082.07897222   4200 11020  40   -2.0 GZG  GLADE SPRING VOR-DME
 * 3 -23.86527800  151.20444400      0 11630 130   10.0 GLA  GLADSTONE VOR
 * 3  48.21527778 -106.62547222   2280 11390 130   14.0 GGW  GLASGOW VOR-DME
 * 3  55.87050300 -004.44572500     37 11540 130   -6.0 GOW  GLASGOW VOR-DME
 * 3  32.15958333 -097.87769444   1300 11500  40    6.0 JEN  GLEN ROSE VORTAC
 */
function vor_build() {
  global $db;
  $range_map = array(
             "T" => 25,
             "L" => 40,
             "H" => 130
           );
  $vor = "";
  $query = "SELECT id, name, type, class, frequency, power, decLongitude, decLatitude, vor_svc_vol, elevation_10, mag_variation from nav
            WHERE type regexp '^VOR'
            AND status regexp 'OPERATIONAL'
            AND public='Y'
            ORDER BY name ASC";
  $r1 = $db->query($query);

  while ($row = $r1->fetch_assoc()) {
    $latitude  = str_replace('+', ' ', sprintf("%11s", sprintf("%+012.8f", $row['decLatitude'])));
    $longitude = str_replace('+', ' ', sprintf("%+013.8f", $row['decLongitude']));
    $elevation = sprintf("%6s", (($row['elevation_10'] == '') ? 0 : (intval($row['elevation_10'] / 10))));
    $frequency = intval($row['frequency'] * 100);
    $mag_variation = sprintf("%6s", sprintf("%3.1f", $row['mag_variation']));
       if (trim($row['vor_svc_vol']) == '') {$row['vor_svc_vol'] = 'L';}
    $range = sprintf("%3s", $range_map[trim($row['vor_svc_vol'])]);
    $ident = sprintf("%-4s", $row['id']);
    $name  = trim($row['name']) . " " . trim($row['type']);;
    $vor .= "3 $latitude $longitude $elevation $frequency $range $mag_variation $ident $name\n";
  }
return($vor);
}

