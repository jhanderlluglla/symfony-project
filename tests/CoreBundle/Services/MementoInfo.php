<?php

namespace Tests\CoreBundle\Services;

/**
 * Class MementoWebInfo
 *
 * @package CoreBundle\Services
 */
class MementoInfo extends \CoreBundle\Services\MementoInfo
{
    /**
     * @param string $site
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    public function getFirstSnapshotDate($site)
    {
        return new \DateTime();
    }
}
