<?php

namespace CoreBundle\Services\Bwa;

class BwaApi
{

    const API_URL = "http://api.bulk-whois-api.com/api/";

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * @param $action
     * @param array $params
     *
     * @return array
     *
     * @throws \Exception
     */
    private function sendRequest($action, array $params = array())
    {
        $result = null;

        $postFields = http_build_query($params);
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $time = $dateTime->format('Y-m-d H:i:s');

        $message = $this->key . $time . $postFields;
        $signature = hash_hmac('sha512', $message, $this->secret);

        // generate extra headers
        $headers = array(
            'Sign: ' . $signature,
            'Time: ' . $time,
            'Key: ' . $this->key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::API_URL . $action);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $res = curl_exec($ch);

        if ($res === false) {
            throw new \Exception('Could not get a reply: ' . curl_error($ch));
        } else {
            $result = json_decode($res, true);

            if ($result === null) {
                throw new \Exception('Invalid response received.');
            }
        }

        return $result;
    }

    public function authTest()
    {
        $response = $this->sendRequest("authTest");
        $result = new BwaApiAuthTest($response);

        return $result;
    }

    public function accountInfo()
    {
        $response = $this->sendRequest("info");
        $result = new BwaApiInfo($response);

        return $result;
    }

    public function whoisQuerySynchronous($query)
    {
        $response = $this->sendRequest("query", array('query' => $query));
        $result = new BwaApiWhoisQuery($response);

        return $result;
    }

    public function whoisQueryAsynchronous($query, $callbackUrl)
    {
        $response = $this->sendRequest("query", array('query' => $query, "asyncCallback" => $callbackUrl));
        $result = new BwaApiResponse($response);

        return $result;
    }

    /**
     * @param string $query
     * @return bool|BwaApiWhoisQuery  false on error
     * @throws \Exception
     */
    public function whoisQueryPolling($query)
    {
        $result = false;

        $response = $this->sendRequest("query", array('query' => $query, 'polling' => "1"));
        $pollingResponse = new BwaApiPollResponse($response); // may throw exception
        $url = $pollingResponse->resultUrl;

        $done = false;
        while (!$done) {

            sleep(5);
            $json = file_get_contents($url);
            $response = json_decode($json, true);

            if ($response === null) {
                throw new \Exception("Invalid JSON.");
            } else {
                $result = new BwaApiWhoisQuery($response);

                if (!$result->success && $result->message === "Pending.") {
                    # Not finished yet.
                } else {
                    $done = true;
                }
            }
        }

        return $result;
    }

}