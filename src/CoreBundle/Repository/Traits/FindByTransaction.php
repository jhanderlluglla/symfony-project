<?php

namespace CoreBundle\Repository\Traits;

use CoreBundle\Entity\Transaction;
use Doctrine\ORM\QueryBuilder;

trait FindByTransaction
{

    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    public function findByTransaction(Transaction $transaction)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e');
        $qb
            ->innerJoin('e.transactions', 't')
            ->andWhere('t.id = :transactionId')
            ->setParameter('transactionId', $transaction->getId())
        ;

        return $qb->getQuery()->getResult();
    }
}