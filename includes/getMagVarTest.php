#!/usr/bin/php
<?php

$dec = magVariation(47, -122.25);

echo "\n\n\n\n  dec is " . $dec;
/**
 * Get current magnetic variation (declination) for given lat/lon
 * See https://www.ngdc.noaa.gov/geomag/help/declinationHelp.html
 * Sample GET: https://www.ngdc.noaa.gov/geomag-web/calculators/calculateDeclination?lat1=40&lon1=-105.25&resultFormat=xml
 */
function magVariation($lat, $lon)
{
$baseUrl = "https://www.ngdc.noaa.gov/geomag-web/calculators/calculateDeclination";

# Use for the variation found on Sectional Charts
#$dateString = "minYear=2004&minMonth=6&minDay=30";

$format = "&resultFormat=xml";                  # Format of calculation results: 'html', 'csv', 'xml', or 'pdf'

$url=$baseUrl . "?lat1=" . trim($lat) .  "&lon1=" . trim($lon) .   $format;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_REFERER, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0");
$output = curl_exec($ch);
curl_close($ch);

$p = xml_parser_create();
xml_parse_into_struct($p, $output, $vals, $index);
xml_parser_free($p);

$idx = $index['DECLINATION'][0];
$decl = $vals[$idx]['value'];

$decl= - round($decl, 1);

return ($decl);

}

