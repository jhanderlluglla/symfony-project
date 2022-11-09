<?php

namespace UserBundle\Services\ExchangeSite;

use CoreBundle\Entity\ExchangeProposition;

/**
 * Interface ExchangePropositionInterface
 *
 * @package UserBundle\Services\ExchangeSite
 */
interface ExchangePropositionInterface
{
    /**
     * @param integer $exchangeSiteId
     * @param array   $data
     * @param ExchangeProposition|null $exchangeProposition
     *
     * @return array
     */
    public function handler($exchangeSiteId, $data, $exchangeProposition = null);
}