<?php

/**
 * @file
 */

//
// Find Grid Corners
//
// To debug print-only version, use URLs of the form http://www.capgrids.com/grid2lonlat.php?id=SEATTLE&mygrid=139&myquadrant=B&dev=1#
//
//
error_reporting(1);
include_once "includes/gridFunctions.php";

global $mapType;

$onload = "";
if (isset($_GET['id'])) {
  $sectional = $_GET['id'];
}
else {
  $sectional = "SEATTLE";
}
if (isset($_GET['mygrid'])) {
  $selectedGrid = $_GET['mygrid'];
}
else {
  $selectedGrid = findDefaultGridNumber($sectional);
}
if (isset($_GET['myquadrant'])) {
  $selectedQuadrant = strtoupper($_GET['myquadrant']);
}
else {
  $selectedQuadrant = "E";
}
if (isset($_GET['myformat'])) {
  $selectedFormat = $_GET['myformat'];
}
else {
  $selectedFormat = "dmm";
}
if (isset($_GET['dev'])) {
  $debugPrintVersion = 1;
}
else {
  $debugPrintVersion = 0;
}                    // Use for development & debugging. Reverses sense of print/screen CSS
if ((isset($_GET['dev'])) and (isset($_GET['delay']))) {
  $delay = $_GET['delay'];
}
else {
  $delay = 0;
}     // Used with 'dev' flag to introduce delays for CLI printing of PDFs
// Recommended delay = 8500
//    if (isset($_GET['id'])) {
//        $onload="onload=\"javascript:onloadHandler();\"";
//    }
$abbrev = $coordinates[$sectional]['Abbrev'];

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
//    var gridCode = document.findCorners.selectGrid.value;

        var quadCode = document.findCorners.selectQuadrant.options[document.findCorners.selectQuadrant.selectedIndex].value;
        var formatCode = document.findCorners.selectFormat.options[document.findCorners.selectFormat.selectedIndex].value;

        window.location='grid2lonlat.php?id=' + sectionalCode + '&mygrid=' + gridCode + '&myquadrant=' + quadCode + '&myformat=' + formatCode;

  }

  function onloadHandler() {
  <?php echo "parent.setGridWindow('" . $sectional . "', '" . $selectedGrid . "', '" . $selectedQuadrant . "');"; ?>
  }

  function pageReady(){
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
$showPrintVersion = 1;
if ($debugPrintVersion) {
  echo "<body onload=\"javascript:pageReady();\">\n";
  $showPrintVersion = !$showPrintVersion;
}
else {
  echo "<body $onload>\n";
}
$showPrintVersion = 1;

// Screen Display Below //
// phpinfo();
$displayQuadrant = " - " . $selectedQuadrant;
if ($selectedQuadrant == "E") {
  $displayQuadrant = "";
}

$cellWidth = 100;

$result = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);
$resultRaw = grid2lonlat($sectional, $selectedGrid, $selectedQuadrant, "raw");

$SurroundingGrids = GetSurroundingGridIDs($sectional, $selectedGrid, $selectedQuadrant, $selectedFormat);

$avgLon = ($resultRaw['NW']['lon'] + $result['NE']['lon']) / 2;
$avgLat = ($resultRaw['NW']['lat'] + $result['SW']['lat']) / 2;
$variation = $variationSigned = magVariation($avgLat, $avgLon);
$varDir = "W";
if ($variation < 0) {
  $varDir = "E";
  $variation = abs($variation);
}
$northSteer = 360 + $variationSigned;
if ($northSteer >= 360) {
  $northSteer -= 360;
}

$steer = [
  "North" => sprintf("%03d", floor($northSteer + 0.5)),
  "East"  => sprintf("%03d", floor(90 + $variationSigned + 0.5)),
  "South" => floor(180 + $variationSigned + 0.5),
  "West"  => floor(270 + $variationSigned + 0.5),
];

$CTwilight = GetCivilTwilight($avgLat, $avgLon);


// Beginning of Print Display.
?>
<table border="0" cellspacing="0" cellpadding="5" width="650" align="center">
<tr valign="bottom"><td valign="middle" align="center"  rowspan="4">
&larr;<br><?php echo $coordinates[$SurroundingGrids['West']['sectional']]['Abbrev'] . " <nobr>" . $SurroundingGrids['West']['grid'] . $SurroundingGrids['West']['quadrant'];  ?></nobr> <br>&larr;</td><td align="center" colspan="3"><b><?php echo $coordinates[$sectional]['Abbrev'] . "&nbsp;" . $selectedGrid . $displayQuadrant; ?></b></td>
<td valign="middle" align="center"  rowspan="4">
&rarr;<br><?php echo $coordinates[$SurroundingGrids['East']['sectional']]['Abbrev'] . " <nobr>" . $SurroundingGrids['East']['grid'] . $SurroundingGrids['East']['quadrant'];  ?></nobr> <br>&rarr;</td></tr>
<tr valign="bottom"><td align="left" class="coord" width="162"><?php echo $result['NW']['lat'] . "<br>" . $result['NW']['lon']; ?></td><td align="center" width="50%">&uarr;&nbsp;
<?php echo $coordinates[$SurroundingGrids['North']['sectional']]['Abbrev'] . " " . $SurroundingGrids['North']['grid'] . $SurroundingGrids['North']['quadrant'];  ?> &nbsp;&uarr;</td><td align="right" class="coord" width="162"><?php echo $result['NE']['lat'] . "<br>" . $result['NE']['lon']; ?></td></tr>
<tr valign="top"><td align="center" colspan="3"><div style="position: relative;"><img src="/images/spacer.gif" style="margin:0;padding:0;width:645px;height:685px;" alt=""><?php
$kmlURL = "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kml.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&embed=1";

$kmlURLencoded = rawurlencode($kmlURL);
$zoom = 12;
if ($selectedQuadrant == "E") {
  $zoom = 11;
}

// $TerrainMapiframeBase= "//maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['terrain'] . "&amp;output=embed&amp;";
// $TerrainMapiframeSrc  = $TerrainMapiframeBase .  "&amp;z=" . $zoom;
$TerrainMapiframeSrc = "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kmlLoader.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&MapTypeId=TERRAIN&embed=1";

$SatMapiframeBase = "https://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "%26embed%3D1&amp;ie=UTF8&amp;t=" . $mapType['satellite'] . "&amp;output=embed&amp;";
$SatMapiframeSrc  = $SatMapiframeBase . "&amp;z=" . $zoom;

$SatMapiframeSrc = "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "kmlLoader.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&MapTypeId=HYBRID&embed=1";

// $iframeSrc = "//maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=" . $zoom . "&amp;output=embed";
// $iframeHref = "https://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=" . $kmlURLencoded . "&amp;ie=UTF8&amp;t=p&amp;z=10";
echo "<iframe id=\"topmap\" width=\"645\" height=\"680\" style=\"opacity:0.5;\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $TerrainMapiframeSrc . "\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>";

echo "<iframe id=\"bottommap\" width=\"645\" height=\"680\" style=\"opacity:0.5;\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"" . $SatMapiframeSrc . "\"></iframe>";

$nearestURL = "https://" . $_SERVER['SERVER_NAME'] . preg_replace("/(.*\/).*/", "$1", $_SERVER['PHP_SELF']) . "nearestAirports.php?id=" . $sectional . "&mygrid=" . $selectedGrid . "&myquadrant=" . $selectedQuadrant . "&embed=1";
$nearestURLencoded = rawurlencode($nearestURL);

?>
</div>
</td></tr>
<tr valign="top"><td align="left" class="coord" width="162"><?php echo $result['SW']['lat'] . "<br>" . $result['SW']['lon']; ?></td>
<td align="center">&darr;&nbsp;<?php echo $coordinates[$SurroundingGrids['South']['sectional']]['Abbrev'] . " " . $SurroundingGrids['South']['grid'] . $SurroundingGrids['South']['quadrant'];  ?> &nbsp;&darr;</td>
<td align="right" class="coord" width="162"><?php echo $result['SE']['lat'] . "<br>" . $result['SE']['lon']; ?></td></tr>
<tr><td rowspan="2" style="background-color:#ffffff;">&nbsp;</td><td colspan="3" style="vertical-align:top;"><hr>
<div style="font-size:9.0pt;font-family:arial;margin-left:0;margin-top:0;display:inline-table;">
   <table style="width:224px;margin-left:0;margin-top:2em;display:inline-table;border-style:solid;position:relative;" border="1" cellpadding="4" cellspacing="0">
   <caption style="caption-side:bottom;margin-top:0.4em;"><i>Avg Mag Variation: <?php echo "$variation&deg; $varDir"; ?></i></caption>
   <tr><td><b>For:</b></td><td>North</td><td>East</td><td>South</td><td>West</td></tr>
   <tr><td><b>Steer:</b></td><td align="center"><?php echo $steer['North']; ?></td><td align="center"><?php echo $steer['East']; ?></td><td align="center"><?php echo $steer['South']; ?></td><td align="center"><?php echo $steer['West']; ?></td></tr>
   </table>
<table border="0" style="margin-top:2em;width:224px;" cellspacing="0" cellpadding="2"><tr><td><p style="padding:0;margin:0;">Start of AM Civil Twilight:</p></td><td><?php echo "<p style=\"padding:0;margin:0;white-space:nowrap\">" . $CTwilight['sunrise_Local'] . " " . $CTwilight['timeZoneAbbrev'];?></p></td></tr>
<tr><td><p style="padding:0;margin:0;">End of PM Civil Twilight:</p></td><td><?php echo "<p  style=\"white-space:nowrap;padding:0;margin:0;\">" . $CTwilight['sunset_Local'] . " " . $CTwilight['timeZoneAbbrev'];?></p></td></tr></table>
</div>
<table style="float:right;display:inline-table;width:400px;margin:0;" border="0"><tr><td style="width:100%;"><iframe style="width:100%;" id="nearest" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" onload="resizeIframe(this)" src="<?php echo $nearestURL;?>"</iframe></td></tr></table>
<hr>
</td><td rowspan="2">&nbsp;</td></tr>
</td></tr>
</table>

</body>
</html>
<?php
/**
 * ===================================================================================================
 * Find the starting grid number (not always 1)
 */
function findDefaultGridNumber($sectional) {

  global $coordinates;

  $startingGrid = 1;

  if (isset($coordinates[$sectional]['nullgrid'])) {
    $idx = 0;
    do {
      $idx++;
    } while (in_array($idx, $coordinates[$sectional]['nullgrid']));
    $startingGrid = $idx;
  }

  return ($startingGrid);
}

/**
 * Create the CSS, accounting for debug mode
 * Return the CSS code.
 */
function build_css($debugPrintVersion) {

  $css = "<style type=\"text/css\">\n";

  // Global CSS.
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

  // CSS for the screen display.
  if ($debugPrintVersion == 1) {
    $css .= "@media print {\n";
  }
  else {
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

  // CSS for the Print display.
  if ($debugPrintVersion == 1) {
    $css .= "@media screen {\n";
  }
  else {
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
