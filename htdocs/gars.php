#!/usr/bin/php
<?php


$lat_deg = 36;
$lat_min = 14;
$lon_deg = -104;
$lon_min = 47;

$lat_min_30 = $lat_min - (($lat_min > 15) * 15);
$lon_min_30 = $lon_min - (($lon_min > 15) * 15);

echo "LatMin = $lat_min_30  LonMin = $lon_min_30\n";

$quad_30 = (($lon_min_30 > 15) + 1)  + (($lat_min_30 > 15) * 2) ;
echo "Q=$quad_30\n";

$glon = 1+ ($lon_deg + 180) * 2;
$glat = 1+($lat_deg + 90) * 2;


$m=makeGARSary();
print_r($m);

$x = $m[$glat];
echo "$lat_deg / $lon_deg\n";
echo $glon . $x . "\n\n";;

function makeGARSary(){
$alpha=array();
$count=1;

for ($x=65; $x<=90; $x++){
  if (!(($x==73) OR ($x==79))) {
    for ($y=65; $y<=90; $y++){
      if (!(($y==73) OR ($y==79))) {
        $alpha[$count++] = chr($x) . chr($y);
      }
    }
  }
}

return ($alpha);

}
