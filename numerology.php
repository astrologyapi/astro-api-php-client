<?php
/**
 * A PHP File to test Numerology APIs from Astrology API
 * User: chandan
 * Date: 14/05/15
 * Time: 5:38 PM
 */


require_once 'src/AstrologyApiClient.php';


$userId = "<your-user-id>";
$apiKey = "<your-api-key>";



//TODO:  Make numerology request data. This needs to come from form in production
$dateOfBirth = 25;
$monthOfBirth = 12;
$yearOfBirth = 1988;
$name = 'Chandan';


// instantiate AstrologyApiClient class
$astrologyApi = new AstrologyApiClient($userId, $apiKey);

// call numerology method of the AstrologyApiClient call .. provides JSON response
$numeroJSONData1 = $astrologyApi->getNumeroReport($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData2 = $astrologyApi->getNumeroTable($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData3 = $astrologyApi->getNumeroPlaceVastu($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData4 = $astrologyApi->getNumeroFavLord($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData5 = $astrologyApi->getNumeroFavMantra($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData6 = $astrologyApi->getNumeroFastsReport($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);
$numeroJSONData7 = $astrologyApi->getNumeroFavTime($dateOfBirth, $monthOfBirth, $yearOfBirth, $name);


// printing the JSON data on the screen/browser
echo $numeroJSONData7;
echo "\n";