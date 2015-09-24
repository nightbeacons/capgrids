<?php
# ===================================================================================================
# Define the initial folder
# Return the text string

function makePreamble($grid)
{

$text='<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.1">
<Document>
	<name>CAP Search Grids for ' . $grid . '</name>
	<open>1</open>
	<description><![CDATA[CAP Conventional Search Grids covering the ' . $grid . ' Sectional Chart<br><br>
By 2Lt Charles Jackson<br>charles.jackson@sun.com<br>
based on original work by<br>
2Lt Landis Bennett, CAP<br>
landis@mac.com]]></description>
	<Style id="AlphaGridRed">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff0000ff</color>
		</LabelStyle>
		<LineStyle>
			<color>7f0000ff</color>
			<width>1.5</width>
		</LineStyle>
	</Style>
	<Style id="AlphaGridBlue">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ffff0000</color>
		</LabelStyle>
		<LineStyle>
			<color>7fff0000</color>
			<width>1.5</width>
		</LineStyle>
	</Style>
	<Style id="GridBlue">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ffff0000</color>
		</LabelStyle>
		<LineStyle>
			<color>ffff0000</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
	<Style id="GridGreen">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff00ff00</color>
		</LabelStyle>
		<LineStyle>
			<color>ff00ff00</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
	<Style id="SectionalGreen">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff00ff00</color>
		</LabelStyle>
		<LineStyle>
			<color>ff00ff00</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
	<Style id="SectionalRed">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff0000ff</color>
		</LabelStyle>
		<LineStyle>
			<color>ff0000ff</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
	<Style id="AlphaGridGreen">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff00ff00</color>
		</LabelStyle>
		<LineStyle>
			<color>7f00ff00</color>
			<width>1.5</width>
		</LineStyle>
	</Style>
	<Style id="SectionalBlue">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ffff0000</color>
		</LabelStyle>
		<LineStyle>
			<color>ffff0000</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
	<Style id="GridRed">
		<IconStyle>
			<Icon>
			</Icon>
		</IconStyle>
		<LabelStyle>
			<color>ff0000ff</color>
		</LabelStyle>
		<LineStyle>
			<color>ff0000ff</color>
			<width>2.5</width>
		</LineStyle>
	</Style>
';

return($text);

}

# ===================================================================================================
?>

