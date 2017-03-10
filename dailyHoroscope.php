<?php

require_once 'src/VedicRishiClient.php';


$userId = "<your-user-id>";
$apiKey = "<your-api-key>";

$data = array(
    'timezone' => 5.5
);

$signArray = ['aries','taurus','gemini','cancer','leo','virgo','libra','scorpio','sagittarius','capricorn','aquarius','pisces'];


// instantiate VedicRishiClient class
$vedicRishi = new VedicRishiClient($userId, $apiKey);



// call prediction method of the VedicRishiClient call .. provides JSON response

$todaysPrediction = $vedicRishi->getTodaysPrediction($signArray[5], $data['timezone']);
$tomorrowsPrediction = $vedicRishi->getTomorrowsPrediction($signArray[5], $data['timezone']);
$yesterdaysPrediction = $vedicRishi->getYesterdaysPrediction($signArray[5], $data['timezone']);


// printing the JSON data on the screen/browser
echo $todaysPrediction;
echo "\n";
echo $tomorrowsPrediction;
echo "\n";
echo $yesterdaysPrediction;
echo "\n";