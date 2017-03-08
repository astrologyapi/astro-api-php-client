<?php
/**
 * A php file to test the Vedic Rishi Client class
 * Author: Chandan Tiwari
 * Date: 06/12/14
 * Time: 5:42 PM
 */

require_once 'src/VedicRishiClient.php';


$userId = "4545";
$apiKey = "33e5731ac9bf30a51180ac18a7269ffb";


// make some dummy data in order to call vedic rishi api
$data = array(

    'date' => 25,
    'month' => 12,
    'year' => 1988,
    'hour' => 4,
    'minute' => 0,
    'latitude' => 25.123,
    'longitude' => 82.34,
    'timezone' => 5.5,
    'prediction_timezone' => 5.5 // Optional. Only For Transit Prediction API
);


//planet name will be used for the planet ashtakvarga
$planetName = ["sun", "moon", "mars", "mercury", "jupiter", "venus", "saturn" , "ascendant"];

//sign name
$signName = ['aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces'];


//chart Id to calculate horoscope chart
$chartId = ['chalit','SUN','MOON','D1','D2','D3','D4','D5','D7','D8','D9','D10','D12','D16','D20','D24','D27','D30','D40','D45','D60'];


// instantiate VedicRishiClient class
$vedicRishi = new VedicRishiClient($userId, $apiKey);


// call horoscope functions of Vedic Rishi Client
$responseData = $vedicRishi->getExtendedHoroChartById($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);




// print response data
echo $responseData;
echo "\n";


// call Transit prediction api's
//$transitResponseData = $vedicRishi->callTransitPrediction($resourceName, $data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'],$data['prediction_timezone']);
//echo $transitResponseData;