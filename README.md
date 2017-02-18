Vedic-Rishi-Astro-API-PHP-Client
================================

This is PHP client to consume Vedic Rishi Astro APIs

Where to get API Key
====================

You can visit https://www.vedicrishiastro.com/astro-api/ to get the astrology API key to be used for your websites or
mobile applications.

How to Use
==========

1. Copy VedicRishiClient.php class file to your local or server file system
2. Instantiate ```VedicRishiClient``` class as follows as follows -
    ```php
    $clientInstance = new VedicRishiClient($userId, $apiKey);
    ```
    Replace ``` $userId ``` and ``` $apiKey``` with your id and keys respectively.
    You can get the API key details from https://www.vedicrishiastro.com/astro-api/

3. Call the api
    ```php
    $response = $clientInstance->call($apiName, $date, $month, $year, $hour, $min, $lat, $lon, $tzone);

    ```
    View test.php for more details about calling APIs.
    
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
5. For calling numerological api, call method name ``` numeroCall() ``` as follows -

    ```php
        $response = $clientInstance->numeroCall($apiName, $date, $month, $year, $name);

    ```
    Only date, month and year along with name is required for numerological calculations.
    Run the numerolgy.php file to test numerological APIs.

6. For match making horoscope calculations and report analysis, please use ```matchMakingCall()``` method as follows -

    ```php
            $response = $clientInstance->matchMakingCall($resourceName, array $maleBirthData, array $femaleBirthData);


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
Run matching.php file to run Vedic Rishi Match Making APIs.
For API documentation, visit - https://www.vedicrishiastro.com/astro-api/docs/
