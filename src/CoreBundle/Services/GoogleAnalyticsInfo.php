<?php

namespace CoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class GoogleAnalyticsInfo
 *
 * @package CoreBundle\Services
 */
class GoogleAnalyticsInfo
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * GoogleAnalyticsInfo constructor.
     *
     */
    public function __construct()
    {

        $this->client = new Client();
    }

    /**
     * @param string $site
     *
     * @return mixed
     */
    public function getInfo($site)
    {
        try {
            $response = $this->client->request(Request::METHOD_GET, $site);

            $content = $response->getBody()->getContents();
            return preg_match('/UA-[0-9]+/', $content) && (preg_match('/google-analytics/', $content) || preg_match('/ga\.js/', $content));
        } catch (\Exception $e) {
            return null;
        }
    }
}
