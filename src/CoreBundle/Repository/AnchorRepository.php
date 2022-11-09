<?php

namespace CoreBundle\Repository;

/**
 * Class AnchorRepository
 *
 * @package CoreBundle\Entity
 */
class AnchorRepository extends BaseRepository implements FilterableRepositoryInterface
{
    /**
     * @var array
     */
    protected $filters = ['netlinkingProject','directory', 'exchangeSite'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('a');

        $this->prepare($filters, $qb);

        return $qb;
    }
}
