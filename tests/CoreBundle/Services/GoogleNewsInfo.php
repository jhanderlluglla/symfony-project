<?php

namespace Tests\CoreBundle\Services;

/**
 * Class GoogleNewsInfo
 *
 * @package CoreBundle\Services
 */
class GoogleNewsInfo extends \CoreBundle\Services\GoogleNewsInfo
{
    /**
     * @param string $domain
     *
     * @return integer
     */
    public function isSource($domain)
    {
        return mt_rand(1, 100);
    }
}