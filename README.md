Vedic-Rishi-Astro-API-PHP-Client
================================

This is PHP client to consume Vedic Rishi Astro APIs

Where to get API Key
====================

You can visit https://www.astrologyapi.com/ to get the astrology API key to be used for your websites or
mobile applications.

How to Use
==========

1. Copy src/VedicRishiClient.php and src/sdk.php files to your local or server file system
2. Instantiate ```VedicRishiClient``` class as follows -
    ```php
    $clientInstance = new VedicRishiClient($userId, $apiKey);
    ```
    Replace ``` $userId ``` and ``` $apiKey``` with your id and keys respectively.
    You can get the API key details from https://www.astrologyapi.com/

3. You can call the functions for the respective APIs by passing the birth data and other relevant data (if required) as argument. Eg. for calling the  ``` /planets/ ``` api, use the following function as shown below
    ```php
    $response = $clientInstance->getPlanetDetails($date, $month, $year, $hour, $min, $lat, $lon, $tzone);

    ```
    View astrology.php for more details about calling APIs related to astrology.
    
4. The ``` $response ``` will be a JSON encoded data returned as an API response. Eg. for ``` /planets/ ``` api - 
    ```js
    [
        {
            "id":0,
            "name":"SUN",
            "fullDegree":95.83230788313479,
            "normDegree":5.8323078831347885,"speed":0.9547191489638442,
            "isRetro":"false",
            "sign":"CANCER",
            "signLord":"MOON",
            "nakshatra":"PUSHYA",
            "nakshatraLord":7,
            "house":11
        }
        ...
    ]
    ```
5. For calling numerological api, call the respective function. Eg. to get numerology report call ``` getNumeroReport() ``` as follows -

    ```php
        $response = $clientInstance->getNumeroReport($date, $month, $year, $name);

    ```
    Only date, month and year along with name is required for numerological calculations.
    Run the numerolgy.php file to test functions related to numerological APIs.

6. For match making horoscope calculations and report analysis, use matchmaking related functions. Eg. to get matchmaking report, use ```getMatchMakingReport()``` function as follows -

    ```php
            $response = $clientInstance->getMatchMakingReport(array $maleBirthData, array $femaleBirthData);


    //where  $maleBirthData and $femaleBirthData is mapped as follows -

                    $femaleData = array(

                        'date' => 9,
                        'month' => 12,
                        'year' => 1990,
                        'hour' => 12,
                        'minute' => 56,
                        'latitude' => 25.123,
                        'longitude' => 82.34,
                        'timezone' => 5.5
                    );

                    $maleData = array(

                        'date' => 25,
                        'month' => 12,
                        'year' => 1988,
                        'hour' => 4,
                        'minute' => 0,
                        'latitude' => 25.123,
                        'longitude' => 82.34,
                        'timezone' => 5.5
                    );
    ```
Run matching.php file to test functions related to Vedic Rishi Match Making APIs.
For API documentation, visit - https://www.astrologyapi.com/docs/
