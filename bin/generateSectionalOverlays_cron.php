#!/usr/bin/php
<?php

$start = date(DATE_RFC850);
$tmp = `/var/www/capgrids/bin/generateSectionalOverlays.php`;
$stop = date(DATE_RFC850);

$topline = "Start: $start\n
End: $stop\n\n";

if (strlen(trim($tmp)) > 0) {

  $to      = 'nightbeacons@gmail.com';
  $subject = 'Updates to Sectional Overlays';
  $message = $topline . $tmp;
  $headers = 'From: webmaster@capgrids.com' . "\r\n" .
    'Reply-To: webmaster@capgrids.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);

}
