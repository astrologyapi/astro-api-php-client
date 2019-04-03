<?php

require_once 'src/WesternApiClient.php';

$userId = "<your-user-id>";
$apiKey = "<your-api-key>";



// make some dummy request data in order to call AstrologyAPI.com

// Starting with p_ the data is of Primary Profile and starting with s_ the data is of Secondary Profile

$sampleRequest = array(
    'p_day' => 18,
    'p_month' => 12,
    'p_year' => 1990,
    'p_hour' => 18,
    'p_min' => 30,
    'p_lat' => 25.123789,
    'p_lon' => 82.341234,
    'p_tzone' => 5.5,
    's_day' => 9,
    's_month' => 6,
    's_year' => 1992,
    's_hour' => 8,
    's_min' => 45,
    's_lat' => 19.435421,
    's_lon' => 72.765123,
    's_tzone' => 5.5
);


//Western Horoscope Settings Array.

/*
 * Below given settings array shows the default settings for Western Horoscope.
 * Other available settings can be found at this link -> https://astrologyapi.com/western-api-docs/api-ref/164/western_horoscope
 *
 */
$settings = array(
    'house_type' => 'placidus',  //other options -> 'koch', 'porphyry', 'equal', 'whole_sign', 'topocentric', 'sripati', 'horizontal', 'campanus'

    'node_type' => 'mean',       //other options -> 'true'

    'aspects' => 'major',        //other options -> 'all','none'

);


//Initializing the WesternApiClient

$astrologyApi = new WesternApiClient($userId, $apiKey);



/*
 * If you want to change the setting then change the option in the $settings array and call the setSettings method like below
 * Or skip it to keep it default.
 *
 * P.S - Apply the settings before calling the callApi method.
 */

$astrologyApi->setSettings($settings);



//Call the API

/*
 * First parameter is the name of the api : ex. karma_destiny_report/tropical
 * Second parameter is the request data to be sent for calculation
 */

$apiResponse = $astrologyApi->callApi('karma_destiny_report/tropical', $sampleRequest);


//To convert the response in PHP Associative array, use the method below

$responseData = json_decode($apiResponse, true);


echo "<pre>";
print_r($responseData);

echo "</pre>";











