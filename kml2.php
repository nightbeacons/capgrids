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

include_once("includes/gridFunctions.php");
include_once("includes/styles.php");


$TEST = 0;

$kmlHeader="Content-type: application/vnd.google-earth.kml+xml";
$kmzHeader="Content-type: application/vnd.google-earth.kmz";


$offset = "0.000000000001";
$altitude=0;

$lonlatflag=0;


	if (isset($_GET['id'])) {
	$sectional=$_GET['id'];
	}	
if (isset($_GET['mygrid'])) $mygrid=$_GET['mygrid'];
if (isset($_GET['myquadrant'])) $myquadrant=$_GET['myquadrant'];

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
$result = grid2lonlat($sectional,$mygrid, "E", "raw");

$abbrev = $coordinates[$sectional]['Abbrev'];

$gridLon = $result['NW']['lon'];
$gridLat = $result['NW']['lat'];

$gridLabel = "$abbrev - $mygrid";
$filenameHeader = "Content-Disposition: attachment; filename=\"" .$abbrev . "_" . $mygrid . ".kml\"";


#echo "Lon: $gridLon    Lat: $gridLat    $abbrev - $mygrid \n";

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

header($kmlHeader);
header($filenameHeader);

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
