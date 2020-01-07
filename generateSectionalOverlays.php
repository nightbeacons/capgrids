#!/usr/bin/php
<?php
/**
 * @file
 * File: generateSectionalOverlays.php.
 *
 * Fetch and generate Sectional Overlays.
 */

// Set to 1 to refresh all chart data, regardless of Edition number.
define('FETCH_ALL', 0);

include_once "includes/coordinates2.php";

$baseDir = "/var/www/capgrids/htdocs/overlays/";

foreach ($coordinates as $grid => $value) {
  if ($coordinates[$grid]['FullName'] != "None") {
    $currentKMLfile = getTiff($coordinates[$grid]['FullName'], $baseDir);
  }
}

/**
 * Function getTiff($geoname, $baseDir)
 *   Per https://app.swaggerhub.com/apis/FAA/APRA/1.2.0#/Sectional%20Charts/getSectionalChart
 *   For each sectional in the coordinates.php file:
 *     - Fetch the Geo-referenced Tiff file (in ZIP format), write it to basedir/geoname and unzip
 *     - Run gdal_translate to create KML superoverlay (KML + images in JPG format)
 *     - Optimize the generated JPGs.
 */
function getTiff($geoname, $baseDir) {
  $user_agent = "Civil Air Patrol - CAPgrids (+https://www.capgrids.com)";
  $geoname_No_Spaces = str_replace(" ", "_", $geoname);
  $url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=current&format=tiff";
  $headers = ['Accept: application/json'];

  // Get JSON for path to the downlad.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  $json = curl_exec($ch);
  curl_close($ch);

  $info = json_decode($json);
  print_r($info);
  if ($info->status->code == "200") {
    $zipfilename = $baseDir . $geoname_No_Spaces . "/" . $geoname_No_Spaces . ".zip";
    $dirname = dirname($zipfilename);
    if (!is_dir($dirname)) {
      mkdir($dirname, 0755, TRUE);
    }

    // Check to see if we have the latest edition.
    $my_edition = 0;
    $dh = opendir($dirname);
    $files = [];
    while (($file = readdir($dh)) !== FALSE) {
      if (substr($file, strlen($file) - 4) == '.htm') {
        $my_edition = preg_replace("/[^0-9]/", "", $file) + 0;
      }
    }
    closedir($dh);

    $faa_edition = $info->edition[0]->editionNumber + 0;

    // If our edition does not match the FAA edition, (or if FETCH_ALL is set) get the FAA edition.
    if (($faa_edition != $my_edition) or (FETCH_ALL == 1)) {

      // Download the zipfile.
      $fh = fopen($zipfilename, 'w');
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $info->edition[0]->product->url);
      curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_FILE, $fh);
      curl_exec($ch);
      curl_close($ch);
      fclose($fh);

      // Unzip the file.
      $zip = new ZipArchive();
      $zip->open($zipfilename);
      for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $parts = pathinfo($filename);
        if (($parts['extension'] == 'tif') and (strpos($parts['filename'], $geoname) !== FALSE)) {
          $tiffFilename = $dirname . "/" . $filename;
        }
      }
      $zip->extractTo($dirname);
      $zip->close();

      // Run gdal_translate to create the KML.
      if (file_exists($tiffFilename)) {
        $kml = $dirname . "/" . $geoname_No_Spaces . ".kml";
        // Use JPEG for all except Hawaiian Islands.
        $output_format = "jpeg";
        if ($geoname_No_Spaces == "Hawaiian_Islands") {
          $output_format = "png";
        }
        // Also check -expand rgba.
        $cmd = "/usr/bin/gdal_translate -of KMLSUPEROVERLAY -expand rgb '" . $tiffFilename . "' $kml -co format=$output_format";
        $tmp = `$cmd`;

      }

      // Remove ZIP, TIFF and TFW files.
      $cmd1 = "/bin/rm " . $dirname . "/*.tif && /bin/rm " . $dirname . "/*.tfw && /bin/rm " . $dirname . "/*.zip";
      $tmp = `$cmd1`;

      // Optimize/Compress JPG files.
      $findCmd = "find $dirname -print | grep -i \".jpg$\"";
      $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
      foreach ($fileAry as $filename) {
        $owner = fileowner($filename);
        $group = filegroup($filename);
        $lastMod = filemtime($filename);
        $cmd1 = "/usr/bin/jpegtran -copy none -progressive -optimize -perfect -outfile \"$filename\" \"$filename\"";
        $tmp1 = `$cmd1`;
        touch($filename, $lastMod);
        chown($filename, $owner);
        chgrp($filename, $group);

        // $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
        echo "JPG: optimizing $filename . . .\n";
      }

      // Optimize/Compress PNG files.
      $findCmd = "find $dirname -print | grep -i \".png$\"";
      $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
      foreach ($fileAry as $filename) {
        $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
        $tmp1 = `$cmd1`;
        echo "PNG: optimizing $filename . . .\n";
      }

      // End of "if ($faa_edition != $my_edition)".
    }

    // End of "if ($info->status->code == "200")".
  }

  // Return relative path to the generated KML file
  //  such as Baltimore.kml.
  $kml_relative = $geoname_No_Spaces . ".kml";
}
