<?php
/**
 * A PHP File to test Matching APIs from Astrology API
 * User: chandan
 * Date: 14/05/15
 * Time: 5:38 PM
 */

require_once 'src/AstrologyApiClient.php';


$userId = "<your-user-id>";
$apiKey = "<your-api-key>";

// create a male profile data
$data = array(

    'date' => 25,
    'month' => 12,
    'year' => 1988,
    'hour' => 4,
    'minute' => 0,
    'latitude' => 25.123,
    'longitude' => 82.34,
    'timezone' => 5.5
);
// create female data and will treat above $data as male data to be sent to matchmaking api
$femaleData = array(

    'date' => 27,
    'month' => 1,
    'year' => 1990,
    'hour' => 13,
    'minute' => 36,
    'latitude' => 25.123,
    'longitude' => 82.34,
    'timezone' => 5.5
);



// instantiate AstrologyApiClient class
$astrologyApi = new AstrologyApiClient($userId, $apiKey);


// call method of astrologyapiclient for matching apis
$res = $astrologyApi->matchObstructions($data, $femaleData);

$res1 = $astrologyApi->matchAshtakootPoints($data, $femaleData);

$res2 = $astrologyApi->matchBirthDetails($data, $femaleData);

$res3 = $astrologyApi->matchPlanetDetails($data, $femaleData);

$res4 = $astrologyApi->matchAstroDetails($data, $femaleData);

$res5 = $astrologyApi->getMatchMakingReport($data, $femaleData);

$res6 = $astrologyApi->getMatchSimpleReport($data, $femaleData);

$res7 = $astrologyApi->getMatchManglikReport($data, $femaleData);








// print response data recieved from api.. data is in the JSON format
echo $res7;
echo "\n";