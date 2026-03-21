#!/usr/bin/php
<?php

/**
 Load files as follows:
 /bin/dos2unix *.csv

   LOAD DATA INFILE '/var/www/capgrids/bin/getAirportData/AIXM_CSV_ORG/CSV_Data/ATC_ATIS.csv'
    REPLACE
     INTO TABLE ATC_ATIS  
     FIELDS TERMINATED BY ','
     ENCLOSED BY '"'
     LINES TERMINATED BY '\n'
     IGNORE 1 LINES;     
 */
$DEBUG=TRUE;

ini_set('auto_detect_line_endings', true);

// Location for the downloaded AIXM data
$workDir = "/tmp/aixm/";

require_once "/var/www/capgrids/pwf/aixm_csv.php";
//include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_apt.php";
//include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_fix.php";
//include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_nav.php";
//include_once "/var/www/capgrids/bin/getAirportData/aixm_includes/parse_ils.php";


$db = new mysqli($dbserver, $w_dbuser, $w_dbpass, $dbname);
if (mysqli_connect_errno()) {
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
}

// If the workDir does not exist, make it
if (!is_dir($workDir)) {
    mkdir($workDir);
}

// Check to see if it's time to download and process a new zipfile
// If so, download and extract it.
$result = doWeNeedUpdatedZipfile($workDir, $DEBUG);

if ($result['status'] != "skipped") {
    // DB table names are lc versions of filenames
    //  Exception: the db 'rwy' is derived from the APT.txt file
    $files_to_process = array('APT', 'NAV', 'FIX', 'ILS');
    $files_to_process = file(getcwd() . "/includes/list_of_csv_files.txt");
    print_r($files_to_process);

    $oneFile="FIX_BASE.csv";
    $status = processCsvFile($oneFile);
    echo "STATUS=$status\n";

}


die;


mysqli_close($db);




/**
 * convertToDecimalDegrees($dash_formatted)
 *   Input: Latitude or Longitude string of the form 088-22-10.820W
 *   Return: Decimal latitude or longitude.
 *         W longitude and S latitude are negative vals
 */
function convertToDecimalDegrees($dash_formatted)
{
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
function downloadLatestZipfile($workDir, $DEBUG)
{
    //$landingPageUrl="https://nfdc.faa.gov/xwiki/bin/view/NFDC/28+Day+NASR+Subscription";
    //$landingPageUrl="https://www.faa.gov/air_traffic/flight_info/aeronav/aero_data/NASR_Subscription/";
    $apiUrl = "https://external-api.faa.gov/apra/nfdc/nasr/chart?edition=current";
    echo "Fetching date of next relese . . . \n";

    $zipFileName = "aixmData.zip";
    $retval = 0;
    $errors = "";
    $result = fetchUrl($apiUrl);
    if (strpos($result['error'], "client certificate not found") > 1) {
        $result['error']="";
    }
    if (strlen($result['error']) < 1) {
        $info = json_decode($result['result']);
         print_r($info);

        $editionDate = $info->edition[0]->editionDate; 
        $zipFileUrl = $info->edition[0]->product->url;
        $zipFileDest = $workDir . $zipFileName;
        echo "Fetching $zipFileUrl\n";

        $zipFileGet = fetchUrl($zipFileUrl, $zipFileDest);
        $retval=$zipFileGet['result'] + 0;
        if (strlen($zipFileGet['error']) > 0) {
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

    if ($retval > 0) {
        echo "Unzipping $zipFileDest to $workDir \n";
        $unpack = "/usr/bin/unzip -o $zipFileDest -d $workDir && /bin/rm $zipFileDest";
        $cmdResult = `$unpack`;
        echo "Result of unzip is $cmdResult\n";
        $CSV_dir = "/tmp/aixm/CSV_Data/";
        $CSVfile = $CSV_dir . (array_values(array_diff(scandir($CSV_dir), array('..', '.'))))[0];
        echo "Unzipping $CSVfile\n";
        $result['csvdir'] = $CSV_dir;
        $unpack1 = "/usr/bin/unzip -o $CSVfile -d $CSV_dir";
        $cmdResult = `$unpack1`;
        echo "Fixing line endings of CSV files\n";
        $fixLE = "/bin/dos2unix -q " . $CSV_dir . "*.csv";
        $cmdResult = `$fixLE`;
        echo "Result of unzip is $cmdResult\n";
           if (!$DEBUG){
           $removeZips = "/bin/rm -f $CSV_dir/*.zip";
           $cmdResult = `$removeZips`;
           }
    }

    return($result);
}


/**
 * Fetch URL
 */
function fetchUrl($url, $downloadFile="")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36");
    if (strlen($downloadFile) > 1) {
        $fp = fopen($downloadFile, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
    } else {
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json'
            )
        );
    }

    $result = curl_exec($ch);
    $curlError = trim(curl_error($ch));
    curl_close($ch);

    $retval = array(
                'result' => $result,
                'error'  => $curlError
          );
    if (strlen($downloadFile) > 1) {
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
    $currentCycle = trim(date('ym', strtotime($effDate)));

    $fh = fopen($includeFile, "w");
    fwrite($fh, "<?php\n\$dataLastModified = \"$effDate\";\n\$airportDataEtag = \"$etag\";\n\$currentCycle = \"$currentCycle\";\n");
    fclose($fh);
}


/**
 * Get the date of the next edition and write to a file
 */
function writeNextEditionDate($nextFile)
{
    $nextUrl = "https://external-api.faa.gov/apra/nfdc/nasr/info?edition=next";
    $result = fetchUrl($nextUrl);
    if (strpos($result['error'], "client certificate not found") > 1) {
        $result['error']="";
    }
    if (strlen($result['error']) < 1) {
        $info = json_decode($result['result']);
        //      print_r($info);

        $editionDateObj = date_create_from_format('m/d/Y', $info->edition[0]->editionDate);
        $editionDate = date_format($editionDateObj, 'Y-m-d');
    }
    file_put_contents($nextFile, $editionDate);
    //echo "NEXT IS |" . $editionDate . "|\n\n";
}

/**
 * Check to see if we need to download a new zipfile
 *  If so, download and return TRUE
 *  Otherwise, skip and return FALSE
 */
function doWeNeedUpdatedZipfile($workDir, $DEBUG)
{
    $result = array(
               "status" => "skipped",
               );
    $nextFile = $workDir . "nextEdition.txt";
    $nextDate = file_get_contents($nextFile);
    $today = date('Y-m-d');

    // If the "Next Edition Date" is today
    //  (or in the past) or if DEBUG==TRUE
    // then fetch the new files
    if (($today >= $nextDate) OR (!file_exists($nextFile)) OR $DEBUG) {

        $result = downloadLatestZipfile($workDir, $DEBUG);
        writeIncludeFile();
        writeNextEditionDate($nextFile);
    }
    return($result);
}

function processCsvFile($oneFile)
{
    $filespec = getcwd() . "/includes/" . $oneFile;
    $parts = pathinfo($oneFile);
    $db_table = $parts['filename'];
    echo "$db_table\n";

}

