<?php

namespace CoreBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MementoWebInfo
 *
 * @package CoreBundle\Services
 */
class MementoInfo
{
    const SERVICE_HOST = 'http://timetravel.mementoweb.org';

    const SERVICE_URI = '/api/json/';

    /**
     * @var Client
     */
    protected $client;

    /**
     * MementoInfo constructor.
     *
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::SERVICE_HOST,
            'timeout' => 50,
        ]);
    }

    /**
     * @param string $site
     *
     * @return \DateTime
     */
    public function getFirstSnapshotDate($site)
    {
        $uri = self::SERVICE_URI . date('Ymd') . '/' . $site;

        try {
            $response = $this->client->request(Request::METHOD_GET, $uri);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        $content = $response->getBody()->getContents();


        return $this->parseResponse($content);
    }

    /**
     * @param string $response
     *
     * @return \DateTime
     */
    private function parseResponse($response)
    {
        $data = json_decode($response,true);

        $firstSnapshotDate = $data['mementos']['first']['datetime'];

        return \DateTime::createFromFormat(\DateTime::ATOM, $firstSnapshotDate);
    }
}