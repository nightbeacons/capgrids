<?php

/**
 * parseAptFile($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  using array of strpos values
 *  Input: Path to APT.txt
 */

function parseAptFile($file){
global $db;

$stringPositions = array(
    "aixm_key"         => array("start" => 3,    "length" => 11),        
    "name"             => array("start" => 133,  "length" => 50),
    "ICAOcode"         => array("start" => 1210, "length" => 4),
    "aptCode"          => array("start" => 27,   "length" => 4),
    "stateAbbrev"      => array("start" => 91,   "length" => 2),
    "city"             => array("start" => 93,   "length" => 40),
    "sectionalAbbrev"  => array("start" => 712,  "length" => 3),
    "sectional"        => array("start" => 716,  "length" => 30),
    "latitude"         => array("start" => 523,  "length" => 13),
    "N_S"              => array("start" => 536,  "length" => 1),
    "decLatitude"      => array("start" => 537,  "length" => 12),
    "longitude"        => array("start" => 550,  "length" => 14),
    "E_W"              => array("start" => 564,  "length" => 1),
    "decLongitude"     => array("start" => 565,  "length" => 11),
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
         }
      }
   $try = $db->query("UPDATE apt set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_apt_data_coord ON apt(coordinates)");

   }
}

