<?php
# Coordinate Data File
#
# MinLon = Longitude of the eastern-most boundary
# MaxLon = Longitude of the western-most boundary
# MinLat = Latitude of the southern-most boundary
# MaxLat = Latitude of the northern-most boundary
# startGrid = Beginning grid number. (Normally 1)
# endGrid   = Ending grid number
# nullgrid = array of grid numbers that do not exist for that sectional
#            Example: Grid numbers 1, 2, 3, and 4 do not exist on the PHX sectional

# Verified:
# SEA

$coordinates = array (

	"None" => array(
		"Abbrev" => "None",
             "MinLon" => "0",
             "MaxLon" => "0",
             "MinLat" => "0",
             "MaxLat" => "0",
             "startGrid" => "1",
             "endGrid"  => "1"
	),

	"ALBUQUERQUE" => array (
		 "Abbrev" => "ABQ",
	     "MinLon" => "-102",
	     "MaxLon" => "-109",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "448",
		"nullgrid" => array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20)
	),
	
	"ATLANTA" => array (
		 "Abbrev" => "ATL",
	     "MinLon" => "-81",
	     "MaxLon" => "-88",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
	"BILLINGS" => array (
		 "Abbrev" => "BIL",
	     "MinLon" => "-101",
	     "MaxLon" => "-109",
	     "MinLat" => "44.5",
	     "MaxLat" => "49",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"BROWNSVILLE" => array (
		 "Abbrev" => "BRO",
	     "MinLon" => "-97",
	     "MaxLon" => "-103",
	     "MinLat" => "24",
	     "MaxLat" => "28",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"CHARLOTTE" => array (
		 "Abbrev" => "CLT",
	     "MinLon" => "-75",
	     "MaxLon" => "-81",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"CHEYENNE" => array (
		 "Abbrev" => "CYS",
	     "MinLon" => "-101",
	     "MaxLon" => "-109",
	     "MinLat" => "40",
	     "MaxLat" => "44.5",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"CHICAGO" => array (
		 "Abbrev" => "ORD",
	     "MinLon" => "-85",
	     "MaxLon" => "-93",
	     "MinLat" => "40",
	     "MaxLat" => "44",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"CINCINNATI" => array (
		 "Abbrev" => "LUK",
	     "MinLon" => "-78",
	     "MaxLon" => "-85",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448",
		"nullgrid" => array(1,2,3,4, 29,30,31,32, 57,58,59,60, 85,86,87,88, 113,114,115,116, 141,142,143,144, 169,170,171,172, 197,198,199,200, 225,226,227,228, 253,254,255,256, 281,282,283,284, 309,310,311,312, 337,338,339,340, 365,366,367,368, 393,394,395,396, 421,422,423,424 )
	),
	
	"DALLAS_FTWORTH" => array (
		 "Abbrev" => "DFW",
	     "MinLon" => "-95",
	     "MaxLon" => "-102",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
	"DENVER" => array (
		 "Abbrev" => "DEN",
	     "MinLon" => "-104",
	     "MaxLon" => "-111",
	     "MinLat" => "35.75",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "476"
	),
	
	"DETROIT" => array (
		 "Abbrev" => "DET",
	     "MinLon" => "-77",
	     "MaxLon" => "-85",
	     "MinLat" => "40",
	     "MaxLat" => "44",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"EL_PASO" => array (
		 "Abbrev" => "ELP",
	     "MinLon" => "-103",
	     "MaxLon" => "-109",
	     "MinLat" => "28",
	     "MaxLat" => "32",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"GREAT_FALLS" => array (
		 "Abbrev" => "GTF",
	     "MinLon" => "-109",
	     "MaxLon" => "-117",
	     "MinLat" => "44.5",
	     "MaxLat" => "49",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"GREEN_BAY" => array (
		 "Abbrev" => "GRB",
	     "MinLon" => "-85",
	     "MaxLon" => "-93",
	     "MinLat" => "44",
	     "MaxLat" => "48.25",
	     "startGrid" => "1",
	     "endGrid"  => "544"
	),
	
	"HALIFAX" => array (
		 "Abbrev" => "HFX",
	     "MinLon" => "-61",
	     "MaxLon" => "-69",
	     "MinLat" => "44",
	     "MaxLat" => "48",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"HOUSTON" => array (
		 "Abbrev" => "HOU",
	     "MinLon" => "-91",
	     "MaxLon" => "-97",
	     "MinLat" => "28",
	     "MaxLat" => "32",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"JACKSONVILLE" => array (
		 "Abbrev" => "JAX",
	     "MinLon" => "-79",
	     "MaxLon" => "-85",
	     "MinLat" => "28",
	     "MaxLat" => "32",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"KANSAS_CITY" => array (
		 "Abbrev" => "MKC",
	     "MinLon" => "-90",
	     "MaxLon" => "-97",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
	"KLAMATH_FALLS" => array (
		 "Abbrev" => "LMT",
	     "MinLon" => "-117",
	     "MaxLon" => "-125",
	     "MinLat" => "40",
	     "MaxLat" => "44.5",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"LAKE_HURON" => array (
		 "Abbrev" => "LHN",
	     "MinLon" => "-77",
	     "MaxLon" => "-85",
	     "MinLat" => "44",
	     "MaxLat" => "48",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"LAS_VEGAS" => array (
		 "Abbrev" => "LAS",
	     "MinLon" => "-111",
	     "MaxLon" => "-118",
	     "MinLat" => "35.75",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "476",
		"nullgrid" => array(449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460)
	),
	
	"LOS_ANGELES" => array (
		 "Abbrev" => "LAX",
	     "MinLon" => "-115",
	     "MaxLon" => "-121.5",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "416"
	),
	
	"MEMPHIS" => array (
		 "Abbrev" => "MEM",
	     "MinLon" => "-88",
	     "MaxLon" => "-95",
	     "MinLat" => "32",
	     "MaxLat" => "36",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
	"MIAMI" => array (
		 "Abbrev" => "MIA",
	     "MinLon" => "-77",
	     "MaxLon" => "-83",
	     "MinLat" => "24",
	     "MaxLat" => "28",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"MONTREAL" => array (
		 "Abbrev" => "MON",
	     "MinLon" => "-69",
	     "MaxLon" => "-77",
	     "MinLat" => "44",
	     "MaxLat" => "48",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"NEW_ORLEANS" => array (
		 "Abbrev" => "MSY",
	     "MinLon" => "-85",
	     "MaxLon" => "-91",
	     "MinLat" => "28",
	     "MaxLat" => "32",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"NEW_YORK" => array (
		 "Abbrev" => "NYC",
	     "MinLon" => "-69",
	     "MaxLon" => "-77",
	     "MinLat" => "40",
	     "MaxLat" => "44",
	     "startGrid" => "1",
	     "endGrid"  => "512"
	),
	
	"OMAHA" => array (
		 "Abbrev" => "OMA",
	     "MinLon" => "-93",
	     "MaxLon" => "-101",
	     "MinLat" => "40",
	     "MaxLat" => "44.5",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"PHOENIX" => array (
		 "Abbrev" => "PHX",
	     "MinLon" => "-109",
	     "MaxLon" => "-116",
	     "MinLat" => "31.25",
	     "MaxLat" => "35.75",
	     "startGrid" => "1",
	     "endGrid"  => "504",
		"nullgrid" => array(1,2,3,4, 29,30,31,32, 57,58,59,60, 85,86,87,88, 113,114,115,116, 141,142,143,144, 169,170,171,172, 197,198,199,200, 225,226,227,228, 253,254,255,256, 281,282,283,284, 309,310,311,312, 337,338,339,340, 365,366,367,368, 393,394,395,396)
	),
	
	"SALT_LAKE_CITY" => array (
		 "Abbrev" => "SLC",
	     "MinLon" => "-109",
	     "MaxLon" => "-117",
	     "MinLat" => "40",
	     "MaxLat" => "44.5",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"SAN_ANTONIO" => array (
		 "Abbrev" => "SAT",
	     "MinLon" => "-97",
	     "MaxLon" => "-103",
	     "MinLat" => "28",
	     "MaxLat" => "32",
	     "startGrid" => "1",
	     "endGrid"  => "384"
	),
	
	"SAN_FRANCISCO" => array (
		 "Abbrev" => "SFO",
	     "MinLon" => "-118",
	     "MaxLon" => "-125",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
	"SEATTLE" => array (
		 "Abbrev" => "SEA",
	     "MinLon" => "-117",
	     "MaxLon" => "-125",
	     "MinLat" => "44.5",
	     "MaxLat" => "49",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"ST_LOUIS" => array (
		 "Abbrev" => "STL",
	     "MinLon" => "-84",
	     "MaxLon" => "-91",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448",
		"nullgrid" => array(1,2,3,4, 29,30,31,32, 57,58,59,60, 85,86,87,88, 113,114,115,116, 141,142,143,144, 169,170,171,172, 197,198,199,200, 225,226,227,228, 253,254,255,256, 281,282,283,284, 309,310,311,312, 337,338,339,340, 365,366,367,368, 393,394,395,396, 421,422,423,424)
	),
	
	"TWIN_CITIES" => array (
		 "Abbrev" => "MSP",
	     "MinLon" => "-93",
	     "MaxLon" => "-101",
	     "MinLat" => "44.5",
	     "MaxLat" => "49",
	     "startGrid" => "1",
	     "endGrid"  => "576"
	),
	
	"WASHINGTON" => array (
		 "Abbrev" => "DCA",
	     "MinLon" => "-72",
	     "MaxLon" => "-79",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448",
		"nullgrid" => array(1,2,3,4, 29,30,31,32, 57,58,59,60, 85,86,87,88, 113,114,115,116, 141,142,143,144, 169,170,171,172, 197,198,199,200, 225,226,227,228, 253,254,255,256, 281,282,283,284, 309,310,311,312, 337,338,339,340, 365,366,367,368, 393,394,395,396, 421,422,423,424)
	),
	
	"WICHITA" => array (
	     "Abbrev" => "ICT",
	     "MinLon" => "-97",
	     "MaxLon" => "-104",
	     "MinLat" => "36",
	     "MaxLat" => "40",
	     "startGrid" => "1",
	     "endGrid"  => "448"
	),
	
);

?>
