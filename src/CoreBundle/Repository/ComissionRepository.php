<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ComissionRepository
 *
 * @package CoreBundle\Repository
 */
class ComissionRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param null $date
     *
     * @return array
     */
    public function getStatisticByUser($user, $date = null)
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->select($qb->expr()->count('c') . ' as registered')
            ->addSelect('SUM(c.amount) as earnings')
            ->where('c.user = :user')
        ;

        if (!is_null($date)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('DATE_FORMAT(c.createdAt, \'%Y-%m\')', $qb->expr()->literal($date))
                );
        }

        $qb->setParameter('user', $user, Type::OBJECT);

        $result = $qb->getQuery()->getOneOrNullResult();

        return [
            'registered' => !empty($result['registered']) ? (int) $result['registered']:0,
            'earnings' => !empty($result['earnings']) ? (float) $result['earnings']:0,
        ];
    }

    /**
     * @param User $user
     * @param string $month
     * @param string $year
     *
     * @return array
     */
    public function getComissionDetail($user, $month, $year)
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->where('c.user = :user')
            ->andWhere(
                $qb->expr()->eq('DATE_FORMAT(c.createdAt, \'%Y-%m\')', $qb->expr()->literal(implode('-', [$year, $month])))
            )
        ;

        $qb->setParameter('user', $user, Type::OBJECT);

        return $qb->getQuery()->getResult();
    }
}