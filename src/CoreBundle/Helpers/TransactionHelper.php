<?php

namespace CoreBundle\Helpers;

use CoreBundle\Entity\Transaction;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

class TransactionHelper
{
    /**
     * @param PersistentCollection|Transaction[] $transactions
     * @param string $tagName
     *
     * @return Collection|Transaction[]
     */
    public static function filterByTag($transactions, $tagName)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(
            Criteria::expr()->eq('name', $tagName)
        );

        return $transactions->filter(function (Transaction $transaction) use ($criteria) {
            $tags = $transaction->getTags();
            if ($tags instanceof PersistentCollection) {
                $tags->initialize();
            }

            return !$tags->matching($criteria)->isEmpty();
        });
    }
}
