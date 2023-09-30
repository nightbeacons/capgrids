<?php

/**
 * @file
 * Accepts a GeoTIFF and PNG
 * Creates a gridded PNG.
 */

include_once "/var/www/capgrids/pwf/apt.php";
include_once "/var/www/capgrids/htdocs/includes/gridFunctions.php";


//$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
//if (mysqli_connect_errno()) {
//  printf("Connection failed: %s\n", mysqli_connect_error());
//  exit();
// }

// buildGriddedPng("Dallas-Ft Worth");


/**
 * Main function
 * Accepts Sectional FullName (with spaces)
 * Generates gridded PNG
 */
function buildGriddedPng($geoname) {
  global $db;

  $query = "SELECT FullName, Abbrev, MinLon, MaxLon, MinLat, MaxLat from coordinates where FullName='" . $geoname . "'";
  $r1 = $db->query($query);
  while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
    $FullName = trim($myrow['FullName']);
    $MinLon = $myrow['MinLon'];
    $MaxLon = $myrow['MaxLon'];
    $MinLat = $myrow['MinLat'];
    $MaxLat = $myrow['MaxLat'];
  }
  $FullName_no_spaces = str_replace(" ", "_", $FullName);
  $src_dir = "/var/www/capgrids/htdocs/overlays/" . $FullName_no_spaces . "/";
  $tiff = $src_dir . $FullName . " SEC.tif";
  $strImagePath = $src_dir . $FullName_no_spaces . ".png";
//    if (!file_exists($strImagePath)){
      $cmd = '/usr/bin/convert -alpha set "' . $tiff . '" "' . $strImagePath . '"';
      echo "Converting TIFF to PNG . . . \n";
      $tmp=`$cmd`;
//    }
  $outfile = "/var/www/capgrids/htdocs/gridded/sectional/" . $FullName_no_spaces . ".png";

  $font = "/usr/share/fonts/gnu-free/FreeSansBold.ttf";
  $font_size_in_points = 36;

  $imgPng = imagecreatefrompng($strImagePath);
  imagealphablending($imgPng, TRUE);
  imagesavealpha($imgPng, TRUE);

  // 0 = opaque, 127 = transparent
  $alpha = 25;
  $black = imagecolorallocatealpha($imgPng, 0, 0, 0, $alpha);
  $white = imagecolorallocatealpha($imgPng, 255, 255, 255, $alpha);
  $gray = imagecolorallocatealpha($imgPng, 127, 127, 127, $alpha);
  $blue = imagecolorallocatealpha($imgPng, 68, 0, 255, $alpha);
  $thick_line = 8;
  $thin_line  = 4;

  imagesetthickness($imgPng, $thick_line);

  $grid_labels = [];
  // Draw Lon lines.
  for ($j = $MaxLon; $j <= $MinLon; $j = $j + 0.25) {
    echo "Lon = $j\n";
    $points = [];
    $quarter_grid = [];
    for ($i = $MinLat; $i <= $MaxLat; $i = $i + 0.25) {
      $m = latlon2pxl($i, $j, $tiff);
      $points[] = $m['col'];
      $points[] = $m['row'];
      // Draw quarter-grid Lon line.
      if (($j + 0.125) <= $MinLon) {
        $n = latlon2pxl($i, $j + 0.125, $tiff);
        $quarter_grid[] = $n['col'];
        $quarter_grid[] = $n['row'];
      }
    }
    imagesetthickness($imgPng, $thick_line);
    imageopenpolygon($imgPng, $points, (count($points) / 2), $black);
    if (count($quarter_grid) > 0) {
      imagesetthickness($imgPng, $thin_line);
      imageopenpolygon($imgPng, $quarter_grid, (count($quarter_grid) / 2), $gray);
      imagesetthickness($imgPng, $thick_line);
    }
  }

  // Draw Lat Lines.
  for ($j = $MinLat; $j <= $MaxLat; $j = $j + 0.25) {
    echo "Lat = $j\n";
    $points = [];
    $quarter_grid = [];
    for ($i = $MaxLon; $i <= $MinLon; $i = $i + 0.25) {
      $m = latlon2pxl($j, $i, $tiff);
      $points[] = $m['col'];
      $points[] = $m['row'];
      // Draw quarter-grid Lat line.
      if (($j + 0.125) <= $MaxLat) {
        $n = latlon2pxl($j + 0.125, $i, $tiff);
        $quarter_grid[] = $n['col'];
        $quarter_grid[] = $n['row'];
        $grid_label_ary = lonlat2grid($i + 0.125, $j + 0.125);
        $grid_label = $grid_label_ary['abbreviation'] . "-" . $grid_label_ary['grid'];
        $tb_coords = imagettfbbox($font_size_in_points, 0, $font, $grid_label);

        // $tb_coords:
        //
        // Array
        //    [0] => 0 // lower left X coordinate
        //    [1] => -1 // lower left Y coordinate
        //    [2] => 198 // lower right X coordinate
        //    [3] => -1 // lower right Y coordinate
        //    [4] => 198 // upper right X coordinate
        //    [5] => -20 // upper right Y coordinate
        //    [6] => 0 // upper left X coordinate
        //    [7] => -20 // upper left Y coordinate
        $x_offset = ceil(($tb_coords[2] - $tb_coords[0]) / 2);
        $y_offset = ceil(($tb_coords[1] - $tb_coords[7]) / 2);
        $g = latlon2pxl($j + 0.125, $i + 0.125, $tiff);

        $grid_labels[] = [
          'col'   => $g['col'] - $x_offset,
          'row'   => $g['row'] + $y_offset,
          'label' => $grid_label,
        ];
      }

    }
    imageopenpolygon($imgPng, $points, (count($points) / 2), $black);
    if (count($quarter_grid) > 0) {
      imagesetthickness($imgPng, $thin_line);
      imageopenpolygon($imgPng, $quarter_grid, (count($quarter_grid) / 2), $gray);
      imagesetthickness($imgPng, $thick_line);
    }
  }

  // Add the grid labels. Add a white drop-shadow to improve readability
  foreach ($grid_labels as $idx => $val) {
    imagettftext($imgPng, $font_size_in_points + 1, 0, $val['col']+2, $val['row']+2, $white, $font, $val['label']);
    imagettftext($imgPng, $font_size_in_points, 0, $val['col'], $val['row'], $blue, $font, $val['label']);
  }

  imagealphablending($imgPng, TRUE);
  imagesavealpha($imgPng, TRUE);
  imagepng($imgPng, $outfile, 9);
  imagedestroy($imgPng);
  unlink($strImagePath);
}

/**
 *
 */
function latlon2pxl($lat, $lon, $tiff) {
  $cmd = "/usr/bin/gdallocationinfo -xml -wgs84 '" . $tiff . "' $lon  $lat";
  $xmlstring = `$cmd`;
  $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
  $json = json_encode($xml);
  $my_ary = (json_decode($json, TRUE))['@attributes'];

  $pixel_coords = [
    'col' => $my_ary['pixel'],
    'row' => $my_ary['line'],
  ];

  return($pixel_coords);
}
