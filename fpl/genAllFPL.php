#!/usr/bin/php
<?php


include_once("../includes/coordinates.php");

$SECTIONAL_NAME="";
$nullgrid = array("0");

foreach($coordinates as $key => $value) {

$SECTIONAL_ABBREV = $dirname = $value['Abbrev'];
$SECTIONAL_NAME = $key;


	if (strlen($SECTIONAL_NAME) < 2) die;

		$endGrid = $coordinates[$SECTIONAL_NAME]['endGrid'];
			if (isset($coordinates[$SECTIONAL_NAME]['nullgrid'])) $nullgrid = $coordinates[$SECTIONAL_NAME]['nullgrid'];

		$baseURL="http://www.painefieldcap.org/gridmaster/fpl.php?id=" . $SECTIONAL_NAME;

		for ($i=1; $i<=$endGrid; $i++){

		$directory = $dirname . (int)($i/100);
		$tmp = `/bin/mkdir -p $directory`;

				if (!(in_array($i, $nullgrid))) {

				$padded = sprintf("%1$03d", $i);

				$url = $baseURL . "&mygrid=" . $i;
				$HTMLoutfile = "\"" . $directory . "/" . $SECTIONAL_ABBREV . " " . $padded . ".fpl\"";

				$cmd = "/usr/bin/wget -O " . $HTMLoutfile . "  \"$url\"";
				$tmp = `$cmd`;
	echo "$cmd\n\n";
				}
		}

	$cmd = "/usr/bin/zip -r5 " . $SECTIONAL_ABBREV . "_FPL.zip " . $SECTIONAL_ABBREV . "[0-9]*";
	echo "$cmd\n";
	$tmp = `$cmd`;
 
}
 
?>

