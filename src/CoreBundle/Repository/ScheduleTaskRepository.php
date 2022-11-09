<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Settings;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use DoctrineExtensions\Query\Mysql\Cast;

class ScheduleTaskRepository extends EntityRepository
{

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param User $user
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTasks($netlinkingProject, $user)
    {
        $qb = $this->createQueryBuilder('st');

        $qb
            ->addSelect('j')
            ->innerJoin('st.netlinkingProject', 'np')
            ->andWhere('st.netlinkingProject = :netlinkingProject')
            ->setParameter('netlinkingProject', $netlinkingProject, TYPE::OBJECT)
            ->leftJoin('st.job', 'j')
            ->leftJoin('j.netlinkingProjectComment', 'npc')
            ->leftJoin('j.directoryBacklink', 'b')
        ;

        // TODO? Change for a real calculations ? It`s "duplicated functional"
        if ($user->isWriterNetlinking()) {
            $qb
                ->leftJoin('j.exchangeProposition', 'ep')
                ->leftJoin(CopywritingOrder::class, 'co', 'WITH', 'co.exchangeProposition = ep.id')
                ->andWhere('((np.affectedToUser = :user AND st.exchangeSite IS NULL) OR (st.exchangeSite IS NOT NULL AND co.copywriter = :user))')
                ->andWhere('(j.status IN (:statuses) OR j.status IS NULL)')
                ->andWhere('st.startAt < NOW()')
                ->setParameter('user', $user, Type::OBJECT)
                ->setParameter(
                    'statuses',
                    [Job::STATUS_NEW, Job::STATUS_IN_PROGRESS, Job::STATUS_EXPIRED_HOLD, Job::STATUS_REJECTED]
                )
            ;
        }

        return $qb;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param boolean $currentState
     *
     * @return array
     */
    public function getNotStartedTasks($netlinkingProject, $currentDayState = false){
        $qb = $this->createQueryBuilder('st');

        $qb
            ->addSelect('j')
            ->innerJoin('st.netlinkingProject', 'np')
            ->andWhere('st.netlinkingProject = :netlinkingProject')
            ->andWhere('j.status is NULL')
            ->setParameter('netlinkingProject', $netlinkingProject, TYPE::OBJECT)
            ->leftJoin('st.job', 'j')
            ->leftJoin('j.netlinkingProjectComment', 'npc')
            ->leftJoin('j.directoryBacklink', 'b')
        ;

        if ($currentDayState){
            $qb->andWhere('DATEDIFF(st.startAt,NOW()) >= 0');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getExchangeSitesTasks()
    {
        $qb = $this->createQueryBuilder('st');

        $qb
            ->addSelect('es')
            ->addSelect('np')
            ->leftJoin('st.job', 'j')
            ->leftJoin('st.netlinkingProject', 'np')
            ->leftJoin('st.exchangeSite', 'es')
            ->andWhere('st.exchangeSite is not NULL')
            ->andWhere('st.startAt < NOW()')
            ->andWhere('j.id is NULL')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $netlinkingProjects
     * @return array
     */
    public function getTaskStatisticsByProjects($netlinkingProjects)
    {
        if (count($netlinkingProjects) > 0) {
            $qb  = $this->createQueryBuilder('st');

            $qb
                ->select('IDENTITY(st.netlinkingProject) as netlinkingProjectId')
                ->addSelect('COUNT(st) total')
                ->addSelect('SUM(CASE WHEN j.id is not NULL then 1 else 0 end) accomplished')
                ->addSelect('SUM(CASE WHEN j.id is NULL then 1 else 0 end) remaining')
                ->leftJoin('st.job', 'j')
                ->groupBy('st.netlinkingProject')
            ;

            $ids = [];
            foreach ($netlinkingProjects as $project) {
                $ids[] = $project->getId();
            }
            $qb->where($qb->expr()->in('st.netlinkingProject', $ids));

            $statistics = $qb->getQuery()->getArrayResult();
            return array_combine(array_column($statistics, 'netlinkingProjectId'), $statistics);
        }

        return [];
    }

    /**
     * @param User $writer
     * @return array
     */
    public function getTaskForWriter(User $writer)
    {
        $sub_qb = $this->createQueryBuilder('st');
        $settings = $this->getEntityManager()->getRepository(Settings::class)->getSettingsByIdentificators([Settings::TARIFF_WEB,Settings::DEFAULT_DIRECTORY_ZERO_WORDS_COUNT]);

        // getting the possible Netlinking-Projects by possible theirs task for making by writer
        // "possible" - it is when webmaster has enough money for any undone task till now
        // IMHO, strange Doctrine`s|DQL approach to sub-queries
        $sub_qb
            ->select('np.id AS id')
            ->addSelect('st.id AS task_id')
            ->addSelect('st.startAt as oldestStartAt')
            ->addSelect('u.balance AS user_balance')
            ->addSelect('(IF(u.spending > 0,u.spending, :tarifweb) + d.tariffExtraWebmaster +  
                                CASE 
                                     WHEN (dl.wordsCount > 0)
                                     THEN (dl.wordsCount - IF( d.minWordsCount IS NULL, :defMinWordsCnt, d.minWordsCount) )/100*IF(u.spending > 0, u.spending, :tarifweb) 
                                     ELSE 0
                                END ) AS task_cost')
            ->join('st.netlinkingProject', 'np')
            ->join('st.directory', 'd')
            ->join('np.user', 'u')
            ->join('np.directoryList','dl')
            ->leftJoin('st.job', 'j')
            ->andWhere('j.id IS NULL')
            ->andWhere('np.affectedToUser = :writer')
            ->andWhere('st.exchangeSite IS NULL')
            ->andWhere('st.startAt < NOW()')
            ->groupBy('st.id')
            ->addGroupBy('np.id')
            ->having('CAST(task_cost as DECIMAL(10,2)) <= user_balance')
            ->orderBy('oldestStartAt', Criteria::DESC)
            ->setParameter('writer', $writer, Type::OBJECT)
            ->setParameter('tarifweb', $settings['tarifweb'], Type::DECIMAL)
            ->setParameter('defMinWordsCnt', $settings[Settings::DEFAULT_DIRECTORY_ZERO_WORDS_COUNT], TYPE::INTEGER)
        ;

        $result = $sub_qb->getQuery()->getArrayResult();

        if ($result){
            $ids = array_column($result, 'task_id');
            $ids = array_unique($ids);

            $qb =  $this->createQueryBuilder('st');
            $qb->select('np.id')
                ->addSelect('MIN(st.startAt) as oldestStartAt')
                ->join('st.netlinkingProject', 'np')
                ->andWhere($qb->expr()->in('st.id',$ids))
                ->groupBy('np.id')
            ;
            return $qb->getQuery()->getArrayResult();
        }

        return [];
    }

    /**
     * @param mixed $projects
     *
     * @return array
     */
    public function getMinDatedScheduleTasksByProjects($projects){
        if (count($projects)){
            if (is_object($projects)){
                $projects = $projects->getIterator()->getArrayCopy();
            }

            $ids = array_map(function($project){
                if ($project instanceof NetlinkingProject)
                    return $project->getId();
            },$projects);

            // remove NULL if CopywritingProjects for togetherPageFanta
            $ids = array_filter($ids);

            if (!empty($ids)) {
                $qb = $this->createQueryBuilder('st');
                $qb->select('np.id AS project_id')
                    ->addSelect('MIN(st.startAt) as oldestStartAt')
                    ->join('st.netlinkingProject', 'np')
                    ->where($qb->expr()->in('np.id', $ids))
                    ->leftJoin('st.job', 'j')
                    ->andWhere('j.id IS NULL')
                    ->andWhere('st.exchangeSite IS NULL')
                    ->andWhere('st.startAt < NOW()')
                    ->groupBy('np.id');

                $result = $qb->getQuery()->getArrayResult();
                $projectsIDs = array_column($result, 'project_id');
                $dates = array_column($result, 'oldestStartAt');
                return array_combine($projectsIDs, $dates);
            }
        }
        return [];

    }

    /**
     * @param DirectoriesList $directoryList
     * @param array $sites
     * @param string $type
     * @return array
     */
    public function getTasksByListAndSites($directoryList, $sites, $type)
    {
        $qb = $this->createQueryBuilder('st');

        $qb
            ->innerJoin('st.netlinkingProject', 'np')
            ->leftJoin('st.job', 'j')
            ->where('np.directoryList = :directoryList')
            ->andWhere(
                $qb->expr()->orX('j.id is null', $qb->expr()->eq(
                    'j.status',
                    $qb->expr()->literal(Job::STATUS_NEW)
                ))
            )
            ->setParameter('directoryList', $directoryList)
        ;

        switch ($type) {
            case ExchangeSite::class:
                $qb->andWhere("st.exchangeSite IN (:exchangeSites)");
                $qb->setParameter('exchangeSites', $sites);
                break;
            case Directory::class:
                $qb->andWhere("st.directory IN (:directories)");
                $qb->setParameter('directories', $sites);
                break;
        }

        return $qb->getQuery()->getResult();
    }
}
