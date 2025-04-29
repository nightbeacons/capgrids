#!/usr/bin/php
<?php

/**
 * Download the current CIFP zipfile from the FAA
 * extract the FAACIFP18 file and move to
 * $targetDir/earth_424.dat
 */

$zipfileDir = "/tmp/cifp/";
$targetDir = "/var/www/capgrids/htdocs/x-plane/";
if (!is_dir($zipfileDir)) {
  mkdir($zipfileDir);
}
$faa_page = "https://www.faa.gov/air_traffic/flight_info/aeronav/digital_products/cifp/download/";

// Get the download URL to the CIFP zipfile.
$downloadURL = getDownloadUrl($faa_page);

$result = processZipfile($downloadURL, $zipfileDir, $targetDir);

/**
 * function processZipfile()
 * Download the zipfile
 * Extract FAACIFP18 from the downloaded zipfile
 * and move to $targetDir/earth_424.dat
 *   $url        = The URL to the current zipfile
 *   $zipfileDir = Where to store the downloaded zipfile
 *   $targetDir  = Where to store the extracted and renamed file
 */
function processZipfile($url, $zipfileDir, $targetDir) {
  $zipfile = $zipfileDir . "cifp.zip";
  $fh = fopen($zipfile, "w");
  $ch = curl_init();
  $globalCurlOptions = setCurlOptions($fh);
  curl_setopt_array($ch, $globalCurlOptions);
  curl_setopt($ch, CURLOPT_URL, $url);
  $result = curl_exec($ch);
  fclose($fh);

  $zip = new ZipArchive();
  $res = $zip->open($zipfile);
  if ($res === TRUE) {
    $zip->extractTo($zipfileDir, ['FAACIFP18']);
    $zip->close();
    rename($zipfileDir . "FAACIFP18", $targetDir . "earth_424.dat");
    $retval = 'ok';
  }
  else {
    $retval = 'failed';
  }

  return($retval);
}

/**
 * function getDownloadUrl()
 * Parse the FAA page for the current CIFP zipfile
 * Return the URL.
 */
function getDownloadUrl($url) {
  $start = "<cfoutput>";
  $end = "</cfoutput>";

  $ch = curl_init();
  $globalCurlOptions = setCurlOptions();
  curl_setopt_array($ch, $globalCurlOptions);
  curl_setopt($ch, CURLOPT_URL, $url);

  $result = curl_exec($ch);

  // Check for errors and display the error message.
  if ($errno = curl_errno($ch)) {
    $error_message = curl_strerror($errno);
    $errors['code'] = $errno;
    $errors['message'] = $error_message;
  }
  else {
    $errors['code'] = -1;
    $errors['message'] = "OK";
  }

  $result1 = preg_match_all("|$start(.*?)$end|s", $result, $matches);

  $start = '<a href="';
  $end = '">';
  $result1 = preg_match_all("|$start(.*?)$end|s", $matches[1][0], $matches2);
  $downloadUrl = trim($matches2[1][0]);

  return($downloadUrl);

}

/**
 * function setCurlOptions()
 *  Set commong curl options
 *  If called to download a file, pass the open file handler resource
 */
function setCurlOptions($download_file_handle = NULL) {
  $cookies = '/tmp/cookies.txt';
  $globalCurlOptions = [
    CURLOPT_BINARYTRANSFER    => TRUE,
    CURLOPT_CONNECTTIMEOUT    => 255,
    CURLOPT_COOKIEJAR         => $cookies,
    CURLOPT_COOKIEFILE        => $cookies,
    CURLOPT_FOLLOWLOCATION    => TRUE,
    CURLOPT_FORBID_REUSE      => TRUE,
    CURLOPT_FRESH_CONNECT     => TRUE,
    CURLOPT_POST              => FALSE,
    CURLOPT_HTTPGET           => TRUE,
    CURLOPT_RETURNTRANSFER    => TRUE,
    CURLOPT_USERAGENT         => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.1) Gecko/20100101 Firefox/17.0',
    CURLOPT_SSL_VERIFYPEER    => FALSE,
    CURLOPT_VERBOSE           => FALSE,
  ];
  if ($download_file_handle !== NULL) {
    $globalCurlOptions[CURLOPT_FILE] = $download_file_handle;
  }
  return($globalCurlOptions);
}
