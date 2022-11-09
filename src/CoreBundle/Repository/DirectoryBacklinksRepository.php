<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Job;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;

/**
 * Class DirectoryBacklinksRepository
 *
 * @package CoreBundle\Repository
 */
class DirectoryBacklinksRepository extends EntityRepository
{

    /**
     * @param User|null $user
     * @param string $status
     * @return QueryBuilder
     */
    public function findByStatus($user = null, $status = DirectoryBacklinks::STATUS_NOT_FOUND_YET)
    {
        $qb = $this->createQueryBuilder('dbl');

        $qb
            ->leftJoin('dbl.job', 'j')
            ->leftJoin('j.scheduleTask', 'st')
            ->leftJoin('j.netlinkingProject', 'np')
            ->leftJoin('st.directory', 'd')
            ->andWhere('dbl.status = :status')
            ->setParameter('status', $status)
        ;

        if (!is_null($user)) {
            if ($user->isWriterNetlinking()) {
                $qb
                    ->andWhere('j.affectedToUser = :affectedToUser')
                    ->setParameter('affectedToUser', $user, Type::OBJECT)
                ;
            }
        }

        return $qb;
    }


    /**
     * @param User|null $user
     * @param string $status
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCount($user = null, $status = DirectoryBacklinks::STATUS_NOT_FOUND_YET)
    {
        $qb = $this->findByStatus($user, $status);

        $qb->select($qb->expr()->count('dbl.id'));
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function usersCount($ids)
    {
        $qb = $this->createQueryBuilder('dbl');

        $qb
            ->select("SUM(CASE WHEN dbl.status = '" . DirectoryBacklinks::STATUS_FOUND ."' then 1 else 0 end) found")
            ->addSelect("SUM(CASE WHEN dbl.status = '" . DirectoryBacklinks::STATUS_NOT_FOUND ."' then 1 else 0 end) not_found")
            ->addSelect('IDENTITY(j.affectedToUser) as id')
        ;

        $qb
            ->leftJoin('dbl.job', 'j')
            ->andWhere($qb->expr()->in('j.affectedToUser', ':users'))
            ->setParameter('users', $ids)
            ->groupBy('j.affectedToUser')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param string            $status
     *
     * @return int
     */
    public function getCountByNetlinkingProject($netlinkingProject, $status = 'not_found_yet')
    {
        $qb = $this->createQueryBuilder('dbl');
        $qb
            ->select('COUNT(1) as cnt')
            ->innerJoin('dbl.job', 'j')
            ->andWhere(
                'j.netlinkingProject = :netlinkingProject'
            )
            ->andWhere(
                'dbl.status = :status'
            )
            ->setParameter('netlinkingProject', $netlinkingProject, Type::OBJECT)
            ->setParameter('status', $status)
        ;

        $result = $qb->getQuery()->getSingleResult();

        return isset($result['cnt']) ? (int) $result['cnt']:0;
    }

    /**
     * @param $projects
     * @return array
     */
    public function getStatisticsByProjects($projects)
    {
        if(count($projects) > 0){
            $qb  = $this->_em->createQueryBuilder();

            $qb
                ->select('IDENTITY(j.netlinkingProject) as netlinkingProjectId')
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_FOUND . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_FOUND)
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_NOT_FOUND_YET . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_NOT_FOUND_YET)
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_NOT_FOUND . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_NOT_FOUND)
                ->addSelect('SUM(CASE WHEN j.status = \'' . Job::STATUS_IMPOSSIBLE . '\' then 1 else 0 end) impossible')
                ->from(Job::class, 'j')
                ->leftJoin('j.directoryBacklink', 'db')
                ->groupBy('j.netlinkingProject')
            ;

            $ids = $this->getCollectionIds($projects);
            $qb->where($qb->expr()->in('j.netlinkingProject', $ids));

            $statistics = $qb->getQuery()->getArrayResult();

            return $this->formatStatistics($statistics);
        }
        return [];
    }

    /**
     * @param $users
     * @return array
     */
    public function getStatisticsByUsers($users)
    {
        if(count($users) > 0){
            $qb  = $this->_em->createQueryBuilder();

            $qb
                ->select('IDENTITY(j.affectedToUser) as affectedToUser')
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_FOUND . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_FOUND)
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_NOT_FOUND_YET . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_NOT_FOUND_YET)
                ->addSelect('SUM(CASE WHEN db.status = \'' . DirectoryBacklinks::STATUS_NOT_FOUND . '\' then 1 else 0 end) '. DirectoryBacklinks::STATUS_NOT_FOUND)
                ->addSelect('SUM(CASE WHEN j.status = \'' . Job::STATUS_IMPOSSIBLE . '\' then 1 else 0 end) impossible')
                ->from(Job::class, 'j')
                ->leftJoin('j.directoryBacklink', 'db')
                ->groupBy('j.affectedToUser')
            ;

            $ids = $this->getCollectionIds($users);
            $qb->where($qb->expr()->in('j.affectedToUser', $ids));

            $statistics = $qb->getQuery()->getArrayResult();

            return $this->formatStatistics($statistics);
        }
        return [];
    }

    /**
     * @param $statistics
     * @return array
     */
    private function formatStatistics($statistics)
    {
        $result = [];

        foreach ($statistics as $statistic){
            $entityId = array_shift($statistic);

            $statistic['total'] = array_sum($statistic);
            foreach ($statistic as $key => $value){
                if($statistic['total'] > 0){
                    $statistic[$key . '_percent'] = round($value / $statistic['total'] * 100, 2);
                }else{
                    $statistic[$key . '_percent'] = 0;
                }
            }
            $result[$entityId] = $statistic;
        }

        return $result;
    }

    /**
     * @param $collection
     * @return array
     */
    private function getCollectionIds($collection)
    {
        $ids = [];

        foreach ($collection as $element) {
            $ids[] = $element->getId();
        }

        return $ids;
    }

    /**
     * @param $netlinkingProject
     * @param $directory
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByNetlinkingProject($netlinkingProject, $directory)
    {
        $qb = $this->createQueryBuilder('dbl');

        $qb
            ->innerJoin('dbl.job', 'j')
            ->innerJoin('j.scheduleTask', 'st')
            ->andWhere('j.netlinkingProject = :netlinkingProject')
            ->andWhere('st.directory = :directory')
            ->setParameter('netlinkingProject', $netlinkingProject, Type::OBJECT)
            ->setParameter('directory', $directory, Type::OBJECT)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}