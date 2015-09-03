<?php
################################################################
#
# Find Grid Corners
#
################################################################
error_reporting(0);
include_once("includes/gridFunctions.php");

global $mapType;

$onload="";
        if (isset($_GET['id'])) {$sectional=$_GET['id'];} else {$sectional="SEATTLE";}
        if (isset($_GET['mygrid'])) {$selectedGrid=$_GET['mygrid'];} else {$selectedGrid=findDefaultGridNumber($sectional);}
        if (isset($_GET['myquadrant'])) {$selectedQuadrant=strtoupper($_GET['myquadrant']);} else {$selectedQuadrant="E";}
        if (isset($_GET['myformat'])) {$selectedFormat=$_GET['myformat'];} else {$selectedFormat="dmm";}
	if (isset($_GET['dev'])) {$displayPrintVersion=1;} else {$displayPrintVersion=0;}		         # Use for development & debugging. Reverses sense of print/screen CSS
	if ((isset($_GET['dev'])) AND (isset($_GET['delay']))) {$delay = $_GET['delay'];} else {$delay = 0;}	 # Used with 'dev' flag to introduce delays for CLI printing of PDFs
														 # Recommended delay = 8500
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

<?php
	if ($displayPrintVersion) {echo "@media print {\n"; }		# Used for debug/dev
	else {echo "@media screen {\n"; }				# Used for production
?>
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

<?php
        if ($displayPrintVersion) {echo "@media screen {\n"; }          # Used for debug/dev
        else {echo "@media print {\n"; }                                # Used for production
?>

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

#topmap, #bottommap {
        position: absolute;
        top: 0px;
        left: 0px;
        opacity: 0.5;
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
        <?php
       echo "setTimeout(function(){window.status=\"ready\"},$delay);\n";
        ?>

	var op1 = parent.frames['resources'].document.getElementById('top').style.opacity;
	var op2 = 1 - op1;
	document.getElementById('topmap').style.opacity    = op1;
	document.getElementById('bottommap').style.opacity = op2;

  }
  </script>

</head>
<?php
        if ($displayPrintVersion){
        echo "<body onload=\"javascript:pageReady();\">\n";
        } else {
        echo "<body $onload>\n";
        }

echo "<div class=\"screenonly\">
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

$SurroundingGrids = GetSurroundingGridIDs($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);

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

<!-- Printed Material -- not normally displayed onscreen -->
 
<div class="printonly">
<table border="0" cellspacing="0" cellpadding="5" width="650" align="center">
<tr valign="bottom"><td valign="middle" align="center"  rowspan="5">
&larr;<br><?php echo $coordinates[$SurroundingGrids['West']['sectional']]['Abbrev'] . " <nobr>" . $SurroundingGrids['West']['grid'] . $SurroundingGrids['West']['quadrant'];  ?></nobr> <br>&larr;</td><td align="center" colspan="3"><b><?php echo $coordinates[$sectional]['Abbrev'] . "&nbsp;" .  $selectedGrid . $displayQuadrant; ?></b></td>
<td valign="middle" align="center"  rowspan="5">
&rarr;<br><?php echo $coordinates[$SurroundingGrids['East']['sectional']]['Abbrev'] . " <nobr>" . $SurroundingGrids['East']['grid'] . $SurroundingGrids['East']['quadrant'];  ?></nobr> <br>&rarr;</td></tr>
<tr valign="bottom"><td align="left" class="coord" width="162"><?php echo $result['NW']['lat'] . "<br>" . $result['NW']['lon']; ?></td><td align="center" width="50%">&uarr;&nbsp;
<?php echo $coordinates[$SurroundingGrids['North']['sectional']]['Abbrev'] . " " . $SurroundingGrids['North']['grid'] . $SurroundingGrids['North']['quadrant'];  ?> &nbsp;&uarr;</td><td align="right" class="coord" width="162"><?php echo $result['NE']['lat'] . "<br>" . $result['NE']['lon']; ?></td></tr>
<tr valign="top"><td align="center" colspan="3""><div style="position: relative;"><img src="images/spacer.gif" width="645" height="690"><?php
$kmlURL = "http://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&embed=1";

$kmlURLencoded = rawurlencode($kmlURL);
$zoom=12;
if ($selectedQuadrant=="E") $zoom=11;

$TerrainMapiframeBase= "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['terrain'] . "&amp;output=embed&amp;";
$TerrainMapiframeSrc  = $TerrainMapiframeBase .  "&amp;z=" . $zoom;

$SatMapiframeBase= "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['satellite'] . "&amp;output=embed&amp;";
$SatMapiframeSrc  = $SatMapiframeBase .  "&amp;z=" . $zoom;

#$iframeSrc = "http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=" . $zoom . "&amp;output=embed";
$iframeHref = "http://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=10";

echo "<iframe id=\"topmap\" width=\"645\" height=\"680\" style=\"opacity:0.5;\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $TerrainMapiframeSrc . "\"></iframe>";
echo "<iframe id=\"bottommap\" width=\"645\" height=\"680\" style=\"opacity:0.5;\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $SatMapiframeSrc . "\"></iframe>";
?>
</div>
</td></tr>
<tr valign="top"><td align="left" class="coord" width="162"><?php echo $result['SW']['lat'] . "<br>" . $result['SW']['lon']; ?></td>
<td align="center">&darr;&nbsp;<?php echo $coordinates[$SurroundingGrids['South']['sectional']]['Abbrev'] . " " . $SurroundingGrids['South']['grid'] . $SurroundingGrids['South']['quadrant'];  ?> &nbsp;&darr;</td>
<td align="right" class="coord" width="162"><?php echo $result['SE']['lat'] . "<br>" . $result['SE']['lon']; ?></td></tr>
<tr valign="top"><td align="center" class="coord" colspan="3"><i>Avg Mag Variation:</i></nobr><br><nobr><i><?php echo "$variation&deg; $varDir"; ?></i></td></tr>

</table>

</div>
</body>
</html>
<?php
# ===================================================================================================
# Find the starting grid number (not always 1)
#

function findDefaultGridNumber($sectional) {

global $coordinates;

$startingGrid=1;

	if (isset($coordinates[$sectional]['nullgrid'])) {
	$idx = 0;
		do {$idx++;}
		while (in_array($idx, $coordinates[$sectional]['nullgrid']));
	$startingGrid = $idx;
	}


return ($startingGrid);
}
# ===================================================================================================

