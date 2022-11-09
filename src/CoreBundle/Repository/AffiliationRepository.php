<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

/**
 * Class AffiliationRepository
 *
 * @package CoreBundle\Entity
 */
class AffiliationRepository extends EntityRepository implements FilterableRepositoryInterface
{

    /**
     * @param User $user
     * @param null $date
     *
     * @return array
     */
    public function getStatisticByUser($user, $date = null)
    {
        $qb = $this->createQueryBuilder('a');

        $qb
            ->select($qb->expr()->count('a') . ' as registered')
            ->addSelect('SUM(a.tariff) as earnings')
            ->where('a.parent = :parent')
        ;

        if (!is_null($date)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('DATE_FORMAT(a.createdAt, \'%Y-%m\')', $qb->expr()->literal($date))
                );
        }

        $qb->setParameter('parent', $user, Type::OBJECT);

        $result = $qb->getQuery()->getOneOrNullResult();

        return [
            'registered' => !empty($result['registered']) ? (int) $result['registered']:0,
            'earnings' => !empty($result['earnings']) ? (float) $result['earnings']:0,
        ];
    }

    /**
     * @param User $parent
     * @param User $affiliation
     */
    public function hasAffilation($parent, $affiliation)
    {
        $qb = $this->createQueryBuilder('a');

        $qb
            ->select($qb->expr()->count('a') . ' as cnt')
            ->where(
                $qb->expr()->andX(
                    'a.parent = :parent',
                       'a.affiliation = :affiliation'
                )
            )
        ;

        $qb->setParameter('parent', $parent, Type::OBJECT);
        $qb->setParameter('affiliation', $affiliation, Type::OBJECT);

        $result = $qb->getQuery()->getOneOrNullResult();

        return !empty($result['cnt']);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('a');

        return $qb;
    }
}