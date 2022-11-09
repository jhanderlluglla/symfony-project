<?php

namespace Tests\CoreBundle\Services\Metrics;

use GuzzleHttp\Client;

/**
 * Class MajesticInfo
 *
 * @package CoreBundle\Services\Metrics
 */
class Semrush extends \CoreBundle\Services\Metrics\Semrush
{
    /**
     * @param string $domain
     * @param string $regionalDatabase
     * @throws \Exception
     */
    public function getDomainOverview($domain, $regionalDatabase)
    {
        $this->requestedDomain = $domain;
        $this->regionalDatabase = $regionalDatabase;
        $this->response = [
            'Database' => $regionalDatabase,
            'Domain' => $domain,
            'Rank' => '17',
            'Organic Keywords' => '16464474',
            'Organic Traffic' => '149904314',
            'Organic Cost' => '169865994',
            'Adwords Keywords' => '128201',
            'Adwords Traffic' => '2419518',
            'Adwords Cost' => '2807373',
            'PLA keywords' => '38208',
            'PLA uniques' => '1583',
        ];
    }
}
