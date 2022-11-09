<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Transaction;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Class TransactionTagRepository
 *
 * @package CoreBundle\Entity
 */
class TransactionTagRepository extends BaseRepository implements FilterableRepositoryInterface
{
    /**
     * @var array
     */
    protected $filters = ['name'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('tt');

        $this->prepare($filters, $qb);

        return $qb;
    }

    /**
     * @param $name
     *
     * @return Transaction|null
     */
    public function getByName($name)
    {
        try {
            return $this->filter(['name' => $name])->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
