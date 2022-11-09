<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserPaymentDataRepository
 *
 * @package CoreBundle\Entity
 */
class UserPaymentDataRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('upd');

        return $qb;
    }
}