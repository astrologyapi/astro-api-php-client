<?php
/**
 * A PHP File to test Matching APIs from Vedic Rishi Astro
 * User: chandan
 * Date: 14/05/15
 * Time: 5:38 PM
 */

require_once 'src/VedicRishiClient.php';


$userId = "<YourUserIdhere>";
$apiKey = "<YourApiKeyHere>";

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

// match making api to be called
$matchMakingReourceName = "match_ashtakoot_points";


// call matchMakingCall method of vedicrishiclient for matching apis
$ashtakootaPoints = $vedicRishi->matchMakingCall($matchMakingReourceName, $data, $femaleData);

// print ashtakoota response data recieved from api.. data is in the JSON format
echo $ashtakootaPoints;