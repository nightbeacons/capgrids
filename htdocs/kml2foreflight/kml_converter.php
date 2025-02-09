<?php

/**
 *
 */
function buildFromKml($kml) {
  $doc = new DOMDocument();
  $doc->loadXML($kml, LIBXML_PARSEHUGE);
  $coordinates = [];

  $tailNumber  = trim($doc->getElementsByTagName('name')->item(3)->nodeValue);
  $to_from     = $doc->getElementsByTagName('description')->item(2)->nodeValue;
  $latlonStart = explode(",", trim($doc->getElementsByTagName('Point')->item(0)->nodeValue));
  $latlonEnd   = explode(",", trim($doc->getElementsByTagName('Point')->item(1)->nodeValue));

  $record[0]['lat'] = $latlonStart[1];
  $record[0]['lon'] = $latlonStart[0];
  $record[0]['alt'] = 0;
  $record[0]['speed'] = 0;

  $i = 1;
  foreach ($doc->getElementsByTagName('when') as $coord) {
    $record[$i]['when'] = $coord->nodeValue;
    $mydate = explode("T", $record[$i]['when']);
    $record[$i]['date'] = trim($mydate[0]);
    $record[$i]['time'] = trim($mydate[1], " Z");
    $record[$i]['ts'] = strtotime($record[$i]['when']);
    $i++;
  }
  $record[0]['ts'] = $record[1]['ts'] - 20;

  $i = 1;
  foreach ($doc->getElementsByTagName("coord") as $coord) {
    $pos = explode(" ", $coord->nodeValue);
    $record[$i]['lon'] = $pos[0];
    $record[$i]['lat'] = $pos[1];
    // Convert Altitude to feeet.
    $record[$i]['alt'] = $pos[2] * 3.28084;
    $i++;
  }

  $previous = 0;
  $firstSpeedRecorded = $firstHeadingRecorded = 0;
  for ($i = 0; $i < count($record); $i++) {
    $dist = getDistanceFromLatLonInNm($record[$previous]['lat'], $record[$previous]['lon'], $record[$i]['lat'], $record[$i]['lon']);
    $heading = floor(bearing($record[$previous]['lat'], $record[$previous]['lon'], $record[$i]['lat'], $record[$i]['lon']));
    if ($dist == 0) {
      $speed = "undefined";
      $heading = "undefined";
    }
    else {
      $speed = floor($dist / (($record[$i]['ts'] - $record[$previous]['ts']) / 3600));
      if ($firstSpeedRecorded == 0) {
        $firstSpeedRecorded = $speed;
        $firstHeadingRecorded = $heading;
      }
    }

    $previous = $i;
    $record[$i]['heading'] = $heading;
    $record[$i]['speed'] = $speed;
  }

  for ($j = 0; $j < 10; $j++) {
    if ($record[$j]['speed'] == "undefined") {
      $record[$j]['speed'] = $firstSpeedRecorded;
    }
    if ($record[$j]['heading'] == "undefined") {
      $record[$j]['heading'] = $record[1]['heading'];
    }
  }

  $record[0]['speed'] = 0;
  $record[0]['date'] = $record[1]['date'];
  $record[0]['time'] = $record[1]['time'];
  $record[0]['ts'] = $record[1]['ts'];
  $record[0]['heading'] = $record[1]['heading'];

  $numRecords = count($record);

  $record[$numRecords]['lat']     = $latlonEnd[1];
  $record[$numRecords]['lon']     = $latlonEnd[0];
  $record[$numRecords]['alt']     = 0;
  $record[$numRecords]['speed']   = 0;
  $record[$numRecords]['date']    = $record[$numRecords - 1]['date'];
  $record[$numRecords]['time']    = $record[$numRecords - 1]['time'];
  $record[$numRecords]['ts']      = $record[$numRecords - 1]['ts'];
  $record[$numRecords]['heading'] = $record[$numRecords - 1]['heading'];

  $myvals = [
    'result' => $record,
    'tailNumber' => $tailNumber,
  ];

  return($myvals);
}

/**
 * Print_r($record);.
 */
function getDistanceFromLatLonInNm($lat1, $lon1, $lat2, $lon2) {
  // Radius of the earth in km.
  $R = 6378.137;
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
  // Distance in km.
  $d = $R * $c;
  // Distance in nm.
  return ($d / 1.8520000016);
}

/**
 *
 */
function bearing($startLat, $startLng, $destLat, $destLng) {
  $startLat = deg2rad($startLat);
  $startLng = deg2rad($startLng);
  $destLat = deg2rad($destLat);
  $destLng = deg2rad($destLng);

  $y = sin($destLng - $startLng) * cos($destLat);
  $x = cos($startLat) * sin($destLat) -
        sin($startLat) * cos($destLat) * cos($destLng - $startLng);
  $brng = atan2($y, $x);
  $brng = (rad2deg($brng) + 360) % 360;
  return ($brng);
}

/**
 *
 */
function buildCSV($record_ary) {
  $record     = $record_ary['result'];
  $tailNumber = $record_ary['tailNumber'];
  $acType     = getAcType($tailNumber);
  $zone       = "-00:00";
  // Garmin G1000 Tracklog header @see: https://www.reddit.com/r/flying/comments/6jgntl/find_garmin_g1000_sample_csv_file/
  $header = "#airframe_info, log_version=\"1.00\", airframe_name=\"$acType\", unit_software_part_number=\"000-A0000-0A\", unit_software_version=\"9.00\", system_software_part_number=\"000-A0000-00\", system_id=\"$tailNumber\", mode=NORMAL,\n#yyy-mm-dd, hh:mm:ss,   hh:mm,  ident,      degrees,      degrees, ft Baro,  inch,  ft msl, deg C,     kt,     kt,     fpm,    deg,    deg,      G,      G,   deg,   deg, volts,   gals,   gals,      gph,      psi,   deg F,     psi,     Hg,    rpm,   deg F,   deg F,   deg F,   deg F,   deg F,   deg F,   deg F,   deg F,  ft wgs,  kt, enum,    deg,    MHz,    MHz,     MHz,     MHz,    fsd,    fsd,     kt,   deg,     nm,    deg,    deg,   bool,  enum,   enum,   deg,   deg,   fpm,   enum,   mt,    mt,     mt,    mt,     mt\n  Lcl Date, Lcl Time, UTCOfst, AtvWpt,     Latitude,    Longitude,    AltB, BaroA,  AltMSL,   OAT,    IAS, GndSpd,    VSpd,  Pitch,   Roll,  LatAc, NormAc,   HDG,   TRK, volt1,  FQtyL,  FQtyR, E1 FFlow, E1 FPres, E1 OilT, E1 OilP, E1 MAP, E1 RPM, E1 CHT1, E1 CHT2, E1 CHT3, E1 CHT4, E1 EGT1, E1 EGT2, E1 EGT3, E1 EGT4,  AltGPS, TAS, HSIS,    CRS,   NAV1,   NAV2,    COM1,    COM2,   HCDI,   VCDI, WndSpd, WndDr, WptDst, WptBrg, MagVar, AfcsOn, RollM, PitchM, RollC, PichC, VSpdG, GPSfix,  HAL,   VAL, HPLwas, HPLfd, VPLwas\n";

  $csv = $header;

  foreach ($record as $row) {
    $csv .= str_pad($row['date'], 10, ' ', STR_PAD_LEFT) . ",";
    $csv .= str_pad($row['time'], 9, ' ', STR_PAD_LEFT) . ",";
    $csv .= str_pad($zone, 8, ' ', STR_PAD_LEFT) . ",       ,";
    $csv .= str_pad($row['lat'], 13, ' ', STR_PAD_RIGHT) . ",";
    $csv .= str_pad($row['lon'], 13, ' ', STR_PAD_RIGHT) . ",        ,      ,";
    $csv .= str_pad($row['alt'], 8, ' ', STR_PAD_LEFT) . ",      ,       ,";
    $csv .= str_pad($row['speed'], 7, ' ', STR_PAD_LEFT) . ",        ,";
    $csv .= "       0,       0,       0";
    $csv .= ",       ,        ,     ,      ,      ,       ,       ,         ,         ,        ,        ,       ,       ,        ,        ,        ,        ,        ,        ,        ,        ,        ,    ,     ,";
    $csv .= str_pad($row['heading'], 7, ' ', STR_PAD_LEFT) . "\n";
  }

  return($csv);
}

/**
 * Fetch URL.
 */
function fetchUrl($url, $downloadFile = "") {
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
  if (strlen($downloadFile) > 1) {
    $fp = fopen($downloadFile, 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    // } else {
    //   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //    'accept: application/json'
    //    ));
  }

  $result = curl_exec($ch);
  $curlError = trim(curl_error($ch));
  curl_close($ch);

  $retval = [
    'result' => $result,
    'error'  => $curlError,
  ];
  if (strlen($downloadFile) > 1) {
    fclose($fp);
  }
  return($retval);
}

/**
 *
 */
function getAcType($tailNumber) {
  $url = "https://www.flightaware.com/resources/registration/" . strtoupper($tailNumber);
  $page_raw = fetchUrl($url);
  $html = $page_raw['result'];
  $snippet = trim(preg_replace("|.*?<legend>Aircraft Summary</legend>(.*?)Airworthiness.*|s", '${1}', $html));
  $name = trim(preg_replace("|.*?<div class=\"medium-3 columns\">(.*?)<br/>.*|s", '${1}', $snippet));
  return($name);
}
