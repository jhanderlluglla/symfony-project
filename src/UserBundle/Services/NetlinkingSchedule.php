<?php

namespace UserBundle\Services;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\User;
use CoreBundle\Repository\NetlinkingProjectRepository;
use CoreBundle\Repository\ScheduleTaskRepository;
use CoreBundle\Services\CalculatorNetlinkingPrice;
use Doctrine\ORM\EntityManager;


class NetlinkingSchedule
{
    /**
     * @var EntityManager
     */
    protected $entityManager;


    /**
     * @var CalculatorNetlinkingPrice
     */
    protected $calculatorNetlinkingPrice;

    /**
     * NetlinkingSchedule constructor.
     * @param $entityManager
     */
    public function __construct($entityManager, $calculatorNetlinkingPrice)
    {
        $this->entityManager = $entityManager;
        $this->calculatorNetlinkingPrice = $calculatorNetlinkingPrice;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createSchedule($netlinkingProject)
    {
        $day = new \DateTime();

        if (!$netlinkingProject->getDirectoryList()) {
            return;
        }

        $directories = $netlinkingProject->getDirectoryList()->getDirectories();
        $exchangeSites = $netlinkingProject->getDirectoryList()->getExchangeSite();
        $sites = array_merge($directories->getValues(), $exchangeSites->getValues());
        shuffle($sites);

        $this->createTasks($sites, $netlinkingProject, $day);
        $this->entityManager->flush();
    }

    public function createTasks($sites, $netlinkingProject, $day, $countTasks = 0)
    {
        foreach ($sites as $i=>$site) {
            $scheduleTask = new ScheduleTask();

            if ($sites[$i] instanceof Directory) {
                $scheduleTask->setDirectory($site);
            } else {
                $scheduleTask->setExchangeSite($site);
            }
            $scheduleTask->setNetlinkingProject($netlinkingProject);

            $previousStep = $i + $countTasks;
            if ($previousStep > 0 && $previousStep % $netlinkingProject->getFrequencyDirectory() === 0) {
                $day->modify("+{$netlinkingProject->getFrequencyDay()} day");
            }
            $scheduleTask->setStartAt(clone $day);
            $this->entityManager->persist($scheduleTask);
        }
    }

    /**
     * @param DirectoriesList $directoryList
     */
    public function updateSchedule($directoryList)
    {
        $directoriesInsertDiff = $directoryList->getDirectories()->getInsertDiff();
        $exchangeSiteInsertDiff = $directoryList->getExchangeSite()->getInsertDiff();
        $togetherDiff = array_merge($directoriesInsertDiff, $exchangeSiteInsertDiff);

        if ($togetherDiff) {
            $this->addTasksToSchedule($directoryList, $togetherDiff);
        }

        $directoriesDeleteDiff = $directoryList->getDirectories()->getDeleteDiff();
        $exchangeSiteDeleteDiff = $directoryList->getExchangeSite()->getDeleteDiff();

        if ($directoriesDeleteDiff) {
            $this->removeTasksFromSchedule($directoryList, $directoriesDeleteDiff, Directory::class);
        }

        if ($exchangeSiteDeleteDiff) {
            $this->removeTasksFromSchedule($directoryList, $exchangeSiteDeleteDiff, ExchangeSite::class);
        }

        if ($directoriesDeleteDiff || $exchangeSiteDeleteDiff){
            $this->updateNotStaredTasksOnDeletion($directoryList);
        }
        $directoryList->setUpdatedAt(new \DateTime());
    }


    /**
     * @param DirectoriesList $directoryList
     * @param array $tasks
     */
    public function addTasksToSchedule($directoryList, $tasks)
    {
        /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
        $netlinkingProjectRepository = $this->entityManager->getRepository(NetlinkingProject::class);

        $netlinkingProjectsWithData = $netlinkingProjectRepository->getProjectByDirectoryList($directoryList, NetlinkingProject::STATUS_IN_PROGRESS);

        foreach ($netlinkingProjectsWithData as $netlinkingProjectData) {
            $this->createTasks(
                $tasks,
                $netlinkingProjectData[0],
                new \DateTime($netlinkingProjectData['latestTaskDate']),
                $netlinkingProjectData['totalTasks']
            );
        }
    }

    /**
     * @param DirectoriesList $directoryList
     * @param array $tasks
     * @param string $type
     */
    public function removeTasksFromSchedule($directoryList, $tasks, $type)
    {
        $scheduleTaskRepository = $this->entityManager->getRepository(ScheduleTask::class);
        $scheduleTasks = $scheduleTaskRepository->getTasksByListAndSites($directoryList, $tasks, $type);

        foreach ($scheduleTasks as $scheduleTask) {
            $this->entityManager->remove($scheduleTask);
        }
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return bool
     */
    public function updateStoppedProjectTasksSchedule($netlinkingProject){

        /** @var DirectoriesList $directoryList */
        $directoryList = $netlinkingProject->getDirectoryList();

        $directories = $directoryList->getDirectories()->toArray();
        $exchangeSites = $directoryList->getExchangeSite()->toArray();

        $currentDirectories = [];
        $currentExchanges = [];
        $currentScheduleTasks = $netlinkingProject->getScheduleTasks();

        /** @var ScheduleTask $scheduleTask */
        foreach($currentScheduleTasks as $scheduleTask){
            if ($scheduleTask->getDirectory() !== null ){
                $currentDirectories[] = $scheduleTask->getDirectory();
            }
            if ($scheduleTask->getExchangeSite() !== null ){
                $currentExchanges[] = $scheduleTask->getExchangeSite();
            }
        }

        $directoryInsertDiff = $this->diffArrays($directories, $currentDirectories);
        $exchangeInsertDiff = $this->diffArrays($exchangeSites, $currentExchanges);
        $togetherInsertDiff = array_merge($directoryInsertDiff, $exchangeInsertDiff);

        if ($togetherInsertDiff){
            $this->createTasks($directoryInsertDiff, $netlinkingProject, new \DateTime());
            $this->entityManager->flush();
        }
        return $this->updateNotStartedTasks($netlinkingProject);
    }

    public function updateNotStaredTasksOnDeletion($directoryList){
        /** @var ScheduleTaskRepository $scheduleRepository */
        $scheduleRepository = $this->entityManager->getRepository(ScheduleTask::class);

        /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
        $netlinkingProjectRepository = $this->entityManager->getRepository(NetlinkingProject::class);
        // TODO for all actual statuses
        // TODO creating tasks is wrong in the current state ( it`s not just "IN_PROGRESS"). Now it`s a disorder
        $netlinkingProjectsWithData = $netlinkingProjectRepository->getProjectByDirectoryList($directoryList, NetlinkingProject::STATUS_IN_PROGRESS);

        foreach ($netlinkingProjectsWithData as $index=>$netlinkingProjectData) {
            $netlinkingProject = array_shift($netlinkingProjectsWithData)[0];
            // update only "since today"-tasks
            $this->updateNotStartedTasks($netlinkingProject, new \DateTime(),true);
        }
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param \DateTime $datetime
     *
     * @return boolean|null
     */
    public function updateNotStartedTasks($netlinkingProject, $datetime = null, $currentTodayState = false){
        /** @var ScheduleTaskRepository $scheduleRepository */
        $scheduleRepository = $this->entityManager->getRepository(ScheduleTask::class);
        $tasks = $scheduleRepository->getNotStartedTasks($netlinkingProject, $currentTodayState);

        if ($tasks) {
            $day = $datetime ?: new \DateTime();
            /** @var ScheduleTask $scheduleTask */
            foreach ($tasks as $i => $scheduleTask) {
                $previousStep = $i;
                if ($previousStep > 0 && $previousStep % $netlinkingProject->getFrequencyDirectory() === 0) {
                    $day->modify("+{$netlinkingProject->getFrequencyDay()} day");
                }
                $scheduleTask->setStartAt(clone $day);
                $this->entityManager->persist($scheduleTask);
            }
            return $this->entityManager->flush();
        }
    }


    /**
     * @param User  $webmaster
     * @param float $debit
     *
     * @return boolean
     */
    public function checkSchedulesOfStoppedProjects($webmaster,$debit){
        $balance = $webmaster->getBalance();
        $increasedBalance = $balance + $debit;

        /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
        $netlinkingProjectRepository = $this->entityManager->getRepository(NetlinkingProject::class);

        $filters = [
            'user' => $webmaster,
            'status' => 'current',
            'user_role' => User::ROLE_WEBMASTER_STRING
        ];

        $updateNetlinkingProjects = [];
        $netlinkingProjects = $netlinkingProjectRepository->filter($filters)->getQuery()->getResult();

        if ($netlinkingProjects){
            /** @var NetlinkingProject $project */
            foreach($netlinkingProjects as $project){
                 $tasks = $project->getScheduleTasks();
                 if (!$tasks->isEmpty()){
                    foreach ($tasks as $task){
                        $taskCost = $this->calculatorNetlinkingPrice->getWebmasterCost($task);
                        if ( ($taskCost > $balance) && ($taskCost <=$increasedBalance)){
                            $updateNetlinkingProjects[] = $project;
                            break;
                        }
                    }
                 }
            }

            if ($updateNetlinkingProjects){
                foreach($updateNetlinkingProjects as $project){
                    $this->updateNotStartedTasks($project);
                }
            }
        }

    }

    /**
     * @param array $origin
     * @param array $target
     *
     * @return array
     */
    private function diffArrays($origin, $target){
        return array_udiff(
            $origin,
            $target,
            function($a, $b) { return $a->getId() - $b->getId();}
        );
    }

}
