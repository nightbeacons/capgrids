#!/usr/bin/php
<?php

include_once "includes/gridFunctions.php";

$sectional = "CHICAGO";
$gridNum = 148;
$quadrant = "C";

$alpha = ['A', 'B', 'C', 'D', 'E'];

for ($gridNum = 1; $gridNum < 500; $gridNum++) {
  for ($q = 0; $q < 5; $q++) {
    $quadrant = $alpha[$q];
    $cell = grid2cell($sectional, $gridNum, $quadrant);

    echo "$sectional $gridNum -- $quadrant = CELL = $cell\n";
  }
}

/**
 * Grid2cell
 * Accept a lat/lon array from grid2lontal
 * Return cell label.
 *
 * Cell system is based on the SE corner of the grid
 * Sample return val from grid2lonlat is:
 *     [SE] => Array
 *     (
 *          [lon] => -120&deg; 7.5'
 *          [lat] => 47&deg; 52.5'
 *      )
 */
function grid2cell($sectional, $gridNum, $quadrant = "E") {
  $alpha = ['A', 'B', 'C', 'D'];
  $cell = "";

  $latlon_ary = grid2lonlat($sectional, $gridNum, $quadrant);
  $lat = explode("&deg;", $latlon_ary['SE']['lat']);
  $lat_deg = $lat[0] + 0;
  $lat_min = trim($lat[1], " '");

  $lon = explode("&deg;", $latlon_ary['SE']['lon']);
  $lon_deg = abs($lon[0]) + 0;
  $lon_min = trim($lon[1], " '");

  // Example: "48093".
  $cell = $lat_deg . sprintf('%03d', $lon_deg);

  $x = resolveCellGrid($lat_min, $lon_min, 30, $quadrant);
  $y = $cell . $x;
  echo "\n=======================\nNEW = $y\n";

  // Move grid to SE quadrant.
  $lat_min_30_SE = $lat_min - ($lat_min > 30) * 30;
  $lon_min_30_SE = $lon_min - ($lon_min > 30) * 30;
  $letter2 = (($lat_min_30_SE < 15) * 2) + (($lon_min_30_SE < 15));

  $cell .= $alpha[$letter2];

  // Move quarter-grid to SE quadrant.
  $lat_min_15_SE = $lat_min_30_SE - ($lat_min_30_SE > 15) * 15;
  $lon_min_15_SE = $lon_min_30_SE - ($lon_min_30_SE > 15) * 15;
  $letter3 = (($lat_min_15_SE < 7.5) * 2) + (($lon_min_15_SE < 7.5));

  $cell .= $alpha[$letter3];

  // For CAP quarter-grids only.
  if ($quadrant != "E") {
    // Move quarter-grid to SE quadrant.
    $lat_min_75_SE = $lat_min_15_SE - ($lat_min_15_SE > 7.5) * 7.5;
    $lon_min_75_SE = $lon_min_15_SE - ($lon_min_15_SE > 7.5) * 7.5;
    $letter4 = (($lat_min_75_SE < 3.75) * 2) + (($lon_min_75_SE < 3.75));
    $cell .= $alpha[$letter4];
  }
if ($cell != $y) {echo "ERR: Q=$quadrant, cell=$cell, new=$y\n\n";} 
  return ($cell);
}


/**
 * resolveCellGrid
 * Recursive function to move coords into SE corner, then determine grid letter
 *  Accepts:
 *    - The 'minute' value of the lat and lon of the SE corner,
 *    - The required resolution (default = 30 min when initially calling the function)
 *    - The quadrant (A, B, C, D, E) E = Entire grid
 *    - Cell identifier (empty when initially calling the function
 *  Return the alphabetic (suffix) portion of the cell grid identifier. 
 */
function resolveCellGrid($lat_min, $lon_min, $resolution, $quadrant, $cell = "") {
  $res_limit = ($quadrant == 'E') ? 8 : 4;
  if ($resolution > $res_limit ) {
    $alpha = ['A', 'B', 'C', 'D'];

    $lat_min_new_SE = $lat_min - ($lat_min > $resolution) * $resolution;
    $lon_min_new_SE = $lon_min - ($lon_min > $resolution) * $resolution;
    $letter = (($lat_min_new_SE < ($resolution / 2)) * 2) + (($lon_min_new_SE < ($resolution / 2)));

    $resolution = $resolution / 2;
    $cell .= $alpha[$letter];
    $cell = resolveCellGrid($lat_min_new_SE, $lon_min_new_SE, $resolution, $quadrant, $cell);
  }
  return($cell);
}
