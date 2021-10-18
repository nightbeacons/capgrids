#!/usr/bin/php
<?php
include_once "/var/www/capgrids/pwf/aixm.php";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}


$output_file = "nav.dat";

//$ndb = ndb_build();
//echo $ndb;
//$vor = vor_build();
//echo $vor;
//$loc = loc_build();
//echo $loc;
// $gs = gs_build();
//echo $gs;
$mb = mb_build();
echo $mb;


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
 *  Column 39: Frequency (MHz) multiplied by 100, integer. Right-aligned to Col 42
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

/**
 * function loc_build()
 * Create the "4" and "5" LOC records
 *  Uses the 'ils' MySQL table
 * Record type "4" handles LOC that is attached to an ILS ("class" is non-empty)
 * Record type "5" handles others
 * Record format for LOC is:
 *  Column 1:  4
 *  Column 3:  latitude of LOC (identical to VOR and NDB format)
 *  Column 16: longitude of LOC (identical to VOR and NDB format)
 *  Column 32: elevation in MSL. (identical to VOR and NDB format)
 *  Column 39: Frequency (MHz) multiplied by 100, integer. (Identical to VOR format)
 *  Column 43: Range (NM), integer. (Identical to VOR format)
 *  Column   : Localizer bearing in TRUE degrees, up to three decimal places
 *  Column   : Localizer Identifier, Up to 4 chars (usually begins with "I")
 *  Column   : Airport ICAO code, up to 4 characters
 *  Column   : Localizer name (Use “ILS-cat-I”, “ILS-cat-II”, “ILS-cat-III”, “LOC”, “LDA” or “SDF”)
 */
function loc_build (){
  global $db;
  $loc = "";
  $query = "SELECT ils.airport, apt.ICAOcode,  ils.system_type, ils.loc_elevation_10,
              ils.loc_decLatitude, ils.loc_decLongitude, ils.loc_frequency, 
              ils.ops_status_loc, ils.approach_bearing, ils.runway_end_id, 
              ils.ils_identifier, ils.category, ils.mag_variation 
            FROM ils
            LEFT JOIN apt ON ils.airport_site_id = apt.aixm_key
            WHERE ils.ops_status_loc regexp 'OPERATIONAL'
            ORDER BY ils.category DESC, apt.ICAOcode ASC, ils.approach_bearing ASC"; 
  $r1 = $db->query($query);

  while ($row = $r1->fetch_assoc()) {
    $category = trim($row['category']);
    $record_type = ($category == '') ? 5 : 4;
    $category = str_replace('IIIB', 'III', $category);
    $latitude  = str_replace('+', ' ', sprintf("%11s", sprintf("%+012.8f", $row['loc_decLatitude'])));
    $longitude = str_replace('+', ' ', sprintf("%+013.8f", $row['loc_decLongitude']));
    $elevation = sprintf("%6s", (($row['loc_elevation_10'] == '') ? 0 : (intval($row['loc_elevation_10']))));
    $frequency = intval($row['loc_frequency'] * 100);
    $mag_variation = intval(substr($row['mag_variation'], 0, -1)) * ((substr($row['mag_variation'], -1) == 'E') ? 1 : -1);
    $bearing = $row['approach_bearing'] + $mag_variation;
      if ($bearing < 0) {$bearing += 360.0;}
      if ($bearing >= 360){$bearing -= 360.0; }
    $bearing = sprintf("%11s", sprintf("%7.3f", $bearing));
    $runway = sprintf("%-3s", $row['runway_end_id']);
    $range = " 18";
    $ils_identifier = sprintf("%4s", str_replace("-", "", $row['ils_identifier']));
    $airport = sprintf("%-4s", $row['ICAOcode']);
    $name  = trim($row['system_type']) . " " . trim($row['category']);
      if ($category != ''){$name = "ILS-cat-" . $category;}
      if (substr($row['system_type'], 0, 3) == 'LOC') {$name="LOC";}
    $loc .= "$record_type $latitude $longitude $elevation $frequency $range $bearing $ils_identifier $airport $runway $name\n";
  }
return($loc);
}

/**
 * function gs_build()
 * Create the "6"  GS records
 *  Uses the 'ils' MySQL table
 * Record format for GS is:
 *  Column 1:  6
 *  Column 3:  latitude of LOC (identical to VOR and NDB format)
 *  Column 16: longitude of LOC (identical to VOR and NDB format)
 *  Column 32: elevation in MSL. (identical to VOR and NDB format)
 *  Column 39: Frequency (MHz) multiplied by 100, integer. (Identical to VOR format)
 *             Note: This nneds to be the Localizer freq, not the GS frequency
 *  Column 43: Range (NM), integer. (Identical to VOR format)
 *  Column   : Bbearing in TRUE degrees, up to three decimal places, prefixed with GS angle
 *             (Glideslope of 3.25 degrees on heading of 123.456 becomes 325123.456)
 *  Column   : GS Identifier, Up to 4 chars (usually begins with "I")
 *  Column   : Airport ICAO code, up to 4 characters
 *  Column   : Runway number
 *  Column   : "GS"
 */

function gs_build() {
  global $db;
  $gs = "";
  $query = "SELECT ils.airport, apt.ICAOcode,  ils.system_type, ils.gs_type, ils.gs_elevation_10,
              ils.gs_decLatitude, ils.gs_decLongitude, ils.loc_frequency,
              ils.ops_status_gs, ils.gs_angle, ils.runway_end_id,
              ils.ils_identifier, ils.category, ils.approach_bearing, ils.mag_variation
            FROM ils
            LEFT JOIN apt ON ils.airport_site_id = apt.aixm_key
            WHERE ils.ops_status_gs regexp 'OPERATIONAL'
            ORDER BY apt.ICAOcode ASC, ils.approach_bearing DESC";
  $r1 = $db->query($query);
  while ($row = $r1->fetch_assoc()) {
    $record_type = 6;
    $latitude  = str_replace('+', ' ', sprintf("%11s", sprintf("%+012.8f", $row['gs_decLatitude'])));
    $longitude = str_replace('+', ' ', sprintf("%+013.8f", $row['gs_decLongitude']));
    $elevation = sprintf("%6s", (($row['gs_elevation_10'] == '') ? 0 : (intval($row['gs_elevation_10']))));
    $angle = sprintf("%3.0f", ($row['gs_angle'] * 100));
    $frequency = intval($row['loc_frequency'] * 100);
    $mag_variation = intval(substr($row['mag_variation'], 0, -1)) * ((substr($row['mag_variation'], -1) == 'E') ? 1 : -1);
    $bearing = $row['approach_bearing'] + $mag_variation;
      if ($bearing < 0) {$bearing += 360.0;}
      if ($bearing >= 360){$bearing -= 360.0; }
    $bearing = sprintf("%07.3f", $bearing);
    $angle_with_bearing = " " . $angle . $bearing;
//    $bearing = sprintf("%11s", sprintf("%07.3f", $bearing));
    $runway = sprintf("%-3s", $row['runway_end_id']);
    $range = " 10";
    $ils_identifier = sprintf("%4s", str_replace("-", "", $row['ils_identifier']));
    $airport = sprintf("%-4s", $row['ICAOcode']);
    $name  = "GS";

    $gs .= "$record_type $latitude $longitude $elevation $frequency $range $angle_with_bearing $ils_identifier $airport $runway $name\n";
  }

return($gs);

}


/** 
 * function mb_build()
 * Create the "7", "8" and "9"  Marker Beacon records
 */

function mb_build(){
  global $db;
  $marker_type_map = array(
             "OM" => 7,
             "MM" => 8,
             "IM" => 9
           );
  $mb="";
  $query = "SELECT ICAOcode, system_type, mb_type, mb_elevation_10,
              mb_decLatitude, mb_decLongitude,
              ops_status_mb, runway_end_id,
              mb_facility, mb_name,
              approach_bearing, mag_variation
            FROM markerbeacon
            WHERE ops_status_mb regexp 'OPERATIONAL'
            ORDER BY  mb_type DESC, ICAOcode ASC";
  $r1 = $db->query($query);
  while ($row = $r1->fetch_assoc()) {
    $record_type = $marker_type_map[$row['mb_type']];
    $marker_type = trim($row['mb_type']);
    $latitude  = str_replace('+', ' ', sprintf("%11s", sprintf("%+012.8f", $row['mb_decLatitude'])));
    $longitude = str_replace('+', ' ', sprintf("%+013.8f", $row['mb_decLongitude']));
    $elevation = sprintf("%6s", (($row['mb_elevation_10'] == '') ? 0 : (intval($row['mb_elevation_10']))));
    $mag_variation = intval(substr($row['mag_variation'], 0, -1)) * ((substr($row['mag_variation'], -1) == 'E') ? 1 : -1);
    $bearing = $row['approach_bearing'] + $mag_variation;
      if ($bearing < 0) {$bearing += 360.0;}
      if ($bearing >= 360){$bearing -= 360.0; }
    $bearing = sprintf("%7.3f", $bearing);
    $runway = sprintf("%-3s", $row['runway_end_id']);
    $airport = sprintf("%-4s", $row['ICAOcode']);

    $unused  = "    0   0    ";


    $mb .= "$record_type $latitude $longitude $elevation $unused $bearing ---- $airport $runway $marker_type\n";

  }
return ($mb);

}


 
