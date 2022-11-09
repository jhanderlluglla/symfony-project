<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;

/**
 * Class NetlinkingProjectCommentsRepository
 *
 * @package CoreBundle\Repository
 */
class NetlinkingProjectCommentsRepository extends EntityRepository implements FilterableRepositoryInterface
{

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('nlc');

        return $qb;
    }
}