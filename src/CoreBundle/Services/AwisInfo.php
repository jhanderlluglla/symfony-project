<?php

namespace CoreBundle\Services;

/**
 * Class AwisInfo
 *
 * @package CoreBundle\Services
 */
class AwisInfo
{

    const ACTION_NAME = 'UrlInfo';
    const RESPONSE_GROUP_NAME = "Rank,LinksInCount";
    const SERVICE_REGION = "us-west-1";
    const SERVICE_NAME = "awis";
    const SERVICE_ENDPOINT = self::SERVICE_NAME . "." . self::SERVICE_REGION . ".amazonaws.com";
    const SERVICE_HOST = 'awis.amazonaws.com';
    const SERVICE_URI = "/api";
    const NUM_RETURN = 10;
    const START_NUM = 1;

    /**
     * @var string
     */
    protected $accessKeyId;

    /**
     * @var string
     */
    protected $secretAccessKey;

    /**
     * @var string
     */
    protected $amzDate;

    /**
     * @var string
     */
    protected $dateStamp;

    /**
     * AwisInfo constructor.
     *
     * @param string $accessKeyId
     * @param string $secretAccessKey
     */
    public function __construct($accessKeyId, $secretAccessKey)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;

        $now = time();
        $this->amzDate = gmdate("Ymd\THis\Z", $now);
        $this->dateStamp = gmdate("Ymd", $now);
    }

    /**
     * @param string $site
     *
     * @return int
     */
    public function getAlexaRank($site)
    {
        return $this->getUrlInfo($site);
    }

    /**
     * @param $site
     * @return String
     */
    private function getUrlInfo($site)
    {
        $canonicalQuery = $this->buildQueryParams($site);
        $canonicalHeaders =  $this->buildHeaders(true);
        $signedHeaders = $this->buildHeaders(false);

        $canonicalRequest =
            "GET\n" .
            self::SERVICE_URI . "\n" .
            "$canonicalQuery\n" .
            "$canonicalHeaders\n" .
            "$signedHeaders\n" .
            hash('sha256', ""); //payload hash

        $credentialScope =
            $this->dateStamp . "/" .
            self::SERVICE_REGION . "/" .
            self::SERVICE_NAME . "/" .
            "aws4_request";

        $algorithm = "AWS4-HMAC-SHA256";
        $stringToSign =
            "$algorithm\n" .
            "$this->amzDate\n" .
            "$credentialScope\n" .
            hash('sha256', $canonicalRequest);

        $signingKey = $this->getSignatureKey();
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorizationHeader =
            $algorithm . ' ' . 'Credential=' . $this->accessKeyId . '/' .
            $credentialScope . ', ' .
            'SignedHeaders=' . $signedHeaders . ', ' .
            'Signature=' . $signature;

        $url = 'https://' . self::SERVICE_HOST . self::SERVICE_URI . '?' . $canonicalQuery;

        $response = $this->makeRequest($url, $authorizationHeader);
        return $this->parseRank($response);
    }

    private function sign($key, $msg)
    {
        return hash_hmac('sha256', $msg, $key, true);
    }

    private function getSignatureKey()
    {
        $kSecret = 'AWS4' . $this->secretAccessKey;
        $kDate = $this->sign($kSecret, $this->dateStamp);
        $kRegion = $this->sign($kDate, self::SERVICE_REGION);
        $kService = $this->sign($kRegion, self::SERVICE_NAME);
        $kSigning = $this->sign($kService, 'aws4_request');
        return $kSigning;
    }

    /**
     * Builds headers for the request to AWIS.
     * @param bool $asList
     * @return String headers for the request
     */
    private function buildHeaders($asList)
    {
        $params = [
            'host'            => self::SERVICE_ENDPOINT,
            'x-amz-date'      => $this->amzDate
        ];
        ksort($params);

        $keyvalue = [];
        foreach ($params as $key => $value) {
            if ($asList) {
                $keyvalue[] = $key . ':' . $value;
            } else {
                $keyvalue[] = $key;
            }
        }

        return $asList ? implode("\n", $keyvalue) . "\n" : implode(';', $keyvalue) ;
    }

    private function buildQueryParams($site)
    {
        $params = [
            'Action'            => self::ACTION_NAME,
            'Count'             => self::NUM_RETURN,
            'ResponseGroup'     => self::RESPONSE_GROUP_NAME,
            'Start'             => self::START_NUM,
            'Url'               => $site
        ];
        ksort($params);

        return http_build_query($params);
    }

    /**
     * Makes request to AWIS
     * @param String $url   URL to make request to
     * @param String authorizationHeader  Authorization string
     * @return String       Result of request
     */
    private function makeRequest($url, $authorizationHeader)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/xml',
            'Content-Type: application/xml',
            'X-Amz-Date: ' . $this->amzDate,
            'Authorization: ' . $authorizationHeader
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * @param string $response
     *
     * @return int
     */
    private function parseRank($response) //check this function
    {
        if (!$response) {
            return 0;
        }

        $xml = new \SimpleXMLElement($response, null, false, 'http://awis.amazonaws.com/doc/2005-07-11');

        if ($xml->count() && $xml->Response->UrlInfoResult->Alexa->count()) {
            $info = $xml->Response->UrlInfoResult->Alexa;

            return (int) $info->TrafficData->Rank;
        }

        return 0;
    }
}
