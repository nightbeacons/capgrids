#!/usr/bin/php
<?php

/**
 * parseFixFile($file)
 *  Line-by-line, parses the flat-text FIX.txt file
 *  using array of strpos values
 *  Input: Path to FIX.txt
 */

function parseFixFile($file){
global $db;

$stringPositions = array(
    "aixm_id"          => array("start" => 4,   "length" => 30),
    "id"               => array("start" => 228, "length" => 5),
    "state"            => array("start" => 29,  "length" => 30),	
    "category"         => array("start" => 94,  "length" => 3),
    "published"        => array("start" => 212, "length" => 1),
    "use"              => array("start" => 213, "length" => 15),
    "latitude"         => array("start" => 66, "length" => 12),
    "N_S"              => array("start" => 78, "length" => 1),
    "decLatitude"      => array("start" => 385, "length" => 10),
    "longitude"        => array("start" => 80, "length" => 13),
    "E_W"              => array("start" => 93, "length" => 1),
    "decLongitude"     => array("start" => 410, "length" => 10),
    );


$query = "DELETE FROM fix";
$try = $db->query($query);
$try = $db->query("ALTER TABLE fix DROP INDEX ix_spatial_fix_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 0, 4));    // Type of record
         if ($type == "FIX1"){
         $fixData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $fixData[trim($key)] = $value;
            }
         $fixData['decLatitude']  = convertToDecimalDegrees($fixData['latitude'] . $fixData['N_S']);
         $fixData['decLongitude'] = convertToDecimalDegrees($fixData['longitude']. $fixData['E_W']);

         $query = "INSERT INTO fix SET \n";
            foreach($fixData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

          }
       } // End of while ($line = fgets($fh)
   $try = $db->query("UPDATE fix set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_fix_data_coord ON fix(coordinates)");

     } else {
     echo "Cannot open FIX file $file\n";
     }
fclose($fh);
}

