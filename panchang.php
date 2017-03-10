<?php
/**
 * Created by PhpStorm.
 * User: ritesh
 * Date: 10/03/17
 * Time: 2:53 PM
 */


require_once 'src/VedicRishiClient.php';


$userId = "<your-user-id>";
$apiKey = "<your-api-key>";


// make some dummy data in order to call vedic rishi panchang api function
$data = array(

    'date' => 25,
    'month' => 12,
    'year' => 1988,
    'hour' => 4,
    'minute' => 0,
    'latitude' => 25.123,
    'longitude' => 82.34,
    'timezone' => 5.5,
    
);


// instantiate VedicRishiClient class
$vedicRishi = new VedicRishiClient($userId, $apiKey);

//Get Basic Panchang
$responseData = $vedicRishi->getBasicPanchang($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Basic Panchang at the time of sunrise
$responseData1 = $vedicRishi->getBasicPanchangSunrise($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Advance Panchang
$responseData2 = $vedicRishi->getAdvancedPanchang($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Advance Panchang at the time of sunrise
$responseData3 = $vedicRishi->getAdvancedPanchangSunrise($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Planet Panchang
$responseData4 = $vedicRishi->getPlanetPanchang($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Planet Panchang at the time of sunrise
$responseData5 = $vedicRishi->getPlanetPanchangSunrise($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Chaughadiya Muhurta
$responseData6 = $vedicRishi->getChaughadiyaMuhurta($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Hora Muhurta
$responseData7 = $vedicRishi->getHoraMuhurta($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);



// print response data. Change the name of variable to get the respective panchang response data
echo $responseData;
echo "\n";
