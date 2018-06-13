<?php
/**
 * A php file to test the Vedic Rishi Client class
 * Author: Chandan Tiwari
 * Date: 06/12/14
 * Time: 5:42 PM
 */

require_once 'src/VedicRishiClient.php';

$userId = "<your-user-id>";
$apiKey = "<your-api-key>";



// make some dummy data in order to call AstrologyAPI
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


//Western Horoscope Settings Array.

/*
 * Below given settings array shows the default settings for Western Horoscope.
 * If a any setting is changed then it is required to pass this $settings array variable to getWesternHoroscope() function (example given below).
 * Other available settings can be found at this link -> https://astrologyapi.com/western-api-docs/api-ref/164/western_horoscope
 *
 */
$settings = array(
    'house_type' => 'placidus',  //other options -> 'koch', 'porphyry', 'equal', 'whole_sign', 'topocentric', 'sripati', 'horizontal', 'campanus'

    'node_type' => 'mean',       //other options -> 'true'

    'aspects' => 'major',        //other options -> 'all','none'

);



// instantiate VedicRishiClient class
$vedicRishi = new VedicRishiClient($userId, $apiKey);

//Get Western Horoscope with default setting.
$horoscopeData = $vedicRishi->getWesternHoroscope($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//Get Westen Horoscope with Setting.
$horoscopeDataWithSetting = $vedicRishi->getWesternHoroscope($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'],$settings);

//Get Wheel Chart
$wheelChart = $vedicRishi->getWheelChartTropical($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

