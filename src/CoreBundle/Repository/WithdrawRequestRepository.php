<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class WithdrawRequestRepository
 *
 * @package CoreBundle\Repository
 */
class WithdrawRequestRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function filter($filters)
    {
        $qb = $this->createQueryBuilder('wr');
        if(isset($filters['user'])){
            $qb
                ->andWhere('wr.user = :user')
                ->setParameter('user', $filters['user'])
            ;
        }
        $qb->orderBy('wr.createdAt', 'desc');

        return $qb;
    }

    /**
     * @param User $user
     * @param array $filters
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountByLastMonth($user, $filters = [])
    {
        $lastMonth = new \DateTime();
        $lastMonth->modify('-1 month');

        $qb = $this->createQueryBuilder('wr');

        $qb
            ->select('COUNT(wr.id)')
            ->where('wr.user = :user')
            ->setParameter('user', $user)
            ->andWhere('wr.createdAt > :lastMonth')
            ->setParameter('lastMonth', $lastMonth)
        ;

        if (isset($filters['status'])) {
            $qb->andWhere('wr.status IN (:status)');
            $qb->setParameter('status', $filters['status']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return Pagerfanta
     */
    public function getCollection($filters, $page = 1, $perPage = 20)
    {
        $adapter = new DoctrineORMAdapter($this->filter($filters));
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $pagerfanta;
    }

    /**
     * @param $user
     *
     * @return mixed
     */
    public function getLastByUser($user)
    {
        $qb = $this->createQueryBuilder('wr');
        $qb->where('wr.user = :user_id')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNotNull('wr.paypal'),
                $qb->expr()->isNotNull(
                    'wr.iban'),
                $qb->expr()->isNotNull('wr.swift')
            ))
            ->orderBy('wr.id', 'DESC')
            ->setParameter('user_id', $user->getId())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
