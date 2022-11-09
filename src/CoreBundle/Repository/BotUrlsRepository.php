<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

/**
 * Class BotUrlsRepository
 *
 * @package CoreBundle\Repository
 */
class BotUrlsRepository extends EntityRepository
{

    /**
     * @param string $analyzedUrl
     *
     * @return int
     */
    public function getCount($analyzedUrl = null)
    {
        $qb = $this->createQueryBuilder('bu');
        $qb->select($qb->expr()->count('bu') . ' as cnt');

        if (!empty($analyzedUrl)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('bu.analyzedUrl', $qb->expr()->literal($analyzedUrl))
                );
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return isset($result['cnt']) ? (int) $result['cnt']:0;
    }
}