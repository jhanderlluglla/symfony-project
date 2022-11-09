<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface FilterableRepositoryInterface
 */
interface FilterableRepositoryInterface
{
    /**
     * @param array   $filters
     * @param boolean $count
     *
     * @return QueryBuilder|array
     */
    public function filter(array $filters, $count = false);
}
