<?php

namespace CoreBundle\Services\Metrics;

use GuzzleHttp\Client;

/**
 * Class MajesticInfo
 *
 * @package CoreBundle\Services\Metrics
 */
class Semrush
{
    const SERVICE_HOST = 'https://api.semrush.com';

    const REPORT_TYPE = 'domain_ranks';

    const ORGANIC_KEYWORDS = ['key' => 'Or', 'name' => 'Organic Keywords'];
    const ORGANIC_TRAFFIC = ['key' => 'Ot', 'name' => 'Organic Traffic'];
    const ORGANIC_COST = ['key' => 'Oc', 'name' => 'Organic Cost'];

    const COLUMNS_KEYS = [
        self::ORGANIC_KEYWORDS['key'],
        self::ORGANIC_TRAFFIC['key'],
        self::ORGANIC_COST['key']
    ];

    const LANGUAGE_TO_REGION = ["en" => "uk"];

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $response;

    /** @var string */
    protected $requestedDomain = null;

    /** @var string */
    protected $regionalDatabase = null;

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

        $this->response = [];
    }

    /**
     * @param string $domain
     * @param string $regionalDatabase
     * @throws \Exception
     */
    public function getDomainOverview($domain, $regionalDatabase)
    {
        if ($this->response && $this->requestedDomain === $domain && $this->regionalDatabase === $regionalDatabase) {
            return true;
        }

        if (is_null($this->apiKey)) {
            throw new \Exception("Api key is null");
        }

        $options = [
            'query' => [
                'key' => $this->apiKey,
                'type' => self::REPORT_TYPE,
                'domain' => $domain,
                'database' => $this->convert($regionalDatabase),
                'export_columns' => implode(",", self::COLUMNS_KEYS)
            ]
        ];

        $response = $this->client->request("GET", '', $options);
        $content = $response->getBody()->getContents();

        if (strpos($content, 'ERROR') === 0) {
            throw new \Exception($content);
        }

        $this->requestedDomain = $domain;
        $this->regionalDatabase = $regionalDatabase;

        $this->response = $this->parseResponse($content);
    }
    
    /**
     * @return int|null
     */
    public function getSemrushTraffic($domain, $language)
    {
        try {
            $this->getDomainOverview($domain, $language);

            return $this->getValue(self::ORGANIC_TRAFFIC['name']);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return int|null
     */
    public function getSemrushKeyword($domain, $language)
    {
        try {
            $this->getDomainOverview($domain, $language);

            return $this->getValue(self::ORGANIC_KEYWORDS['name']);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return int|null
     */
    public function getSemrushTrafficCost($domain, $language)
    {
        try {
            $this->getDomainOverview($domain, $language);

            return $this->getValue(self::ORGANIC_COST['name']);

        } catch (\Exception $e) {
            return 0;
        }
    }

    private function convert($language)
    {
        if (in_array($language, array_keys(self::LANGUAGE_TO_REGION))) {
            return self::LANGUAGE_TO_REGION[$language];
        }
        return $language;
    }

    /**
     * @param $content
     * @return array
     */
    private function parseResponse($content)
    {
        $csv = str_getcsv($content, "\n");

        return array_combine(str_getcsv($csv[0], ";"), str_getcsv($csv[1], ";"));
    }

    private function getValue($key)
    {
        if ($this->response) {
            return $this->response[$key];
        }

        return null;
    }
}
