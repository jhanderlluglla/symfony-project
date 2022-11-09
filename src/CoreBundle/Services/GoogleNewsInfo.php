<?php

namespace CoreBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GoogleNewsInfo
 *
 * @package CoreBundle\Services
 */
class GoogleNewsInfo
{
    const SERVICE_HOST = 'https://newsapi.org';

    const SERVICE_URI = '/v2/everything';

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * MajesticInfo constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        $this->client = new Client([
            'base_uri' => self::SERVICE_HOST,
            'timeout' => 50,
        ]);
    }

    /**
     * @param string $domain
     *
     * @return integer
     */
    public function isSource($domain)
    {
        $params = ['domains'=> $domain,'apiKey' => $this->apiKey];
        try {
            $response = $this->client->request(Request::METHOD_GET, self::SERVICE_URI.'?'.http_build_query($params));
        } catch (GuzzleException $e) {
            return false;
        }

        $content = $response->getBody()->getContents();

        return $this->parseResponse($content);
    }

    /**
     * @param string $response
     *
     * @return boolean
     */
    private function parseResponse($response)
    {
        $data = json_decode($response,true);

        return $data['totalResults'];
    }
}