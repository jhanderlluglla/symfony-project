<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class AdminImageRepository
 *
 * "MATCH_AGAINST" "(" {StateFieldPathExpression ","}* InParameter {Literal}? ")"
 */
class AdminImageRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('ai');
        return $qb;
    }

    /**
     * @param string $query
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSearchQueryBuilder($query)
    {
        $qb = $this->createQueryBuilder('ai');

        $qb
            ->select('ai')
            ->andWhere("MATCH_AGAINST(ai.description) AGAINST(:searchTerm BOOLEAN) > 0 ")
            ->setParameter('searchTerm', '+' . $query . '* ');

        return $qb;
    }
}
