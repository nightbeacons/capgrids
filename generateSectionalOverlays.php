#!/usr/bin/php
<?php

$baseDir="/var/www/dev.capgrids/htdocs/sectionalOverlays/";
$geonames = getGeonames();


for ($i=0; $i<=5; $i++){
getTiff($geonames[$i], $baseDir);
}


/**
 * function getTiff($geoname, $baseDir)
 *   Per https://app.swaggerhub.com/apis/FAA/APRA/1.2.0#/Sectional%20Charts/getSectionalChart
 *   fetch georeferenced Tiff and write to basedir/geoname
 */

function getTiff($geoname, $baseDir){
$geoname_No_Spaces = str_replace(" ", "_", $geoname);
$url = "https://soa.smext.faa.gov/apra/vfr/sectional/chart?geoname=" . urlencode($geoname) . "&edition=current&format=tiff"; 
$headers = ['Accept: application/json'];

// Get JSON for path to the downlad
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$json = curl_exec($ch);
curl_close($ch);

$info = json_decode($json);
print_r($info);
  if ($info->status->code == "200"){
   $zipfilename = $baseDir . $geoname_No_Spaces . "/" . $geoname_No_Spaces . ".zip";
   $dirname = dirname($zipfilename);
      if (!is_dir($dirname)){
         mkdir($dirname, 0755, true);
      }
   $fh = fopen($zipfilename, 'w'); 
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $info->edition[0]->product->url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($ch, CURLOPT_FILE, $fh);
   curl_exec($ch);
   curl_close($ch); 
   fclose($fh); 

  // Unzip the file
  $zip = new ZipArchive;
  $zip->open($zipfilename);
    for ($i = 0; $i < $zip->numFiles; $i++) {
       $filename = $zip->getNameIndex($i);
       $parts = pathinfo($filename);
         if ($parts['extension'] == 'tif'){
            $tiffFilename = $dirname . "/" . $filename;
         }
     }
  $zip->extractTo($dirname);
  $zip->close();

// Create the KML
   if (file_exists($tiffFilename)){
      $kml = $dirname . "/" . $geoname_No_Spaces . ".kml";
      $cmd = "/usr/bin/gdal_translate -of KMLSUPEROVERLAY -expand rgba '" . $tiffFilename . "' $kml -co format=png";
      $tmp = `$cmd`; 

    }

  }

}






/**
 * Return geonames array
 */

function getGeonames()
{

return  array(
	'Albuquerque',
	'Anchorage', 
	'Atlanta', 
	'Billings', 
	'Brownsville', 
	'Charlotte', 
	'Cheyenne', 
	'Chicago', 
	'Cincinnati', 
	'Dallas-Ft Worth', 
	'Denver', 
	'Detroit', 
	'El Paso', 
	'Great Falls', 
	'Green Bay', 
	'Halifax', 
	'Hawaiian Islands', 
	'Houston', 
	'Jacksonville', 
	'Kansas City', 
	'Klamath Falls', 
	'Lake Huron', 
	'Las Vegas', 
	'Los Angeles', 
	'Memphis', 
	'Miami', 
	'Montreal', 
	'New Orleans', 
	'New York', 
	'Omaha', 
	'Phoenix', 
	'Salt Lake City', 
	'San Antonio', 
	'San Francisco', 
	'Seattle', 
	'Seward', 
	'St Louis', 
	'Twin Cities', 
	'Washington', 
	'Wichita');
}


