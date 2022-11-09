<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Collections\Criteria;
use CoreBundle\Entity\User;

/**
 * Class DirectoriesListRepository
 *
 * @package CoreBundle\Repository
 */
class DirectoriesListRepository extends BaseRepository implements FilterableRepositoryInterface
{

    protected $filters = ['user', 'netlinkingProject'];
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('dl');
        $this->prepare($filters, $qb);

        if ($count) {
            $qb->select($qb->expr()->count('dl') . ' as cnt');
            $result = $qb->getQuery()->getOneOrNullResult();

            return isset($result['cnt']) ? (int) $result['cnt'] : 0;
        }

        return $qb;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getDirectoriesListAsKeyAndValue($user)
    {
        $result = [];
        $qb = $this->createQueryBuilder('dl');

        if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $qb
                ->where(
                    'dl.user = :user'
                );

            $qb->setParameter('user', $user, Type::OBJECT);
        }

        $qb->orderBy('dl.name', Criteria::ASC);

        $users = $qb->getQuery()->getArrayResult();
        if ($users) {
            foreach ($users as $user) {
                $result[$user['id']] = $user['name'];
            }
        }

        return $result;
    }

    /**
     * @param $filters
     *
     * @return QueryBuilder
     */
    public function getNotEmptyDirectoriesList($filters)
    {
        $qb = $this->filter($filters);

        $qb
            ->leftJoin('dl.exchangeSite', 'es')
            ->leftJoin('dl.directories', 'd')
            ->andWhere($qb->expr()->orX(
                'es.id IS NOT NULL',
                'd.id IS NOT NULL'
            ))
            ->groupBy('dl.id')
        ;

        return $qb;
    }
}
