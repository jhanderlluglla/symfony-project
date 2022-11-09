<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Filter;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FilterRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * @param array $filters
     * @param boolean $count
     * @param int|boolean $limit
     * @param array $sort
     *
     * @return QueryBuilder|array
     */
    public function filter(array $filters, $count = false, $limit = false, $sort = [])
    {
        $qb = $this->createQueryBuilder('f');

        if (isset($filters['type'])) {
            switch ($filters['type']) {
                case Filter::TYPE_DIRECTORY_LIST:
                    $qb->leftJoin(DirectoriesList::class, 'dl', 'WITH', 'f.context = dl.id');
                    $qb->leftJoin(NetlinkingProject::class, 'np', 'WITH', 'np.directoryList = dl.id');

                    if (isset($filters['np.startedAt'])) {
                        $qb->andWhere('np.startedAt > :npStartedAt');
                        $qb->setParameter('npStartedAt', $filters['np.startedAt']);
                    }

                    if (isset($filters['user'])) {
                        $qb->andWhere('np.user = :user');
                    }
                    break;

                default:
                    throw new UnprocessableEntityHttpException('Filter type "' . $filters['type'] . '" is not correct');
            }
        }

        if (isset($filters['user'])) {
            $qb->andWhere('f.user = :user');
            $qb->setParameter('user', $filters['user']);
        }


        if (isset($filters['updatedAt'])) {
            $qb->andWhere('f.updatedAt > :fUpdatedAt');
            $qb->setParameter('fUpdatedAt', $filters['updatedAt']);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        foreach ($sort as $sortField => $order) {
            $qb->addOrderBy($sortField, $order);
        }

        $qb->groupBy('f.id');

        return $qb;
    }

    /**
     * @param array $filters
     * @param int|boolean $limit
     * @param array $sort
     *
     * @return array
     */
    public function findByFilter(array $filters, $limit = false, $sort = [])
    {
        return $this->filter($filters, false, $limit, $sort)->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param string $type
     * @param null $context
     *
     * @return Filter
     */
    public function findByType($user, $type, $context = null)
    {
        $qb = $this->createQueryBuilder('f');

        $qb->andWhere('f.user = :user')
            ->setParameter('user', $user, \Doctrine\DBAL\Types\Type::OBJECT)
            ->andWhere('f.type = :type')
            ->setParameter('type', $type);
        if ($context) {
            $qb
                ->andWhere('f.context = :context')
                ->setParameter('context', $context);
        } else {
            $qb
                ->andWhere('f.context IS NULL');
        }

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
        }
    }

    /**
     * @param User $user
     * @param string $type
     * @param array $data
     * @param string $context - numeric or string id of the object in the context of which the filter was saved
     * @param array $defaultParams - these params will be removed (key => value or key => null)
     *
     * @return Filter
     */
    public function save($user, $type, $data, $context = null, $defaultParams = [])
    {
        // Remove default params
        $defaultParams['_token'] = null;
        foreach ($defaultParams as $k => $v) {
            if (isset($data[$k]) && ($v === null || $data[$k] === $v)) {
                unset($data[$k]);
            }
        }

        $filter = $this->findByType($user, $type, $context);

        if (!$filter) {
            $filter = new Filter();
            $filter->setUser($user);
            $filter->setType($type);
            $filter->setContext($context);
        }

        $filter->setData($data);

        try {
            $this->getEntityManager()->persist($filter);
            $this->getEntityManager()->flush();
        } catch (OptimisticLockException $e) {
        }

        return $filter;
    }
}
