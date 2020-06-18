#!/usr/bin/php
<?php

$dbserver = "localhost";
$dbuser="redcap";
$dbpass="KEC-995 Empire 81";
$dbname = "capgrids";

$db =  new mysqli($dbserver, $dbuser, $dbpass, $dbname);
  if (mysqli_connect_errno()){
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
  }


$file = "/tmp/aixm/APT.txt";

$result = parseFile($file);

/**
 * parseFile($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  using array of strpos values
 *  Input: Path to APT.txt
 */

function parseFile($file){
global $db;

$stringPositions = array(
    "name"             => array("start" => 133,  "length" => 50),
    "ICAOcode"         => array("start" => 1210, "length" => 4),
    "aptCode"          => array("start" => 27,   "length" => 4),
    "stateAbbrev"      => array("start" => 91,   "length" => 2),
    "city"             => array("start" => 93,   "length" => 40),
    "sectionalAbbrev"  => array("start" => 712,  "length" => 3),
    "sectional"        => array("start" => 716,  "length" => 30),
    "latitude"         => array("start" => 523,  "length" => 13),
    "N_S"              => array("start" => 536,  "length" => 1),
    "latMinutes"       => array("start" => 537,  "length" => 12),
    "longitude"        => array("start" => 550,  "length" => 14),
    "E_W"              => array("start" => 564,  "length" => 1),
    "lonMinutes"       => array("start" => 565,  "length" => 11),
    );

$query = "DELETE FROM apt_data";
$try = $db->query($query);

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

            if ($airportData['N_S'] == 'S') {$airportData['latMinutes'] = -(abs($airportData['latMinutes']));}
            if ($airportData['E_W'] == 'W') {$airportData['lonMinutes'] = -(abs($airportData['lonMinutes']));}
         $airportData['name'] = ucwords(strtolower($airportData['name']));
         $airportData['city'] = ucwords(strtolower($airportData['city']));

         $query = "INSERT INTO apt_data SET \n";
            foreach($airportData as $key => $value){
            $query .= "   " . trim($key) . " = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

echo "$query\n\n";
         }
      }
   }



}

/**
 * parseFileTest($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  Input: Path to APT.txt
 */

function parseFileTest($file){

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 14, 13));    // Position of "AIRPORT"
         if ($type == "AIRPORT"){
         $ICAOcode = trim(substr($line, 1210, 4));  // Four-character ICAO ident (may be empty)
         $aptCode  = trim(substr($line, 27, 4));    // non-ICAO ident
         $name = trim(substr($line, 133, 50));
         $stateAbbrev = trim(substr($line, 91, 2));
         $city = trim(substr($line, 93, 40));
         $sectionalAbbrev = trim(substr($line, 712, 3));
         $sectional = trim(substr($line, 716, 45));

         $lat = trim(substr($line, 523, 13));
         $N_S = trim(substr($line, 536, 1));
         $minLat = trim(substr($line, 537, 12));
         $N_S2   = trim(substr($line, 549, 1));

         $lon = trim(substr($line, 550, 14));
         $E_W = trim(substr($line, 564, 1));
         $minLon = trim(substr($line, 565, 11));
         $E_W2 = trim(substr($line, 576, 1));         
         echo "|$name|$sectionalAbbrev|$sectional|$ICAOcode|$aptCode|$lon|$E_W|$minLon|$E_W2| \n";
         }
      }


   fclose($fh);
   }
}
