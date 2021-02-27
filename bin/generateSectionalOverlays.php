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

define('DEBUG', 0);

// Set to 1 to refresh all chart data, regardless of Edition number.
define('FETCH_ALL', 0);

// include_once "/var/www/capgrids/includes/coordinates2.php";
include_once "/var/www/capgrids/pwf/apt.php";

$baseDir = "/var/www/capgrids/htdocs/overlays/";
$latestSectionalFile = "/var/www/capgrids/htdocs/includes/latestSectionals.php";
$user_agent = "Civil Air Patrol - CAPgrids (+https://www.capgrids.com)";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

// $query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\"";
$query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\" AND nextDate <= now()";

$r1 = $db->query($query);

$currentKMLfile="";
while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
  $currentKMLfile = getTiff($myrow['FullName'], $baseDir);
}
  if ($currentKMLfile != ""){
    writeLatestSectionalIncludeFile($db, $latestSectionalFile);
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
  if (DEBUG ==1) {
    echo "Fetching $geoname from $url\n";
  }
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
  if (DEBUG ==1){
    $returned_json = print_r($info, TRUE);
    echo "JSON received from $url is \n$json\an and decoded is $returned_json\n\n";
  }
  if (isset($info->status->code)) {
    if ($info->status->code == "200") {
      $zipfilename = $baseDir . $geoname_No_Spaces . "/" . $geoname_No_Spaces . ".zip";
      $dirname = dirname($zipfilename);
        if (DEBUG ==1){
          echo "zipfilename = $zipfilename\n\n";
        }
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
      if (DEBUG ==1){
        echo "FAA edition date is $faa_edition_date\n";
      }
      if (($faa_edition != $my_edition) or (FETCH_ALL == 1)) {
            if (DEBUG ==1){
              echo "FAA edition date does not match ours\n   FAA = $faa_edition\n   Ours = $my_edition\n\n";
            } 
        // Remove the older .htm files
        $cmd1 = "/bin/rm " . $dirname . "/*.htm";
        $tmp = `$cmd1`;

        // Download the zipfile.
        $zipfile_url = trim($info->edition[0]->product->url);
        if (DEBUG ==1){
          echo "Downloading the zipfile from $zipfile_url\n";
        }
        $fh = fopen($zipfilename, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipfile_url);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_exec($ch);
        $curlError = trim(curl_error($ch));
        curl_close($ch);
        fclose($fh);
         if (strlen($curlError) > 1) {
           echo "CURL Error: $curlError fetching $zipfile_url\n\n";
         }

        // Unzip the file.
        $zip = new ZipArchive();
        $zip->open($zipfilename);
          if (DEBUG ==1){
            echo "Unzipping $zipfilename to $dirname . . . \n";
          }
        for ($i = 0; $i < $zip->numFiles; $i++) {
          $filename = $zip->getNameIndex($i);
          $parts = pathinfo($filename);
          if (DEBUG ==1){
            echo "Checking for tif extension . . . \n";
          }
          if (($parts['extension'] == 'tif') and (strpos($parts['filename'], $geoname) !== FALSE)) {
            $tiffFilename = $dirname . "/" . $filename;
             if (DEBUG ==1){
             echo "TIF filename is $tiffFilename\n";
             }
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
          $cmd = "/usr/bin/gdal_translate -of KMLSUPEROVERLAY -expand rgba '" . $tiffFilename . "' $kml -co format=$output_format";
             if (DEBUG ==1){
             echo "Running gdal_translate on $tiffFilename to create KML called $kml in format $output_format\n     $cmd\n";
             }
          $tmp = `$cmd`;

          // Add the current edition number to the <name> of the KML file
             if (DEBUG ==1){
             echo "Reading contents of $kml\n";
             }
          $kml_contents = file_get_contents($kml);
          $searchTerm = "<name>" . $geoname_No_Spaces . "</name>";
          $replacement = "<name>" . $geoname . " Sectional " . $faa_edition . "</name>";
          $kml_contents = str_replace($searchTerm, $replacement, $kml_contents);
          
             if (DEBUG ==1){
             echo "Writing revised contents of $kml\n";
             }

          file_put_contents($kml, $kml_contents);

          // Get the date of the next edition.
          $nextDate = getNextEditionDate($geoname);

          // Update MySQL tables.
          $query = "UPDATE coordinates SET editionNumber='" . $faa_edition . "', editionDate='" . $faa_edition_date . "', nextDate='" . $nextDate . "'  WHERE FullName='" . $geoname . "' LIMIT 1";

             if (DEBUG ==1){
             echo "Running query $query\n";
             }

          echo "\n$query\n";
          if (!$db->query($query)) {
            echo "\nquery failed: (" . $db->errno . ") " . $db->error;
            echo "\nQuery = $query\n";
          }
        }

        // Remove ZIP, TIFF and TFW files.
        $cmd1 = "/bin/rm " . $dirname . "/*.tif && /bin/rm " . $dirname . "/*.tfw && /bin/rm " . $dirname . "/*.zip";
             if (DEBUG ==1){
                echo "Removing ZIP, TIFF and TFW files\n $cmd1\n\n";
             }
        $tmp = `$cmd1`;

        // Optimize/Compress JPG files.
             if (DEBUG ==1){
               echo "Compressing JPGs\n";
             }
        $findCmd = "find $dirname -print | grep -i \".jpg$\"";
        $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
        foreach ($fileAry as $filename) {
          $owner = fileowner($filename);
          $group = filegroup($filename);
          $lastMod = filemtime($filename);
          $cmd1 = "/usr/bin/jpegtran -copy none -progressive -optimize -perfect -outfile \"$filename\" \"$filename\"";
             if (DEBUG ==1){
               echo "     $cmd1\n";
             }
          $tmp1 = `$cmd1`;
          touch($filename, $lastMod);
          chown($filename, $owner);
          chgrp($filename, $group);

          // $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
         // echo "JPG: optimizing $filename . . .\n";
        }

        // Make transparent, then Optimize/Compress PNG files.
        $findCmd = "find $dirname -print | grep -i \".png$\"";
        $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
        foreach ($fileAry as $filename) {

          // If a given tile is more than 75% white, set white to transparent
          $cmdC1 = "/usr/bin/convert \"$filename\" -alpha off -scale 1x1 -format \"%[fx:u]\" info:";
          $check1 = trim(`$cmdC1`) + 0;
          //  $cmdC2 = "/usr/bin/convert  \"$filename\" -format %c histogram:info:- | grep -v rgba | head -1";
          //  $check2a = trim(`$cmdC2`);
          //  $check2 = preg_match("/^\d+: \(255,255,255\) #FFFFFF white/", $check2a);
          //  echo "$filename\n|CHECK 1 = $check1|\n|$check2a|\n$check2\n\n";
            if ($check1 > 0.75) {
              $cmd4 = "/usr/bin/convert \"$filename\" -fuzz 2% -transparent white \"$filename\"";
              $tmp4 = `$cmd4`;
            }

          $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
          $tmp1 = `$cmd1`;
        //  echo "PNG: optimizing $filename . . .\n";
        }

        // End of "if ($faa_edition != $my_edition)".
      }
      else {
        echo "Skipping $geoname -- we have latest version: Ours = $my_edition and FAA = $faa_edition \n";
      }

      // End of "if ($info->status->code == "200")".
    }

    // End of   "if (isset($info->status->code))".
  }

  // Return relative path to the generated KML file
  //  such as Baltimore.kml.
  $kml_relative = $geoname_No_Spaces . ".kml";
       if (DEBUG ==1){
          echo "KML Relative Path is $kml_relative\n";
       }
  return($kml_relative);
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
       if (DEBUG ==1){
          echo "Next FAA edition for $geoname is $faa_next_date\n";
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


/**
 * writeLatestSectionalIncludeFile($db, $filespec)
 *   Write the 'include' file that lists the sectionals
 * just updated.
 */
function writeLatestSectionalIncludeFile($db, $latestSectionalFile){
       if (DEBUG ==1){
       echo "Writing to Include file at $latestSectionalFile\n";
       }
$fh = fopen($latestSectionalFile, "w");
      if ($fh == FALSE){
        echo "Unable to open $latestSectionalFile for writing.\n";
      }
$count=0;
$query = "SELECT FullName, Abbrev, editionNumber, DATE_FORMAT(editionDate, '%e-%M-%Y') AS editionDate from coordinates
   WHERE editionDate = (select distinct editionDate from coordinates  order by editionDate desc limit 1)
   ORDER BY FullName";

$r1 = $db->query($query);

  while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
    if ($count ==0){
      fwrite($fh, "<p class=\"sectionalUpdateHead\">Sectionals updated " . $myrow['editionDate'] . ":</p>\n<ul>\n");
    }
  fwrite($fh, "<li class=\"sectionalUpdateRow\"><a class=\"sectionalUpdateLink\" href=\"/overlays/" . $myrow['Abbrev'] . "_grid.kmz\">" . $myrow['FullName'] . "</a>  " . $myrow['editionNumber'] . "</li>\n");
  $count++;
  }
fwrite($fh, "</ul>\n");
fclose($fh);

}
