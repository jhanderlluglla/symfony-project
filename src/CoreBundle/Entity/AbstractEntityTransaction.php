<?php

namespace CoreBundle\Entity;

use CoreBundle\Helpers\TransactionHelper;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

abstract class AbstractEntityTransaction
{
    /**
     * @var Transaction[]|PersistentCollection
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\Transaction", cascade={"persist"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    /**
     * @return Transaction[]|PersistentCollection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $criteria
     *
     * @return Transaction[]|PersistentCollection
     */
    public function findTransactions($criteria)
    {
        if (isset($criteria['tag'])) {
            $tagName = $criteria['tag'];
            unset($criteria['tag']);
        } else {
            $tagName = null;
        }

        if (!empty($criteria)) {
            $criteriaObj = Criteria::create();
            foreach ($criteria as $key => $value) {
                $criteriaObj->andWhere(
                    Criteria::expr()->eq($key, $value)
                );
            }

            $result = $this->transactions->matching($criteriaObj);
        } else {
            $result = $this->transactions;
        }

        if ($tagName) {
            TransactionHelper::filterByTag($result, $tagName);
        }

        return $result;
    }

    /**
     * @param $tagName
     *
     * @return Transaction[]
     */
    public function getTransactionsByTag($tagName)
    {
        return $this->findTransactions(['tag' => $tagName]);
    }

    /**
     * @param Transaction[] $transactions
     *
     * @return self
     */
    public function setTransactions($transactions)
    {
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }

        return $this;
    }

    /**
     * @param Transaction $transaction
     *
     * @return self
     */
    public function addTransaction($transaction)
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
        }

        return $this;
    }

    /**
     * @param Transaction $transaction
     *
     * @return self
     */
    public function removeTransaction($transaction)
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
        }

        return $this;
    }
}
