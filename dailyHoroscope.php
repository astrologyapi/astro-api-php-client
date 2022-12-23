<?php

require_once 'src/AstrologyApiClient.php';


$userId = "<your-user-id>";
$apiKey = "<your-api-key>";

$data = array(
    'timezone' => 5.5
);

$signArray = ['aries','taurus','gemini','cancer','leo','virgo','libra','scorpio','sagittarius','capricorn','aquarius','pisces'];


// instantiate AstrologyApiClient class
$astrologyApi = new AstrologyApiClient($userId, $apiKey);



// call prediction method of the AstrologyApiClient call .. provides JSON response

$todaysPrediction = $astrologyApi->getTodaysPrediction($signArray[5], $data['timezone']);
$tomorrowsPrediction = $astrologyApi->getTomorrowsPrediction($signArray[5], $data['timezone']);
$yesterdaysPrediction = $astrologyApi->getYesterdaysPrediction($signArray[5], $data['timezone']);


// printing the JSON data on the screen/browser
echo $todaysPrediction;
echo "\n";
echo $tomorrowsPrediction;
echo "\n";
echo $yesterdaysPrediction;
echo "\n";