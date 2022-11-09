<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\ScheduleTask;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\User;

/**
 * Class NetlinkingProjectRepository
 *
 * @package CoreBundle\Repository
 */
class NetlinkingProjectRepository extends EntityRepository implements FilterableRepositoryInterface
{

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false, $orderBy = null)
    {
        $qb = $this->createQueryBuilder('nl');
        $qb->leftJoin(Job::class, 'j', 'WITH', 'j.netlinkingProject = nl.id');

        if ($count) {
            $qb->select($qb->expr()->count('nl') . ' as cnt');
        }

        if (isset($filters['user']) && ($filters['user'] instanceof User)) {
            $user = $filters['user'];

            if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
                $qb->andWhere(
                    'nl.user = :user'
                );
                $qb->setParameter('user', $user, Type::OBJECT);
            }
        }

        if (isset($filters['affected_user']) && ($filters['affected_user'] instanceof User)) {
            $qb
                ->andWhere('(nl.affectedToUser = :affected_user OR j.affectedToUser = :affected_user)')
                ->setParameter('affected_user', $filters['affected_user'], Type::OBJECT)
            ;
        }

        if ($this->isStatus($filters)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('nl.status', $qb->expr()->literal($filters['status']))
                );
        }

        if (!empty($filters['user_role'])) {
            switch ($filters['user_role']) {
                case User::ROLE_WRITER_STRING:
                    $qb
                        ->innerJoin(ScheduleTask::class, 'st', Join::WITH, 'st.netlinkingProject = nl.id')
                        ->andWhere('st.startAt < NOW()')
                    ;

                    if (!empty($filters['status'])) {
                        switch ($filters['status']) {
                            case 'getnew':
                                $qb
                                    ->andWhere(
                                        $qb->expr()->andX(
                                            $qb->expr()->isNull('nl.affectedToUser'),
                                            $qb->expr()->eq('nl.status', $qb->expr()->literal(NetlinkingProject::STATUS_WAITING))
                                        )
                                    );
                                break;
                            case 'current':
                                $qb
                                    ->andWhere(
                                        $qb->expr()->eq('nl.status', $qb->expr()->literal(NetlinkingProject::STATUS_IN_PROGRESS))
                                    );
                                break;
                        }
                    }
                    break;
                case User::ROLE_WEBMASTER_STRING:
                    if (!empty($filters['status'])) {
                        switch ($filters['status']) {
                            case 'current':
                                $qb
                                    ->andWhere(
                                        $qb->expr()->eq('nl.status', $qb->expr()->literal(NetlinkingProject::STATUS_IN_PROGRESS))
                                    );
                                break;
                        }
                    }
                    break;
            }
        }

        if ($count) {
            $result = $qb->getQuery()->getOneOrNullResult();

            return isset($result['cnt']) ? (int) $result['cnt']:0;
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy("IF(nl.$orderBy IS NULL, 1, 0)")
                ->addOrderBy("nl.$orderBy", Criteria::ASC)
            ;
        }

        $qb->groupBy('nl.id');

        return $qb;
    }

    /**
     * @param Directory $directory
     */
    public function getBacklinks($directory)
    {
        $qb = $this->createQueryBuilder('nl');

        $qb
            ->innerJoin('nl.jobs', 'j')
            ->innerJoin('j.scheduleTask', 'st')
            ->where(
                'st.directory = :directory'
            )
            ->leftJoin('j.directoryBacklink', 'dbl')
            ->andWhere(
                $qb->expr()->eq('dbl.statusType', $qb->expr()->literal(DirectoryBacklinks::STATUS_TYPE_CRON))
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('dbl.backlink'),
                    $qb->expr()->eq('LENGTH(dbl.backlink)', 0)
                )
            )
            ->setParameter('directory', $directory, Type::OBJECT)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @param User  $affectedToUser
     * @param User  $affectedByUser
     */
    public function assignMass($ids, $affectedToUser, $affectedByUser)
    {

        $now =  new \DateTime();
        $qb = $this->createQueryBuilder('nl');
        $query = $qb->update()
            ->set('nl.affectedToUser', ':affectedToUser')
            ->set('nl.affectedByUser', ':affectedByUser')
            ->set('nl.affectedAt', ':affectedAt')
            ->set('nl.status', ':status')
            ->where(
                $qb->expr()->in('nl.id', $ids)
            )
            ->setParameter('affectedToUser', $affectedToUser,Type::OBJECT)
            ->setParameter('affectedByUser', $affectedByUser,Type::OBJECT)
            ->setParameter('affectedAt', $now->format('Y-m-d H:i:s'))
            ->setParameter('status', NetlinkingProject::STATUS_IN_PROGRESS)
            ->getQuery();
        ;

        $query->execute();
    }

    /**
     * @param array $ids
     */
    public function deleteMass($ids)
    {
        $qb = $this->createQueryBuilder('nl');
        $query = $qb
            ->delete()
            ->where(
                $qb->expr()->in('nl.id', $ids)
            )
            ->getQuery()
        ;

        $query->execute();
    }

    /**
     * @param array $filters
     *
     * @return bool
     */
    private function isStatus($filters)
    {
        return !empty($filters['status']) && in_array($filters['status'], NetlinkingProject::getAvailableStatuses());
    }

    /**
     * @param User $user
     * @return array
     */
    public function getCount($user)
    {
        $qb = $this->createQueryBuilder('n');
        if($user->hasRole(User::ROLE_WRITER) || $user->hasRole(User::ROLE_WRITER_NETLINKING)){
            $filters = [
                'status' => 'current',
                'user_role' => 'account_type.seo',
                'affected_user' => $user,
                'query' => null,
            ];

            return [
                'progressCount' => $this->getNetlinkingProjectForWriter($user, $filters, true),
                'noStartCount'  => 0,
                'waitingCount' => 0,
                'finishedCount' => 0
            ];
        }else{
            $qb
                ->select('COUNT(n) as allCount')
                ->innerJoin('n.user','u')
                ->addSelect('SUM(CASE WHEN n.status = \'' . NetlinkingProject::STATUS_IN_PROGRESS . '\' then 1 else 0 end) progressCount')
                ->addSelect('SUM(CASE WHEN n.status = \'' . NetlinkingProject::STATUS_WAITING . '\' then 1 else 0 end) waitingCount')
                ->addSelect('SUM(CASE WHEN n.status = \'' . NetlinkingProject::STATUS_NO_START . '\' then 1 else 0 end) noStartCount')
                ->addSelect('SUM(CASE WHEN n.status = \'' . NetlinkingProject::STATUS_FINISHED . '\' then 1 else 0 end) finishedCount')
            ;

            if($user->hasRole(User::ROLE_WEBMASTER)){
                $qb
                    ->where('n.user = :user')
                    ->setParameter('user', $user, Type::OBJECT)
                    ->groupBy('n.user')
                ;
            }
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param User $writer
     * @param array $filters
     * @param bool $count
     * @return array
     */
    public function getNetlinkingProjectForWriter(User $writer, $filters = [], $count = false)
    {
        /** @var ScheduleTaskRepository $scheduleTaskRepository */
        $scheduleTaskRepository = $this->getEntityManager()->getRepository(ScheduleTask::class);

        $result = $scheduleTaskRepository->getTaskForWriter($writer);
        $ids = array_column($result, 'id');

        if ($count) {
            $ids = array_unique($ids);
            return count($ids);
        }

        $qb = $this->createQueryBuilder('np');
        $qb
            ->andWhere('np.id IN (:ids)')
            ->setParameter(':ids', $ids)
        ;
        return [$qb, array_combine($ids, array_column($result, 'oldestStartAt'))];
    }


    /**
     * @param array $ids
     *
     * @return array
     */
    public function getNetLinkingProjects($ids)
    {
        $qb = $this->createQueryBuilder('nl');
        $qb->select('IDENTITY(nl.affectedToUser) as writer_id ');
        $qb->addSelect('SUM(CASE WHEN nl.status = \'' . NetlinkingProject::STATUS_IN_PROGRESS . '\'  
         then 1 else 0 end) in_progress');
        $qb->addSelect('SUM(CASE WHEN nl.status = \'' . NetlinkingProject::STATUS_FINISHED . '\'  
         then 1 else 0 end) finished');

        $qb
            ->andWhere(
                $qb->expr()->in('nl.affectedToUser', ':affected_user')
            )
            ->setParameter('affected_user', $ids);

        $qb->groupBy('nl.affectedToUser');

        $netLinkingProjects = $qb->getQuery()->getResult();
        $userNetLinkingProjects = [];
        foreach ($netLinkingProjects as $project) {
            $userNetLinkingProjects[$project['writer_id']]['current'] = $project['in_progress'];
            $userNetLinkingProjects[$project['writer_id']]['completed'] = $project['finished'];
        }

        return $userNetLinkingProjects;
    }

    /**
     * @param string $query
     * @param User $user
     * @return array
     */
    public function searchProject($query, $user)
    {
        $qb = $this->createQueryBuilder('np');

        $qb->andWhere("np.url LIKE :query");
        $qb->setParameter(":query", "%$query%");

        if (!$user->isSuperAdmin()) {
            $qb->andWhere(
                $qb->expr()->orX(
                    "np.affectedToUser = :user",
                    "np.user = :user"
                )
            );
            $qb->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param DirectoriesList $directoryList
     * @param string $status
     *
     * @return array
     */
        public function getProjectByDirectoryList($directoryList, $status = NetlinkingProject::STATUS_IN_PROGRESS)
    {
        $qb = $this->createQueryBuilder('np');

        $qb
            ->innerJoin('np.scheduleTasks', 'st')
            ->addSelect('COUNT(st.id) as totalTasks')
            ->addSelect('MAX(st.startAt) as latestTaskDate')
            ->where('np.directoryList = :directoryList')
            ->andWhere($qb->expr()->eq('np.status', $qb->expr()->literal($status)))
            ->setParameter('directoryList', $directoryList, Type::OBJECT)
            ->groupBy('np.id')
        ;

        return $qb->getQuery()->getResult();
    }


}
