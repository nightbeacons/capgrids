#!/usr/bin/php
<?php
include_once "/var/www/capgrids/pwf/aixm.php";
include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_apt.php";
include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_fix.php";
include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_nav.php";
include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_ils.php";

$DEVMODE=FALSE;

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

// DB table names are lc versions of filenames
//  Exception: the db 'rwy' is derived from the APT.txt file
$files_to_process = array('APT', 'NAV', 'FIX', 'ILS');
// $files_to_process = array('ILS');


$workDir = "/tmp/aixm/";
   if (!is_dir($workDir)){
   mkdir($workDir);
   }

$nextFile = $workDir . "nextEdition.txt";
$nextDate = file_get_contents($nextFile);
$today = date('Y-m-d');

// If the "Next Edition Date" is today 
//  (or in the past)
// then fetch the new files
  if (($today >= $nextDate) OR (!file_exists($nextFile)) OR $DEVMODE){

    if (mysqli_connect_errno()){
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
    }
  $result = downloadLatestZipfile($workDir);

  foreach($files_to_process as $data_file){
  $file = $workDir . $data_file . ".txt";

     if (!file_exists($file)){
     die("Could not find $data_file file. Exiting");
     }

  switch($data_file){
    case "APT":
      $result = parseAptFile($file);
      $result = parseAptFileForRwy($file);
      break;

    case "NAV":
      $result = parseNavFile($file);
      break;

    case "FIX":
      $result = parseFixFile($file);
      break;

    case "ILS":
      $result = parseIlsFile($file);
      break;
  }

  $query = "OPTIMIZE TABLE " . strtolower($data_file);
  $try = $db->query($query);

// Update X-Plane CIFP data
  $tmp=`/var/www/capgrids/bin/xplane_earth_424/xplane_earth_424.php`;
  }
 
  if (!$DEVMODE){
    writeIncludeFile();
    writeNextEditionDate($nextFile);
  }
  mysqli_close($db);

  }





/**
 * convertToDecimalDegrees($dash_formatted)
 *   Input: Latitude or Longitude string of the form 088-22-10.820W
 *   Return: Decimal latitude or longitude.
 *         W longitude and S latitude are negative vals
 */
function convertToDecimalDegrees($dash_formatted) {
  $finalChar = substr(trim($dash_formatted), -1);
  $degAry = explode("-", $dash_formatted);
  $decimalVal = $degAry[0] + $degAry[1] / 60 + (rtrim($degAry[2], 'A..Z')) / 3600;
  if ($finalChar == 'W' or $finalChar == 'S') {
    $decimalVal = -($decimalVal);
  }
  return($decimalVal);
}

/**
 * Find and download the latest AIXM data file
 * Save to $workDir as aixmData.zip
 *
 *  See https://app.swaggerhub.com/apis/FAA/APRA/ (28 Day Subscription) for API details
 */

function downloadLatestZipfile($workDir){
//$landingPageUrl="https://nfdc.faa.gov/xwiki/bin/view/NFDC/28+Day+NASR+Subscription";
//$landingPageUrl="https://www.faa.gov/air_traffic/flight_info/aeronav/aero_data/NASR_Subscription/";
$apiUrl = "https://external-api.faa.gov/apra/nfdc/nasr/chart?edition=current";
echo "Fetching data . . . \n";

$zipFileName = "aixmData.zip";
$retval = 0;
$errors = "";
$result = fetchUrl($apiUrl);
   if (strpos($result['error'], "client certificate not found") > 1){
   $result['error']="";
   }
   if (strlen($result['error']) < 1){
   $info = json_decode($result['result']);
         print_r($info);

   $editionDate = $info->edition[0]->editionDate; 
   $zipFileUrl = $info->edition[0]->product->url;
   $zipFileDest = $workDir . $zipFileName;
echo "Fetching $zipFileUrl\n";

   $zipFileGet = fetchUrl($zipFileUrl, $zipFileDest);
   $retval=$zipFileGet['result'] + 0;
      if (strlen($zipFileGet['error']) > 0){
      $errors .= "Error fetching $zipFileUrl: " . $zipFileGet['error'] . "\n";
      }
   }else{
   $errors .= "Error fetching $zipFileUrl: " . $result['error'] . "\n";
   }
   $result = array(
                "status"  => $retval,
                "error"   => $errors,
                "edition" => $editionDate,
                "zipfile" => $zipFileDest
             );

      if ($retval > 0){
      echo "Unzipping $zipFileDest to $workDir \n";
      $unpack = "/usr/bin/unzip -o $zipFileDest -d $workDir && /bin/rm $zipFileDest";
      $cmdResult = `$unpack`;
      echo "Result of unzip is $cmdResult\n";
      }

return($result);
}


/**
 * Fetch URL
 */
function fetchUrl($url, $downloadFile=""){
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
curl_setopt($ch, CURLOPT_HTTPGET, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36");
   if (strlen($downloadFile) > 1){
   $fp = fopen($downloadFile, 'w');
   curl_setopt($ch, CURLOPT_FILE, $fp);
   } else {
   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: application/json'
    ));
   }

$result = curl_exec($ch);
$curlError = trim(curl_error($ch));
curl_close($ch);

$retval = array(
                'result' => $result,
                'error'  => $curlError
          );
   if (strlen($downloadFile) > 1){
   fclose($fp);
   }
return($retval);
}



/**
 * Parse the README for the effective date of the data
 * Write to an include file
 */
function writeIncludeFile()
{
$includeFile = "/var/www/capgrids/htdocs/includes/lastMod.php";
$readmeFile = "/tmp/aixm/README.txt";
$readme = file_get_contents($readmeFile);

$p = preg_match("/AIS subscriber files effective date.*[\.\$]/m", $readme, $matches);
$effDate = trim(preg_replace("/AIS subscriber files effective date/", "", $matches[0]), " .");
$etag = md5($effDate);

$fh = fopen($includeFile, "w");
fwrite($fh, "<?php\n\$dataLastModified = \"$effDate\";\n\$airportDataEtag = \"$etag\";\n");
fclose($fh);
}


/**
 * Get the date of the next edition and write to a file
 */
function writeNextEditionDate($nextFile)
{
$nextUrl = "https://external-api.faa.gov/apra/nfdc/nasr/info?edition=next";
$result = fetchUrl($nextUrl);
   if (strpos($result['error'], "client certificate not found") > 1){
   $result['error']="";
   }
   if (strlen($result['error']) < 1){
   $info = json_decode($result['result']);
   //      print_r($info);

   $editionDateObj = date_create_from_format('m/d/Y', $info->edition[0]->editionDate);
   $editionDate = date_format($editionDateObj, 'Y-m-d');
   }
file_put_contents($nextFile, $editionDate);
//echo "NEXT IS |" . $editionDate . "|\n\n";
}


