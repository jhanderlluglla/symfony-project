<?php

namespace Tests\CoreBundle\Services;

/**
 * Class BwaInfo
 *
 * @package CoreBundle\Services
 */
class BwaInfo extends \CoreBundle\Services\BwaInfo
{
    /**
     * @param string $domain
     *
     * @return false|mixed|string
     */
    public function getDomainCreation($domain)
    {
        return date('Y-m-d');
    }
}
