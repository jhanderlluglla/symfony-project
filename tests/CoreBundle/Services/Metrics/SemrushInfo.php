<?php

namespace Tests\CoreBundle\Services\Metrics;

use CoreBundle\Services\Metrics\Semrush;

/**
 * Class MajesticInfo
 */
class SemrushInfo extends Semrush
{
    public function getDomainOverview($domain, $regionalDatabase)
    {
        return array_fill(0, count($domain) - 1, ['upa' => 10, 'pda' => 12]);
    }
}
