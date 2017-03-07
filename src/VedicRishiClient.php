<?php
/**
 * Vedic Rishi Client for consuming Vedic Rishi Astro Web APIs
 * http://www.vedicrishiastro.com/astro-api/
 * Author: Chandan Tiwari
 * Date: 06/12/14
 * Time: 5:42 PM
 */

class VedicRishiClient
{
    private $userId = null;
    private $apiKey = null;

    //TODO: MUST enable this on production- MUST
    //private $apiEndPoint = "https://api.vedicrishiastro.com/v1";

    //TODO: MUST- comment this and uncomment https url above on production for added security
    private $apiEndPoint = "http://api.vedicrishiastro.com/v1";

    /**
     * @param $uid string userId for Vedic Rishi Astro API
     * @param $key string api key for Vedic Rishi Astro API access
     */
    public function __construct($uid, $key)
    {
        $this->userId = $uid;
        $this->apiKey = $key;
    }

    private function getCurlReponse($resource, array $data)
    {
        $serviceUrl = $this->apiEndPoint.'/'.$resource.'/';
        $authData = $this->userId.":".$this->apiKey;
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

    private function packageHoroData($date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone)
    {
        return array(
            'day' => $date,
            'month' => $month,
            'year' => $year,
            'hour' => $hour,
            'min' => $minute,
            'lat' => $latitude,
            'lon' => $longitude,
            'tzone' => $timezone,
            'name' => 'chandan'
        );
    }

    private function packageTransitPredictionData($date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone, $predictionTimezone)
    {
        return array(
            'day' => $date,
            'month' => $month,
            'year' => $year,
            'hour' => $hour,
            'min' => $minute,
            'lat' => $latitude,
            'lon' => $longitude,
            'tzone' => $timezone,
            'prediction_timezone' => $predictionTimezone
        );
    }

    private function packageNumeroData($date, $month, $year, $name)
    {
        return array(
            'day' => $date,
            'month' => $month,
            'year' => $year,
            'name' => $name
        );
    }

    private function packageMatchMakingData($maleBirthData, $femaleBirthData)
    {
        $mData = array(
            'm_day' => $maleBirthData['date'],
            'm_month' => $maleBirthData['month'],
            'm_year' => $maleBirthData['year'],
            'm_hour' => $maleBirthData['hour'],
            'm_min' => $maleBirthData['minute'],
            'm_lat' => $maleBirthData['latitude'],
            'm_lon' => $maleBirthData['longitude'],
            'm_tzone' => $maleBirthData['timezone']
        );
        $fData = array(
            'f_day' => $femaleBirthData['date'],
            'f_month' => $femaleBirthData['month'],
            'f_year' => $femaleBirthData['year'],
            'f_hour' => $femaleBirthData['hour'],
            'f_min' => $femaleBirthData['minute'],
            'f_lat' => $femaleBirthData['latitude'],
            'f_lon' => $femaleBirthData['longitude'],
            'f_tzone' => $femaleBirthData['timezone']
        );

        return array_merge($mData, $fData);
    }

    private function packageSunSignPredictionData($predictionTimezone)
    {
        return array (
            'timezone' => $predictionTimezone
        );
    }

    private function dataSanityCheck($data)
    {

    }

    /**
     * @param $resourceName string apiName name of an api without any begining and end slashes (ex 'birth_details')
     * @param $date date
     * @param $month month
     * @param $year year
     * @param $hour hour
     * @param $minute minute
     * @param $latitude latitude
     * @param $longitude longitude
     * @param $timezone timezone
     * @return array response data decoded in PHP associative array format
     */
    public function call($resourceName, $date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone)
    {

        $data = $this->packageHoroData($date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone);
        $resData = $this->getCurlReponse($resourceName, $data);
        return $resData;

    }

    /**
     * @param $resourceName string apiName name of numerological api (numero_table and numero_report)
     * @param $date int date of birth
     * @param $month int month of birth
     * @param $year int year of birth
     * @param $name string name
     * @return array response data decoded in PHP associative array format
     */

    public function numeroCall($resourceName, $date, $month, $year, $name)
    {
        $data = $this->packageNumeroData($date, $month, $year, $name);
        $resData = $this->getCurlReponse($resourceName, $data);
        return $resData;
    }

    /**
     * @param $resourceName apiName name of an api along without any begining and end slashes (ex match_birth_details)
     * @param array $maleBirthData  array maleBirthdata associative array format
     * @param array $femaleBirthData array femaleBirthdata associative array format
     * @return array response data decoded in PHP associative array format
     */
    public function matchMakingCall($resourceName, array $maleBirthData, array $femaleBirthData)
    {
        //TODO:  needs to validate male and female birth data against expected keys
        //$this->dataSanityCheck($maleBirthData);
        //$this->dataSanityCheck($femaleBirthData);

        $data = $this->packageMatchMakingData($maleBirthData, $femaleBirthData);
        $response = $this->getCurlReponse($resourceName, $data);
        return $response;
    }

    /*Prediction with timezone*/
    public function callTransitPrediction($resourceName, $date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone,$predictionTimezone)
    {
        $data = $this->packageTransitPredictionData($date, $month, $year, $hour, $minute, $latitude, $longitude, $timezone,$predictionTimezone);
        $resData = $this->getCurlReponse($resourceName, $data);
        return $resData;
    }

    private function callSunSignDailyPrediction($resourceName, $predictionTimezone)
    {
        $data = $this->packageSunSignPredictionData($predictionTimezone);
        $response = $this->getCurlReponse($resourceName, $data);
        return $response;
    }

   public function getTodaysPrediction($zodiacSign, $timezone)
    {
        $resourceName = 'sun_sign_prediction/daily/'.$zodiacSign;
        return $this->callSunSignDailyPrediction($resourceName, $timeZone);

    }

    public function getTomorrowsPrediction($zodiacSign, $timezone)
    {
        $resourceName = 'sun_sign_prediction/daily/next/'.$zodiacSign;
        return $this->callSunSignDailyPrediction($resourceName, $timeZone);

    }

    public function getYesterdaysPrediction($zodiacSign, $timezone)
    {
        $resourceName = 'sun_sign_prediction/daily/previous/'.$zodiacSign;
        return $this->callSunSignDailyPrediction($resourceName, $timeZone);
    }

}