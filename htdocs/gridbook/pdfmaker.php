#!/usr/bin/php
<?php

$SECTIONAL_ABBREV="SFO";

$PAGE_SIZE_CMD = "--page-size letter ";
#$PAGE_SIZE_CMD = "--page-height 215  --page-width 139 ";



include_once("../includes/coordinates.php");

$cmdBase = "/usr/local/bin/wkhtmltopdf --window-status ready $PAGE_SIZE_CMD -q ";

$SECTIONAL_NAME="";
$nullgrid = array("0");

	foreach($coordinates as $key => $value) {
		if ($value['Abbrev'] == $SECTIONAL_ABBREV) $SECTIONAL_NAME = $key;
	}


	if (strlen($SECTIONAL_NAME) > 1) {

	$title = preg_replace("/_/", " ", $SECTIONAL_NAME);

	$endGrid = $coordinates[$SECTIONAL_NAME]['endGrid'];
		if (isset($coordinates[$SECTIONAL_NAME]['nullgrid'])) $nullgrid = $coordinates[$SECTIONAL_NAME]['nullgrid'];


echo "result: $title\n";

	$cmdBase .= " --title \"$title\" ";

	for ($gridcounter=0; $gridcounter <= $endGrid; $gridcounter += 10) {
	$prefix =  substr(sprintf("%1$03d", $gridcounter), 0, 2);

	$pdfFile1 = "PDF/" . $SECTIONAL_ABBREV . "-" . sprintf("%1$03d", $gridcounter) . ".pdf";
        $pdfFile2 = "PDF/" . $SECTIONAL_ABBREV . "-" . sprintf("%1$03d", ($gridcounter+5)) . ".pdf";


	$cmd1 =  $cmdBase . $SECTIONAL_ABBREV . "-" . $prefix . "[0-4].html $pdfFile1 ; sleep 1" ;
	$cmd2 =  $cmdBase . $SECTIONAL_ABBREV . "-" . $prefix . "[5-9].html $pdfFile2 ; sleep 1";

	echo "$cmd1\n";
	$tmp = `$cmd1`;

        echo "$cmd2\n";
        $tmp = `$cmd2`;

	} 



	}


?>

