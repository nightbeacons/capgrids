<?php
//$msg = "<pre>" . print_r($_POST, TRUE) . "</pre>";
//echo $msg;
$show_errors=0;

   if ($show_errors){
   $conf['error_level'] = 2;  // Show all messages on your screen, 2 = ERROR_REPORTING_DISPLAY_ALL.
   error_reporting(E_ALL);
   ini_set('display_startup_errors', TRUE);
   ini_set('display_errors',1);
   } else {
   error_reporting(0);  // Have PHP complain about absolutely everything
   //$conf['error_level'] = 2;  // Show all messages on your screen, 2 = ERROR_REPORTING_DISPLAY_ALL.
   ini_set('display_errors', FALSE);  // These lines just give you content on WSOD pages.
   ini_set('display_startup_errors', FALSE);
   }
?>
<?php include_once "kml_converter.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-us">
<title>CAPgrids | Civil Air Patrol Emergency Services</title>
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//www.google-analytics.com">

<link rel="stylesheet" type="text/css" href="/css/style.css">
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet"> 
<meta name="description" 
	content="Search-and-Rescue grid tool for Civil Air Patrol Emergency Services teams. The Swiss Army Knife of Search Grids, it calculates grid corners and grid identifiers for any US Sectional chart. It also provides adjustable maps, downloadable gridded sectionals, Google Earth overlays with current sectionals, and large map printouts.">
<meta name="keywords" 
	content="CAP grid, SAR grid, gridded sectional, search grids, search and rescue grids, Google Earth overlay, G1000 Flight Plan, G695 Flight Plan, FPL, Civil Air Patrol">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />


</head>
<body style="margin:0;">
<?php include_once("../includes/fb.php") ?>
<?php include_once("../includes/ga.php") ?>

<table dir="ltr" border="0" cellpadding="0" cellspacing="0" width="100%" style="position:relative;">
<tr><td class="pageBG pageBGleft" style="width:auto;" rowspan="3"><td style="width:850px;"><a href="/"><img style="width:850;height:201; border-style:none; margin:0;" height="201" border="0" src="/images/banner.jpg"></a><div class="bannerOverlay"><h1 class="overlay">CAPgrids</h1><h3 class="overlay">The Swiss-Army Knife of Search Grids<h3></div><hr></td><td class="pageBG pageBGright" style="width:auto;" rowspan="3"></tr>

<tr>
<td valign="top">

	<!-- ======================= -->
	<!-- Begin Main Content Area -->
	<!-- ======================= -->

<table border="0" cellpadding="0" cellspacing="0"  align="left" id="table1" width="850" style="margin-left: 0;">
	<tr>
		<td align="left" valign="top" width="500">
<h2 class="main">CAPgrids: ForeFlight KML Converter</h2>
<p>Converts a FlightAware track log into a ForeFlight-compatible CSV file.</p>
<?php
$SELF= $_SERVER['PHP_SELF'];
// Keep generated CSVs for at least two days
$seconds_to_keep = 2 * 24 * 60 * 60;
$output_dir = preg_replace("|(.*)/.*|", '${1}/csv/', $_SERVER['SCRIPT_FILENAME']);
echo "<form name=\"kmlg1000\" method=\"POST\" ENCTYPE=\"multipart/form-data\" ACTION=\"$SELF\">
<input type=\"text\" name=\"trackurl\" placeholder=\"URL to a ForeFlight Track Log page\" size=\"55\">
<p>Paste the URL to a ForeFlight track log page, then click <b>Convert</b></p>
<input type=\"submit\" value=\"Convert\" style=\"margin-top:0.5rem;margin-left:25%;text-align:center;background-color:aqua;\"></form>";

 if (isset($_POST['trackurl'])){

   $FA_tracklog_url = trim($_POST['trackurl']);
   $kml_url = str_replace("/tracklog", "/google_earth", $FA_tracklog_url);
   $fa_result = fetchUrl($kml_url);
   $kml = $fa_result['result'];
   $record_ary = buildFromKml($kml);
   $csv = buildCSV($record_ary);
   $output_filename = $record_ary['tailNumber'] . "-" . $record_ary['result'][0]['date'] . ":" . $record_ary['result'][0]['time'] . ".csv"; 
   $output_path_plus_file = $output_dir . $output_filename; 
     if (strlen($output_filename) > 7){
       $fh = fopen($output_path_plus_file, 'w');
       fwrite($fh, $csv);
       fclose($fh);
       echo "<p>Download <a href=\"csv/$output_filename\">$output_filename</a></p>";
     } else {
       echo "<p><i>Please check the FlightAware tracklog URL and try again.</i></p>";
     }
   $csv_files = glob($output_dir . "*.csv");
     foreach($csv_files as $myfile){
       if (time() - filectime($myfile) > $seconds_to_keep){
          unlink($myfile);
       }
     }
}
?>


</td><td valign="top" width="240" bgcolor="#fef0f0">
<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/help/kmlconverter.php";
?>
</td>
	</tr>

</table>
	<!-- ======================= -->
	<!--  End Main Content Area  -->
	<!-- ======================= -->
</td></tr>
<tr><td><?php include_once $_SERVER['DOCUMENT_ROOT'] . "/includes/footer.php"; ?></td></tr></table>
</body>
</html>
