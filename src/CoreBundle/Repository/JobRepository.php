<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Job;
use CoreBundle\Helpers\DQLToSQLHelper;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\DBAL\Types\Type;
use CoreBundle\Entity\User;
use CoreBundle\Entity\NetlinkingProject;

/**
 * Class JobRepository
 *
 * @package CoreBundle\Repository
 */
class JobRepository extends BaseRepository implements FilterableRepositoryInterface
{
    protected $filters = [
        ['name' => 'user', 'alias' => 'np'],
        'netlinkingProject',
        'affectedToUser',
        'status',
        ['name'=> 'takeAt', 'filter' => 'takeAtLte', 'compare' => self::COMPARE_LTE],
    ];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false, $orders = [])
    {
        $qb = $this->createQueryBuilder('j');

        $this->prepare($filters, $qb);

        $qb
            ->innerJoin('j.netlinkingProject', 'np')
            ->innerJoin('j.scheduleTask', 'st')
            ->leftJoin('j.affectedToUser', 'writer')
            ->leftJoin('j.netlinkingProjectComment', 'npc')
        ;

        foreach ($orders as $field => $order) {
            $qb->addOrderBy($field, $order);
        }

        return $qb;
    }

    /**
     * @param array $ids
     *
     * return array
     */
    public function countTotalSubmissions($ids)
    {
        $dateTime = new \DateTime();
        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');
        $qb = $this->createQueryBuilder('j');
        $qb->select('COUNT(j) as all_count, IDENTITY(j.affectedToUser) as writer_id ');
        $qb->addSelect('SUM(CASE WHEN j.status = \'' . Job::STATUS_COMPLETED . '\' 
         AND MONTH(j.completedAt) = ' . $month .'
         AND YEAR(j.completedAt) = ' . $year .'  
         then 1 else 0 end) success_count');
        $qb
            ->andWhere('st.exchangeSite IS NULL')
            ->andWhere(
                $qb->expr()->in('j.affectedToUser', ':user_id')
            )
            ->setParameter('user_id', $ids)
        ;
        $qb
            ->innerJoin('j.scheduleTask', 'st')
        ;
        $qb->groupBy('j.affectedToUser');
        $results = $qb->getQuery()->getResult();
        $totalSubmissions = [];
        foreach ($results as $result) {
            $totalSubmissions[$result['writer_id']]['total'] = $result['all_count'];
            $totalSubmissions[$result['writer_id']]['success'] = $result['success_count'];
        }

        return $totalSubmissions;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * return float
     */
    public function getCurrentCost($netlinkingProject)
    {
        $qb = $this->createQueryBuilder('j');
        $qb
            ->select('SUM(j.costWebmaster)')
            ->where(
                'j.status = :statusCompleted'
            )
            ->andWhere(
                'j.netlinkingProject = :netlinkingProject'
            )
            ->setParameter('netlinkingProject', $netlinkingProject, Type::OBJECT)
            ->setParameter('statusCompleted', Job::STATUS_COMPLETED)
            ->groupBy('j.netlinkingProject')
        ;

        $result = $qb->getQuery()->getOneOrNullResult();

        return !empty($result[1]) ? floatval($result[1]):0;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return array
     */
    public function getJobsByNetlinkingProject($netlinkingProject)
    {
        $qb = $this->createQueryBuilder('j');

        $qb
            ->innerJoin('j.scheduleTask', 'st')
            ->innerJoin('j.affectedToUser', 'u')
            ->innerJoin('j.netlinkingProjectComment', 'npc')
            ->where(
                'j.netlinkingProject = :project'
            )
            ->setParameter('project', $netlinkingProject, Type::OBJECT)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     *
     * @return float|int
     */
    public function getEarnedLastMonth($user)
    {
        $qb = $this->createQueryBuilder('j');
        $qb
            ->select('SUM(j.costWebmaster)')
//            ->innerJoin('')
            ->where(
                'j.status = :statusCompleted'
            )
            ->andWhere(
                'j.affectedToUser = :user'
            )
            ->setParameter('user', $user, Type::OBJECT)
            ->setParameter('statusCompleted', Job::STATUS_COMPLETED)
            ->groupBy('j.user')
        ;

        $result = $qb->getQuery()->getOneOrNullResult();

        return !empty($result[1]) ? floatval($result[1]):0;
    }

    /**
     * @param User $writer
     * @param \DateTime $dateTime
     * @return int|mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getCopywriterEarningsByMonth(User $writer, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('j');
        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');

        $qb
            ->select('SUM(j.costWriter)')
            ->where('j.affectedToUser = :user')
            ->setParameter('user', $writer)
            ->andWhere('MONTH(j.completedAt) = :month')
            ->andWhere('YEAR(j.completedAt) = :year')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $ids
     * @param \DateTime $dateTime
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function usersCopywriterEarningsByMonth($ids, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('j');
        $month = $dateTime->format('m');
        $year = $dateTime->format('Y');
        $qb
            ->select('SUM(j.costWriter) as earning_directory')
            ->addSelect('IDENTITY(j.affectedToUser) as id')
            ->where($qb->expr()->in('j.affectedToUser', ':users'))
            ->andWhere('MONTH(j.completedAt) = :month')
            ->andWhere('YEAR(j.completedAt) = :year')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->setParameter('users', $ids)
            ->groupBy('j.affectedToUser')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $copywriter
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLikes(User $copywriter)
    {
        $qb = $this->createQueryBuilder('j');

        $qb
            ->addSelect('SUM(CASE WHEN j.rating = true then 1 else 0 end) likes')
            ->addSelect('SUM(CASE WHEN j.rating = false then 1 else 0 end) dislikes')
            ->where('j.affectedToUser = :copywriter')
            ->setParameter('copywriter', $copywriter)
        ;

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function usersLikes($ids)
    {
        $qb = $this->createQueryBuilder('j');

        $qb
            ->addSelect('SUM(CASE WHEN j.rating = true then 1 else 0 end) likes')
            ->addSelect('SUM(CASE WHEN j.rating = false then 1 else 0 end) dislikes')
            ->addSelect('IDENTITY(j.affectedToUser) as id')
            ->where($qb->expr()->in('j.affectedToUser', ':users'))
            ->setParameter('users', $ids)
            ->groupBy('j.affectedToUser')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getOverTimeInProgressJobs()
    {
        $date = new \DateTime();
        $date->modify("-1 hour");

        $qb = $this->filter(['status' => Job::STATUS_IN_PROGRESS, 'takeAtLte' => $date]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $filters
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getJobsForAdmin($filters)
    {
        $filters['status'] = [Job::STATUS_COMPLETED, Job::STATUS_IMPOSSIBLE];

        $qb = $this->filter($filters, false, ['j.completedAt' => 'DESC'])
            ->andWhere('st.exchangeSite IS NULL')
            ->andWhere('npc.id IS NOT NULL')
        ;

        return $qb;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @return mixed
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLatestJobOfNetlinkingProject($netlinkingProject)
    {
        $qb = $this->createQueryBuilder('j');

        $qb->select('MAX(j.takeAt)');
        $qb->andWhere('j.netlinkingProject = :netlinkingProject');
        $qb->setParameter('netlinkingProject', $netlinkingProject, Type::OBJECT);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
