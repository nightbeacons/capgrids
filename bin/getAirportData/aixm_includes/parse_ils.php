<?php

/**
 * parseIlsFile($file)
 *  Line-by-line, parses the flat-text ILS.txt file
 *  using array of strpos values
 *  Input: Path to ILS.txt
 *     "start" array element is the data file's "START" value - 1
 */

function parseIlsFile($file){
global $db;

$stringPositions = initIlsStringPositions();

$query = "DELETE FROM ils";
$try = $db->query($query);
$try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_loc_data_coord");
$try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_gs_data_coord");
$try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_dme_data_coord");

$fh = fopen($file, "r");
   if ($fh){

         while ($line = fgets($fh)){
         $ilsData = array();
         $type = intval(trim(substr($line, 3, 1)));    // Type of record, 1 - 5
         $airport_record_id = trim(substr($line, 4, 11));
         $escaped_airport_record_id = $db->real_escape_string($airport_record_id);
         $q_init = "INSERT INTO ils(airport_site_id) VALUES ('" . $escaped_airport_record_id . "')
                    ON DUPLICATE KEY UPDATE airport_site_id=airport_site_id";

         $try = $db->query($q_init);
            // Put each value into an array, so data can be manipulated before inserting into DB
         if ($type < 5) {
            foreach($stringPositions[$type] as $key => $position){
               $value = trim(substr($line, $position['start'], $position['length']));
               $ilsData[$airport_record_id][trim($key)] = $value;
            }

            switch ($type){
              case 2:
                $ilsData[$airport_record_id]['loc_decLatitude']  = convertToDecimalDegrees($ilsData[$airport_record_id]['loc_latitude']);
                $ilsData[$airport_record_id]['loc_decLongitude'] = convertToDecimalDegrees($ilsData[$airport_record_id]['loc_longitude']);
              break;

              case 3:
                $ilsData[$airport_record_id]['gs_decLatitude']  = convertToDecimalDegrees($ilsData[$airport_record_id]['gs_latitude']);
                $ilsData[$airport_record_id]['gs_decLongitude'] = convertToDecimalDegrees($ilsData[$airport_record_id]['gs_longitude']);
              break;

              case 4:
                $ilsData[$airport_record_id]['dme_decLatitude']  = convertToDecimalDegrees($ilsData[$airport_record_id]['dme_latitude']);
                $ilsData[$airport_record_id]['dme_decLongitude'] = convertToDecimalDegrees($ilsData[$airport_record_id]['dme_longitude']);
              break;
            } // end of switch

         }  // end of "if type < 5"

      foreach ($ilsData as $airport_record_id => $record_value) {
        $query = "UPDATE ils SET \n";
        foreach ($record_value as $key => $value) {
          $query .= "   `" . trim($key) . "` = '" . $db->real_escape_string($value) . "',\n";
        }
        $query = rtrim($query, " \n,");
        $query .= " WHERE airport_site_id='" . $airport_record_id . "'";
        if (($try = $db->query($query)) === FALSE) {
          printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
          exit();
        }
      }
    } // End of while ($line = fgets($fh)
    $try = $db->query("UPDATE ils set loc_coordinates=Point(loc_decLongitude, loc_decLatitude)");
    $try = $db->query("UPDATE ils set gs_coordinates =Point(gs_decLongitude, gs_decLatitude)");
    $try = $db->query("UPDATE ils set dme_coordinates=Point(dme_decLongitude, dme_decLatitude)");

    $try = $db->query("create spatial index ix_spatial_loc_data_coord ON ils(loc_coordinates)");
    $try = $db->query("create spatial index ix_spatial_gs_data_coord  ON ils(gs_coordinates)");
    $try = $db->query("create spatial index ix_spatial_dme_data_coord ON ils(dme_coordinates)");
   } else {
     echo "Cannot open ILS file $file\n";
   }
   fclose($fh);
}



/**
 * Initialize ithe ILS Position array
 * Return the array
 */
function initIlsStringPositions(){
$stringPositions = array();

$stringPositions[1] = array(
    "aixm_id"          => array("start" => 0,   "length" => 4), // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),// AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )	
    "ils_identifier"   => array("start" => 28,  "length" => 4), // IDENTIFIER IS PREFIXED BY I-
    "airport"          => array("start" => 159, "length" => 4), // AIRPORT IDENTIFIER
    "category"         => array("start" => 172, "length" => 9), // CATEGORY OF THE ILS. (I,II,IIIA)
    "approach_bearing" => array("start" => 281, "length" => 6), // ILS APPROACH BEARING IN DEGREES MAGNETIC
    "mag_variation"    => array("start" => 287, "length" => 6), // Mag variation (Ex: 09E)
    );

$stringPositions[2] = array(                                     // ILS Record type #2 is LOC data
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "ops_status_loc"   => array("start" => 28,  "length" => 22), // OPERATIONAL STATUS OF LOCALIZER (OPERATIONAL IFR, DECOMMISSIONED...) 
    "loc_latitude"     => array("start" => 60,  "length" => 14), // LATITUDE OF LOCALIZER ANTENNA.(FORMATTED)
    "loc_decLatitude"  => array("start" => 74,  "length" => 11), // LATITUDE OF LOCALIZER ANTENNA.(ALL SECONDS)
    "loc_longitude"    => array("start" => 85,  "length" => 14), // LONGITUDE OF LOCALIZER ANTENNA.(FORMATTED)
    "loc_decLongitude" => array("start" => 99,  "length" => 11), // LONGITUDE OF LOCALIZER ANTENNA.(ALL SECONDS)
    "loc_elevation_10" => array("start" => 126, "length" => 7),  // ELEVATION OF LOCALIZER ANTENNA IN TENTH OF A FOOT (MSL)
    "loc_frequency"    => array("start" => 133, "length" => 7),  // LOCALIZER FREQUENCY (MHZ). (EX: 108.10)
    );

$stringPositions[3] = array(                                     // ILS Record type #3 is GS data
    "ops_status_gs"   => array("start" => 28,  "length" => 22),  // OPERATIONAL STATUS OF GS (OPERATIONAL IFR, DECOMMISSIONED...)
    "gs_latitude"     => array("start" => 60,  "length" => 14),  // LATITUDE OF GLIDESLOPE ANTENNA.(FORMATTED)
    "gs_decLatitude"  => array("start" => 74,  "length" => 11),  // LATITUDE OF GLIDESLOPE ANTENNA.(ALL SECONDS)
    "gs_longitude"    => array("start" => 85,  "length" => 14),  // LONGITUDE OF GLIDESLOPE ANTENNA.(FORMATTED)
    "gs_decLongitude" => array("start" => 99,  "length" => 11),  // LONGITUDE OF GLIDESLOPE ANTENNA.(ALL SECONDS)
    "gs_elevation_10" => array("start" => 126, "length" => 7),   // ELEVATION OF GLIDESLOPE ANTENNA IN TENTH OF A FOOT (MSL)
    "gs_type"         => array("start" => 133, "length" => 15),  // GLIDE SLOPE CLASS/TYPE ("GLIDE SLOPE" or "GLIDE SLOPE/DME")
    "gs_angle"        => array("start" => 148, "length" => 5),   // GLIDE SLOPE ANGLE IN DEGREES AND HUNDREDTHS OF DEGREE.(EX:  2.75)
    "gs_frequency"    => array("start" => 153, "length" => 7),   // GS FREQUENCY (MHZ). (EX: 108.10)
    );

$stringPositions[4] = array(                                      // ILS Record type #4 is DME data
    "ops_status_dme"   => array("start" => 28,  "length" => 22),  // OPERATIONAL STATUS OF DME (OPERATIONAL IFR, DECOMMISSIONED...)
    "dme_latitude"     => array("start" => 60,  "length" => 14),  // LATITUDE OF DME ANTENNA.(FORMATTED)
    "dme_decLatitude"  => array("start" => 74,  "length" => 11),  // LATITUDE OF DME ANTENNA.(ALL SECONDS)
    "dme_longitude"    => array("start" => 85,  "length" => 14),  // LONGITUDE OF DME ANTENNA.(FORMATTED)
    "dme_decLongitude" => array("start" => 99,  "length" => 11),  // LONGITUDE OF DME ANTENNA.(ALL SECONDS)
    "dme_elevation_10" => array("start" => 126, "length" => 7),   // ELEVATION OF DME ANTENNA IN TENTH OF A FOOT (MSL)
    "dme_channel"      => array("start" => 133, "length" => 4),   // CHANNEL ON WHICH DISTANCE DATA IS TRANSMITTED (EX:  032X, 038X)
    );

$stringPositions[5] = array(                                      // ILS Record type #5 is Marker Beacon data
    "airport_site_id"  => array("start" => 4,   "length" => 11),  // AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "mb_type"          => array("start" => 28,  "length" => 2),   // MB Type (IM, MM, OM) 
    "mb_status_dme"    => array("start" => 30,  "length" => 22),  // OPERATIONAL STATUS OF MB (OPERATIONAL IFR, DECOMMISSIONED...)
    "mb_latitude"      => array("start" => 62,  "length" => 14),  // LATITUDE OF MB.(FORMATTED)
    "mb_decLatitude"   => array("start" => 76,  "length" => 11),  // LATITUDE OF MB.(ALL SECONDS)
    "mb_longitude"     => array("start" => 87,  "length" => 14),  // LONGITUDE OF MB.(FORMATTED)
    "mb_decLongitude"  => array("start" => 101, "length" => 11),  // LONGITUDE OF MB.(ALL SECONDS)
    "mb_elevation_10"  => array("start" => 128, "length" => 7),   // ELEVATION OF MB IN TENTH OF A FOOT (MSL)
    "mb_facility"      => array("start" => 135, "length" => 15),  // FACILITY/TYPE OF MARKER/LOCATOR (Ex: MARKER, NDB)
    "mb_name"          => array("start" => 182, "length" => 3),   // NAME OF THE MARKER LOCATOR BEACON (Ex. VIOLE)
    );

return($stringPositions);
}
