<?php
################################################################
#
# Find Grid Corners
#
# To debug print-only version, use URLs of the form http://www.capgrids.com/grid2lonlat.php?id=SEATTLE&mygrid=139&myquadrant=B&dev=1#
#
################################################################
error_reporting(1);
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/gridFunctions.php");

global $mapType;

$onload="";
        if (isset($_GET['id'])) {$sectional=$_GET['id'];} else {$sectional="SEATTLE";}
        if (isset($_GET['mygrid'])) {$selectedGrid=$_GET['mygrid'];} else {$selectedGrid=findDefaultGridNumber($sectional);}
        if (isset($_GET['myquadrant'])) {$selectedQuadrant=strtoupper($_GET['myquadrant']);} else {$selectedQuadrant="E";}
        if (isset($_GET['myformat'])) {$selectedFormat=$_GET['myformat'];} else {$selectedFormat="dmm";}
	if (isset($_GET['dev'])) {$debugPrintVersion=1;} else {$debugPrintVersion=0;}   		         # Use for development & debugging. Reverses sense of print/screen CSS
	if ((isset($_GET['dev'])) AND (isset($_GET['delay']))) {$delay = $_GET['delay'];} else {$delay = 0;}	 # Used with 'dev' flag to introduce delays for CLI printing of PDFs
														 # Recommended delay = 8500
	if (isset($_GET['id'])) {
		$onload="onload=\"javascript:onloadHandler();\"";
	}

$abbrev=$coordinates[$sectional]['Abbrev'];

// Change the etag only when this file or gridFunctions.php is updated
$etag = md5(filemtime($_SERVER['SCRIPT_FILENAME']) + filemtime($_SERVER['DOCUMENT_ROOT'] . "/includes/gridFunctions.php"));
$etag = 'etag: "' . $etag . '"';
header("Cache-Control: no-cache");  // https://stackoverflow.com/questions/55025948/does-the-etag-header-make-the-cache-control-header-obsolete-how-to-make-sure-ca
header($etag);

?>
<!DOCTYPE html>
<html lang="en">

<head>
<title><?php echo "$abbrev $selectedGrid"; ?> </title>
<meta name="robots" content="noindex, nofollow">
<?php

$my_css = build_css($debugPrintVersion);
echo $my_css;
?>

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

  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
  </script>

</head>
<?php

        if ($debugPrintVersion){
        echo "<body onload=\"javascript:pageReady();\">\n";
        } else {
        echo "<body $onload>\n";
        }
//  Screen Display Below //
// echo "ETAG=$etag<br>\n";
$showPrintVersion=0;
if (!$showPrintVersion) {

echo "<form name=\"findCorners\">
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

}

$displayQuadrant= " - " . $selectedQuadrant;
if ($selectedQuadrant == "E") $displayQuadrant="";

$cellWidth=100;

$result = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);
$resultRaw = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, "raw");
$cell = grid2cell($sectional, $selectedGrid, $selectedQuadrant);

$SurroundingGrids = GetSurroundingGridIDs($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);

$avgLon=($resultRaw['NW']['lon'] + $result['NE']['lon'])/2;
$avgLat=($resultRaw['NW']['lat'] + $result['SW']['lat'])/2;
$variation =  $variationSigned = magVariation($avgLat, $avgLon);
$varDir="W";
	if ($variation < 0) {
	$varDir="E";
	$variation=abs($variation);
	}
$northSteer = 360 + $variationSigned;
   if ($northSteer >= 360) $northSteer -= 360;

$steer = array("North" => sprintf("%03d", floor($northSteer + 0.5)),
               "East"  => sprintf("%03d", floor(90 + $variationSigned + 0.5)),
               "South" => floor(180 + $variationSigned + 0.5),
               "West"  => floor(270 + $variationSigned + 0.5)
         );

$CTwilight = GetCivilTwilight($avgLat, $avgLon);

echo "<br><table border=\"1\" cellspacing=\"0\" cellpadding=\"10\" width=\"375\" align=\"center\" style=\"border-width:10px;border-style:solid;border-color:#c0c0c0;\"><tr><td><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" align=\"center\">
<tr><td width=\"$cellWidth\" valign=\"bottom\" align=\"right\" class=\"coord\">" . $result['NW']['lat'] . "<br>" . $result['NW']['lon'] . "</td><td width=\"($cellWidth + 75)\">&nbsp;</td><td valign=\"bottom\" align=\"left\" width=\"$cellWidth\" class=\"coord\">" . $result['NE']['lat'] . "<br>" . $result['NE']['lon'] . "</td></tr>
<tr><td>&nbsp;</td><td align=\"center\" valign=\"middle\" style=\"margin-bottom:0;padding:0 0.3rem 0 0.3rem;border-width:2px;border-style:solid;width:" . $cellWidth . "px;height:80px;background-color:#f0f0f0;\">" . $coordinates[$sectional]['Abbrev'] . "<br>$selectedGrid $displayQuadrant<hr>Cell:&nbsp;$cell<img src=\"/images/spacer.gif\" style=\"width:" . $cellWidth . "px;height:1px;\" alt=\"\"></td><td width=\"$cellWidth\">&nbsp;</td></tr>
<tr><td valign=\"top\" align=\"right\" class=\"coord\">" . $result['SW']['lat'] . "<br>" . $result['SW']['lon'] . "</td><td align=\"center\" valign=\"top\" class=\"coord\"><nobr><small><i>Mag Variation:</i></small></nobr><br><nobr><i>$variation&deg; $varDir</i></nobr></td><td valign=\"top\" align=\"left\" class=\"coord\">" . $result['SE']['lat'] . "<br>" . $result['SE']['lon'] . "</td></tr>
</table></td></tr></table></div>\n";

// End of Screen Display

// Beginning of Print Display
?>

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

/**
 *
 * Create the CSS, accounting for debug mode
 * Return the CSS code
 */
function build_css($debugPrintVersion){

$css = "<style type=\"text/css\">\n";

// Global CSS
$css .= ".coord {
        font-family: Arial, Helvetica;
        font-size: 10pt;
        }
#topmap, #bottommap {
        position: absolute;
        top: 0px;
        left: 0px;
        opacity: 0.5;
        }

@page {
   margin: 0cm;
}\n";


// CSS for the screen display

   if ($debugPrintVersion == 1){
     $css .= "@media print {\n";
   } else {
     $css .= "@media screen {\n";
   }

$css .= "div.printonly {
        display: block;
        visibility: hidden;
        margin:0pt 18pt 0pt 18pt;
        padding:0;
        }

div.screenonly {
        margin:0;
        padding:0;
        display: block;
        visibility:visible;
        }
}\n";



// CSS for the Print display

   if ($debugPrintVersion == 1){
     $css .= "@media screen {\n";
   } else {
     $css .= "@media print {\n";
   }

$css .= "body {
    margin: 0;
  }
div.printonly {
        margin:0;
        padding:0;
        display: block;
        visibility: visible;
        }

div.screenonly {
        display: none;
        }
}\n";

$css .= "</style>\n";

return ($css);
}

