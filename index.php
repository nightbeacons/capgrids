<?php
include_once("includes/gridFunctions.php");

$sectionalAry = ourSectional();
$default_sectional = $sectionalAry['name'];
//$default_sectional = "SEATTLE";
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-us">
<title>CAPgrids | Civil Air Patrol Emergency Services</title>
<link rel="stylesheet" type="text/css" href="/css/style.css">
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<meta name="description" 
	content="Search-and-Rescue grid tool for Civil Air Patrol Emergency Services teams. The Swiss Army Knife of Search Grids, it can calculate grid corners and grid identifiers for any US Sectional chart. It also provides adjustable maps, and large map printouts."">
<meta name="keywords" 
	content="CAP grid, SAR grid, gridmaster, search grids, search and rescue grids, Google Earth overlay, G1000 Flight Plan, G695 Flight Plan, FPL, Civil Air Patrol">

<META NAME="revisit-after" content="15 days">

<script type="text/javascript" async src="/js/jquery-1.9.1.js"></script>
<script language="JavaScript" type="text/javascript">

  function gridHandler() {
    var sectionalCode = document.gridPulldown.sectionalMenu.options[document.gridPulldown.sectionalMenu.selectedIndex].value;
    document.getElementById('gridwin').src = 'gridinfo.php?id=' + sectionalCode;
    document.getElementById('win1').src = 'grid2lonlat.php?id=' + sectionalCode;
  }

  function setCornerUrl(newurl) {
  	document.getElementById('win1').src='grid2lonlat.php?' + newurl; 
  }
  function setGridWindow(sectional, grid, quadrant) {
  document.getElementById('gridwin').src = 'gridinfo.php?id=' + sectional;
//  document.getElementById('sectionalMenu').options[sectional].selected = true;
  document.getElementById('resources').src = 'resources.php?id=' + sectional + '&mygrid=' + grid + '&myquadrant=' + quadrant;
  document.getElementById('nearest').src = 'nearestAirports.php?id=' + sectional + '&mygrid=' + grid + '&myquadrant=' + quadrant;
  }

  function printHandler() {
  var win1Handle = document.getElementById('win1');
  var resHandle  = document.getElementById('resources');
  var op1 = window.frames['resources'].document.getElementById('top').style.opacity;
  var op2 = 1 - op1;
  window.frames['win1'].document.getElementById('topmap').style.opacity= op1;
  window.frames['win1'].document.getElementById('bottommap').style.opacity= op2;

  $("win1Handle").ready(function() {
       win1Handle.contentWindow.focus();
       win1Handle.contentWindow.print();
  });
  return false;
  }
  </script>
</head>
<body style="margin:0;">
<?php include_once("includes/ga.php") ?>
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
<h2 class="main">CAPgrids: CAP Search Grids</h2>
<form name="gridPulldown" >
<i>Select a Sectional:</i> &nbsp;	<select name="sectionalMenu" id="sectionalMenu" onChange="javascript:gridHandler();" style="border-color:black;border-width:1px;border-color:#303030;">

<?php
drawSectionalOptions($default_sectional);
?>
	</select>
</form>
<IFRAME id=gridwin marginWidth=0 marginHeight=0 src="gridinfo.php?id=<?php echo $default_sectional;?>" frameBorder=0 width=500 scrolling=no height=200></IFRAME>


</td><td valign="top" width="240" bgcolor="#fef0f0">
<?php
include_once("help/searchgrid.php");
?>
</td>
	</tr>
<tr><td colspan="2"><hr></td></tr>

<tr><td valign="top"><h2 class="main">Find a Grid</h2>

<IFRAME id="win2" name="win2" marginWidth=0 marginHeight=0 src="lonlat2grid.php?lon=<?php echo $sectionalAry['longitude'];?>&lat=<?php echo $sectionalAry['latitude'];?>" frameBorder=0 scrolling=no style="width:500px; height:10em;" ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0" width-"240">
<?php
include_once("help/findgrid.php");
?>
</td></tr>


<tr><td colspan="2"><hr></td></tr>
<tr><td valign="top"><h2 class="main">Find Grid Corners</h2>
<img align="right"  src="/images/btn_print.gif" onclick="javascript:printHandler();" class="printbutton" style="position:relative;bottom:30px;margin-right:20px;cursor:pointer;cursor:hand;">

<IFRAME id="win1" name="win1" marginWidth=0 marginHeight=0 src="grid2lonlat.php?id=<?php echo $default_sectional;?>&mygrid=<?php echo $sectionalAry['grid'];?>&myquadrant=<?php echo $sectionalAry['quadrant'];?>" frameBorder=0 width=500 scrolling=no height=300 ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0" width="240">
<?php
include_once("help/findcorners.php");
?>
</td></tr>
<tr><td colspan="2"><hr></td></tr>
<tr><td valign="top"><img align="right"  src="/images/btn_print.gif" onclick="javascript:printHandler();" class="printbutton" style="position:relative;top:20px;margin-right:20px;cursor:pointer;cursor:hand;">
<IFRAME id="resources" name="resources" marginWidth=0 marginHeight=0 src="resources.php?id=<?php echo $default_sectional;?>&mygrid=<?php echo $sectionalAry['grid'];?>&myquadrant=<?php echo $sectionalAry['quadrant'];?>" frameBorder=0 width=500 scrolling=no height=540 ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0" width="240">
<?php
include_once("help/resources.php");
?>
</td></tr>





<tr><td colspan="2"><hr></td></tr>
<tr><td valign="top"><img align="right"  src="/images/btn_print.gif" onclick="javascript:printHandler();" class="printbutton" style="position:relative;top:20px;margin-right:20px;cursor:pointer;cursor:hand;">
<IFRAME id="nearest" name="nearest" marginWidth=0 marginHeight=0 src="nearestAirports.php?id=<?php echo $default_sectional;?>&mygrid=<?php echo $sectionalAry['grid'];?>&myquadrant=<?php echo $sectionalAry['quadrant'];?>" frameBorder=0 width=500 scrolling=no height=540 ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0" width="240">
<?php
include_once("help/nearest.php");
?>
</td></tr>



</table>
<p style="margin-top: 0; margin-bottom: 0"></p>
	<!-- ======================= -->
	<!--  End Main Content Area  -->
	<!-- ======================= -->
</td></tr>
<tr><td><?php include $_SERVER['DOCUMENT_ROOT'] . "/includes/footer.php"; ?></td></tr></table>
</body>
</html>
