<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class AffiliationClickRepository
 *
 * @package CoreBundle\Entity
 */
class AffiliationClickRepository extends EntityRepository implements FilterableRepositoryInterface
{

    /**
     * @param User        $user
     * @param string|null $date
     *
     * @return int
     */
    public function getCountByUser($user, $date = null)
    {
        $qb = $this->createQueryBuilder('ac');

        $qb
            ->select($qb->expr()->count('ac') . ' as cnt')
            ->where('ac.user = :user')
        ;

        $qb->setParameter('user', $user);

        if (!is_null($date)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('DATE_FORMAT(ac.createdAt, \'%Y-%m\')', $qb->expr()->literal($date))
                );
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return isset($result['cnt']) ? (int) $result['cnt']:0;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('ac');

        return $qb;
    }


}