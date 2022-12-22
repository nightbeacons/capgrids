#!/usr/bin/php
<?php
/**
 * Update the apt.dat file for x-plane
 */


include_once "/var/www/capgrids/pwf/aixm.php";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}
$curdir = getcwd();

$input_file  = $curdir . "/apt.dat";
$output_file = $curdir . "/new_apt.dat";

$fpr = fopen($input_file, 'r');
$fpw = fopen($output_file, 'w');

    while (($line = fgets($fpr, 4096)) !== false) {
      $type = substr($line, 0, 4);    // Position of airport record, which should begin with "1 " 

      switch ($type) {
         case '1   ':
           $airport_code = trim(substr($line, 15, 4));
           $in_airport_record=TRUE;
echo "$line";
           break;

         case '100 ':
           if ($in_airport_record){
           $runway = $line;
           $runway_num = substr($runway, 31, 3);
           $lrc_tag = trim(preg_replace('/[0-9]+/', '', trim($runway_num)));
echo "$runway\n  $runway_num|\n|LRC = $lrc_tag|\n\n";
           } else {
           echo "PROBLEM\n";
           }
           break;

         default:
         $in_airport_record=FALSE;

      }
              






    }

fclose($fpr);
fclose($fpw);
