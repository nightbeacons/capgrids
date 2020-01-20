#!/usr/bin/php
<?php

$SECTIONAL_ABBREV="SFO";


include_once("../includes/coordinates.php");

$SECTIONAL_NAME="";
$nullgrid = array("0");

	foreach($coordinates as $key => $value) {
		if ($value['Abbrev'] == $SECTIONAL_ABBREV) $SECTIONAL_NAME = $key;
	}

if (strlen($SECTIONAL_NAME) < 2) die;

	$endGrid = $coordinates[$SECTIONAL_NAME]['endGrid'];
		if (isset($coordinates[$SECTIONAL_NAME]['nullgrid'])) $nullgrid = $coordinates[$SECTIONAL_NAME]['nullgrid'];

	$baseURL="http://www.painefieldcap.org/gridmaster/grid2lonlatPRINT.php?id=" . $SECTIONAL_NAME . "&myquadrant=E&mygrid=";

	for ($i=1; $i<=$endGrid; $i++){

		if (!(in_array($i, $nullgrid))) {

		$padded = sprintf("%1$03d", $i);

		$url = $baseURL . $i;
		$HTMLoutfile = $SECTIONAL_ABBREV . "-" . $padded . ".html  ";

		$cmd = "/usr/bin/wget -O " . $HTMLoutfile . "\"$url\"";
		$tmp = `$cmd`;
		}

	}


?>

