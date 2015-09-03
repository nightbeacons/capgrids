#!/usr/bin/php
<?php

$SECTIONAL_ABBREV="SEA";

$dirname=$SECTIONAL_ABBREV;

include_once("../includes/coordinates.php");

$SECTIONAL_NAME="";
$nullgrid = array("0");

	foreach($coordinates as $key => $value) {
		echo "KEY is $key \n";
		print_r($value);
	}

  
?>

