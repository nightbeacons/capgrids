<?php
# Grid Functions

include_once("coordinates.php");

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

# ===================================================================================================
# changeFormat($decDegree,$format)
#
# Changes a degree value of the form XX.yyy into Degrees + Decimal Minutes (dmm)
#                                             or Degrees Minutes Seconds (dms)     
#

function changeFormat($decDegree, $format)
{

$intDeg=(int)($decDegree);
$decimalMinutes=sprintf("%01.3f", (60 * abs($decDegree - $intDeg) + 0.00005));

$intDecimalMinutes = (int)($decimalMinutes);
$decimalsSeconds = sprintf("%01.2f", (60 * abs((60 * abs($decDegree - $intDeg)) - $intDecimalMinutes) + 0.00005));

switch($format) {
	case "dmm":
		$result = "$intDeg&deg; $decimalMinutes'";
		break;

	case "dms":
		$result = "$intDeg&deg $intDecimalMinutes' $decimalsSeconds\"";
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

function lonlat2grid($longitude, $latitude)
{
global $coordinates,$quadrantOffsets;

$quadAry = array("A","B","C","D");

$map = $result = array();
reset($coordinates);
$gridCounter=$lonCounter=$latCounter = 0;
$quadrant=-1;

	# Determine the Sectional Chart
	 
	while (list($sectional, $info) = each($coordinates))  {
    #echo "Key: $sectional; Value: $info<br />\n";
		if (($longitude < $info['MinLon']) AND ($longitude > $info['MaxLon']) AND
		    ($latitude  > $info['MinLat']) AND ($latitude  < $info['MaxLat'])) {
		    	$map[]= $sectional;
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
	
		while ((!$found) AND ($gridCounter <= $dataset['endGrid'])) {
			if (($longitude > $lonCounter) AND ($longitude < ($lonCounter + 0.25)) AND
			    ($latitude  < $latCounter) AND ($latitude  > $latCounter - 0.25)) {
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


	$gridCenterLat = $latCounter - 0.125;
	$gridCenterLon = $lonCounter + 0.125;
	$quadrant  = $quadAry[(($latitude < $gridCenterLat)*2 + ($longitude > $gridCenterLon))];

	$result = array (
	 	"sectional"	=> $currSectional,
	 	"grid" 		=> $gridCounter,
	 	"quadrant"	=> $quadrant
	);

	}


echo "$longitude :: $latitude at Grid $gridCounter $quadrant\nNW corner = $lonCounter  $latCounter\n";

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
	$output .= "	<option value=\"$sectional\" $SELECTED>$displayName</option>\n";
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
$defaultVal=strtoupper(trim(preg_replace("/ /", "_", $sectional)));
$dataset=$coordinates[$sectional];

for ($counter=$dataset['startGrid']; $counter <= $dataset['endGrid']; $counter++){
$SELECTED="";
if ($counter == $selectedGridNum) $SELECTED=" SELECTED ";
$output .= "	<option value=\"$counter\" $SELECTED>$counter</option>\n";
}

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
