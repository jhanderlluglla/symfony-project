<?php

namespace Tests\CoreBundle\Services;

/**
 * Class MajesticInfo

 */
class MozInfo extends \CoreBundle\Services\MozInfo
{
    public function batchRetrieveData($domains)
    {
        $this->domainInfo = array_fill(0, count($domains) - 1, ['upa' => 10, 'pda' => 12]);
    }

    protected function retrieveData($domain)
    {
        return ['upa' => mt_rand(1, 99), 'pda' => mt_rand(1, 99)];
    }
}
