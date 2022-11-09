<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;

class TransactionRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('t');

        /** @var User $user */
        $user = $filters['user'];
        if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $qb->andWhere(
                't.user = :user'
            );

            $qb->setParameter('user', $user, Type::OBJECT);
        } elseif (isset($filters['user_id'])) {
            $qb->andWhere(
                't.user = :user'
            );

            $qb->setParameter('user', $filters['user_id'], Type::OBJECT);
        }

        if (isset($filters['hidden']) && $filters['hidden'] == 1) {
            $hidden = 1;
        } else {
            $hidden = 0;
        }

        $qb->andWhere('t.hidden = ' . $hidden);

        $qb->orderBy($qb->expr()->desc('t.createdAt'));

        return $qb;
    }

    /**
     * @param User $user
     * @param \DateTime $date
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDebitByUser($user, $date)
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->select("SUM(t.debit) as sumDebit")
            ->addSelect("SUM(t.credit) as sumCredit")
            ->where('t.user = :user')
            ->andWhere('t.createdAt > :date')
            ->setParameter('user', $user, Type::OBJECT)
            ->setParameter('date', $date, Type::DATETIME)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
