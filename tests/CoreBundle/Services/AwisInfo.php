<?php

namespace Tests\CoreBundle\Services;

/**
 * Class AwisInfo
 *
 * @package CoreBundle\Services
 */
class AwisInfo extends \CoreBundle\Services\AwisInfo
{
    /**
     * @param string $site
     *
     * @return int
     */
    public function getAlexaRank($site)
    {
        return mt_rand(0, 100);
    }
}
