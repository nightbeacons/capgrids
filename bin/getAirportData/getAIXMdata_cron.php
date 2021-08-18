#!/usr/bin/php
<?php
include_once "/var/www/capgrids/pwf/aixm.php";

$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);

// DB table names are lc versions of filenames
//$files_to_process = array('APT', 'NAV', 'FIX');
$files_to_process = array('FIX');


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
// DEV  if ($today >= $nextDate){
  if (TRUE) {

    if (mysqli_connect_errno()){
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
    }
// DEV  $result = downloadLatestZipfile($workDir);

  foreach($files_to_process as $data_file){
  $file = $workDir . $data_file . ".txt";

     if (!file_exists($file)){
     die("Could not find $data_file file. Exiting");
     }

  switch($data_file){
    case "APT":
      $result = parseAptFile($file);
      break;

    case "NAV":
      $result = parseNavFile($file);
      break;

    case "FIX":
      $result = parseFixFile($file);
      break;

  }

//  $query = "OPTIMIZE TABLE " . lc($data_file);
//  $try = $db->query($query);

  }

// DEV  writeIncludeFile();
// DEV  writeNextEditionDate($nextFile);
  mysqli_close($db);

  }



/**
 * parseAptFile($file)
 *  Line-by-line, parses the flat-text APT.DAT file
 *  using array of strpos values
 *  Input: Path to APT.txt
 */

function parseAptFile($file){
global $db;

$stringPositions = array(
    "name"             => array("start" => 133,  "length" => 50),
    "ICAOcode"         => array("start" => 1210, "length" => 4),
    "aptCode"          => array("start" => 27,   "length" => 4),
    "stateAbbrev"      => array("start" => 91,   "length" => 2),
    "city"             => array("start" => 93,   "length" => 40),
    "sectionalAbbrev"  => array("start" => 712,  "length" => 3),
    "sectional"        => array("start" => 716,  "length" => 30),
    "latitude"         => array("start" => 523,  "length" => 13),
    "N_S"              => array("start" => 536,  "length" => 1),
    "decLatitude"      => array("start" => 537,  "length" => 12),
    "longitude"        => array("start" => 550,  "length" => 14),
    "E_W"              => array("start" => 564,  "length" => 1),
    "decLongitude"     => array("start" => 565,  "length" => 11),
    );

$query = "DELETE FROM apt";
$try = $db->query($query);
$try = $db->query("ALTER TABLE apt DROP INDEX ix_spatial_apt_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 14, 13));    // Position of "AIRPORT"
         if ($type == "AIRPORT"){
         $airportData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $airportData[trim($key)] = $value;
            }

            if ($airportData['N_S'] == 'S') {$airportData['decLatitude']  = -(abs($airportData['decLatitude']));}
            if ($airportData['E_W'] == 'W') {$airportData['decLongitude'] = -(abs($airportData['decLongitude']));}
         $airportData['decLatitude']  = $airportData['decLatitude']  / 3600.00;
         $airportData['decLongitude'] = $airportData['decLongitude'] / 3600.00;
         $airportData['name'] = ucwords(strtolower($airportData['name']));
         $airportData['city'] = ucwords(strtolower($airportData['city']));

         $query = "INSERT INTO apt SET \n";
            foreach($airportData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

         echo $airportData['name'] . "\t" . $airportData['city'] . "\n";
         }
      }
   $try = $db->query("UPDATE apt set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_apt_data_coord ON apt(coordinates)");

   }
}

/**
 * parseNavFile($file)
 *  Line-by-line, parses the flat-text NAV.txt file
 *  using array of strpos values
 *  Input: Path to NAV.txt
 */

function parseNavFile($file){
global $db;

$stringPositions = array(
    "id"               => array("start" => 4,   "length" => 4),
    "type"             => array("start" => 8,   "length" => 20),
    "status"           => array("start" => 766, "length" => 30),
    "public"           => array("start" => 280, "length" => 1),
    "class"            => array("start" => 281, "length" => 11),
    "frequency"        => array("start" => 533, "length" => 6),
    "power"            => array("start" => 491, "length" => 4),
    "city"             => array("start" => 72,  "length" => 40),
    "state"            => array("start" => 112, "length" => 30),
    "latitude"         => array("start" => 370, "length" => 13),
    "N_S"              => array("start" => 383, "length" => 1),
    "decLatitude"      => array("start" => 385, "length" => 10),
    "longitude"        => array("start" => 396,  "length" => 13),
    "E_W"              => array("start" => 409,  "length" => 1),
    "decLongitude"     => array("start" => 410,  "length" => 10),
    );

$query = "DELETE FROM nav";
$try = $db->query($query);
$try = $db->query("ALTER TABLE nav DROP INDEX ix_spatial_nav_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 0, 4));    // Type of record 
         if ($type == "NAV1"){
         $navData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $navData[trim($key)] = $value;
            }

            if ($navData['N_S'] == 'S') {$navData['decLatitude']  = -(abs($navData['decLatitude']));}
            if ($navData['E_W'] == 'W') {$navData['decLongitude'] = -(abs($navData['decLongitude']));}
         $navData['decLatitude']  = $navData['decLatitude']  / 3600.00;
         $navData['decLongitude'] = $navData['decLongitude'] / 3600.00;

         $query = "INSERT INTO nav SET \n";
            foreach($navData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

          }
       } // End of while ($line = fgets($fh)
   $try = $db->query("UPDATE nav set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_nav_data_coord ON nav(coordinates)");

     }

}



/**
 * parseFixFile($file)
 *  Line-by-line, parses the flat-text FIX.txt file
 *  using array of strpos values
 *  Input: Path to FIX.txt
 */

function parseFixFile($file){
global $db;

$stringPositions = array(
    "aixm_id"          => array("start" => 4,   "length" => 30),
    "id"               => array("start" => 228, "length" => 5),
    "state"            => array("start" => 29,  "length" => 30),	
    "category"         => array("start" => 94,  "length" => 3),
    "published"        => array("start" => 212, "length" => 1),
    "use"              => array("start" => 213, "length" => 15),
    "latitude"         => array("start" => 66, "length" => 12),
    "N_S"              => array("start" => 78, "length" => 1),
    "decLatitude"      => array("start" => 385, "length" => 10),
    "longitude"        => array("start" => 80, "length" => 13),
    "E_W"              => array("start" => 93, "length" => 1),
    "decLongitude"     => array("start" => 410, "length" => 10),
    );


$query = "DELETE FROM fix";
$try = $db->query($query);
$try = $db->query("ALTER TABLE fix DROP INDEX ix_spatial_fix_data_coord");

$fh = fopen($file, "r");
   if ($fh){
      while ($line = fgets($fh)){
      $type = trim(substr($line, 0, 4));    // Type of record
         if ($type == "FIX1"){
         $fixData = array();
            // Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $fixData[trim($key)] = $value;
            }
         $fixData['decLatitude']  = convertToDecimalDegrees($fixData['latitude'] . $fixData['N_S']);
         $fixData['decLongitude'] = convertToDecimalDegrees($fixData['longitude']. $fixData['E_W']);

         $query = "INSERT INTO fix SET \n";
            foreach($fixData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

          }
       } // End of while ($line = fgets($fh)
   $try = $db->query("UPDATE fix set coordinates=Point(decLongitude, decLatitude)");
   $try = $db->query("create spatial index ix_spatial_fix_data_coord ON fix(coordinates)");

     } else {
     echo "Cannot open FIX file $file\n";
     }
fclose($fh);
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
$apiUrl = "https://soa.smext.faa.gov/apra/nfdc/nasr/chart?edition=current";
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

$fh = fopen($includeFile, "w");
fwrite($fh, "<?php\n\$dataLastModified = \"$effDate\";\n");
fclose($fh);

}


/**
 * Get the date of the next edition and write to a file
 */
function writeNextEditionDate($nextFile)
{
$nextUrl = "https://soa.smext.faa.gov/apra/nfdc/nasr/info?edition=next";
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


