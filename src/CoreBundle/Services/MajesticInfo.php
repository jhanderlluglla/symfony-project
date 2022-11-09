<?php

namespace CoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class MajesticInfo
 *
 * @package CoreBundle\Services
 */
class MajesticInfo
{

    const SERVICE_HOST = 'https://api.majestic.com';

    const SERVICE_URI = '/api/json';

    const COMMAND = 'GetIndexItemInfo';

    const ITEMS = 1;

    private $urlInfo = [];

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
     * @param string $uri
     *
     * @return mixed
     */
    public function getUrlInfo($uri)
    {
        $options = [
            'query' => [
                'app_api_key' => $this->apiKey,
                'cmd' => self::COMMAND,
                'items' => self::ITEMS,
                'item0' => $uri,
                'datasource' => 'fresh',
            ]
        ];

        try {
            $response = $this->client->request(Request::METHOD_GET, self::SERVICE_URI, $options);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        $content = $response->getBody()->getContents();

        return json_decode($content, true);
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    public function getTrustFlow($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['TrustFlow'];
        }

        return 0;
    }

    /**
     * @param string $domain
     *
     * @return int
     */
    public function getRefDomains($domain)
    {
        if(isset($this->urlInfo[$domain])) {
            $data = $this->urlInfo[$domain];
        } else {
            $data = $this->getUrlInfo($domain);
            $this->urlInfo[$domain] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['RefDomains'];
        }

        return 0;
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    public function getTopicalTrustFlow($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            $parameters = $data['DataTables']['Results']['Data'][0];

            $ttfTopicsKeys = preg_grep('/^TopicalTrustFlow_Topic/', array_keys($parameters));
            $ttfValuesKeys = preg_grep('/^TopicalTrustFlow_Value/', array_keys($parameters));

            $ttfTopics = array_intersect_key($parameters, array_flip($ttfTopicsKeys));
            $ttfValues = array_intersect_key($parameters, array_flip($ttfValuesKeys));

            $ttfCategories = array_combine($ttfTopics, $ttfValues);

            return $ttfCategories;
        }

        return [];
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    public function getCitationFlow($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['CitationFlow'];
        }

        return 0;
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    public function getBacklinks($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['ExtBackLinks'];
        }

        return 0;
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    public function getEduBacklinks($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['ExtBackLinksEDU'];
        }

        return 0;
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    public function getGovBacklinks($uri)
    {
        if(isset($this->urlInfo[$uri])) {
            $data = $this->urlInfo[$uri];
        } else {
            $data = $this->getUrlInfo($uri);
            $this->urlInfo[$uri] = $data;
        }

        if ($data['Code'] == 'OK') {
            return (int) $data['DataTables']['Results']['Data'][0]['ExtBackLinksGOV'];
        }

        return 0;
    }
}