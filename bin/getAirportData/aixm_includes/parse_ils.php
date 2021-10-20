<?php

/**
 * parseIlsFile($file)
 *  Line-by-line, parses the flat-text ILS.txt file
 *  using array of strpos values
 *  Input: Path to ILS.txt
 *     "start" array element is the data file's "START" value - 1
 */

function parseIlsFile($file) {
  global $db;

  $stringPositions = initIlsStringPositions();

  $try = $db->query("DELETE FROM ils");
  $try = $db->query("DELETE FROM markerbeacon");
  $try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_loc_data_coord");
  $try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_gs_data_coord");
  $try = $db->query("ALTER TABLE ils DROP INDEX ix_spatial_dme_data_coord");
  $try = $db->query("ALTER TABLE markerbeacon DROP INDEX ix_spatial_mb_data_coord");

  $fh = fopen($file, "r");
  if ($fh) {

    while ($line = fgets($fh)) {
      $ilsData = [];
      $mbData  = [];
      // Type of record, 1 - 5.
      $type = intval(trim(substr($line, 3, 1)));

      if ($type < 5) {

        $airport_record_id = trim(substr($line, 4, 11));
        $escaped_airport_record_id = $db->real_escape_string($airport_record_id);
        $runway_end = trim(substr($line, 15, 3));
        $ils_type = trim(substr($line, 18, 10)); 
        $ils_unique_key = $airport_record_id . "+" . $runway_end . "+" . $ils_type; 
        $q_init = "INSERT INTO ils(ils_unique_key) VALUES ('" . $ils_unique_key . "')
                    ON DUPLICATE KEY UPDATE ils_unique_key=ils_unique_key";

        $try = $db->query($q_init);
        // Put each value into an array, so data can be manipulated before inserting into DB.
        foreach ($stringPositions[$type] as $key => $position) {
          $value = trim(substr($line, $position['start'], $position['length']));
          $ilsData[$ils_unique_key][trim($key)] = $value;
        }

        switch ($type) {
          case 2:
            $ilsData[$ils_unique_key]['loc_decLatitude']  = convertToDecimalDegrees($ilsData[$ils_unique_key]['loc_latitude']);
            $ilsData[$ils_unique_key]['loc_decLongitude'] = convertToDecimalDegrees($ilsData[$ils_unique_key]['loc_longitude']);
            break;

          case 3:
            $ilsData[$ils_unique_key]['gs_decLatitude']  = convertToDecimalDegrees($ilsData[$ils_unique_key]['gs_latitude']);
            $ilsData[$ils_unique_key]['gs_decLongitude'] = convertToDecimalDegrees($ilsData[$ils_unique_key]['gs_longitude']);
            break;

          case 4:
            $ilsData[$ils_unique_key]['dme_decLatitude']  = convertToDecimalDegrees($ilsData[$ils_unique_key]['dme_latitude']);
            $ilsData[$ils_unique_key]['dme_decLongitude'] = convertToDecimalDegrees($ilsData[$ils_unique_key]['dme_longitude']);
            break;
        } // end of switch

        foreach ($ilsData as $ils_unique_key => $record_value) {
          $query = "UPDATE ils SET \n";
          foreach ($record_value as $key => $value) {
            $query .= "   `" . trim($key) . "` = '" . trim($db->real_escape_string($value)) . "',\n";
          }
          $query = rtrim($query, " \n,");
          $query .= " WHERE ils_unique_key='" . $ils_unique_key . "'";
          if (($try = $db->query($query)) === FALSE) {
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
          }
        }

      } elseif ($type ==5) { 
        // Handle marker Beacons
	// Put each value into an array, so data can be manipulated before inserting into DB
            foreach($stringPositions[$type] as $key => $position){
            $value = trim(substr($line, $position['start'], $position['length']));
            $mbData[trim($key)] = $value;
            }
            $airport_record_id = trim($mbData['airport_site_id']);
            $escaped_airport_record_id = $db->real_escape_string($airport_record_id);
            $runway_end = trim($mbData['runway_end_id']);
            $ils_type = trim($mbData['ils_system_type']);
            $mbData['ils_unique_key'] = $airport_record_id . "+" . $runway_end . "+" . $ils_type;
            $mbData['mb_decLatitude']  = convertToDecimalDegrees($mbData['mb_latitude']);
            $mbData['mb_decLongitude'] = convertToDecimalDegrees($mbData['mb_longitude']);


         $query = "INSERT INTO markerbeacon SET \n";
            foreach($mbData as $key => $value){
            $query .= "   `" . trim($key) . "` = '" . trim($db->real_escape_string($value)) . "',\n";
            }
         $query = rtrim($query, " \n,");
            if (($try = $db->query($query))===false){
            printf("Invalid query: %s\nWhole query: %s\n", $db->error, $query);
            exit();
            }

      } // end of "if type < 5"

    } // End of while ($line = fgets($fh)
    $try = $db->query("UPDATE ils set loc_coordinates=Point(loc_decLongitude, loc_decLatitude)");
    $try = $db->query("UPDATE ils set gs_coordinates =Point(gs_decLongitude, gs_decLatitude)");
    $try = $db->query("UPDATE ils set dme_coordinates=Point(dme_decLongitude, dme_decLatitude)");
    $try = $db->query("UPDATE markerbeacon set mb_coordinates=Point(mb_decLongitude, mb_decLatitude)");

    $try = $db->query("create spatial index ix_spatial_loc_data_coord ON ils(loc_coordinates)");
    $try = $db->query("create spatial index ix_spatial_gs_data_coord  ON ils(gs_coordinates)");
    $try = $db->query("create spatial index ix_spatial_dme_data_coord ON ils(dme_coordinates)");
    $try = $db->query("create spatial index ix_spatial_mb_data_coord ON markerbeacon(mb_coordinates)");
    $try = $db->query("UPDATE ils set dme_bias = 0 where dme_bias=''");
  }
  else {
    echo "Cannot open ILS file $file\n";
  }
  fclose($fh);

// Add to the ils table
   $query = "UPDATE ils 
            INNER JOIN apt ON ils.airport_site_id = apt.aixm_key
            SET ils.ICAOcode = apt.ICAOcode";
   $try = $db->query($query);

// Add to markerbeacon table
   $query = "UPDATE markerbeacon
            INNER JOIN apt ON markerbeacon.airport_site_id = apt.aixm_key
            SET markerbeacon.ICAOcode = apt.ICAOcode";
   $try = $db->query($query);

   $query = "UPDATE markerbeacon
             INNER JOIN ils ON markerbeacon.ils_unique_key = ils.ils_unique_key
             SET markerbeacon.approach_bearing = ils.approach_bearing, 
                 markerbeacon.mag_variation=ils.mag_variation";
   $try = $db->query($query);
}


/**
 * Initialize ithe ILS Position array
 * Return the array
 * All records contain aixm_id, airport_site_id, runway_end_id and system_type
 */
function initIlsStringPositions(){
$stringPositions = array();

$stringPositions[1] = array(
    "aixm_id"          => array("start" => 0,   "length" => 4), // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),// AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )	
    "ils_identifier"   => array("start" => 28,  "length" => 6), // IDENTIFIER IS PREFIXED BY I-
    "airport"          => array("start" => 159, "length" => 4), // AIRPORT IDENTIFIER
    "category"         => array("start" => 172, "length" => 9), // CATEGORY OF THE ILS. (I,II,IIIA)
    "approach_bearing" => array("start" => 281, "length" => 6), // ILS APPROACH BEARING IN DEGREES MAGNETIC
    "mag_variation"    => array("start" => 287, "length" => 6), // Mag variation (Ex: 09E)
    );

$stringPositions[2] = array(                                     // ILS Record type #2 is LOC data
    "aixm_id"          => array("start" => 0,   "length" => 4), // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),// AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )
    "ops_status_loc"   => array("start" => 28,  "length" => 22), // OPERATIONAL STATUS OF LOCALIZER (OPERATIONAL IFR, DECOMMISSIONED...) 
    "loc_latitude"     => array("start" => 60,  "length" => 14), // LATITUDE OF LOCALIZER ANTENNA.(FORMATTED)
    "loc_decLatitude"  => array("start" => 74,  "length" => 11), // LATITUDE OF LOCALIZER ANTENNA.(ALL SECONDS)
    "loc_longitude"    => array("start" => 85,  "length" => 14), // LONGITUDE OF LOCALIZER ANTENNA.(FORMATTED)
    "loc_decLongitude" => array("start" => 99,  "length" => 11), // LONGITUDE OF LOCALIZER ANTENNA.(ALL SECONDS)
    "loc_elevation_10" => array("start" => 126, "length" => 7),  // ELEVATION OF LOCALIZER ANTENNA IN TENTH OF A FOOT (MSL)
    "loc_frequency"    => array("start" => 133, "length" => 7),  // LOCALIZER FREQUENCY (MHZ). (EX: 108.10)
    );

$stringPositions[3] = array(                                     // ILS Record type #3 is GS data
    "aixm_id"          => array("start" => 0,   "length" => 4), // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),// AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )
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
    "aixm_id"          => array("start" => 0,   "length" => 4), // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),// AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )
    "ops_status_dme"   => array("start" => 28,  "length" => 22),  // OPERATIONAL STATUS OF DME (OPERATIONAL IFR, DECOMMISSIONED...)
    "dme_latitude"     => array("start" => 60,  "length" => 14),  // LATITUDE OF DME ANTENNA.(FORMATTED)
    "dme_decLatitude"  => array("start" => 74,  "length" => 11),  // LATITUDE OF DME ANTENNA.(ALL SECONDS)
    "dme_longitude"    => array("start" => 85,  "length" => 14),  // LONGITUDE OF DME ANTENNA.(FORMATTED)
    "dme_decLongitude" => array("start" => 99,  "length" => 11),  // LONGITUDE OF DME ANTENNA.(ALL SECONDS)
    "dme_bias"         => array("start" => 112, "length" => 7),   // DISTANCE OF DME TRANSMITTER ANTENNA FROM APPROACH END OF RUNWAY.(FEET)
                                                                  // BIAS MUST BE CONVERTED TO NM
                                                                  // Ref: https://flightgear-devel.narkive.com/gzJcsGTQ/dme-bias-question
    "dme_elevation_10" => array("start" => 126, "length" => 7),   // ELEVATION OF DME ANTENNA IN TENTH OF A FOOT (MSL)
    "dme_channel"      => array("start" => 133, "length" => 4),   // CHANNEL ON WHICH DISTANCE DATA IS TRANSMITTED (EX:  032X, 038X)
    );

$stringPositions[5] = array(                                      // ILS Record type #5 is Marker Beacon data
    "aixm_id"          => array("start" => 0,   "length" => 4),   // Main ILS Record Type
    "airport_site_id"  => array("start" => 4,   "length" => 11),  // AIRPORT SITE NUMBER IDENTIFIER. (EX. 04508.*A)
    "ils_system_type"  => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )
    "runway_end_id"    => array("start" => 15,  "length" => 3),  // ILS RUNWAY END IDENTIFIER. (EX: 18,36L)
    "system_type"      => array("start" => 18,  "length" => 10), // ILS SYSTEM TYPE (ILS, SDF . . . )
    "mb_type"          => array("start" => 28,  "length" => 2),   // MB Type (IM, MM, OM) 
    "ops_status_mb"    => array("start" => 30,  "length" => 22),  // OPERATIONAL STATUS OF MB (OPERATIONAL IFR, DECOMMISSIONED...)
    "mb_latitude"      => array("start" => 62,  "length" => 14),  // LATITUDE OF MB.(FORMATTED)
    "mb_decLatitude"   => array("start" => 76,  "length" => 11),  // LATITUDE OF MB.(ALL SECONDS)
    "mb_longitude"     => array("start" => 87,  "length" => 14),  // LONGITUDE OF MB.(FORMATTED)
    "mb_decLongitude"  => array("start" => 101, "length" => 11),  // LONGITUDE OF MB.(ALL SECONDS)
    "mb_elevation_10"  => array("start" => 128, "length" => 7),   // ELEVATION OF MB IN TENTH OF A FOOT (MSL)
    "mb_facility"      => array("start" => 135, "length" => 15),  // FACILITY/TYPE OF MARKER/LOCATOR (Ex: MARKER, NDB)
    "mb_name"          => array("start" => 152, "length" => 30),  // NAME OF THE MARKER LOCATOR BEACON (Ex. VIOLE)
    );

return($stringPositions);
}
