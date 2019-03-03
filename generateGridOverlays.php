#!/usr/bin/php
<?php
/**
 * Create Google Earth (KMZ) overlays
 * for each gridded sectional
 *
 * C. Jackson
 * Version 2.0   -- Feb 2019
 */

#$grid="SEATTLE";

include_once("includes/gridFunctions.php");
include_once("includes/coordinates1.php");
include_once("includes/styles.php");

$offset = "0.000000000001";
$altitude=45;

foreach  ($coordinates as $grid => $value) {

$coordinates[$grid]['avgLon'] = ($coordinates[$grid]['MaxLon'] + $coordinates[$grid]['MinLon']) / 2;

# Fetch and output the Styles section

$preamble= makePreamble($grid);

$view =  initialView($coordinates[$grid]);

$folder = initialFolder($coordinates[$grid]);

$grids = doGrid($coordinates[$grid]);

$gridNums = makeGridNumbers($coordinates[$grid]);

$alphaGrid = makeAlphaGrid($coordinates[$grid]);

$numberGrid = makeNumbers($coordinates[$grid]);


$ending = "			</Folder>
		</Folder>
	</Folder>
</Document>
</kml>\n";


$output = $preamble . $view . $folder . $grids . $gridNums . $alphaGrid . $numberGrid . $ending;

$fh=fopen("doc.kml", "w");
fwrite($fh,$output);
fclose($fh);
$kmzFileName = $coordinates[$grid]['Abbrev'] . "_grid.kmz";

$cmd = "/usr/bin/zip -9q $kmzFileName doc.kml";
$tmp=`$cmd`;
$cmd= "/bin/mv $kmzFileName overlays";
$tmp=`$cmd`;
echo $coordinates[$grid]['Abbrev'] . "	chart: " . $coordinates[$grid]['endGrid'] . "	Calc: " . (1 + $gridCounter - $coordinates[$grid]['startGrid']) . "	Start: " . $coordinates[$grid]['startGrid'] ; 
if ($coordinates[$grid]['endGrid'] == (1 + $gridCounter - $coordinates[$grid]['startGrid'])) echo "	OK";
echo "\n";
}




# ===================================================================================================
# Define the initial view shown by Google Earth
# Return the text string

function initialView($dataset)
{

$text="	<Folder>
		<name>Sectionals</name>
		<Region>
			<LatLonAltBox>
				<north>" . $dataset['MaxLat'] . "</north>
				<south>" . $dataset['MinLat'] . "</south>
				<east>" . $dataset['MinLon'] . "</east>
				<west>" . $dataset['MaxLon'] . "</west>
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
# Define the initial folder 
# Return the text string

function initialFolder($dataset)
{
$avgLat = ($dataset['MaxLat'] + $dataset['MinLat']) /2;

$text="		<Folder>
			<name>" . $dataset['Abbrev'] . "</name>
			<Placemark>
				<name>" . $dataset['Abbrev'] . "</name>
				<styleUrl>#SectionalRed</styleUrl>
				<LineString>
					<extrude>1</extrude>
					<tessellate>1</tessellate>
					<coordinates>";

        if (isset($dataset['BoundingBox'])){
           $text .= $dataset['BoundingBox'];
        } else {

		for ($i=$dataset['MaxLon']; $i<=$dataset['MinLon']; $i=$i + 0.25) {
		$text .= "$i," . $dataset['MaxLat'] . ",0 "; 
		}

		for ($i=$dataset['MinLon']; $i>= $dataset['MaxLon']; $i=$i - 0.25) {
       		$text .= "$i," . ($dataset['MinLat']) . ",0 ";
		}

  	    $text .= $dataset['MaxLon'] . "," . $dataset['MaxLat'] . ",0 ";
        }
        $text .= "</coordinates>
                                </LineString>
                        </Placemark>
                        <Placemark>
                                <name>" . $dataset['Abbrev'] . "</name>
                                <styleUrl>#SectionalRed</styleUrl>
                                <Point>
                                        <coordinates>" . $dataset['avgLon'] . "," . $avgLat . ",0</coordinates>
                                </Point>
                        </Placemark>
                </Folder>\n";



// Rewrite above, removing <Placemark> container

$text="         <Folder>
                        <name>" . $dataset['Abbrev'] . "</name>
			<Placemark>
				<name>" . $dataset['Abbrev'] . "</name>
				<styleUrl>#SectionalRed</styleUrl>
				<Point>
					<coordinates>" . $dataset['avgLon'] . "," . $avgLat . ",0</coordinates>
				</Point>
			</Placemark>
		</Folder>\n";

return($text);
}
# ===================================================================================================
# Create the Grids
# Return the text string

function doGrid($dataset)
{
global $offset,$gridCounter,$altitude;
$text="	<Folder>
		<name>Grids</name>
		<Folder>
			<name>" . $dataset['Abbrev'] . "</name>
			<Folder>
				<name>Grid</name>
				<Region>
					<LatLonAltBox>
						<north>" . ($dataset['MaxLat'] + 2) . "</north>
						<south>" . ($dataset['MinLat'] - 2) . "</south>
						<east>" . ($dataset['avgLon'] + 10.5) . "</east>
						<west>" . ($dataset['avgLon'] - 10.5) . "</west>
						<rotation>nan</rotation>
						<minAltitude>0</minAltitude>
						<maxAltitude>0</maxAltitude>
					</LatLonAltBox>
					<Lod>
						<minLodPixels>1800</minLodPixels>
						<maxLodPixels>-1</maxLodPixels>
						<minFadeExtent>0</minFadeExtent>
						<maxFadeExtent>0</maxFadeExtent>
					</Lod>
				</Region>\n";
$gridCounter = $dataset['startGrid'] - 1;

	for ($latCounter=$dataset['MaxLat']; $latCounter > ($dataset['MinLat']); $latCounter=$latCounter - 0.25) { 
		for ($lonCounter=$dataset['MaxLon']; $lonCounter < $dataset['MinLon']; $lonCounter = $lonCounter + 0.25) {

		$gridCounter++;
		$gridLabel = sprintf("%03d", $gridCounter);

		$placemark  = $lonCounter . "," . $latCounter . ",$altitude ";
		$placemark .= ($lonCounter + 0.25) . "," . $latCounter . ",$altitude ";
		$placemark .= ($lonCounter + 0.25) . "," . ($latCounter - 0.25 + $offset) . ",$altitude "; 
		$placemark .= $lonCounter . "," . ($latCounter - 0.25 + $offset) . ",$altitude ";
		$placemark .= $lonCounter . "," . $latCounter . ",$altitude ";
		   $skip = FALSE;
			if (isset($dataset['nullgrid'])){
				if ((in_array($gridCounter, $dataset['nullgrid']))){
                      		$skip = TRUE;
				}
			}


			if ($skip == FALSE){
 				$text .= "				<Placemark>
					<name>$gridLabel</name>
					<styleUrl>#GridRed</styleUrl>
					<LineString>
						<extrude>1</extrude>
						<tessellate>1</tessellate>
						<coordinates> $placemark </coordinates>
					</LineString>
				</Placemark>\n";
			}

		}
	# Create grid offset for sectionals that do not start at grid #1
	$gridCounter = $gridCounter + $dataset['startGrid'] - 1; 	
	}

$text .= "                        </Folder>\n";

return($text);
}

# ===================================================================================================
# Create the Grid Numbers
# Return the text string

function makeGridNumbers($dataset)
{
global $altitude;

$text = "			<Folder>
				<name>Numbers</name>
				<Region>
                                        <LatLonAltBox>
                                                <north>" . ($dataset['MaxLat'] + 2) . "</north>
                                                <south>" . ($dataset['MinLat'] - 2) . "</south>
  												<east>"  . ($dataset['avgLon'] + 10.5) . "</east>
												<west>"  . ($dataset['avgLon'] - 10.5) . "</west>
												<rotation>nan</rotation>
						<minAltitude>0</minAltitude>
						<maxAltitude>0</maxAltitude>
					</LatLonAltBox>
					<Lod>
						<minLodPixels>1800</minLodPixels>
						<maxLodPixels>20000</maxLodPixels>
						<minFadeExtent>0</minFadeExtent>
						<maxFadeExtent>0</maxFadeExtent>
					</Lod>
				</Region>
";

$gridCounter = $dataset['startGrid'] - 1;

        for ($latCounter=$dataset['MaxLat']; $latCounter > ($dataset['MinLat']); $latCounter=$latCounter - 0.25) {
                for ($lonCounter=$dataset['MaxLon']; $lonCounter < $dataset['MinLon']; $lonCounter = $lonCounter + 0.25) {

                $gridCounter++;
                $gridLabel = sprintf("%03d", $gridCounter);

                $placemark  = ($lonCounter + 0.125) . "," . ($latCounter - 0.125) . ",$altitude ";
		$skip = FALSE;
                        if (isset($dataset['nullgrid'])){
                                if ((in_array($gridCounter, $dataset['nullgrid']))){
                                $skip = TRUE;
                                }
                        }

      			if ($skip == FALSE){
			$text .= "				<Placemark>
					<name>$gridLabel</name>
					<styleUrl>#GridRed</styleUrl>
					<Point>
						<coordinates>$placemark</coordinates>
					</Point>
				</Placemark>
";
			}
		}
        # Create grid offset for sectionals that do not start at grid #1
        $gridCounter = $gridCounter + $dataset['startGrid'] - 1;

	}

$text .= "                        </Folder>\n";

return($text);
}


# ===================================================================================================
# Create the Alpha Grid
# Return the text string

function makeAlphaGrid($dataset)
{

global $offset, $altitude;

$text = "                       <Folder>
                               <name>Alpha Grid</name>
                                <Region>
                                        <LatLonAltBox>
                                                <north>" . ($dataset['MaxLat'] + 2) . "</north>
                                                <south>" . ($dataset['MinLat'] - 2) . "</south>
                                            	<east>"  . ($dataset['avgLon'] + 10.5) . "</east>
												<west>"  . ($dataset['avgLon'] - 10.5) . "</west>
                                                <rotation>nan</rotation>
                                                <minAltitude>0</minAltitude>
                                                <maxAltitude>0</maxAltitude>
                                        </LatLonAltBox>
                                        <Lod>
                                                <minLodPixels>13000</minLodPixels>
                                                <maxLodPixels>-1</maxLodPixels>
                                                <minFadeExtent>0</minFadeExtent>
                                                <maxFadeExtent>0</maxFadeExtent>
                                        </Lod>
                                </Region>
";


$gridCounter = $dataset['startGrid'] - 1;

        for ($latCounter=$dataset['MaxLat']; $latCounter > ($dataset['MinLat']); $latCounter=$latCounter - 0.25) {
                for ($lonCounter=$dataset['MaxLon']; $lonCounter < $dataset['MinLon']; $lonCounter = $lonCounter + 0.25) {

                $gridCounter++;
                $gridLabel = sprintf("%03d", $gridCounter);

		$placemark  = ($lonCounter + 0.125) . "," . $latCounter . ",$altitude ";
		$placemark .= ($lonCounter + 0.125) . "," . ($latCounter - 0.25 + $offset) . ",$altitude ";
		$placemark .= ($lonCounter + 0.25) . "," . ($latCounter - 0.25 + $offset) . ",$altitude ";
		$placemark .= ($lonCounter + 0.25) . "," . ($latCounter - 0.125)  . ",$altitude ";
		$placemark .= $lonCounter . "," . ($latCounter - 0.125)  . ",$altitude ";

		$text .= "					<Placemark>
						<name>$gridLabel</name>
						<styleUrl>#AlphaGridRed</styleUrl>
						<LineString>
							<extrude>1</extrude>
							<tessellate>1</tessellate>
							<coordinates> $placemark </coordinates>
						</LineString>
					</Placemark>\n";

		}
        # Create grid offset for sectionals that do not start at grid #1
        $gridCounter = $gridCounter + $dataset['startGrid'] - 1;

	}


$text .= "                        </Folder>\n";

return($text);
}


# ===================================================================================================
# Create the Numbers
# Return the text string

function makeNumbers($dataset)
{
global $altitude;

$gridLetter=array("A", "B", "C", "D");

$text = "				<Folder>
					<name>Numbers</name>
					<Region>
						<LatLonAltBox>
                                                	<north>" . ($dataset['MaxLat'] + 2) . "</north>
                                                	<south>" . ($dataset['MinLat'] - 2) . "</south>
                                                	<east>"  . ($dataset['avgLon'] + 10.5) . "</east>
													<west>"  . ($dataset['avgLon'] - 10.5) . "</west>
							<rotation>nan</rotation>
							<minAltitude>0</minAltitude>
							<maxAltitude>0</maxAltitude>
						</LatLonAltBox>
						<Lod>
							<minLodPixels>20000</minLodPixels>
							<maxLodPixels>-1</maxLodPixels>
							<minFadeExtent>0</minFadeExtent>
							<maxFadeExtent>0</maxFadeExtent>
						</Lod>
					</Region>\n";

$gridCounter = $dataset['startGrid'] - 1;

        for ($latCounter=$dataset['MaxLat']; $latCounter > ($dataset['MinLat']); $latCounter=$latCounter - 0.25) {
                for ($lonCounter=$dataset['MaxLon']; $lonCounter < $dataset['MinLon']; $lonCounter = $lonCounter + 0.25) {
                $gridCounter++;

			for ($quadrant=0; $quadrant < 4; $quadrant++) {

				if (($quadrant==0) OR ($quadrant==2)) {
				$lonOffset = 0.0625;
				} else {
				$lonOffset = 0.1875;
				}

				if ($quadrant < 2) {
				$latOffset = 0.0625;
				} else {
				$latOffset = 0.1875;
				}

			$placemark = ($lonCounter + $lonOffset) . "," . ($latCounter - $latOffset) . ",$altitude ";
			$gridLabel = sprintf("%03d %s", $gridCounter, $gridLetter[$quadrant]);

			$text .= "					<Placemark>
						<name>$gridLabel</name>
						<styleUrl>#AlphaGridRed</styleUrl>
						<Point>
							<coordinates>$placemark</coordinates>
						</Point>
					</Placemark>\n";
			}
		}
        # Create grid offset for sectionals that do not start at grid #1
        $gridCounter = $gridCounter + $dataset['startGrid'] - 1;

	}		
 
$text .= "				</Folder>\n";

return($text);
}


# ===================================================================================================
