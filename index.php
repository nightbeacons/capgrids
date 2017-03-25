<?php
include_once("includes/gridFunctions.php");

$default_sectional = ourSectional();
//$default_sectional = "SEATTLE";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-us">
<title>Gridmaster | Civil Air Patrol Emergency Services</title>
<link rel="stylesheet" type="text/css" href="/css/style.css">
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<meta name="description" 
	content="Search-and-Rescue grid tool for Civil Air Patrol Emergency Services teams">
<meta name="keywords" 
	content="CAP grid, SAR grid, gridmaster, search grids, search and rescue grids, G1000 Flight Plan, G695 Flight Plan, FPL, Civil Air Patrol, Paine Field Squadron, KPAE, Everett, Washington">

<META NAME="revisit-after" content="15 days">

<script type="text/javascript" src="js/jquery-1.9.1.js"></script>
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

<body>
<?php include  "includes/top.php"; ?>

<table dir="ltr" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td valign="top" width="1%">
<?php include $_SERVER['DOCUMENT_ROOT'] . "/includes/navbar.php"; ?>

</td><td valign="top" width="24"></td><td valign="top">

	<!-- ======================= -->
	<!-- Begin Main Content Area -->
	<!-- ======================= -->

<table border="0" cellpadding="0" cellspacing="0"  align="left" id="table1" width="710" style="margin-left: 30px;">
	<tr>
		<td align="left" valign="top" width="500">
<h1 style="color:#CC3300;margin-bottom:0;">Gridmaster: CAP Search Grids</h1>
<form name="gridPulldown" >
	<select name="sectionalMenu" id="sectionalMenu" onChange="javascript:gridHandler();" style="border-color:black;border-width:1px;border-color:#303030;">
<?php
drawSectionalOptions($default_sectional);
?>
	</select>
</form>
<IFRAME id=gridwin marginWidth=0 marginHeight=0 src="gridinfo.php?id=SEATTLE" frameBorder=0 width=500 scrolling=no height=200></IFRAME>


</td><td valign="top" width="200" bgcolor="#fef0f0">
<?php
include_once("help/searchgrid.php");
?>
</td>
	</tr>
<tr><td colspan="2"><hr></td></tr>






<tr><td valign="top"><h1 style="color:#CC3300;margin-bottom:0;">Find a Grid</h1>

<IFRAME id="win2" name="win2" marginWidth=0 marginHeight=0 src="lonlat2grid.php?lon=-119&lat=48" frameBorder=0 width=500 scrolling=no height=200 ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0">
<?php
include_once("help/findgrid.php");
?>
</td></tr>


<tr><td colspan="2"><hr></td></tr>
<tr><td valign="top"><h1 style="color:#CC3300;margin-bottom:0;">Find Grid Corners</h1>
<img align="right"  src="/images/btn_print.gif" onclick="javascript:printHandler();" class="printbutton" style="position:relative;bottom:30px;margin-right:20px;cursor:pointer;cursor:hand;">

<IFRAME id="win1" name="win1" marginWidth=0 marginHeight=0 src="grid2lonlat.php?id=SEATTLE&mygrid=139&myquadrant=B" frameBorder=0 width=500 scrolling=no height=300 ></IFRAME>
</td>
<td valign="top" bgcolor="#fef0f0">
<?php
include_once("help/findcorners.php");
?>
</td></tr>
<tr><td colspan="2"><hr></td></tr>
<tr><td valign="top"><img align="right"  src="/images/btn_print.gif" onclick="javascript:printHandler();" class="printbutton" style="position:relative;top:20px;margin-right:20px;cursor:pointer;cursor:hand;">
<IFRAME id="resources" name="resources" marginWidth=0 marginHeight=0 src="resources.php?id=SEATTLE&mygrid=139&myquadrant=B" frameBorder=0 width=500 scrolling=no height=540 ></IFRAME>
<div class="coord" style="color:#909090; font-size:12px;margin-top:20px;"><i>Gridmaster</i> developed by <a class="credit" style="color:#909090;" href="mailto:nightbeacons@gmail.com">Capt Charles Jackson</a></div>
</td>
<td valign="top" bgcolor="#fef0f0">
<?php
include_once("help/resources.php");
?>
</td></tr>
</table>
<p style="margin-top: 0; margin-bottom: 0"></p>

	<!-- ======================= -->
	<!--  End Main Content Area  -->
	<!-- ======================= -->

</td></tr></table>
<?php include $_SERVER['DOCUMENT_ROOT'] . "/includes/footer.php"; ?>
</body>
</html>
