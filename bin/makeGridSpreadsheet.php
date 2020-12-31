#!/usr/bin/php
<?php
/**
 * @file
 * File: makeGridSpreadsheet.php 
 *
 * Create spreadsheet containing grid lat/lon/mag-var for each quarter-grid
 *
 */

include_once "/var/www/capgrids/pwf/apt.php";
include_once "/var/www/capgrids/htdocs/includes/gridFunctions.php";

$csv_file="/var/www/capgrids/bin/grids2.csv";
$fh = fopen($csv_file, "w");
$header = '"AREA","GRID","NW_LAT","NW_LON","NE_LAT","NE_LON","SW_LAT","SW_LON","SE_LAT","SE_LON","MAG_VAR"' . "\n";
fwrite($fh, $header);

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

//  Main loop
$query = "SELECT Abbrev, FullName, MinLon, MaxLon, MinLat, MaxLat, startGrid, endGrid, nullgrid FROM coordinates WHERE Abbrev >= 'ELP'  ORDER BY Abbrev";

$r1 = $db->query($query);


while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
  $text = doSectional($myrow);
}


fclose($fh);


/**
 * function doSectional($sectional)
 *   Accept assoc array of sectinoal chart info for one sectional
 *   Find corners for each grid quadrant 
 *   Find avg magnetic variation for each quadrant
 *   Pass values to funtion to write one record
 */
function doSectional($sectional){
$quadAry = array("A","B","C","D");

$nullGridAry = explode(",", trim($sectional['nullgrid'], "][ "));

  for ($gridNum = $sectional['startGrid']; $gridNum <= $sectional['endGrid']; $gridNum++){
    if (!(in_array($gridNum, $nullGridAry))){
      foreach($quadAry as $quadrant){
        $FullNameFix = str_replace(" ", "_", $sectional['FullName']);
        $lonLatAry = grid2lonlat($FullNameFix, $gridNum, $quadrant, 'raw');
        $avgLon = ($lonLatAry['NW']['lon'] + $lonLatAry['NE']['lon']) / 2;
        $avgLat = ($lonLatAry['NW']['lat'] + $lonLatAry['SW']['lat']) / 2; 
        $magVar = magVariation($avgLat, $avgLon);
        sleep(1);
        $lonLatAry['MagVar'] = $magVar;
        $lonLatAry['Abbrev'] = $sectional['Abbrev'];
        writeOneRow($lonLatAry);
      }
    }
  } 
return("");
}




function writeOneRow($info){
global $fh;

$q = '"';
$com = ",";

$record  = $q . $info['Abbrev'] . $q . $com;
$record .= $q . $info['Grid'] . $info['Quadrant'] . $q . $com;
$record .= $q . $info['NW']['lat'] . $q . $com;  
$record .= $q . $info['NW']['lon'] . $q . $com;
$record .= $q . $info['NE']['lat'] . $q . $com;
$record .= $q . $info['NE']['lon'] . $q . $com;
$record .= $q . $info['SW']['lat'] . $q . $com;
$record .= $q . $info['SW']['lon'] . $q . $com;
$record .= $q . $info['SE']['lat'] . $q . $com;
$record .= $q . $info['SE']['lon'] . $q . $com;
$record .= $q . $info['MagVar']    . $q . "\n";
echo $record;
fwrite($fh, $record);

}

