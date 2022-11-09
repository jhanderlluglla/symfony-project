<?php

namespace UserBundle\Services;

use CoreBundle\Entity\Anchor;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Repository\DirectoriesListRepository;
use CoreBundle\Repository\NetlinkingProjectRepository;
use CoreBundle\Repository\ScheduleTaskRepository;
use CoreBundle\Services\AccessManager;
use CoreBundle\Services\CalculatorNetlinkingPrice;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use CoreBundle\Services\Mailer;
use CoreBundle\Model\DirectoryModel;
use UserBundle\Entity\NetlinkingAnchorFlowEntity;
use UserBundle\Entity\NetlinkingFlowEntity;
use UserBundle\Entity\NetlinkingUrlAnchorsFlowEntity;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Job;
use UserBundle\Entity\NetlinkingDetailWriter;

/**
 * Class Netlinking
 *
 * @package UserBundle\Services
 */
class NetlinkingService
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var DirectoryModel
     */
    protected $directoryModel;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var CalculatorNetlinkingPrice
     */
    protected $calculatorNetlinkingPrice;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var NetlinkingSchedule
     */
    private $netlinkingSchedule;

    /** @var AccessManager */
    private $accessManager;

    /**
     * Netlinking constructor.
     *
     * @param EntityManager $entityManager
     * @param TokenStorage $tokenStorage
     * @param TranslatorInterface $translator
     * @param Mailer $mailer
     * @param DirectoryModel $directoryModel
     * @param TransactionService $transactionService
     * @param CalculatorNetlinkingPrice $calculatorNetlinkingPrice
     * @param UrlGeneratorInterface $router
     * @param NetlinkingSchedule $netlinkingSchedule
     * @param AccessManager $accessManager
     */
    public function __construct(
        $entityManager,
        $tokenStorage,
        $translator,
        $mailer,
        $directoryModel,
        $transactionService,
        $calculatorNetlinkingPrice,
        $router,
        NetlinkingSchedule $netlinkingSchedule,
        AccessManager $accessManager
    ) {
        $this->entityManager     = $entityManager;
        $this->translator        = $translator;
        $this->mailer            = $mailer;
        $this->directoryModel    = $directoryModel;
        $this->transactionService = $transactionService;
        $this->calculatorNetlinkingPrice = $calculatorNetlinkingPrice;
        $this->router = $router;
        $this->netlinkingSchedule = $netlinkingSchedule;

        if ($tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }

        $this->accessManager = $accessManager;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * return bool
     */
    public function start(NetlinkingProject $netlinkingProject)
    {

        if (!$this->user->hasRole(User::ROLE_SUPER_ADMIN) && !$this->accessManager->canManageNetlinkingProject()) {
            $isCan = $this->isProjectCanBeProduced($netlinkingProject);

            if (!$isCan) {
                $this->errorMessage = $this->translator->trans('errors.insufficient_balance', [], 'netlinking');
                return false;
            }
        }

        if ($netlinkingProject->getStatus() == NetlinkingProject::STATUS_FINISHED){
            $this->netlinkingSchedule->updateStoppedProjectTasksSchedule($netlinkingProject);
        }

        $systemEmail = $this->entityManager->getRepository(Settings::class)->getSettingValue('email');
        $this->mailer->sendToEmail(User::NOTIFICATION_START_NEW_NETLINKING_PROJECT, $systemEmail);

        $netlinkingProject
            ->setStartedAt(new \DateTime())
            ->setFinishedAt(null)
            ->setStatus(NetlinkingProject::STATUS_WAITING)
        ;

        $this->entityManager->persist($netlinkingProject);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return bool
     */
    public function isProjectCanBeProduced($netlinkingProject){
        $webmasterBalance = floatval($netlinkingProject->getUser()->getBalance());
        $tasks = $netlinkingProject->getScheduleTasks();

        // "fresh"|"empty"
        if ($tasks->isEmpty())
            return true;

        /** @var ScheduleTask $task */
        foreach ($tasks as $task){
            if ($task->getJob() === null) {
                $taskCost = $this->calculatorNetlinkingPrice->getWebmasterCost($task);
                if ($webmasterBalance >= $taskCost)
                  return true;
            }
        }
        return false;
    }


    /**
     * @param NetlinkingProject $netlinkingProject
     * @param null|User         $copyWriter
     *
     * @return bool
     */
    public function inProgress(NetlinkingProject $netlinkingProject, $copyWriter = null)
    {
        $affectedToUser = $this->user;
        if ($copyWriter) {
            $affectedToUser = $copyWriter;
        }

        $netlinkingProject
            ->setAffectedToUser($affectedToUser)
            ->setAffectedByUser($this->user)
            ->setAffectedAt(new \DateTime())
            ->setStatus(NetlinkingProject::STATUS_IN_PROGRESS)
        ;

        $this->entityManager->persist($netlinkingProject);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return bool
     */
    public function stop(NetlinkingProject $netlinkingProject)
    {
        $netlinkingProject
            ->setFinishedAt(new \DateTime())
            ->setStatus(NetlinkingProject::STATUS_FINISHED)
        ;

        $this->entityManager->persist($netlinkingProject);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param ScheduleTask $scheduleTask
     *
     * @return NetlinkingDetailWriter
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function detailWriter($scheduleTask)
    {
        $netlinkingProject = $scheduleTask->getNetlinkingProject();
        $directory = $scheduleTask->getDirectory();

        $netlinkingDetailWriter = new NetlinkingDetailWriter();

        if ($scheduleTask->getJob()) {
            $job = $scheduleTask->getJob();
        } else {
            $job = new Job();
            $job->setScheduleTask($scheduleTask);
            $job->setNetlinkingProject($scheduleTask->getNetlinkingProject());
        }

        /** @var Job $job */
        $taskWordsCount = $job->getWordsCount();
        $wordsWithoutDirectoryWords = $taskWordsCount - $directory->getMinWordsCount();

        if ($this->user->hasRole(User::ROLE_WRITER) || $this->user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $netlinkingDetailWriter->setTaskWordsCount($taskWordsCount);
        }

        if ($this->user->hasRole(User::ROLE_WRITER)) {
            $totalCompensation = $this->calculatorNetlinkingPrice->getWriterCost($scheduleTask, $this->user, $wordsWithoutDirectoryWords);
            $netlinkingDetailWriter->setCompensation($totalCompensation);
        }

        if ($this->user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $webmasterTaskCost = $this->calculatorNetlinkingPrice->getWebmasterCost($scheduleTask, $taskWordsCount);

            $writerTaskCost =  $this->calculatorNetlinkingPrice->getWriterCost($scheduleTask, $netlinkingProject->getAffectedToUser(), $wordsWithoutDirectoryWords);

            $netlinkingDetailWriter
                ->setWebmasterTaskCost($webmasterTaskCost)
                ->setWriterTaskCost($writerTaskCost)
            ;
        }

        $netlinkingDetailWriter
            ->setDirectoryInstructions($directory->getInstructions())
            ->setProjectInstructions($netlinkingProject->getComment())
        ;

        if ($directory->getWebmasterAnchor()) {
            $netlinkingDetailWriter->setAnchors($netlinkingProject->getAnchorList());
        }

        return $netlinkingDetailWriter;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function finishedProject(NetlinkingProject $netlinkingProject)
    {
        $netlinkingProject->setStatus(NetlinkingProject::STATUS_FINISHED);
        $this->entityManager->flush();

        $replace = [
            '%project_link%' => $this->router->generate('netlinking_detail', ['id' => $netlinkingProject->getId()]),
        ];

        $this->mailer->sendToUser(User::NOTIFICATION_NETLINKING_PROJECT_FINISHED, $netlinkingProject->getUser(), $replace);
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return bool
     */
    public function checkCompleted(NetlinkingProject $netlinkingProject)
    {
        /** @var ScheduleTaskRepository $scheduleTaskRepository */
        $scheduleTaskRepository = $this->entityManager->getRepository(ScheduleTask::class);

        /** @var DirectoriesListRepository $directoryListRepository */
        $directoryListRepository = $this->entityManager->getRepository(DirectoryBacklinks::class);

        $scheduleTaskCount = count($scheduleTaskRepository->findBy(['netlinkingProject' => $netlinkingProject]));
        $directoryBacklinksCount = $directoryListRepository->getCountByNetlinkingProject($netlinkingProject, DirectoryBacklinks::STATUS_FOUND);

        return $scheduleTaskCount === $directoryBacklinksCount;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param string $comment
     * @param NetlinkingAnchorFlowEntity[] $anchors
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateNetlinkingProject(NetlinkingProject $netlinkingProject, $comment, $anchors)
    {
        $netlinkingProject
            ->setComment($comment)
        ;


        if (!empty($anchors)) {
            /** @var NetlinkingAnchorFlowEntity $anchorFlow */
            foreach ($anchors as $anchorFlow) {
                $anchorName = $anchorFlow->getAnchor();
                if (empty($anchorName)) {
                    continue;
                }

                $anchor = $this->entityManager->getRepository(Anchor::class)->filter([
                    'netlinkingProject' => $netlinkingProject,
                    'exchangeSite' => $anchorFlow->getExchangeSite(),
                    'directory' => $anchorFlow->getDirectory(),
                ])->getQuery()->getOneOrNullResult();

                if (!$anchor) {
                    $anchor = new Anchor();
                }

                if (!is_null($anchorFlow->getDirectory())) {
                    $directory = $this->entityManager->getRepository(Directory::class)->find($anchorFlow->getDirectory());
                    if (!is_null($directory)) {
                        $anchor
                            ->setDirectory($directory)
                            ->setName($anchorName)
                        ;
                    }
                }

                if (!is_null($anchorFlow->getExchangeSite())) {
                    $exchangeSite = $this->entityManager->getRepository(ExchangeSite::class)->find($anchorFlow->getExchangeSite());
                    if (!is_null($exchangeSite)) {
                        $anchor
                            ->setExchangeSite($exchangeSite)
                            ->setName($anchorName);
                    }
                }

                if ($anchor->getName() === null || $anchor->getName() === '') {
                    continue;
                }

                $this->entityManager->persist($anchor);
                $netlinkingProject->addAnchor($anchor);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param NetlinkingFlowEntity $netlinkingFlowEntity
     * @param User $userCreator
     * @param array $errors
     * @param array $flashes
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createNetlinkingProject(
        NetlinkingFlowEntity $netlinkingFlowEntity,
        User $userCreator,
        &$errors = [],
        &$flashes = []
    ) {
        $urlAnchors = $netlinkingFlowEntity->getUrlAnchors();
        if (!empty($urlAnchors)) {

            /** @var NetlinkingUrlAnchorsFlowEntity $uAnchor */
            foreach ($urlAnchors as $uAnchor) {

                /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
                $netlinkingProjectRepository = $this->entityManager->getRepository(NetlinkingProject::class);
                $netlinkingProject = $netlinkingProjectRepository->findOneBy(['url' => rtrim($uAnchor->getUrl(), '/')]);

                if (!is_null($netlinkingProject)) {
                    $errors['urls'] = $this->translator->trans('form.url_exists', [
                        '%%url%%' => $uAnchor->getUrl()
                    ], 'netlinking');
                    break;
                }

                $netlinkingProject = new NetlinkingProject();

                $netlinkingProject
                    ->setUser($userCreator)
                    ->setDirectoryList($netlinkingFlowEntity->getDirectoryList())
                    ->setUrl($uAnchor->getUrl())
                    ->setFrequencyDay($netlinkingFlowEntity->getFrequencyDay())
                    ->setFrequencyDirectory($netlinkingFlowEntity->getFrequencyDirectory())
                ;

                $this->entityManager->persist($netlinkingProject);

                if (!$this->start($netlinkingProject)) {
                    $flashes[] = ['type' => 'error', 'message' =>  $this->getErrorMessage()];
                }

                $this->updateNetlinkingProject($netlinkingProject, $netlinkingFlowEntity->getComment(), $uAnchor->getAnchors());

                if ($netlinkingProject->getContainsType() === DirectoriesList::CONTAINS_ONLY_BLOG) {
                    $this->netlinkingSchedule->createSchedule($netlinkingProject);
                    $netlinkingProject->setStatus(NetlinkingProject::STATUS_IN_PROGRESS);
                }
            }

            $this->entityManager->flush();
        }
    }

    /**
     * @return CalculatorNetlinkingPrice
     */
    public function calculatorNetlinkingPrice()
    {
        return $this->calculatorNetlinkingPrice;
    }

}
