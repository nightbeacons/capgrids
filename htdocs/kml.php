<?php
######################################################################
# kml.php
#
# Accepts a query string of the form:
#
#               kml.php?id=SEATTLE&mygrid=42&myquadrant=A
#          or
#               kml.php?lat=48.625&lon=-123.456
#
# and outputs a KML file of the appropriate grid.
#
# In the second case, where a lon and lat is given, provide the KML grid, 
# and add a placemark for the given lon/lat
#
######################################################################
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/gridFunctions.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/styles.php");

$TEST = 0;
$embed=isset($_GET['embed']);

$kmlHeader="Content-type: application/vnd.google-earth.kml+xml";
$kmzHeader="Content-type: application/vnd.google-earth.kmz";


$offset = "0.000000000001";
$altitude=0;
$myquadrant="E";
$quadrantDisplay="";
$lonlatflag=0;
	if (isset($_GET['id'])) {
	$sectional=$_GET['id'];
	}	
if (isset($_GET['mygrid'])) $mygrid=$_GET['mygrid'];
if (isset($_GET['myquadrant'])) $myquadrant=$_GET['myquadrant'];

if ($myquadrant != "E") $quadrantDisplay="-" . $myquadrant;

	if (isset($_GET['lon'])) {
	$pointLongitude=$_GET['lon'];
	$lonlatflag=1;
	}
	if (isset($_GET['lat'])) $pointLatitude=$_GET['lat'];



# Determine the raw lon/lat of the top-left grid corner
$gridLon=$gridLat=$longitude=$latitude=0;

	if ($lonlatflag) {
	$result1 = lonlat2grid($pointLongitude, $pointLatitude);
	$sectional = $result1['sectional'];
	$mygrid= $result1['grid'];
	$quadrant = $result1['quadrant'];
	} 
$result = grid2lonlat($sectional,$mygrid, $myquadrant, "raw");

$abbrev = $coordinates[strtoupper($sectional)]['Abbrev'];

$gridLon = $result['NW']['lon'];  # Returns coords of NW corner of grid or quarter-grid
$gridLat = $result['NW']['lat'];  # 

$gridLabel = "$abbrev  $mygrid$quadrantDisplay";
$kmlFilename = $abbrev . "_" . $mygrid . $quadrantDisplay . ".kml";
$eTag = 'ETag: "' . md5($abbrev . "_" . $mygrid . $quadrantDisplay) . '"';

$filenameHeader = "Content-Disposition: attachment; filename=\"" . $kmlFilename . "\"";


#echo "Lon: $gridLon    Lat: $gridLat    $abbrev - $mygrid \n";

   if ($embed) {

      if ($myquadrant == "E") {
      $nw_coord = ($gridLon)  . ","       . ($gridLat) . ",0";
      $ne_coord = ($gridLon + 0.25) . "," . ($gridLat) . ",0";
      $se_coord = ($gridLon + 0.25) . "," . ($gridLat - 0.25) . ",0";
      $sw_coord = ($gridLon) . ","        . ($gridLat - 0.25) . ",0";
      } else {
      $nw_coord = ($gridLon)  . ","       . ($gridLat) . ",0";
      $ne_coord = ($gridLon + 0.125) . "," . ($gridLat) . ",0";
      $se_coord = ($gridLon + 0.125) . "," . ($gridLat - 0.125) . ",0";
      $sw_coord = ($gridLon) . ","        . ($gridLat - 0.125) . ",0";
      }

$buffer="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<kml xmlns=\"http://www.opengis.net/kml/2.2\" xmlns:gx=\"http://www.google.com/kml/ext/2.2\" xmlns:kml=\"http://www.opengis.net/kml/2.2\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
<Document>
    <name>CAP Search Grids for $gridLabel</name>
    <open>1</open>
    <description><![CDATA[CAP Conventional Search Grids covering the $abbrev - $mygrid  Sectional Chart<br><br>
By Capt Charles Jackson<br>nightbeacons@gmail.com<br>
based on original work by<br>
2Lt Landis Bennett, CAP<br>
landis@mac.com]]></description>
    <Style id=\"s_ylw-pushpin_hl\">
        <IconStyle>
            <color>ff00aa55</color>
            <scale>1.0</scale>
            <Icon>
                <href>https://" .  $_SERVER['SERVER_NAME'] . "/images/markers/foo.png</href>
            </Icon>
        </IconStyle>
        <LabelStyle>
            <color>807fffff</color>
        </LabelStyle>
        <LineStyle>
            <color>88000000</color>
            <width>3</width>
        </LineStyle>
    </Style>

    <Placemark>
        <name>Polyline 1</name>
        <description> xx</description>
        <LineString>
            <coordinates>$nw_coord $ne_coord $se_coord $sw_coord $nw_coord</coordinates>
        </LineString>
    </Placemark>
</Document>
</kml>";

} else {

# Fetch and output the Styles section

	$styles = makePreamble($gridLabel); # Create the "<Style>" sections of the KML. No folders. 

	$firstRegion = makeRegion($gridLon,$gridLat);
	$boundingBox = makeBBfolder($gridLon,$gridLat,$abbrev,$mygrid);
	$gridFolder  = makeGridFolder($gridLon,$gridLat,$abbrev,$mygrid);
	$ending = "		</Folder>
	</Folder>
</Folder>
</Document>
</kml>\n";

	$buffer = $styles . "<Folder>\n" . $firstRegion . $boundingBox . $gridFolder . $ending;

}

header($kmlHeader);
$content_length_header = "Content-Length: " . strlen($buffer);
header($content_length_header);
// $lastMod_Header = "Last-Modified: " . date(DATE_RFC7231);
$lastMod_Header = "Last-Modified: Wed, 15 Nov 2023 15:52:16 GMT";
header($lastMod_Header);
header($eTag);
header("Accept-Ranges: bytes");

if (!$embed) {header($filenameHeader);}

//$kmlFileSave = $_SERVER['DOCUMENT_ROOT'] . "/kml/" . $kmlFilename;
//file_put_contents($kmlFileSave, $buffer);
echo "$buffer";

# ===================================================================================================
# Create the Sectionals Region
# Return the text string

function makeRegion($lon,$lat)
{

$text="	<name>Sectionals</name>
	<Region>
		<LatLonAltBox>

			<north>" . $lat . "</north>
			<south>" . ($lat - 0.25) . "</south>
			<east>" . ($lon + 0.25) . "</east>
			<west>" . $lon . "</west>
			
			<rotation>0</rotation>
			<minAltitude>0</minAltitude>
			<maxAltitude>0</maxAltitude>
		</LatLonAltBox>
		<Lod>
			<minLodPixels>256</minLodPixels>
			<maxLodPixels>4098</maxLodPixels>
			<minFadeExtent>0</minFadeExtent>
			<maxFadeExtent>0</maxFadeExtent> 
		</Lod>
	</Region>\n";


return($text);
}
# ===================================================================================================
# Create the Bounding Box Folder
# Return the text string
#	<coordinates> = lon/lat of the center of the grid

function makeBBfolder($gridLon,$gridLat,$abbrev,$mygrid)
{

$text="	<Folder>
	<name>$abbrev</name>
		<Placemark>
			<name>$abbrev</name>
			<styleUrl>#SectionalRed</styleUrl>
		</Placemark>
		<Placemark>
			<name>$abbrev $mygrid</name>   <!-- Name inside entire-area bounding box -->
			<styleUrl>#SectionalRed</styleUrl>
			<Point>
				<coordinates>" . ($gridLon + 0.125) . "," . ($gridLat - 0.125) . ",0</coordinates>
			</Point>
		</Placemark>
	</Folder>\n";
 
return($text);
}
# ===================================================================================================
# Create the Grids Folder
# Return the text string
#


function makeGridFolder($gridLon,$gridLat,$abbrev,$mygrid)
{
global $offset, $altitude;

$quadAry = array("E","A","B","C","D");

$north = $gridLat + 0.125;
$south = $gridLat - 0.375;
$east  = $gridLon + 0.375;
$west  = $gridLon - 0.125; 

$gridLabel = sprintf("%03d", $mygrid);
$text="	<Folder>
	<name>Grids</name>
		<Folder>
		<name>$abbrev $mygrid</name>
			<Folder>
				<name>Grid</name>
				<Region>
					<LatLonAltBox>	
						<north>$north</north>
						<south>$south</south>
						<east>$east</east>
						<west>$west</west>
						<rotation>0</rotation>
						<minAltitude>0</minAltitude>
						<maxAltitude>0</maxAltitude>
					</LatLonAltBox>
					<Lod>
						<minLodPixels>130</minLodPixels>
						<maxLodPixels>-1</maxLodPixels>
						<minFadeExtent>0</minFadeExtent>
						<maxFadeExtent>0</maxFadeExtent>
					</Lod>
				</Region>
				<Placemark>
					<name>$gridLabel</name>
					<styleUrl>#GridRed</styleUrl>
					<LineString>
						<extrude>1</extrude>
						<tessellate>1</tessellate>
						<coordinates> $gridLon,$gridLat,$altitude " . ($gridLon + 0.25) . ",$gridLat,$altitude " . ($gridLon + 0.25) . "," . (($gridLat - 0.25) + $offset) . ",$altitude $gridLon," . (($gridLat - 0.25) + $offset) . ",$altitude $gridLon,$gridLat,$altitude </coordinates>
					</LineString>
				</Placemark>
			</Folder>
			<Folder>
			    <name>Alpha Grid</name>
			     <Region>
			      <LatLonAltBox>
					<north>$north</north>
					<south>$south</south>
					<east>$east</east>
					<west>$west</west>
					<rotation>0</rotation>
					<minAltitude>0</minAltitude>
					<maxAltitude>0</maxAltitude>
			      </LatLonAltBox>
			      <Lod>
					<minLodPixels>180</minLodPixels>
					<maxLodPixels>-1</maxLodPixels>
					<minFadeExtent>0</minFadeExtent>
					<maxFadeExtent>0</maxFadeExtent>
			      </Lod>
			     </Region>
				
				<Placemark>
					<name>$gridLabel</name>
					<styleUrl>#AlphaGridRed</styleUrl>
					<LineString>
						<extrude>1</extrude>
						<tessellate>1</tessellate>
						<coordinates> " . ($gridLon + 0.125) . ",$gridLat,$altitude " . ($gridLon + 0.125) . "," . (($gridLat - 0.25) + $offset) . ",$altitude " . ($gridLon + 0.25) . "," . (($gridLat - 0.25) + $offset)  .",$altitude " . ($gridLon + 0.25) . "," . ($gridLat - 0.125) . ",$altitude $gridLon," . ($gridLat - 0.125) . ",$altitude  </coordinates>
					</LineString>
				</Placemark>
			</Folder>
			<Folder>
				<name>Numbers</name>
				<Region>
					<LatLonAltBox>		
						<north>$north</north>
						<south>$south</south>
						<east>$east</east>
						<west>$west</west>
						<rotation>0</rotation>
						<minAltitude>0</minAltitude>
						<maxAltitude>0</maxAltitude>
					</LatLonAltBox>
					<Lod>
						<minLodPixels>300</minLodPixels>
						<maxLodPixels>-1</maxLodPixels>
						<minFadeExtent>0</minFadeExtent>
						<maxFadeExtent>0</maxFadeExtent>
					</Lod>
				</Region>\n";


	for ($quad=1; $quad <= 4; $quad++) {

	$latFactor = 0.125 * (($quad > 2) + 0) + 0.0625;
	$lonFactor = 0.125 * ((($quad/2) == ((int)($quad/2))) + 0) + 0.0625;

	$text .= "				<Placemark>
					<name>$gridLabel " . $quadAry[$quad] . "</name>
					<styleUrl>#AlphaGridRed</styleUrl>
					<Point>
						<coordinates>" . ($gridLon + $lonFactor) . "," . ($gridLat - $latFactor) . ",$altitude </coordinates>
					</Point>
				</Placemark>\n";
	}	

$text .= "			</Folder>\n";

return($text);
}


# ===================================================================================================

?>
