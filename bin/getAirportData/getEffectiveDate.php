#!/usr/bin/php
<?php

$file = "/tmp/aixm/README.txt";

$readme = file_get_contents($file);
//echo $readme;

$p = preg_match("/AIS subscriber files effective date.*[\.\$]/m", $readme, $matches); 
$effDate = trim(preg_replace("/AIS subscriber files effective date/", "", $matches[0]), " ."); 
echo "\n\n$effDate \n\n";

$fh = fopen("/var/www/capgrids/htdocs/includes/lastMod.php", "w");
fwrite($fh, "<?php\n\$DataLastModified = \"$effDate\";\n");
fclose($fh);

