<?php

namespace Tests\CoreBundle\Services;

/**
 * Class GoogleAnalyticsInfo
 *
 * @package CoreBundle\Services
 */
class GoogleAnalyticsInfo extends \CoreBundle\Services\GoogleAnalyticsInfo
{
    /**
     * @param string $site
     *
     * @return mixed
     */
    public function getInfo($site)
    {
        return true;
    }
}
