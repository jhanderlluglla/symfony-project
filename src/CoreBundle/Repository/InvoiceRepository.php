<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

class InvoiceRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param array $filters
     *
     * @return QueryBuilder
     */
    public function filter(array $filters)
    {
        $qb = $this->createQueryBuilder('i');

        /** @var User $user */
        $user = $filters['user'];

        if ($user->hasRole(User::ROLE_WEBMASTER)) {
            $qb->andWhere(
                'i.user = :user'
            );
            $qb->setParameter('user', $user, Type::OBJECT);
        }
        $qb->orderBy('i.createdAt', Criteria::DESC);

        return $qb;
    }
}
