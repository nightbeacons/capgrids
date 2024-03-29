<?php

/**
 * parseNavFile($file)
 *  Line-by-line, parses the flat-text NAV.txt file
 *  using array of strpos values
 *  Input: Path to NAV.txt
 */

function parseNavFile($file){
global $db;

$stringPositions = array(
    "id"               => array("start" => 4,   "length" => 4),
    "name"             => array("start" => 42,  "length" => 30),
    "type"             => array("start" => 8,   "length" => 20),
    "status"           => array("start" => 766, "length" => 30),
    "public"           => array("start" => 280, "length" => 1),
    "class"            => array("start" => 281, "length" => 11),
    "frequency"        => array("start" => 533, "length" => 6),
    "voice_call"       => array("start" => 499, "length" => 30),
    "power"            => array("start" => 491, "length" => 4),
    "vor_svc_vol"      => array("start" => 576, "length" => 2),
    "dme_svc_vol"      => array("start" => 578, "length" => 2),
    "city"             => array("start" => 72,  "length" => 40),
    "state"            => array("start" => 112, "length" => 30),
    "latitude"         => array("start" => 370, "length" => 13),
    "N_S"              => array("start" => 383, "length" => 1),
    "decLatitude"      => array("start" => 385, "length" => 10),
    "longitude"        => array("start" => 396, "length" => 13),
    "E_W"              => array("start" => 409, "length" => 1),
    "decLongitude"     => array("start" => 410, "length" => 10),
    "elevation_10"     => array("start" => 472, "length" => 7),
    "mag_variation"    => array("start" => 479, "length" => 5),
    );

$query = "DELETE FROM nav";
$try = $db->query($query);
$try = $db->query("ALTER TABLE nav DROP INDEX ix_spatial_nav_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 0, 4));    // Type of record 
         if ($type == "NAV1"){
         $navData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $navData[trim($key)] = $value;
            }

            if ($navData['N_S'] == 'S') {$navData['decLatitude']  = -(abs($navData['decLatitude']));}
            if ($navData['E_W'] == 'W') {$navData['decLongitude'] = -(abs($navData['decLongitude']));}
         $navData['decLatitude']  = $navData['decLatitude']  / 3600.00;
         $navData['decLongitude'] = $navData['decLongitude'] / 3600.00;
         $magvar_aixm = trim($navData['mag_variation']);
            if (substr(trim($magvar_aixm), -1) == "E") { $navData['mag_variation'] = intval(substr($magvar_aixm, 0, -1));}
            if (substr(trim($magvar_aixm), -1) == "W") { $navData['mag_variation'] = -intval(substr($magvar_aixm, 0, -1));}

         $query = "INSERT INTO nav SET \n";
            foreach($navData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

          }
       } // End of while ($line = fgets($fh)
   $try = $db->query("UPDATE nav set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_nav_data_coord ON nav(coordinates)");

   $try = $db->query("UPDATE nav SET range_nm='25' WHERE (type = 'NDB' OR type='NDB/DME') and class regexp 'MH'");

     }

}

