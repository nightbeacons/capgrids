#!/usr/bin/php
<?php

include_once("gridFunctions.php");
include_once("coordinates.php");
$fh=fopen("sect.log", "w");

for ($lat=24; $lat<50; $lat=$lat+0.15){
	for ($lon=-69; $lon >= -125; $lon=$lon-0.15) {

	$result=lonlat2grid($lon,$lat);
echo "$lat x $lon:  ";

if (isset($result['sectional'])) {
fwrite($fh,$result['sectional']);
fwrite($fh,"\n");
echo $result['sectional'] . " at " . $result['grid'] . " - " . $result['quadrant'] . "\n";  
} else {
echo "Not Found\n";
}


	}

}



fclose($fh);

?>

