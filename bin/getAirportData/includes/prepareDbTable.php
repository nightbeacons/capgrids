<?php

/**
 * prepareDbTable($oneFile, $action, $db, $DEBUG)
 *   Take care of the spatial indexes and 'Point' rows
 *     $oneFile = the CSV file
 *     $action  =  'preprocess'  or 'postprocess'
 *     $db      = db handle
 *     $dbname  = name of the database
 *     $DEBUG
 *   Return status
 */
function prepareDbTable($oneFile, $action, $db, $dbname, $DEBUG)
{
    $parts = pathinfo($oneFile);
    $db_table = $parts['filename'];

    if ($action=='preprocess') {
        $db->query("DELETE FROM $db_table");
        $db->query("alter table $db_table drop if exists coordinates");
        $query = "SELECT COLUMN_NAME from information_schema.COLUMNS WHERE TABLE_SCHEMA='" . $dbname . "' AND TABLE_NAME='" . $db_table . "'
                  AND (COLUMN_NAME = 'LAT_DECIMAL' OR COLUMN_NAME = 'LONG_DECIMAL')";
        $result = $db->query($query);
        $status['latlon'] = $result->num_rows; 

    } // End of 'preprocess'

    if ($action=='postprocess') {

        $query = "SELECT COLUMN_NAME, DATA_TYPE from information_schema.COLUMNS WHERE TABLE_SCHEMA='" . $dbname . "' AND TABLE_NAME='" . $db_table . "'
                  AND (COLUMN_NAME = 'LAT_DECIMAL' OR COLUMN_NAME = 'LONG_DECIMAL')";
        $result = $db->query($query);
        while ($row = mysqli_fetch_assoc($result)){
            $colname  = $row['COLUMN_NAME'];
            $datatype = $row['DATA_TYPE'];
            if ($datatype == 'varchar') {
                $db->query("UPDATE $db_table SET $colname = 0.0 WHERE $colname =''");
            } elseif ($datatype == 'decimal') {
                $db->query("UPDATE $db_table SET $colname = 0.0 WHERE $colname IS NULL OR $colname=0");
            }
        }
        if ($result->num_rows > 1) {
            $db->query("ALTER TABLE $db_table ADD COLUMN `coordinates` Point NOT NULL");
            $db->query("UPDATE $db_table set coordinates=Point(LONG_DECIMAL, LAT_DECIMAL)");
            $db->query("create spatial index ix_spatial_" . $db_table . "_coord ON $db_table(coordinates)");
        }

    } // End of 'postprocess'

    return(1);
}


