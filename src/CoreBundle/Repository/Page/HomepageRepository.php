<?php
namespace CoreBundle\Repository\Page;

use CoreBundle\Repository\FilterableRepositoryInterface;
use Doctrine\ORM\EntityRepository;

class HomepageRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('h');
        return $qb;
    }
}