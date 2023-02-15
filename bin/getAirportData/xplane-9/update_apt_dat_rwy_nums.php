#!/usr/bin/php
<?php
/**
 * @file
 * Update the apt.dat file for x-plane.
 *
 *  Record format:
 * 1      133 1 0 KRHV Reid Hillview Of Santa Clara Co
 * 100   22.86   1   0 0.25 0 2 1 13L  37.33651400 -121.82260300  152.10    0.00 2  0 0 1 31R  37.32972500 -121.81616700  124.97    0.00 2  0 0 1
 * 100   22.86   1   0 0.25 0 0 1 13R  37.33601400 -121.82342500  152.10    0.00 2  0 0 0 31L  37.32923100 -121.81699200  124.66    0.00 2  0 0 0
 *
 * 1       16 0 1 S43  Harvey Field
 * 100   10.97   1   0 0.25 0 2 0 15L  47.90833200 -122.10365300  137.16    0.00 1  0 0 0 33R  47.90123800 -122.10085200   79.25    0.00 1  0 0 0
 * 100   22.86   3   0 0.25 0 0 0 15R  47.90145100 -122.10136000    0.00    0.00 0  0 0 0 35L  47.90841300 -122.10399400    0.00    0.00 1  0 0 0
 * 21   47.90545522 -122.10217693  5 165.17   3.00 15L VASI-3L
 * 21   47.90361100 -122.10212886  5 345.18   3.00 33R VASI-3L
 *
 * "Base end" (rwy identifiers < 18) are on the left
 * "Reciprocal" are on the right
 * ICAO identifiers are used for airports that have them.
 */

include_once "/var/www/capgrids/pwf/aixm.php";
$DEBUG = FALSE;

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

$curdir = getcwd();
//$input_file = $curdir . "/apt_test.dat";
$input_file = $curdir . "/apt.dat";

$output_file = $curdir . "/new_apt.dat";
$in_airport_record = FALSE;

$fpr = fopen($input_file, 'r');
$fpw = fopen($output_file, 'w');

// Step through the x-plane apt.dat file, checking for
// airport records (case '1') and Land runway records (case '100')
$line_number = 0;
while (($line = fgets($fpr, 4096)) !== FALSE) {
  $type = ltrim(substr($line, 0, 4));
  if (strlen(trim($line)) < 2) {
    $in_airport_record = FALSE;
  }

  switch ($type) {
    case '1   ':
      $airport_code = trim(substr($line, 15, 4));
      $in_airport_record = TRUE;
      if ($DEBUG) {
        echo "\n===========================================================\n$line\n";
      }
      break;

    case '100 ':
      if ($in_airport_record) {
        $line = processRunwayRecord($line);
      }
      break;

    default:
      $in_airport_record = FALSE;
      if ($DEBUG) {
        echo "Default Case: Type = $type\n";
      }

  }
  fwrite($fpw, $line);
  // Echo $line;.
}

fclose($fpr);
fclose($fpw);

/**
 * Function processRunwayRecord($xp_runway_record)
 *   Take one '100' (runway) record from apt.dat
 *   check the base and recip runway identifiers against AIXM
 *   update the apt.dat line if necessary.
 *   return the record .
 *
 *   Find the AIXM airport record ident that is
 * geographically closest to the lon/lat of the base end
 * of the apt.dat runway record.
 */
function processRunwayRecord($xp_runway_record) {
  global $in_airport_record, $DEBUG;

  $line = $xp_runway_record;
  $msg = $status_msg = "";
  if (trim(substr($xp_runway_record, 31, 1)) != "H") {
    $xp_base_runway_num = trim(substr($xp_runway_record, 31, 3));
    $xp_base_lrc_tag = trim(preg_replace('/[0-9]+/', '', trim($xp_base_runway_num)));
    $lat_base = trim(substr($xp_runway_record, 35, 12));
    $lon_base = trim(substr($xp_runway_record, 48, 14));

    $xp_recip_runway_num = trim(substr($xp_runway_record, 87, 3));

    $nearest_base = findNearestRwy($lat_base, $lon_base, 'base');
    if ($nearest_base['apt_code'] == "NOT FOUND") {
      $nearest_base = "NOT FOUND\n";
    }
    else {
      $msg .= "XP Base = $xp_base_runway_num, AIXM Base = " . $nearest_base['base_end_ident'] . "\n";
      $msg .= "XP Recip = $xp_recip_runway_num, AIXM Recip = " . $nearest_base['recip_end_ident'] . "\n";
      if ($xp_base_runway_num == $nearest_base['base_end_ident']) {
        $msg .= "Base RWY corresponds\n";
      }
      else {
        if (!ctype_alpha(trim($nearest_base['base_end_ident']))) {
          $msg .= "Base RWY needs update\n";
          $line = substr_replace($line, str_pad(ltrim($nearest_base['base_end_ident'], '0 '), 3, " "), 31, 3);
          $status_msg .= "   Changing " . $nearest_base['apt_code'] . " runway " . $xp_base_runway_num . " to " . ltrim($nearest_base['base_end_ident'], '0 ') . "\n";
        }
      }

      if ($xp_recip_runway_num == $nearest_base['recip_end_ident']) {
        $msg .= "Recip RWY correspond\n";
      }
      else {
        if (!ctype_alpha(trim($nearest_base['recip_end_ident']))) {
          $msg .= "Recip RWY needs update\n";
          $line = substr_replace($line, str_pad(ltrim($nearest_base['recip_end_ident'], '0 '), 3, " "), 87, 3);
          $status_msg .= "   Changing " . $nearest_base['apt_code'] . " runway " . $xp_recip_runway_num . " to " . ltrim($nearest_base['recip_end_ident'], '0 ') . "\n\n";
        }
      }
      if ($DEBUG) {
        echo $msg . "$xp_runway_record\n$line\n\n\n";
      }
    }
    echo $status_msg;
  }
  return($line);
}

/**
 * Function findNearestRwy($lat, $lon, $endspec)
 *    Associate an apt.dat runway reference with an AIXM record.
 *
 *    Provide decimal lat/lon
 *    and end specifier ('base' or 'recip')
 *    for the end of an X-Plane runway
 *    search the aixm MySQL db for the nearest
 *    match.
 *
 *   Return array of the form:
 *   [apt_code] => KRHV
 *   [ICAOcode] => KRHV
 *   [aixm_key] => 02203.*A
 *   [ident] => 13R
 *   [endspec] => base
 */
function findNearestRwy($lat, $lon, $endspec) {
  global $db, $DEBUG;
  $runway = [];
  $runway['apt_code'] = $runway['aixm_key'] = $runway['ident'] = "NOT FOUND";
  $runway['ICAOcode'] = $runway['base_end_ident'] = $runway['recip_end_ident'] = "";
  $runway['endspec']  = $endspec;

  switch (trim($endspec)) {
    case 'base':
      $col1 = 'base_end_ident';
      $col2 = 'base_end_coordinates';
      break;

    case 'recip':
      $col1 = 'recip_end_ident';
      $col2 = 'recip_end_coordinates';
      break;
  }

  $query = "SELECT
            rwy.aixm_key,apt.aptCode,apt.ICAOcode,
            rwy.base_end_ident, recip_end_ident,
            " . $col1 . ",
            X(" . $col2 . ") AS 'latitude',
            Y(" . $col2 . ") AS 'longitude',
           (
             GLength(
               LineStringFromWKB(
                 LineString(
                  " . $col2 . ",
                  GeomFromText('POINT(" . $lon . " " . $lat . " )')
                 )
               )
             )
           )
           AS distance
           FROM rwy
           LEFT JOIN apt on rwy.aixm_key=apt.aixm_key
           WHERE (aptCode IS NOT NULL) AND (aptCode != '') AND (SUBSTRING(runway_idents, 1 ,1) != 'H') 
          HAVING (distance < 0.003)
          ORDER BY distance ASC LIMIT 1";

  $r1 = $db->query($query);

  while ($row = $r1->fetch_assoc()) {
    // $apt = $row['ICAOcode'] . " " . $row['base_end_ident'] . "  --  " . $row['distance'];
    $runway['apt_code']        = trim($row['aptCode']);
    $runway['ICAOcode']        = trim($row['ICAOcode']);
    $runway['aixm_key']        = trim($row['aixm_key']);
    $runway['base_end_ident']  = trim($row['base_end_ident']);
    $runway['recip_end_ident'] = trim($row['recip_end_ident']);
    $runway['ident']           = $row[$col1];
    $runway['distance']        = $row['distance'];
  }
  if ($runway['ident'] == "NOT FOUND") {
    $runway['endspec'] = "NOT FOUND";
  }
  if ($runway['apt_code'] != "NOT FOUND") {
    echo "Processing runways for " . $runway['apt_code'] . "\n";
   if ($DEBUG) {echo "Distance is " . $runway['distance'] . "\n";
     }
  }
  if ($DEBUG) {
    print_r($runway);
  }
  return($runway);
}
