#!/usr/bin/php
<?php
/**
 * Fetch and generate Sectional Overlays
 */

include_once "includes/coordinates2.php";

$baseDir = "/var/www/capgrids/htdocs/overlays2/";

foreach ($coordinates as $grid => $value) {
  if ($coordinates[$grid]['FullName'] != "None") {
    $currentKMLfile = getTiff($coordinates[$grid]['FullName'], $baseDir);
  }
}



/**
 * Function getTiff($geoname, $baseDir)
 *   Per https://app.swaggerhub.com/apis/FAA/APRA/1.2.0#/Sectional%20Charts/getSectionalChart
 *   fetch georeferenced Tiff and write to basedir/geoname.
 */
function getTiff($geoname, $baseDir) {
  $geoname_No_Spaces = str_replace(" ", "_", $geoname);
  $url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=current&format=tiff";
  $headers = ['Accept: application/json'];

  // Get JSON for path to the downlad.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
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
    $fh = fopen($zipfilename, 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $info->edition[0]->product->url);
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
      if ($parts['extension'] == 'tif') {
        $tiffFilename = $dirname . "/" . $filename;
      }
    }
    $zip->extractTo($dirname);
    $zip->close();

    // Create the KML.
    if (file_exists($tiffFilename)) {
      $kml = $dirname . "/" . $geoname_No_Spaces . ".kml";
      // Also check -expand rgba.
      $cmd = "/usr/bin/gdal_translate -of KMLSUPEROVERLAY -expand rgb '" . $tiffFilename . "' $kml -co format=png";
      $tmp = `$cmd`;

    }

    // Optimize/Compress PNG files.
    $findCmd = "find $dirname -print | grep -i \".png$\"";
    $fileAry = array_filter(explode(PHP_EOL, `$findCmd`));
    foreach ($fileAry as $filename) {
      $cmd1 = "/usr/bin/optipng -quiet -preserve -strip all -o5 \"$filename\"";
      echo "PNG: Checking $filename . . .\n";
      $tmp1 = `$cmd1`;
    }

  }
  // Return relative path to the generated KML file
  //  such as Baltimore.kml.
  $kml_relative = $geoname_No_Spaces . ".kml";
}

