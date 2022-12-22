<?php

/**
 * parseAptFile($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  checking for AIRPORT and RWY values
 *  using array of strpos values
 *  Input: Path to APT.txt
 */

function parseAptFile($file){
global $db;

// Array keys correspond to MySQL column names

$stringPositions = array(
    "aixm_key"         => array("start" => 3,    "length" => 11),        
    "name"             => array("start" => 133,  "length" => 50),
    "aptType"          => array("start" => 14,   "length" => 13),
    "ICAOcode"         => array("start" => 1210, "length" => 4),
    "aptCode"          => array("start" => 27,   "length" => 4),
    "stateAbbrev"      => array("start" => 91,   "length" => 2),
    "city"             => array("start" => 93,   "length" => 40),
    "sectionalAbbrev"  => array("start" => 712,  "length" => 3),
    "sectional"        => array("start" => 716,  "length" => 30),
    "elevation"        => array("start" => 578,  "length" => 7),
    "latitude"         => array("start" => 523,  "length" => 13),
    "N_S"              => array("start" => 536,  "length" => 1),
    "decLatitude"      => array("start" => 537,  "length" => 12),
    "longitude"        => array("start" => 550,  "length" => 14),
    "E_W"              => array("start" => 564,  "length" => 1),
    "decLongitude"     => array("start" => 565,  "length" => 11),
    "magvar"           => array("start" => 586,  "length" => 3),
    );

$query = "DELETE FROM apt";
$try = $db->query($query);
$try = $db->query("ALTER TABLE apt DROP INDEX ix_spatial_apt_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 14, 13));    // Position of "AIRPORT"
         if ($type == "AIRPORT"){
         $airportData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $airportData[trim($key)] = $value;
            }

            if ($airportData['N_S'] == 'S') {$airportData['decLatitude']  = -(abs($airportData['decLatitude']));}
            if ($airportData['E_W'] == 'W') {$airportData['decLongitude'] = -(abs($airportData['decLongitude']));}
         $airportData['decLatitude']  = $airportData['decLatitude']  / 3600.00;
         $airportData['decLongitude'] = $airportData['decLongitude'] / 3600.00;
         $airportData['name'] = ucwords(strtolower($airportData['name']));
         $airportData['city'] = ucwords(strtolower($airportData['city']));

         $query = "INSERT INTO apt SET \n";
            foreach($airportData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

         echo $airportData['name'] . "\t" . $airportData['city'] . "\n";
         }  // End of 'if ($type == "AIRPORT") '
      }
   $try = $db->query("UPDATE apt set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_apt_data_coord ON apt(coordinates)");

   }
}


/**
 * ParseAptFileForRwy($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  for Runway information
 *  using array of strpos values
 *  Input: Path to APT.txt
 *  Update the aixm.rwy table.
 */

function parseAptFileForRwy($file) {
  global $db;

  // Array keys correspond to MySQL column names.
  $stringPositions = [
    "aixm_key"                => ["start" => 3,   "length" => 11],
    "runway_idents"           => ["start" => 16,  "length" => 7],
    "runway_length"           => ["start" => 23,  "length" => 5],
    "runway_width"            => ["start" => 28,  "length" => 4],
    "base_end_ident"          => ["start" => 65,  "length" => 3],
    "base_end_true_heading"   => ["start" => 68,  "length" => 3],
    "base_end_ILS_type"       => ["start" => 71,  "length" => 10],
    "base_end_right_traffic"  => ["start" => 81,  "length" => 1],
    "base_end_markings"       => ["start" => 82,  "length" => 5],
    "base_end_elevation"      => ["start" => 142, "length" => 7],
    "base_end_dec_lat"        => ["start" => 103, "length" => 12],
    "base_end_dec_lon"        => ["start" => 130, "length" => 12],
    "recip_end_ident"         => ["start" => 287, "length" => 3],
    "recip_end_true_heading"  => ["start" => 290, "length" => 3],
    "recip_end_ILS_type"      => ["start" => 293, "length" => 10],
    "recip_end_right_traffic" => ["start" => 303, "length" => 1],
    "recip_end_markings"      => ["start" => 304, "length" => 5],
    "recip_end_elevation"     => ["start" => 364, "length" => 7],
    "recip_end_dec_lat"       => ["start" => 325, "length" => 12],
    "recip_end_dec_lon"       => ["start" => 352, "length" => 12],
  ];

  $query = "DELETE FROM rwy";
  $try = $db->query($query);

  $fh = fopen($file, "r");
  if ($fh) {
    while ($line = fgets($fh)) {
      // Position of "RWY".
      if (substr($line, 0, 3) == "RWY"){
        $runwayData = [];
        // Put each value into an array, so data can be manipulated before inserting into DB.
        foreach ($stringPositions as $key => $position) {
          $value = trim(substr($line, $position['start'], $position['length']));
          $runwayData[trim($key)] = $value;
        }

        $runwayData['base_end_dec_lat']  = decodeCoordinates($runwayData['base_end_dec_lat']);
        $runwayData['base_end_dec_lon']  = decodeCoordinates($runwayData['base_end_dec_lon']);
        $runwayData['recip_end_dec_lat'] = decodeCoordinates($runwayData['recip_end_dec_lat']);
        $runwayData['recip_end_dec_lon'] = decodeCoordinates($runwayData['recip_end_dec_lon']);

        $query = "INSERT INTO rwy SET \n";
        foreach ($runwayData as $key => $value) {
          $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
        }
        $query = rtrim($query, " \n,");
        if (($try = $db->query($query)) === FALSE) {
          printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
          exit();
        }
        echo $runwayData['aixm_key'] . "\t" . $runwayData['runway_idents'] . "\n";
      }  // End of 'if (substr($line, 0, 3) == "RWY") '
    }

  }
}


/**
 * function decodeCoordinates()
 *    Accept lon or lat coordinate of the form nnnnnnn.dddW
 * and return signed decimal degrees
 */
function decodeCoordinates($coord){
$finalChar = substr($coord, -1);
  if (ctype_alpha($finalChar)){
     $coord = rtrim($coord, 'NSEW');
       if ($finalChar=='W' OR $finalChar=='S'){
          $coord = -$coord;
       }
  }
if (trim($coord) == '') {$coord=0;}
return($coord/3600);

}
