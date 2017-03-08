<?php
/**
 * Created by PhpStorm.
 * User: ritesh
 * Date: 08/03/17
 * Time: 10:23 AM
 */


function getCurlReponse($userId, $apiKey, $resource, array $data)
{
    $apiEndPoint = "http://api.vedicrishiastro.com/v1";


    $serviceUrl = $apiEndPoint.'/'.$resource.'/';
    $authData = $userId.":".$apiKey;
    //echo $serviceUrl;
    //echo "\n";
    //echo $authData;
    //echo  "\n";
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $serviceUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $header[] = 'Authorization: Basic '. base64_encode($authData);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}