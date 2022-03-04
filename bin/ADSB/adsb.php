#!/usr/bin/php
<?php
include_once "nnumber_converter.php";



// Test case:  N313CP = A35236
// Test case:  N7303Y = A9CDE3.
$nnumber = "N313CP";
$nnumber = "N7303Y";

$result = nnumber_to_icao($nnumber);

$ICAO = nnumber_to_icao($nnumber);

echo "$nnumber = $ICAO \n\n"; 

