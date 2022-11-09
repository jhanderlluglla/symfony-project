<?php

namespace CoreBundle\Repository;
use Doctrine\ORM\EntityRepository;

/**
 * Class ArticleBlogRepository
 *
 * @package CoreBundle\Entity
 */
class ArticleBlogRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('ab');

        return $qb;
    }
}