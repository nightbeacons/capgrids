<?php
/**
 * @file
 * Provides nnumber_to_icao($nnumber);
 *
 * Adapted from https://github.com/guillaumemichel/icao-nnumber_converter/blob/master/src/icao_nnumber_converter_us/convert.py.
 */

/* ===================================================================== */
/* ===================================================================== */
/* ===================================================================== */

/**
 * Function nnumber_to_icao($nnumber)
 *    Wrapper function to compute
 * ICAO number for US-registered N-numbers.
 *
 * Accepts: N-Number
 * Returns: ICAO number, or '' on error.
 */
function nnumber_to_icao($nnumber) {

  // Size of an icao address.
  $ICAO_SIZE = 6;
  // Max size of a N-Number.
  $NNUMBER_MAX_SIZE = 6;

  // Alphabet without I and O.
  $charset = "ABCDEFGHJKLMNPQRSTUVWXYZ";
  $digitset = "0123456789";
  $allchars = $charset . $digitset;

  $suffix_size = 1 + strlen($charset) * (1 + strlen($charset));
  $bucket_size = [];
  $bucket_size[4] = 1 + strlen($charset) + strlen($digitset);
  $bucket_size[3] = strlen($digitset) * $bucket_size[4] + $suffix_size;
  $bucket_size[2] = strlen($digitset) * $bucket_size[3] + $suffix_size;
  $bucket_size[1] = strlen($digitset) * $bucket_size[2] + $suffix_size;
  $nnumber = strtoupper($nnumber);
  $return_value = '';
  $valid = FALSE;
  $count = 0;
  if ((strlen($nnumber) <= $NNUMBER_MAX_SIZE) and ($nnumber[0] == 'N') and (ctype_alnum($nnumber))) {
    $valid = TRUE;
  }

  if ($valid) {
    $prefix = 'a';
    if (strlen($nnumber) > 1) {
      $nnumber = ltrim($nnumber, "Nn");
      $count++;

      for ($i = 0; $i < strlen($nnumber); $i++) {

        if ($i == ($NNUMBER_MAX_SIZE - 2)) {
          $count += strpos($allchars, $nnumber[$i]) + 1;
        }
        elseif (strpos($charset, $nnumber[$i]) !== FALSE) {
          $count += adsb_suffix_offset(substr($nnumber, $i), $charset);
          break;
          // Break # nothing comes after alphabetical chars.
        }
        else {
          // Number.
          switch ($i) {
            case 0:
              $count += (intval($nnumber[$i]) - 1) * $bucket_size[1];
              break;

            case 1:
              $count += (intval($nnumber[$i])) * $bucket_size[2] + $suffix_size;
              break;

            case 2:
              $count += intval($nnumber[$i]) * $bucket_size[3] + $suffix_size;
              break;

            case 3:
              $count += intval($nnumber[$i]) * $bucket_size[4] + $suffix_size;
          }
        }
      }
    }
    $suffix = dechex((float) $count);
    if (strlen($prefix) + strlen($suffix) > $ICAO_SIZE) {
      $return_value = '';
    }
    else {
      $return_value = $prefix . $suffix;
    }
  }
  return($return_value);
}

/**
 * Function adsb_suffix_offset($s, $charset)
 * Inverse of get_suffix()
 */
function adsb_suffix_offset($s, $charset) {
  $count = (strlen($charset) + 1) * strpos($charset, $s[0]) + 1;
  if (strlen($s) == 2) {
    $count += strpos($charset, $s[1]) + 1;
  }
  return ($count);
}
