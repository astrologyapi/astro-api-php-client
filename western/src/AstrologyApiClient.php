<?php
/**
 * Astrology API Client for consuming Astrology APIs
 * https://astrologyapi.com/
 * Author: Chandan Tiwari
 * Date: 06/12/14
 * Time: 5:42 PM
 */

require 'sdk.php';


class AstrologyApiClient
{
    private $userId = null;
    private $apiKey = null;
    private $language = null;
    private $settings = array();

    //TODO: MUST enable this on production- MUST
    //private $apiEndPoint = "https://json.astrologyapi.com/v1/";

    //TODO: MUST- comment this and uncomment https url above on production for added security

    /**
     * @param $uid string userId for Astrology API
     * @param $key string api key for Astrology API access
     */
    public function __construct($uid, $key)
    {
        $this->userId = $uid;
        $this->apiKey = $key;
    }


    /*
    A Function to set the Language of Response.
    Just call this function and you can change the language.
    This function should be passed either 'en' for English or 'hi' for Hindi.
*/
    public function setLanguage( $language )
    {
        $this->language = $language;
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }



    public function callApi($apiName, $request){

        $requestData = $request;

        if(count($this->settings) > 0 && $this->settings != null){

           $requestData = array_merge($request, $this->settings);

        }
        return getCurlReponse($this->userId, $this->apiKey, $apiName ,$requestData, $this->language);
    }




}