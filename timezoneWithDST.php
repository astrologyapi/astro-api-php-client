<?php
/**
 * Created by PhpStorm.
 * User: vicky
 * Date: 4/17/19
 * Time: 2:43 PM
 */


require_once 'src/VedicRishiClient.php';

$userId = "<your-user-id>";
$apiKey = "<your-api-key>";



// Some sampe request data in order to call api
$data = array(

    'latitude' => 19.45234,
    'longitude' => 72.23234,
    'date'=> "4-26-2018"   // date in mm-dd-yyyy format; This is required to return the Timezone of that date
);


$client = new VedicRishiClient($userId, $apiKey);
$response =  $client->callApi("timezone_with_dst", $data);



//To convert the response in PHP Associative array, use the method below

$responseData = json_decode($response, true);


echo "<pre>";
print_r($responseData);

echo "</pre>";



