<?php
include_once("includes/gridFunctions.php");
$SELF= $_SERVER['PHP_SELF'];

if (isset($_GET['myformat'])) {$selectedFormat=$_GET['myformat'];} else {$selectedFormat="dmm";}
if (isset($_GET['latDegDMM'])) {$latDegDMM =(int)($_GET['latDegDMM']);} else {$latDegDMM="";}
if (isset($_GET['latMinDMM'])) {$latMinDMM =(float)($_GET['latMinDMM']);} else {$latMinDMM="";}
if (isset($_GET['lonDegDMM'])) {$lonDegDMM=(int)(abs($_GET['lonDegDMM']));} else {$lonDegDMM="";}
if (isset($_GET['lonMinDMM'])) {$lonMinDMM=(float)($_GET['lonMinDMM']);} else {$lonMinDMM="";}
if (isset($_GET['latDegDMS'])) {$latDegDMS=(int)($_GET['latDegDMS']);} else {$latDegDMS="";}
if (isset($_GET['latMinDMS'])) {$latMinDMS=(int)($_GET['latMinDMS']);} else {$latMinDMS="";}
if (isset($_GET['latSecDMS'])) {$latSecDMS=(float)($_GET['latSecDMS']);} else {$latSecDMS="";}if (isset($_GET['lonDegDMS'])) {$lonDegDMS=(int)(abs($_GET['lonDegDMS']));} else {$lonDegDMS="";}
if (isset($_GET['lonMinDMS'])) {$lonMinDMS=(int)($_GET['lonMinDMS']);} else {$lonMinDMS="";}
if (isset($_GET['lonSecDMS'])) {$lonSecDMS=(float)($_GET['lonSecDMS']);} else {$lonSecDMS="";}
if (isset($_GET['submit']))    {$submit=trim($_GET['submit']);} else {$submit="";}

$output=$onload="";
$rawLat=$rawLon=0;

if ($submit == "Go") {

$output = "<div align=\"center\" class=\"coord\"><b>Not Found</b></div>";
$onload="";

   switch ($selectedFormat) {
        case "dmm":
                $rawLat = $latDegDMM +  $latMinDMM/60;
                $rawLon = -($lonDegDMM +  $lonMinDMM/60);
                break;

        case "dms":
                $rawLat = $latDegDMS + $latMinDMS/60 + $latSecDMS/3600;
                $rawLon = -($lonDegDMS + $lonMinDMS/60 + $lonSecDMS/3600);
                break;
   }


$result = lonlat2grid($rawLon, $rawLat);

if (isset($result['quadrant'])) {$quadrant=$result['quadrant'];} else {$quadrant=-1;}

if ($quadrant > -1) {

$sectional=$result['sectional'];
$abbrev=$coordinates[$sectional]['Abbrev'];
$displaySectional = ucwords(strtolower(preg_replace("/_/"," ", $sectional)));
$grid=$result['grid'];
$quadrant=$result['quadrant'];

$output= "<div align=\"center\" class=\"coord\"><b>$displaySectional ($abbrev) &nbsp; &nbsp; $grid - $quadrant</b></div>";
$urlSrc="id=" . $sectional . "&mygrid=" . $grid . "&myquadrant=" . $quadrant . "&myformat=" . $selectedFormat;
$onload="onLoad=\"javascript:parent.setCornerUrl('" . $urlSrc . "');\"";
}
}
$googleMapUrl="http://maps.google.com/maps?f=q&hl=en&geocode=&q=$rawLat,$rawLon&ie=UTF8&t=h&z=8&iwloc=addr";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>

  <style type="text/css">
    <!--

.coord {
	font-family: Arial, Helvetica;
	font-size: 10pt;
	}

    -->
  </style>


<script language="JavaScript" type="text/javascript">
  function swapTable(ptr) {
  var dms=document.getElementById('dmsTable');
  var dmm=document.getElementById('dmmTable');
        if (ptr == 'dmm') {
	dms.style.display = 'none';
	dmm.style.display = 'block';
	} else {
        dms.style.display = 'block';
        dmm.style.display = 'none';
	}

  }
  </script>

</head>

<?php
echo "<body $onload>\n";
$dmmChecked = $dmsChecked = "";
if ($selectedFormat == "dmm") {
$dmmDisplay="block";
$dmsDisplay="none";
$dmmChecked=" CHECKED ";
} else {
$dmmDisplay="none";
$dmsDisplay="block";
$dmsChecked=" CHECKED ";
}

# Draw the title and the radio buttons

echo "<form name=\"findGrid\" method=\"GET\" ENCTYPE=\"multipart/form-data\" ACTION=\"$SELF\"><table border=\"0\" cellpadding=\"0\" width=\"360\" style=\"background-color:#e0e0e0;\" align=\"center\"><tr class=\"coord\"><th colspan=\"2\">Select format for Latitude and Longitude</th></tr>
<tr class=\"coord\">
<td width=\"180\" align=\"center\" TITLE=\" Degrees and Decimal Minutes \"><input type =\"radio\" name=\"myformat\" value=\"dmm\" onClick=\"swapTable(this.value);\" $dmmChecked> DD MM.mm </td>
<td width=\"180\" align=\"center\" TITLE=\" Degrees, Minutes, Seconds \"><input type =\"radio\" name=\"myformat\" value=\"dms\" onClick=\"swapTable(this.value);\" $dmsChecked> DD MM SS.ss </td>
</tr></table><br>\n";

# Draw the two DIVs. (Only one will be visible)

# DIV for Degree + Decimal Minutes
  
echo "<div name=\"dmmTable\" id=\"dmmTable\" style=\"display:$dmmDisplay;\"><table width=\"400\" cellspacing=\"0\" cellpadding=\"4\" border=\"1\" align=\"center\"><tr valign=\"top\"><td align=\"center\" class=\"coord\" style=\"border-right-style:none;\">
<nobr><b>Lat:</b> <input type=\"text\" name=\"latDegDMM\" size=\"3\" value=\"$latDegDMM\">&nbsp;&nbsp;<input type=\"text\" name=\"latMinDMM\" size=\"8\" value=\"$latMinDMM\"> N</nobr><br>
<small><i>(Example: 44  &nbsp;  23.09)&nbsp; &nbsp;</i></small>&nbsp; </td><td align=\"center\" class=\"coord\" style=\"border-left-style:none;\"><nobr><b>Lon:</b> <input type=\"text\" name=\"lonDegDMM\" size=\"4\" value=\"$lonDegDMM\">&nbsp; &nbsp;<input type=\"text\" name=\"lonMinDMM\" size=\"8\" value=\"$lonMinDMM\"> W</nobr><br>
<small><i>(Example: 120  &nbsp;  14.09)&nbsp; &nbsp;</i></small>&nbsp; </td></tr></table></div>";

# DIV for Degree + Minute + Seconds

echo "<div name=\"dmsTable\" id=\"dmsTable\" style=\"display:$dmsDisplay;\"><table width=\"400\" cellspacing=\"0\" cellpadding=\"4\" border=\"1\" align=\"center\"><tr valign=\"top\"><td align=\"center\" class=\"coord\" style=\"border-right-style:none;\"><nobr><b>Lat:</b> <input type=\"text\" name=\"latDegDMS\" size=\"3\" value=\"$latDegDMS\">&nbsp;&nbsp;<input type=\"text\" name=\"latMinDMS\" size=\"2\" value=\"$latMinDMS\">&nbsp;&nbsp;<input type=\"text\" name=\"latSecDMS\" size=\"4\" value=\"$latSecDMS\"> N</nobr><br>
<small><i>(Example: 44  &nbsp;  23 &nbsp; &nbsp; &nbsp; 12.09)</i></small>&nbsp; </td><td align=\"center\" class=\"coord\" style=\"border-left-style:none;\"><nobr><b>Lon:</b> <input type=\"text\" name=\"lonDegDMS\" size=\"4\" value=\"$lonDegDMS\">&nbsp; &nbsp;<input type=\"text\" name=\"lonMinDMS\" size=\"2\" value=\"$lonMinDMS\">&nbsp; &nbsp;<input type=\"text\" name=\"lonSecDMS\" size=\"3\" value=\"$lonSecDMS\"> W</nobr><br><small><i>(Example: 120  &nbsp;  14 &nbsp; 14.29)&nbsp; &nbsp;</i></small>&nbsp; </td></tr></table></div>";

echo "<div align=\"center\" style=\"margin-top:10px;\"><input type=\"submit\" name=\"submit\" value=\" Go \" ></div>";
echo "</form>";

# End of input form


echo $output;

?>
</body>
</html>
