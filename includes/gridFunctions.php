<?php
# Grid Functions

include_once("coordinates.php");

# ============================================== Globals ===========================================


# The Google Maps query string uses the "t" parameter to specify the type of map to display.
# This array provides a key to the map types

$mapType = array(
		"street" 	=> "m",
		"satellite"	=> "h",
		"terrain"	=> "p",
		"earth"  	=> "f"
		);



# ======================================= End of Globals ===========================================


# ===================================================================================================
# grid2lonlat()
# Return the Lon/Lat for a given grid
#   Input values:
#		- Full Sectional Name
#		- Grid Number
#		- Quadrant letter (optional)
#			If omitted, return lon/lat for the entire grid
#		- Format (optional)
#			dmm = Degrees plus decimal minutes (default)
#			dms = Degree Minutes Seconds 
#
#

function grid2lonlat($sectional, $gridNum, $quadrant="E", $format="dmm")
{
global $coordinates,$quadrantOffsets;

reset($coordinates);
$grid=strtoupper(trim($sectional));

$dataset=$coordinates[$grid];

$latCounter = $dataset['MaxLat'];
$lonCounter = $dataset['MaxLon'];

	if ($gridNum > $dataset['endGrid'] ) {
	echo "Invalid Grid Number\n";
	} else {

		for ($gridCounter=$dataset['startGrid']; $gridCounter < $gridNum; $gridCounter++) {
		$lonCounter = $lonCounter + 0.25;
			if ($lonCounter > ($dataset['MinLon'] - 0.25)) {
			$lonCounter = $dataset['MaxLon'];
			$latCounter = $latCounter - 0.25;
			}
		# echo "\nLatCounter = $latCounter  | LonCounter = $lonCounter\n===========================================\n";
		}
	
	# echo "\n\n$latCounter -- $lonCounter \n";
 
	}

$formattedLatCounter=changeFormat($latCounter, $format);
$formattedLonCounter=changeFormat($lonCounter, $format);

if ($quadrant == "E") {	# Quadrant letter is not set. Return Lat/Lon of entire grid

	$gridBox = array(
 			"Sectional" => $grid,
			"Grid"		=> $gridNum,
			"Quadrant"	=> $quadrant,
		
		"NW" => array (
			"lon"	=> $formattedLonCounter,
			"lat"	=> $formattedLatCounter
		),
		
		"NE" => array (
			"lon"	=> changeFormat(($lonCounter + 0.25), $format),
			"lat"	=> $formattedLatCounter
		),

		"SW" => array (
			"lon"	=> $formattedLonCounter,
			"lat"	=> changeFormat(($latCounter - 0.25), $format)
		),
	
		"SE" => array (
			"lon"	=> changeFormat(($lonCounter + 0.25), $format),
			"lat"	=> changeFormat(($latCounter - 0.25), $format)
		)
	);
	
} else {		# Determine Lon/Lat for the given quadrant


$quadrant=strtoupper(trim($quadrant));
$offset=$quadrantOffsets[$quadrant];

	$gridBox = array(
 			"Sectional" => $grid,
			"Grid"		=> $gridNum,
			"Quadrant"	=> $quadrant,
		
		"NW" => array (
			"lon"	=> changeFormat(($lonCounter + $offset['lon']), $format),
			"lat"	=> changeFormat(($latCounter + $offset['lat']), $format)
		),
		
		"NE" => array (
			"lon"	=> changeFormat(($lonCounter + $offset['lon'] + 0.125), $format),
			"lat"	=> changeFormat(($latCounter + $offset['lat']), $format)
		),

		"SW" => array (
			"lon"	=> changeFormat(($lonCounter + $offset['lon']), $format),
			"lat"	=> changeFormat((($latCounter - 0.125) + $offset['lat']), $format)
		),
	
		"SE" => array (
			"lon"	=> changeFormat(($lonCounter + $offset['lon'] + 0.125), $format),
			"lat"	=> changeFormat((($latCounter - 0.125) + $offset['lat']), $format)
		)
	);

	
}

return ($gridBox);



}

/**
 * changeFormat($decDegree,$format)
 *
 * Changes a degree value of the form XX.yyy into Degrees + Decimal Minutes (dmm)
 *                                            or Degrees Minutes Seconds (dms)     
 *					      or do nothing (raw)	
 */

function changeFormat($decDegree, $format)
{

$intDeg=(int)($decDegree);
$decimalMinutes=sprintf("%01.3f", (60 * abs($decDegree - $intDeg) + 0.00005)) + 0;

$intDecimalMinutes = (int)($decimalMinutes);
$decimalsSeconds = sprintf("%01.2f", (60 * abs((60 * abs($decDegree - $intDeg)) - $intDecimalMinutes) + 0.00005)) + 0;

switch($format) {
	case "dmm":
		$result = "$intDeg&deg; $decimalMinutes'";
		break;

	case "dms":
		$result = "$intDeg&deg $intDecimalMinutes' $decimalsSeconds\"";
		break;

	case "raw":
		$result = $decDegree;
		break;
}

 
return($result);

}

# ===================================================================================================
# lonlat2grid()
# Returns the grid identifier for a given lon/lat
#   Input values:
#		- Lon / Lat in decimal (i.e.  -117.5 means 117 degrees, 30' West)
#
#    Output:   array (
#	 	"sectional"	=> $currSectional,
#	 	"grid" 		=> $gridCounter,
#	 	"quadrant"	=> $quadrant
#	);
#


function lonlat2grid($longitude, $latitude)
{
global $coordinates,$quadrantOffsets;

$quadAry = array("A","B","C","D");

$map = $result = array();
reset($coordinates);
$gridCounter=$lonCounter=$latCounter = 0;
$quadrant=-1;
$found=0;

	# Determine the Sectional Chart
	 
	while (list($sectional, $info) = each($coordinates))  {
    	#echo "Key: $sectional; Value: $info   longitude=$longitude  MinLon=" . $info['MinLon'] . " MaxLon=" . $info['MaxLon'] ;
	#	if (($longitude < $info['MinLon']) AND ($longitude > $info['MaxLon'])) echo "  LONGITUDE MATCH  ";
	#	if (($latitude  > $info['MinLat']) AND ($latitude  < $info['MaxLat'])) echo "  LATITUDE MATCH ";
	# echo "<br>\n";

		if (($longitude < $info['MinLon']) AND ($longitude > $info['MaxLon']) AND
		    ($latitude  > $info['MinLat']) AND ($latitude  < $info['MaxLat'])) {
		    	$map[]= $sectional;	# $map is array of all matches
		    }

	} 

	# Parse the data for the sectional and derive a grid number
	# When done, $lonCounter and $latCounter will be the NW corner of the grid

	foreach($map as $currSectional) {
	$found = 0;
	reset($coordinates);
	$dataset=$coordinates[$currSectional];
	
	$latCounter = $dataset['MaxLat'];
	$lonCounter = $dataset['MaxLon'];
	$gridCounter = $dataset['startGrid'];
		if (isset($dataset['nullgrid'])) {
		$nullgrid = $dataset['nullgrid'];
		} else {
		$nullgrid = array(0);
		}
	
		while ((!$found) AND ($gridCounter <= $dataset['endGrid'])) {

			if (($longitude >= $lonCounter) AND ($longitude <= ($lonCounter + 0.25)) AND
			    ($latitude  <= $latCounter) AND ($latitude  >= $latCounter - 0.25)) {
			    	$found=1;
			} else {
			$lonCounter = $lonCounter + 0.25;
			$gridCounter++;
			 	if ($lonCounter >= $dataset['MinLon']) {
			    $lonCounter = $dataset['MaxLon'];
			    $latCounter = $latCounter - 0.25;
			    }
			}
		}

	if ((in_array($gridCounter, $nullgrid)) OR ($gridCounter < 1)) $found = 0;

	$gridCenterLat = $latCounter - 0.125;
	$gridCenterLon = $lonCounter + 0.125;
	$quadrant  = $quadAry[(($latitude < $gridCenterLat)*2 + ($longitude > $gridCenterLon))];

		if ($found) {
		$result = array (
		 	"sectional"	=> $currSectional,
	 		"grid" 		=> $gridCounter,
	 		"quadrant"	=> $quadrant
		);
		}
	}

	if (! $found) {
        $result = array (
                 "sectional"     => "None",
                 "grid"          => "",
                 "quadrant"      => ""
         );
	}

return($result);
}


# ===================================================================================================
# drawSectionalOptions()
# Displays <option> elements for Sectional select menu
#   Input values:
#		- Default value
#

function drawSectionalOptions($defaultVal)
{
global $coordinates;
reset($coordinates);
$output="";
$defaultVal=strtoupper(trim(preg_replace("/ /", "_", $defaultVal)));

	while (list($sectional, $info) = each($coordinates)) {
	$SELECTED="";
		if ($sectional == $defaultVal) $SELECTED=" SELECTED ";
	$displayName = ucwords(strtolower(preg_replace("/_/"," ", $sectional)));
	if ($sectional != "None") $output .= "	<option value=\"$sectional\" id=\"$sectional\" $SELECTED>$displayName</option>\n";
	}

echo $output;
}

# ===================================================================================================
# drawGridOptions()
# Displays <option> grid numbers for the selected Sectional
#   Input values:
#               - Name of selected sectional
#		- Default grid number

function drawGridOptions($sectional, $selectedGridNum)
{
global $coordinates;
reset($coordinates);

$output="";
$gridOverlap=0;
$defaultVal=strtoupper(trim(preg_replace("/ /", "_", $sectional)));
$dataset=$coordinates[$sectional];
if (isset($dataset['nullgrid'])) $gridOverlap=1;

	for ($counter=$dataset['startGrid']; $counter <= $dataset['endGrid']; $counter++){
	$SELECTED="";

		if ($counter == $selectedGridNum) {
		$SELECTED=" SELECTED ";
			if ($gridOverlap) {				# If the selected grid number is not available on the map, remove "SELECTED"
											# This may happen when changing Sectionals
				if (in_array($counter, $dataset['nullgrid'])) {
				$SELECTED = "";
				}
			}
		}


		$appended = "	<option value=\"$counter\" $SELECTED>$counter</option>\n";

		if ($gridOverlap) {
			if (in_array($counter, $dataset['nullgrid'])) {
			$appended = "";
			}
		} 

	$output .= $appended;
		
	}

## Check for a default SELECTED value
#	if (!(strpos($output, "SELECTED"))) {
#	$output = preg_replace("|<option value=(.*?)>|", '<option value=$1 SELECTED >', $output, 1);
#	}

echo $output;

}

# ===================================================================================================

# Quadrant Offset Array
# Add appropriate values to top-left lon/lat values to 
# obtain top-left lon/lat values of the quadrant

$quadrantOffsets = array (
	"A" => array (
		"lat" => "0",
		"lon" => "0"
	),
	
	"B" => array (
		"lat" => "0",
		"lon" => "0.125"
	),
	
	"C" => array (
		"lat" => "-0.125",
		"lon" => "0"
	),
	
	"D" => array (
		"lat" => "-0.125",
		"lon" => "0.125"
	)
);

# ===================================================================================================

/**
 * Get current magnetic variation (declination) for given lat/lon
 * See https://www.ngdc.noaa.gov/geomag/help/declinationHelp.html
 * Sample GET: https://www.ngdc.noaa.gov/geomag-web/calculators/calculateDeclination?lat1=40&lon1=-105.25&resultFormat=xml
 *    $lat is decimal degrees. North is positive
 *    $lon is decimal degrees. East is positive
 */
function magVariation($lat, $lon)
{
$baseUrl = "https://www.ngdc.noaa.gov/geomag-web/calculators/calculateDeclination";

# Use for the variation found on Sectional Charts
#$dateString = "minYear=2004&minMonth=6&minDay=30";

$format = "&resultFormat=xml";                  # Format of calculation results: 'html', 'csv', 'xml', or 'pdf'

$url=$baseUrl . "?lat1=" . trim($lat) .  "&lon1=" . trim($lon) .   $format;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_REFERER, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0");
$output = curl_exec($ch);
curl_close($ch);

$p = xml_parser_create();
xml_parse_into_struct($p, $output, $vals, $index);
xml_parser_free($p);

$idx = $index['DECLINATION'][0];
$decl = $vals[$idx]['value'];

$decl= - round($decl, 1);

return ($decl);

}


# ===================================================================================================
# Use geolocation services to find default sectional for current area
#   Input = empty
#   Return = full name of sectional, such as "SEATTLE";

function ourSectional()
{
$longitude="-147";
$latitude="48";

$geolocationURL="http://ipinfo.io/" . $_SERVER['REMOTE_ADDR'] . "/loc";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $geolocationURL);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);    // Timeout in 4 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
//curl_setopt($ch, CURLOPT_REFERER, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0");
$output = curl_exec($ch);
curl_close($ch);

// Split into array, Lat first, then lon
$latLonAry = explode(",", $output);
  if(count($latLonAry) == 2){
  $latitude=(float)$latLonAry[0];
  $longitude=(float)$latLonAry[1];
  }

$latlonAry = lonlat2grid($longitude, $latitude);
$sectional = $latlonAry['sectional'];

// $logfile = $_SERVER['DOCUMENT_ROOT'] . "/logs/lonlat.log";
// $fh = fopen($logfile, "a");
// $record = $latitude . "\t" . $longitude . "\t" . $sectional . "\n";
// fwrite($fh, $record);
// fclose($fh);

return($sectional);

}



# ===================================================================================================
#     DEPRECATED
# Use geolocation services to find default sectional for current area
#   Input = empty
#   Return = full name of sectional, such as "SEATTLE";

function ourSectional2()
{
$longitude="-147";
$latitude="48";
$geolocationURLs = array("https://www.sonosite.com/apps/index.php?callback=loc",
                         "http://ajaxhttpheaders.appspot.com?callback=loc");

// $geolocationURL = "http://freegeoip.net/xml/";

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $geolocationURLs[0]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);    // Timeout in 4 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_REFERER, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0");
$output = curl_exec($ch); 
curl_close($ch);    
$output = preg_replace("/loc\((.*)\)$/", "$1",  $output );
$output = str_replace("'", "\"", $output);   // json cannot handle single quotes
$json_data = (array)json_decode($output, TRUE);

if (isset($json_data['latitude'])) $latitude=$json_data['latitude'];
if (isset($json_data['longitude'])) $longitude=$json_data['longitude'];

//$p = xml_parser_create();
//xml_parse_into_struct($p, $output, $vals, $index);
//xml_parser_free($p);

//$latIdx = $index['LATITUDE'][0];
//$lonIdx = $index['LONGITUDE'][0];

//$latitude = $vals[$latIdx]['value'];
//$longitude = $vals[$lonIdx]['value'];

$latlonAry = lonlat2grid($longitude, $latitude);

$sectional = $latlonAry['sectional'];

return($sectional);

}


# ===================================================================================================
# GetSurroundingGridIDs($sectional, $gridNum, $quadrant="E", $format="dmm")
# Return array containing the grid identifiers of the surrounding grids
#
#	Input Values:   (same as grid2lonlat())
#               - Full Sectional Name
#               - Grid Number
#               - Quadrant letter (optional)
#                       If omitted, return lon/lat for the entire grid
#               - Format (optional)
#                       dmm = Degrees plus decimal minutes (default)
#                       dms = Degree Minutes Seconds
#
#	Output:
#		array(	"Center"	=> $CenterGrid,     # Each is an array from lonlat2grid()
#			"North"	  	=> $NorthGrid,      # of the form:		
#			"South"		=> $SouthGrid,      #   array(
#			"East"		=> $EastGrid,       #      "sectional"	=> Full Sectional name,
#			"West"		=> $WestGrid        #      "grid" 	=> $gridCounter,
#			);                                  #      "quadrant"	=> $quadrant
#                                                           #         );
#


function GetSurroundingGridIDs($sectional, $gridNum, $quadrant="E", $format="dmm")
{

$CenterGridCoords = grid2lonlat($sectional, $gridNum, $quadrant, "raw");
$CenterGrid = array(
		"sectional"	=> $sectional,
		"grid"		=> $gridNum,
		"quadrant"	=> $quadrant);

$NorthGridLat =  $CenterGridCoords['NE']['lat'] + 0.05;
$NorthGridLon = ($CenterGridCoords['NE']['lon'] + $CenterGridCoords['NW']['lon']) / 2;
$NorthGrid = lonlat2grid($NorthGridLon, $NorthGridLat);

$SouthGridLat =  $CenterGridCoords['SE']['lat'] - 0.05;
$SouthGridLon =  $NorthGridLon;
$SouthGrid = lonlat2grid($SouthGridLon, $SouthGridLat);

$EastGridLat = ($CenterGridCoords['NE']['lat'] + $CenterGridCoords['SE']['lat']) / 2;
$EastGridLon = $CenterGridCoords['NE']['lon'] + 0.05;
$EastGrid = lonlat2grid($EastGridLon, $EastGridLat);

$WestGridLat = $EastGridLat;
$WestGridLon = $CenterGridCoords['NW']['lon'] - 0.05;
$WestGrid = lonlat2grid($WestGridLon, $WestGridLat);

	if ($CenterGrid['quadrant'] == "E") {
	$NorthGrid['quadrant'] = "";
	$SouthGrid['quadrant'] = "";
	$EastGrid['quadrant'] = "";
	$WestGrid['quadrant'] = "";
	} else {
	$NorthGrid['quadrant'] = " - " . $NorthGrid['quadrant'];
	$SouthGrid['quadrant'] = " - " . $SouthGrid['quadrant'];
	$EastGrid['quadrant']  = " - " . $EastGrid['quadrant'];
	$WestGrid['quadrant']  = " - " . $WestGrid['quadrant'];
	}


$SurroundingGrids = array(
			"Center"	=> $CenterGrid,
			"North"	  	=> $NorthGrid,
			"South"		=> $SouthGrid,
			"East"		=> $EastGrid,
			"West"		=> $WestGrid
			);

return ($SurroundingGrids);

}

# ===================================================================================================


?>
