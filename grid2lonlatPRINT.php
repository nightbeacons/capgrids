<?php
################################################################
#
# Find Grid Corners
#
################################################################

include_once("includes/gridFunctions.php");

$onload="";
        if (isset($_GET['id'])) {$sectional=$_GET['id'];} else {$sectional="SEATTLE";}
        if (isset($_GET['mygrid'])) {$selectedGrid=$_GET['mygrid'];} else {$selectedGrid=$coordinates[$sectional]['startGrid'];}
        if (isset($_GET['myquadrant'])) {$selectedQuadrant=strtoupper($_GET['myquadrant']);} else {$selectedQuadrant="E";}
        if (isset($_GET['myformat'])) {$selectedFormat=$_GET['myformat'];} else {$selectedFormat="dmm";}
	if (isset($_GET['id'])) {
		$onload="onload=\"javascript:onloadHandler();\"";
	}

$abbrev=$coordinates[$sectional]['Abbrev'];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
<title><?php echo "$abbrev $selectedGrid"; ?> </title>

  <style type="text/css">
    <!--

.coord {
	font-family: Arial, Helvetica;
	font-size: 10pt;
	}


@media print {
div.printonly {
	display: block;
	visibility: hidden; 
	}

div.screenonly {
	margin:0;
	padding:0;
	display: block;
	visibility:visible;
	}
}

@media screen {
div.printonly {
	margin:0;
	padding:0;
	display: block;
	visibility: visible;
	}

div.screenonly {
	display: none;
	}
}

    -->
  </style>

<script language="JavaScript" type="text/javascript">
  function reloadHandler() {
        var sectionalCode = document.findCorners.sectionalCorners.options[document.findCorners.sectionalCorners.selectedIndex].value;
	var gridCode = document.findCorners.selectGrid.options[document.findCorners.selectGrid.selectedIndex].value;
//	var gridCode = document.findCorners.selectGrid.value;

        var quadCode = document.findCorners.selectQuadrant.options[document.findCorners.selectQuadrant.selectedIndex].value;
        var formatCode = document.findCorners.selectFormat.options[document.findCorners.selectFormat.selectedIndex].value;

        window.location='grid2lonlat.php?id=' + sectionalCode + '&mygrid=' + gridCode + '&myquadrant=' + quadCode + '&myformat=' + formatCode;

  }

  function onloadHandler() {
	<?php echo "parent.setGridWindow('" . $sectional . "', '" . $selectedGrid . "', '" . $selectedQuadrant . "');"; ?>
  }

  function pageReady(){
       setTimeout(function(){window.status="ready"},8500);
  }
  </script>

</head>
<?php
#echo "<body $onload>
echo "<body onload=\"javascript:pageReady();\">
<div class=\"screenonly\">
<form name=\"findCorners\">
<table width=\"400\" border=\"0\" align=\"center\" style=\"margin-top:0;\"><tr valign=\"top\"><td>
<select name=\"sectionalCorners\" onChange=\"javascript:document.findCorners.selectGrid.value=100;javascript:reloadHandler();\" style=\"border-color:black;border-width:1px;border-color:#303030;\" TITLE=\" Select Sectional \">\n";
drawSectionalOptions($sectional);
echo "</select>
</td><td>
<select name=\"selectGrid\" onChange=\"javascript:reloadHandler();\" style=\"border-color:black;border-width:1px;border-color:#303030;\" TITLE=\" Select Grid # \">\n";
drawGridOptions($sectional, $selectedGrid);
echo "</select></td><td>
<select name=\"selectQuadrant\" onChange=\"javascript:reloadHandler();\" style=\"border-color:black;border-width:1px;border-color:#303030;\" TITLE=\" Grid Quadrant \">\n";

$quadAry = array("E","A","B","C","D");
	foreach ($quadAry as $item){
	$selected="";
	if ($item == $selectedQuadrant) $selected=" SELECTED ";
		if ($item == "E") {
		echo "	<option value=\"E\" $selected>Entire Grid</option>\n";
		} else {
		echo "	<option value=\"$item\" $selected>$item</option>\n";
		}
	}
echo "</select></td><td>
<select name=\"selectFormat\" onChange=\"javascript:reloadHandler();\" style=\"border-color:black;border-width:1px;border-color:#303030;\" TITLE=\" Display Format \" >\n";

echo "  <option value=\"dmm\" ";
	if ($selectedFormat == "dmm") echo "SELECTED ";
echo ">DD mm.mmm</option>";

echo "	<option value=\"dms\" ";
        if ($selectedFormat == "dms") echo "SELECTED ";
echo ">DD mm ss</option>";

echo "</select></td>
</tr>
</table></form>\n";
$displayQuadrant= " - " . $selectedQuadrant;
if ($selectedQuadrant == "E") $displayQuadrant="";

$cellWidth=100;

$result = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);
$resultRaw = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, "raw");

$avgLon=($resultRaw['NW']['lon'] + $result['NE']['lon'])/2;
$avgLat=($resultRaw['NW']['lat'] + $result['SW']['lat'])/2;
$variation =  magVariation($avgLat, $avgLon);
$varDir="W";
	if ($variation < 0) {
	$varDir="E";
	$variation=abs($variation);
	}
echo "<br><table border=\"1\" cellspacing=\"0\" cellpadding=\"10\" width=\"375\" align=\"center\" style=\"border-width:10px;border-style:solid;border-color:#c0c0c0;\"><tr><td><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" align=\"center\">
<tr><td width=\"$cellWidth\" valign=\"bottom\" align=\"right\" class=\"coord\">" . $result['NW']['lat'] . "<br>" . $result['NW']['lon'] . "</td><td width=\"($cellWidth + 75)\">&nbsp;</td><td valign=\"bottom\" align=\"left\" width=\"$cellWidth\" class=\"coord\">" . $result['NE']['lat'] . "<br>" . $result['NE']['lon'] . "</td></tr>
<tr><td>&nbsp;</td><td align=\"center\" valign=\"middle\" style=\"border-width:2px;border-style:solid;width:" . $cellWidth . "px;height:80px;background-color:#f0f0f0;\">" . $coordinates[$sectional]['Abbrev'] . "<br>$selectedGrid $displayQuadrant<br><img src=\"/images/spacer.gif\" style=\"width:" . $cellWidth . "px;height:1px;\"></td><td width=\"$cellWidth\">&nbsp;</td></tr>
<tr><td valign=\"top\" align=\"right\" class=\"coord\">" . $result['SW']['lat'] . "<br>" . $result['SW']['lon'] . "</td><td align=\"center\" valign=\"top\" class=\"coord\"><nobr><small><i>Mag Variation:</i></small></nobr><br><nobr><i>$variation&deg; $varDir</i></nobr></td><td valign=\"top\" align=\"left\" class=\"coord\">" . $result['SE']['lat'] . "<br>" . $result['SE']['lon'] . "</td></tr>
</table></td></tr></table></div>\n";
?>

<div class="printonly">
<table border="0" cellspacing="0" cellpadding="5" width="650" align="center">
<tr valign="bottom"><td align="left" class="coord" width="162"><?php echo $result['NW']['lat'] . "<br>" . $result['NW']['lon']; ?></td><td align="center" width="50%"><b><?php echo $coordinates[$sectional]['Abbrev'] . "&nbsp;" .  $selectedGrid . $displayQuadrant; ?></b></td><td align="right" class="coord" width="162"><?php echo $result['NE']['lat'] . "<br>" . $result['NE']['lon']; ?></td></tr>
<tr valign="top"><td align="center" colspan="3"><?php
$kmlURL = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&embed=1";

$kmlURLencoded = rawurlencode($kmlURL);
$zoom=12;
if ($selectedQuadrant=="E") $zoom=11;
$iframeSrc = "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=" . $zoom . "&amp;output=embed";
$iframeHref = "http://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=10";

echo "<iframe width=\"645\" height=\"680\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $iframeSrc . "\"></iframe>";
?>

</td></tr>
<tr valign="top"><td align="left" class="coord" width="162"><?php echo $result['SW']['lat'] . "<br>" . $result['SW']['lon']; ?></td>
<td align="center" class="coord"><i>Avg Mag Variation:</i></nobr><br><nobr><i><?php echo "$variation&deg; $varDir"; ?></i></td>
<td align="right" class="coord" width="162"><?php echo $result['SE']['lat'] . "<br>" . $result['SE']['lon']; ?></td></tr>
</table>

</div>
</body>
</html>
