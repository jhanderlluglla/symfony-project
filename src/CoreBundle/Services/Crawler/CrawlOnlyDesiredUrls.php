<?php

namespace CoreBundle\Services\Crawler;

use Spatie\Crawler\CrawlProfile;
use Spatie\Crawler\Url;

class CrawlOnlyDesiredUrls implements CrawlProfile
{
    /**
     * @var string
     */
    private $baseHost;

    /**
     * @var string
     */
    private $secondLevelDomain;

    /**
     * @var array
     */
    private $desiredHosts;

    /**
     * CrawlOnlyDesiredUrls constructor.
     * @param string $baseUrl
     * @param array $backLinks
     */
    public function __construct(string $baseUrl, array $backLinks)
    {
        $this->baseHost = parse_url($baseUrl, PHP_URL_HOST);

        $explodedHost = explode('.', $this->baseHost);
        end($explodedHost);
        $this->secondLevelDomain = prev($explodedHost) . '.' . next($explodedHost);

        $this->desiredHosts = array_map(function ($element) {
            return parse_url($element, PHP_URL_HOST);
        }, array_keys($backLinks));
    }

    public function shouldCrawl(Url $url): bool
    {
        if ($url->host === $this->baseHost) {
            return true;
        }

        if ($this->isSubDomainOfHost($url)) {
            return true;
        }

        if (in_array($url->host, $this->desiredHosts)) {
            return true;
        }

        return false;
    }

    public function isSubDomainOfHost(Url $url): bool
    {
        return strpos($url->host, $this->secondLevelDomain) !== false;
    }
}
