#!/usr/bin/php
<?php
/**
 * @file
 * File: generateSectionalOverlays.php.
 *
 * Fetch and generate Sectional Overlays.
 * https://nightbeacons.atlassian.net/wiki/spaces/REFERENCE/pages/136904705/generateSectionalOverlays.php.
 *
 * Detecting all-white pngs -- see http://www.imagemagick.org/discourse-server/viewtopic.php?t=30614
 */

define('DEBUG', 0);

// Set to 1 to refresh all chart data, regardless of Edition number or date.
define('FETCH_ALL', 0);

// Backup HTML page to scrape if API fails.
$FAA_url = "https://www.faa.gov/air_traffic/flight_info/aeronav/digital_products/vfr/";
$scrape_ary = [];

// include_once "/var/www/capgrids/includes/coordinates2.php";.
// include_once "/var/www/capgrids/pwf/apt_dev.php";.
include_once "/var/www/capgrids/pwf/apt.php";
include_once "/var/www/capgrids/bin/generateGriddedSectionals_inc.php";

$baseDir = "/var/www/capgrids/htdocs/overlays/";
$latestSectionalFile = "/var/www/capgrids/htdocs/includes/latestSectionals.php";
$user_agent = "Civil Air Patrol - CAPgrids (+https://www.capgrids.com)";
$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
  printf("Connection failed: %s\n", mysqli_connect_error());
  exit();
}

$query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\" AND nextDate <= now()";
if (FETCH_ALL == 1) {
  $query = "SELECT FullName, editionDate, editionNumber, nextDate from coordinates WHERE FullName != \"None\"";
}


$r1 = $db->query($query);

$currentKMLfile = "";
// Main Loop.
while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
  $currentKMLfile = getTiff($myrow['FullName'], $baseDir, $myrow['editionDate'], $myrow['nextDate']);
}
if ($currentKMLfile != "") {
  writeLatestSectionalIncludeFile($db, $latestSectionalFile);
}

/**
 * Function getTiff($geoname, $baseDir)
 *   Per https://app.swaggerhub.com/apis/FAA/APRA/1.2.0#/Sectional%20Charts/getSectionalChart
 *   For each sectional in the coordinates db:
 *     - Check the downloaded .htm file for the date of the next edition.
 *         If that date greater than today's date, skip everything else
 *     - Get information about the sectional, including Edition #, date, and download link
 *     - Fetch the Geo-referenced Tiff file (in ZIP format), write it to basedir/geoname and unzip
 *     - Run gdal_translate to create KML superoverlay (KML + images in JPG format)
 *     - Optimize the generated JPGs.
 */
function getTiff($geoname, $baseDir, $editionDate, $nextDate) {
  global $user_agent, $FAA_url, $scrape_ary, $db;
  $kml_relative = "";
  $geoname_No_Spaces = str_replace(" ", "_", $geoname);

  $dirname = $baseDir . $geoname_No_Spaces . "/";
  if (!is_dir($dirname)) {
    mkdir($dirname, 0755, TRUE);
  }

  // Check the current edition date from the downloaded .info file to see if we've got the current verions. (Do not use API)
  // Get the new Current and Next dates from the downloaded .info file.
  // (The .info file is a copy of the downloaded .htm file, which is copied from the .htm  after each chart has completed processing)
  $dateAry                                 = parseInfoFileForDates($dirname);
  $my_current_edition_date_original_format = $dateAry['my_current_edition_date_original_format'];
  $my_current_edition_date                 = $dateAry['my_current_edition_date'];
  $my_next_edition_date_original_format    = $dateAry['my_next_edition_date_original_format'];
  $my_next_edition_date                    = $dateAry['my_next_edition_date'];

  $today_no_spaces = date("Ymd") + 0;

  // If we already have the current edition, skip everything else.
  if ($today_no_spaces >= $my_next_edition_date_original_format) {

    // Remove any older .htm files before downloading the new zip.
    $cmd1 = "/bin/rm " . $dirname . "*.htm";
    $tmp = `$cmd1`;

    $zipAry      = getZipFileUrl($geoname, $dateAry);
    $faa_edition = $zipAry['edition'];
    $zipfile_url = $zipAry['zipUrl'];
    $zipfilename = $dirname . $geoname_No_Spaces . ".zip";

    // Download the zipfile.
    if (DEBUG) {
      echo "Downloading the zipfile from $zipfile_url and writing to $zipfilename\n";
    }
    $fh = fopen($zipfilename, 'w');
    $ch = curl_init();
    curl_setopt_array($ch, setCurlOpts());
    curl_setopt($ch, CURLOPT_URL, $zipfile_url);
    curl_setopt($ch, CURLOPT_FILE, $fh);
    curl_exec($ch);
    if ($errno = curl_errno($ch)) {
      $error_message = curl_strerror($errno);
      echo "cURL error ({$errno}):\n {$error_message}";
    }
    $curlError = trim(curl_error($ch));
    $downloadInfo = curl_getinfo($ch);
    curl_close($ch);
    fclose($fh);
    if (strlen($curlError) > 1) {
      echo "CURL Error: $curlError fetching $zipfile_url\n\n";
    }
    if ($downloadInfo['http_code'] == '404') {
      echo "Zipfile not found via API, trying backup\n";
      // Scrape the page just once, and only if needed.
      if (count($scrape_ary) == 0) {
        $scrape_ary = scrape_faa_page($FAA_url);
      }
      $zipfile_url = $scrape_ary[$geoname];

      $fh = fopen($zipfilename, 'w');
      $ch = curl_init();
      curl_setopt_array($ch, setCurlOpts());
      curl_setopt($ch, CURLOPT_URL, $zipfile_url);
      curl_setopt($ch, CURLOPT_FILE, $fh);
      curl_exec($ch);
      if ($errno = curl_errno($ch)) {
        $error_message = curl_strerror($errno);
        echo "cURL error ({$errno}):\n {$error_message}";
      }
      $curlError = trim(curl_error($ch));
      $downloadInfo = curl_getinfo($ch);
      curl_close($ch);
      fclose($fh);
      if (strlen($curlError) > 1) {
        echo "CURL Error: $curlError fetching $zipfile_url\n\n";
      }

    }
    // Unzip the file.
    $zip = new ZipArchive();
    $zip->open($zipfilename);
    if (DEBUG) {
      echo "Unzipping $zipfilename to $dirname . . . \n";
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $filename = $zip->getNameIndex($i);
      $parts = pathinfo($filename);
      if (DEBUG) {
        echo "Checking for tif extension . . . \n";
      }
      if (($parts['extension'] == 'tif') and (strpos($parts['filename'], $geoname) !== FALSE)) {
        $tiffFilename = $dirname . $filename;
        if (DEBUG) {
          echo "TIF filename is $tiffFilename\n";
        }
      }
    }
    $zip->extractTo($dirname);
    $zip->close();

    // Run gdal_translate to create the KML.
    if (file_exists($tiffFilename)) {
      $kml = $dirname . $geoname_No_Spaces . ".kml";
      // Use JPEG for all except Hawaiian Islands.
      $output_format = "jpeg";
      if ($geoname_No_Spaces == "Hawaiian_Islands") {
        $output_format = "png";
      }
      // Also check -expand rgba.
      $cmd = "/usr/bin/gdal_translate -of KMLSUPEROVERLAY -expand rgba '" . $tiffFilename . "' $kml -co format=$output_format";
      if (DEBUG == 1) {
        echo "Running gdal_translate on $tiffFilename to create KML called $kml in format $output_format\n     $cmd\n";
      }
      $tmp = `$cmd`;

      // Add the current edition date to the <name> of the KML file.
      if (DEBUG == 1) {
        echo "Reading contents of $kml\n";
      }
      $kml_contents = file_get_contents($kml);
      $searchTerm = "<name>" . $geoname_No_Spaces . "</name>";
      $replacement = "<name>" . $geoname . " Sectional " . $my_current_edition_date_original_format . "</name>";
      $kml_contents = str_replace($searchTerm, $replacement, $kml_contents);

      if (DEBUG == 1) {
        echo "Writing revised contents of $kml\n";
      }

      file_put_contents($kml, $kml_contents);

      // Remove ZIP and TFW files.
      //    $cmd1 = "/bin/rm " . $dirname . "/*.tif && /bin/rm " . $dirname . "/*.tfw && /bin/rm " . $dirname . "/*.zip";.
      $cmd1 = "/bin/rm " . $dirname . "*.tfw && /bin/rm " . $dirname . "*.zip";

      if (DEBUG == 1) {
        echo "Removing ZIP and TFW files\n $cmd1\n\n";
      }
      $tmp = `$cmd1`;

      // Optimize the JPGs in and below $dirname.
      optimizeJpgFiles($dirname);

      // Make transparent, then Optimize/Compress PNG files in and below $dirname.
      optimizePngFiles($dirname);

      // End of "if ($faa_edition != $my_edition)".
      // GENERATE GRIDDED SECTIONAL.
      buildGriddedPng($geoname);

      // End of "if ($info->status->code == "200")".
      //    }
      // End of   "if (isset($info->status->code))".
      //  }
      // Return relative path to the generated KML file
      //  such as Baltimore.kml.
      $kml_relative = $geoname_No_Spaces . ".kml";
      if (DEBUG == 1) {
        echo "KML Relative Path is $kml_relative\n";
      }

      // Update the .info file.
      if (DEBUG) {
        echo "Updating " . $dateAry['info_file'] . " from " . $dateAry['htm_file'] . "\n";
      }
      copy($dateAry['htm_file'], $dateAry['info_file']);

      // Get the date of the next edition.
      $dateAry                 = parseInfoFileForDates($dirname);
      $my_next_edition_date    = $dateAry['my_next_edition_date'];
      $my_current_edition_date = $dateAry['my_current_edition_date'];

      // $nextDate = getNextEditionDate($geoname);
      $nextDate = $my_next_edition_date;
      // Update MySQL tables.
      $query = "UPDATE coordinates SET editionNumber='" . $faa_edition . "', editionDate='" . $my_current_edition_date . "', nextDate='" . $my_next_edition_date . "'  WHERE FullName='" . $geoname . "' LIMIT 1";

      echo "Running query $query\n";

      echo "\n$query\n";
      if (!$db->query($query)) {
        echo "\nquery failed: (" . $db->errno . ") " . $db->error;
        echo "\nQuery = $query\n";
      }

    }    // End of "if (file_exists($tiffFilename))"
  }
  else {
    echo "Skipping $geoname -- we have latest version: Ours = $my_current_edition_date and expires = $my_next_edition_date\n";
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
function XXXgetNextEditionDate($geoname) {
  global $user_agent;

  $faa_next_date = "";
  // $url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=next&format=tiff";
  $url = "https://external-api.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=next&format=tiff";

  $headers = ['Accept: application/json'];
  $ch = curl_init();
  curl_setopt_array($ch, setCurlOpts());
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
  if (DEBUG) {
    echo "Next FAA edition for $geoname is $faa_next_date\n";
  }
  return($faa_next_date);
}

/**
 * FaaDate2SQLdate($faa_date)
 *  Accept an FAA-formatted date (mm/dd/YYYY)
 *  or an FAA metatag date (YYYYmmdd)
 *  and return an SQL-formatted date.
 */
function faaDate2SQLdate($faa_date) {
  $format = "Ymd";
  if (strpos($faa_date, "/") > 0) {
    $format = "m/d/Y";
  }
  $date_ary = date_parse_from_format($format, $faa_date);
  $sql_date = sprintf("%04d-%02d-%02d", $date_ary['year'], $date_ary['month'], $date_ary['day']);
  return($sql_date);
}

/**
 * WriteLatestSectionalIncludeFile($db, $filespec)
 *   Write the 'include' file that lists the sectionals
 * just updated.
 */
function writeLatestSectionalIncludeFile($db, $latestSectionalFile) {
  if (DEBUG) {
    echo "Writing to Include file at $latestSectionalFile\n";
  }
  $fh = fopen($latestSectionalFile, "w");
  if ($fh == FALSE) {
    echo "Unable to open $latestSectionalFile for writing.\n";
  }
  $count = 0;
  $query = "SELECT FullName, Abbrev, editionNumber, DATE_FORMAT(editionDate, '%e-%M-%Y') AS editionDate from coordinates
   WHERE editionDate = (select distinct editionDate from coordinates  order by editionDate desc limit 1)
   ORDER BY FullName";

  $r1 = $db->query($query);

  while ($myrow = $r1->fetch_array(MYSQLI_ASSOC)) {
    if ($count == 0) {
      fwrite($fh, "<p class=\"sectionalUpdateHead\">Sectionals updated " . $myrow['editionDate'] . "</p>\n");
      // fwrite($fh, "<ul>\n");.
    }
    // fwrite($fh, "<li class=\"sectionalUpdateRow\"><a class=\"sectionalUpdateLink\" href=\"/overlays/" . $myrow['Abbrev'] . "_grid.kmz\">" . $myrow['FullName'] . "</a>  " . $myrow['editionNumber'] . "</li>\n");.
    $count++;
  }
  // fwrite($fh, "</ul>\n");.
  fclose($fh);

}

/**
 * Function scrape_faa_page($FAA_url, $sectional_key)
 *   If the API is returning a bad value for the URL to the
 * zipfile, then scrape the HTML page to fetch the correct
 * download URL.
 *
 *  Accept the URL to the FAA "digital products" page
 *  and the sectional_key, "sectional-files"
 *
 *  Return an assoc. array of the form:
 *      [San Francisco] => https://aeronav.faa.gov/visual/02-25-2021/sectional-files/San_Francisco.zip
 */
function scrape_faa_page($FAA_url, $sectional_key = "sectional-files") {
  global $user_agent;
  // Fetch the HTML page containing links to the sectional downloads.
  $ch = curl_init();
  curl_setopt_array($ch, setCurlOpts());
  curl_setopt($ch, CURLOPT_URL, $FAA_url);
  $page = curl_exec($ch);
  $curlError = trim(curl_error($ch));
  $curlStatus = curl_getinfo($ch);
  curl_close($ch);

  if ((strlen($curlError) > 1) or ($curlStatus['http_code'] == '404')) {
    $page .= "CURL Error: $curlError fetching $url\nStatus: " . $curlStatus['http_code'] . "\n";
    if (DEBUG) {
      echo $page;
    }
  }

  // Parse the returned HTML into an array.
  $ary = preg_split("|(<tr><td>.*?</td></tr>?\s)|", $page, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
  $sectionals = [];
  foreach ($ary as $idx => $record) {
    if (strpos($record, $sectional_key) > 1) {
      $name = trim(preg_replace("|<tr><td>(.*?)</td>.*|", '${1}', $record));
      $url = trim(preg_replace("|.*?<a href=(.*?\.zip)>.*|", '${1}', $record));
      $sectionals[$name] = $url;
    }
  }
  return($sectionals);
}

/**
 * ParseInfoFileForDates($dirname)
 *  Scan the directory for a .info file
 *  Parse the file for the meta-data:
 *    dc.coverage.t.min (date of current edition)
 *    dc.coverage.t.max (date of next edition)
 *  which are in the form YYYYmmdd.
 *
 *  The .info file is a copy of the existing .htm file, before the
 *  new zipfile is downloaded and unzipped (and overwriting the .htm)
 *    The zipfile is downloaded and unzipped before any chart processing happens.
 *  The .info file is copied from the .html as the final step in chart processing.
 *  If the script is interrupted or aborted before the processing is complete,
 *  the .info file will contain the correct data.
 *
 *  Return as an assoc. array
 *  If .info is not found, return zeroes in the array.
 */
function parseInfoFileForDates($dirname) {
  $dateAry['my_current_edition_date'] = $dateAry['my_next_edition_date'] = $dateAry['my_current_edition_date_original_format'] = 0;
  $dh = opendir($dirname);
  $files = [];
  while (($file = readdir($dh)) !== FALSE) {
    if (substr($file, strlen($file) - 4) == '.htm') {
      $info_filename = str_replace('.htm', '.info', $file);
      if (!file_exists($dirname . $info_filename)) {
        if (DEBUG) {
          echo "Info file " . $dirname . $info_filename . " does not exist.\nCopying from " . $dirname . $file . " to create it.\n";
        }
        copy($dirname . $file, $dirname . $info_filename);
      }
      $info_file = file_get_contents($dirname . $info_filename);
      if ($info_file !== FALSE) {
        $dateAry['my_current_edition_date_original_format'] = trim(preg_replace("/.*?<meta name=\"dc.coverage.t.min\" content=\"(\d+).*/s", '$1', $info_file)) + 0;
        $dateAry['my_current_edition_date']                 = faaDate2SQLdate($dateAry['my_current_edition_date_original_format']);
        $dateAry['my_next_edition_date_original_format']    = trim(preg_replace("/.*?<meta name=\"dc.coverage.t.max\" content=\"(\d+).*/s", '$1', $info_file)) + 0;
        $dateAry['my_next_edition_date']                    = faaDate2SQLdate($dateAry['my_next_edition_date_original_format']);
        $dateAry['info_file']                               = $dirname . $info_filename;
        $dateAry['htm_file']                                = $dirname . $file;
      }
    }
  }
  closedir($dh);
  return($dateAry);
}

/**
 * GetZipFileUrl($geoname, $dateAry)
 * If we are here, we know that the "Next Edition" date
 *   is less than or equal to the current date.
 * (That is, the chart has expired and a new one is available)
 * Fetch & return the URL for to the appropriate zipfile.
 * Also return the Edition number.
 */
function getZipFileUrl($geoname, $dateAry) {

  $json_ary         = curlFetch($geoname, 'current');
  $json_ary_next    = curlFetch($geoname, 'next');
  $faa_edition_date = faaDate2SQLdate($json_ary->edition[0]->editionDate);
  $edition          = 0;
  $secinfo          = [];

  if ($dateAry['my_current_edition_date'] == $faa_edition_date) {

    // We already have this one. Use the Next.
    $zipUrl  = $json_ary_next->edition[0]->product->url;
    $edition = $json_ary_next->edition[0]->editionNumber;
    if (DEBUG) {
      echo "We already have this chart. Grabbing the 'Next' edition.\n";
    }
  }
  else {

    // We don't already have this one. Use the Current.
    $zipUrl  = $json_ary->edition[0]->product->url;
    $edition = $json_ary->edition[0]->editionNumber;
    if (DEBUG) {
      echo "We don't already have this chart. Grabbing the 'Current' edition.\n";
    }
  }
  $zipUrl = str_replace(' ', '_', trim($zipUrl));
  $secinfo['zipUrl'] = $zipUrl;
  $secinfo['edition'] = trim($edition);
  return ($secinfo);
}

/**
 * CurlFetch($geoname, $edition)
 * Fetch the JSON for the given geoname +  edition (current | next)
 * Return JSON as array.
 */
function curlFetch($geoname, $edition) {
  $url = "https://external-api.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=" . $edition . "&format=tiff";
  $headers = ['Accept: application/json'];
  echo "Fetching $geoname from $url\n";
  // Get JSON for path to the downlad.
  $ch = curl_init();
  curl_setopt_array($ch, setCurlOpts());
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $json = curl_exec($ch);
  $curlError = trim(curl_error($ch));
  curl_close($ch);

  if (strlen($curlError) > 1) {
    echo "CURL Error: $curlError fetching $url\n\n";
  }

  $json_ary = json_decode($json);
  if (DEBUG == 1) {
    $returned_json = print_r($json_ary, TRUE);
    echo "JSON received from $url is \n$json\n and decoded is $returned_json\n\n";
  }
  return($json_ary);
}

/**
 * OptimizeJpgFiles($dirname)
 * Optimize and compress all JPG files in and beneath $dirname.
 */
function optimizeJpgFiles($dirname) {
  if (DEBUG == 1) {
    echo "Compressing JPGs\n";
  }
  $findCmd = "find $dirname -print | grep -i \".jpg$\"";
  $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
  foreach ($fileAry as $filename) {
    $owner = fileowner($filename);
    $group = filegroup($filename);
    $lastMod = filemtime($filename);
    $cmd1 = "/usr/bin/jpegtran -copy none -progressive -optimize -perfect -outfile \"$filename\" \"$filename\"";
    if (DEBUG) {
      // Echo "     $cmd1\n";.
    }
    $tmp1 = `$cmd1`;
    touch($filename, $lastMod);
    chown($filename, $owner);
    chgrp($filename, $group);
  }
}

/**
 * OptimizePngFiles($dirname)
 * Make PNGs transparent
 * If a tile (PNG) is more than 75% white, white to transparent
 * Optimize and compress.
 */
function optimizePngFiles($dirname) {
  // Make transparent, then Optimize/Compress PNG files.
  $findCmd = "find $dirname -print | grep -i \".png$\"";
  $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
  foreach ($fileAry as $filename) {

    // If a given tile is more than 75% white, set white to transparent.
    $cmdC1 = "/usr/bin/convert \"$filename\" -alpha off -scale 1x1 -format \"%[fx:u]\" info:";
    $check1 = trim(`$cmdC1`) + 0;
    // $cmdC2 = "/usr/bin/convert  \"$filename\" -format %c histogram:info:- | grep -v rgba | head -1";
    //  $check2a = trim(`$cmdC2`);
    //  $check2 = preg_match("/^\d+: \(255,255,255\) #FFFFFF white/", $check2a);
    //  echo "$filename\n|CHECK 1 = $check1|\n|$check2a|\n$check2\n\n";
    if ($check1 > 0.75) {
      $cmd4 = "/usr/bin/convert \"$filename\" -fuzz 2% -transparent white \"$filename\"";
      $tmp4 = `$cmd4`;
      if (DEBUG == 1) {
        echo " Tile $filename is more than 75% white -- change white to transparent.\n";
      }
    }

    $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
    $tmp1 = `$cmd1`;
    if (DEBUG == 1) {
      echo "     $cmd1\n";
    }
  }
}

/**
 * SetCurlOpts()
 * Set commonly used curl options
 * return array to be used by curl_setopt_array()
 */
function setCurlOpts() {
  global $user_agent;

  $curlAry = [
    CURLOPT_USERAGENT => $user_agent,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_BINARYTRANSFER => TRUE,
    CURLOPT_CONNECTTIMEOUT => 180,
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_FOLLOWLOCATION => TRUE,
  ];

  return($curlAry);
}
