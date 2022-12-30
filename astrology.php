<?php
/**
 * A php file to test the Astrology Client class
 * Author: Chandan Tiwari
 * Date: 06/12/14
 * Time: 5:42 PM
 */

require_once 'src/AstrologyApiClient.php';

$userId = "<your-user-id>";
$apiKey = "<your-api-key>";



// make some dummy data in order to call astrology api
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


// instantiate AstrologyApiClient class
$astrologyApi = new AstrologyApiClient($userId, $apiKey);
$astrologyApi->setLanguage('hi');

// call horoscope functions of Astrology API Client

//*****************Basic Astro****************//
$responseData = $astrologyApi->getBirthDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData1 = $astrologyApi->getAstroDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData2 = $astrologyApi->getPlanetsDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData3 = $astrologyApi->getPlanetsExtendedDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData4 = $astrologyApi->getPlanetsTropicalDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData5 = $astrologyApi->getGeoDetails('pune', 5);

$responseData6 = $astrologyApi->getTimezone('Asia/Kolkata', 'false');


//*****************Ashtakvarga****************//
$responseData7 = $astrologyApi->getAshtakvargaDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $planetName[3]);

$responseData8 = $astrologyApi->getSarvashtakDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Vimshottari Dasha****************//
$responseData9 = $astrologyApi->getCurrentVimDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData10 = $astrologyApi->getCurrentVimDashaAll($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData11 = $astrologyApi->getMajorVimDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Yogini Dasha****************//
$responseData12 = $astrologyApi->getCurrentYoginiDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData13 = $astrologyApi->getMajorYoginiDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData14 = $astrologyApi->getSubYoginiDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Char Dasha****************//
$responseData15 = $astrologyApi->getCurrentCharDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData16 = $astrologyApi->getMajorCharDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData17 = $astrologyApi->getSubCharDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $signName[3]);

$responseData18 = $astrologyApi->getSubSubCharDasha($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $signName[4], $signName[2]);


//*****************Kalsarpa Dasha****************//
$responseData19 = $astrologyApi->getKalsarpaDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Pitri Dasha****************//
$responseData20 = $astrologyApi->getPitriDoshaReport($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Sadhesati Dosha****************//
$responseData201 = $astrologyApi->getSadhesatiLifeDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData202 = $astrologyApi->getSadhesatiCurrentStatus($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData203 = $astrologyApi->getSadhesatiRemedies($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Manglik Dosha****************//
$responseData21 = $astrologyApi->getManglikDetails($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//*****************Horoscope Charts****************//
$responseData22 = $astrologyApi->getHoroChartById($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $chartId[4]);

$responseData23 = $astrologyApi->getExtendedHoroChartById($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $chartId[5]);


//*****************Suggestions and Remedies****************//
$responseData24 = $astrologyApi->getBasicGemSuggestion($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData25 = $astrologyApi->getRudrakshaSuggestion($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData26 = $astrologyApi->getPujaSuggestion($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//***************************************** GENERAL REPORTS FUNCTIONS ****************************************************
$responseData27 = $astrologyApi->getGeneralHouseReport($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $planetName[6]);

$responseData28 = $astrologyApi->getGeneralRashiReport($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'], $planetName[1]);

$responseData29 = $astrologyApi->getAscendantReport($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

$responseData30 = $astrologyApi->getNakshatraReport($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);

//****************************Nakshatra Prediction**********************//
$responseData31 = $astrologyApi->getDailyNakshatraPrediction($data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone']);


//****************************Timezone Wth DST**********************//
//date formate -> mm-dd-yyyy
$date = $data['month'].'-'.$data['date'].'-'.$data['year'];
$timezoneData = $astrologyApi->timezoneWithDst($date, $data['latitude'], $data['longitude']);
// print response data
echo $timezoneData;

echo "\n";


// call Transit prediction api's
//$transitResponseData = $astrologyApi->callTransitPrediction($resourceName, $data['date'], $data['month'], $data['year'], $data['hour'], $data['minute'], $data['latitude'], $data['longitude'], $data['timezone'],$data['prediction_timezone']);
//echo $transitResponseData;
