#!/usr/bin/php
<?php
/**
 * @file
 * File: generateSectionalOverlays.php.
 *
 * Fetch and generate Sectional Overlays.
 *
 * Detecting all-white pngs -- see http://www.imagemagick.org/discourse-server/viewtopic.php?t=30614
 */

// Set to 1 to refresh all chart data, regardless of Edition number.
define('FETCH_ALL', 0);

include_once "includes/coordinates2.php";
include_once "/var/www/capgrids/pwf/apt.php";


$baseDir = "/var/www/capgrids/htdocs/overlays/";
$user_agent = "Civil Air Patrol - CAPgrids (+https://www.capgrids.com)";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

// $query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\"";
$query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\" AND nextDate <= now()";

$r1 = $db->query($query);


while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
  $currentKMLfile = getTiff($myrow['FullName'], $baseDir);
}

// Foreach ($coordinates as $grid => $value) {
//  if ($coordinates[$grid]['FullName'] != "None") {
//    $currentKMLfile = getTiff($coordinates[$grid]['FullName'], $baseDir);
//  }
// }.

/**
 * Function getTiff($geoname, $baseDir)
 *   Per https://app.swaggerhub.com/apis/FAA/APRA/1.2.0#/Sectional%20Charts/getSectionalChart
 *   For each sectional in the coordinates.php file:
 *     - Get information about the sectional, including Edition #, date, and download link
 *     - Fetch the Geo-referenced Tiff file (in ZIP format), write it to basedir/geoname and unzip
 *     - Run gdal_translate to create KML superoverlay (KML + images in JPG format)
 *     - Optimize the generated JPGs.
 */
function getTiff($geoname, $baseDir) {
  global $user_agent, $db;
  $geoname_No_Spaces = str_replace(" ", "_", $geoname);
  $url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=current&format=tiff";
  $headers = ['Accept: application/json'];

  echo "Fetching $geoname\n";

  // Get JSON for path to the downlad.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  $json = curl_exec($ch);
  $curlError = trim(curl_error($ch));
  curl_close($ch);

  if (strlen($curlError) > 1) {
    echo "CURL Error: $curlError fetching $url\n\n";
  }

  $info = json_decode($json);
  if (isset($info->status->code)) {
    if ($info->status->code == "200") {
      $zipfilename = $baseDir . $geoname_No_Spaces . "/" . $geoname_No_Spaces . ".zip";
      $dirname = dirname($zipfilename);
      if (!is_dir($dirname)) {
        mkdir($dirname, 0755, TRUE);
      }

      // Check the file system to see if we have the latest edition.
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
      // Date is formatted as mm/dd/YYYY. Use faaDate2SQLdate to format as ISO date.
      $faa_edition_date = faaDate2SQLdate($info->edition[0]->editionDate);

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

          // Get the date of the next edition.
          $nextDate = getNextEditionDate($geoname);

          // Update MySQL tables.
          $query = "UPDATE coordinates SET editionNumber='" . $faa_edition . "', editionDate='" . $faa_edition_date . "', nextDate='" . $nextDate . "'  WHERE FullName='" . $geoname . "' LIMIT 1";
          echo "\n$query\n";
          if (!$db->query($query)) {
            echo "\nquery failed: (" . $db->errno . ") " . $db->error;
            echo "\nQuery = $query\n";
          }
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
      else {
        echo "Skipping $geoname -- we have latest version\n";
      }

      // End of "if ($info->status->code == "200")".
    }

    // End of   "if (isset($info->status->code))".
  }

  // Return relative path to the generated KML file
  //  such as Baltimore.kml.
  $kml_relative = $geoname_No_Spaces . ".kml";
}

/**
 * GetNextEditionDate($geoname)
 * Query the FAA server and get the date of the next edition
 * for the given chart.
 *   Input: geoname (aka "FullName") of the chart
 *   Return: Date of the next edition, in SQL format.
 */
function getNextEditionDate($geoname) {
  global $user_agent;

  $faa_next_date = "";
  $url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=next&format=tiff";
  $headers = ['Accept: application/json'];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  $json = curl_exec($ch);
  $curlError = trim(curl_error($ch));
  curl_close($ch);

  if (strlen($curlError) > 1) {
    echo "CURL Error: $curlError fetching $url\n\n";
  }
  $info = json_decode($json);
  if (isset($info->status->code)) {
    if ($info->status->code == "200") {
      $faa_next_date = faaDate2SQLdate($info->edition[0]->editionDate);
    }
  }
  return($faa_next_date);
}

/**
 * FaaDate2SQLdate($faa_date)
 *  Accept an FAA-formatted date (mm/dd/YYYY)
 *  and return an SQL-formatted date.
 */
function faaDate2SQLdate($faa_date) {
  $date_ary = date_parse_from_format("m/d/Y", $faa_date);
  $sql_date = sprintf("%04d-%02d-%02d", $date_ary['year'], $date_ary['month'], $date_ary['day']);
  return($sql_date);
}
