<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingArticleComment;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\NetlinkingProjectComments;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\WorkflowTransitionEntityException;
use CoreBundle\Model\TransactionDescriptionModel;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\StateMachine;
use UserBundle\Services\NetlinkingService;

/**
 * Class JobService
 *
 * @package CoreBundle\Services
 */
class JobService
{
    /** @var EntityManager $em */
    private $em;

    /** @var TransactionService $transactionService */
    private $transactionService;

    /** @var StateMachine $exchangePropositionWorkflow */
    private $jobWorkflow;

    /** @var NetlinkingService */
    private $netlinkingService;

    /** @var CopywritingOrderService */
    private $copywritingOrderService;

    /** @var Security */
    private $security;

    /**
     * ExchangePropositionService constructor.
     *
     * @param EntityManager $entityManager
     * @param TransactionService $transactionService
     * @param StateMachine $jobWorkflow
     * @param NetlinkingService $netlinkingService
     * @param CopywritingOrderService $copywritingOrderService
     * @param Security $security
     */
    public function __construct(
        EntityManager $entityManager,
        TransactionService $transactionService,
        StateMachine $jobWorkflow,
        NetlinkingService $netlinkingService,
        CopywritingOrderService $copywritingOrderService,
        Security $security
    ) {
        $this->em = $entityManager;
        $this->transactionService = $transactionService;
        $this->jobWorkflow = $jobWorkflow;
        $this->netlinkingService = $netlinkingService;
        $this->copywritingOrderService = $copywritingOrderService;
        $this->security = $security;
    }

    /**
     * @param Job $job
     * @param $transition
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function applyTransition(Job $job, $transition)
    {
        if ($this->jobWorkflow->can($job, $transition)) {
            $this->jobWorkflow->apply($job, $transition);
            $this->em->flush();
        } else {
            throw new WorkflowTransitionEntityException($job, $transition);
        }
    }

    /**
     * @param ScheduleTask $scheduleTask
     *
     * @return Job
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createJobForDirectory(ScheduleTask $scheduleTask)
    {
        if ($scheduleTask->getJob()) {
            return $scheduleTask->getJob();
        }

        $netlinkingProject = $scheduleTask->getNetlinkingProject();
        $job = new Job();
        $job
            ->setNetlinkingProject($netlinkingProject)
            ->setScheduleTask($scheduleTask)
            ->setCreatedAt($scheduleTask->getStartAt())
        ;

        /** @var Job $job */
        $taskWordsCount = $job->getWordsCount();
        $job->setCostWebmaster($this->netlinkingService->calculatorNetlinkingPrice()->getWebmasterCost($scheduleTask, $taskWordsCount));

        return $job;
    }

    /**
     * @param Job $job
     * @param User $writer
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function takeToWorkJob(Job $job, User $writer)
    {
        $job->setAffectedToUser($writer);
        $this->applyTransition($job, Job::TRANSITION_TAKE_TO_WORK);
    }

    /**
     * @param Job $job
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function holdMoneyReturns(Job $job)
    {
        if ($job->getStatus() !== Job::STATUS_IN_PROGRESS) {
            throw new BadRequestHttpException();
        }

        if ($job->getExchangeProposition()) {
            return;
        }

        $transaction = $job->getHoldTransaction();

        if (!$transaction) {
            throw new \LogicException('Job [in_progress] must contain a hold transaction');
        }

        $refundTransaction = $this->transactionService->refund(
            $transaction,
            new TransactionDescriptionModel(
                'job.expiredHold',
                [
                    '%netlinkingId%' => $job->getNetlinkingProject()->getId(),
                    '%jobId%' => $job->getId()
                ]
            ),
            [Job::TRANSACTION_TAG_RETURN_HOLD]
        );
        $refundTransaction->setHidden(true);
        $job->addTransaction($refundTransaction);

        $this->em->flush();
    }

    /**
     * @param Job $job
     * @param string $comment
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function completeJob(Job $job, $comment)
    {
        $wordsCount = count(explode(" ", $comment));

        if ($wordsCount < $job->getWordsCount()) {
            throw new UnprocessableEntityHttpException(
                $this->transactionService->translator()->trans(
                    'modal.comment.error',
                    [
                        '%need%' => $job->getWordsCount(),
                        '%given%' => $wordsCount
                    ],
                    'netlinking'
                )
            );
        }

        $this->setNetlinkingComment($job, $job->getAffectedToUser(), $comment);
        $this->applyTransition($job, Job::TRANSITION_COMPLETE);
    }

    /**
     * @param Job $job
     * @param string $comment
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function impossibleJob(Job $job, $comment)
    {
        $this->setNetlinkingComment($job, $job->getAffectedToUser(), $comment);
        $this->applyTransition($job, Job::TRANSITION_IMPOSSIBLE);
    }

    /**
     * @param Job $job
     *
     * @return DirectoryBacklinks
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createBackLinkForJob(Job $job)
    {
        if ($job->getDirectoryBacklink()) {
            return $job->getDirectoryBacklink();
        }

        $backlinks = new DirectoryBacklinks();

        $backlinks
            ->setJob($job)
            ->setStatus(DirectoryBacklinks::STATUS_NOT_FOUND_YET)
            ->setStatusType(DirectoryBacklinks::STATUS_TYPE_CRON)
            ->setDateChecked(new \DateTime())
            ->setDateCheckedFirst(new \DateTime())
        ;

        $this->em->persist($backlinks);
        $this->em->flush();

        return $backlinks;
    }

    /**
     * @param Job $job
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function rewardWriter(Job $job)
    {
        $netlinkingProject = $job->getNetlinkingProject();
        $directory = $job->getScheduleTask()->getDirectory();

        if ($job->getCostWriter()) {
            $costWriter = $job->getCostWriter();
        } else {
            $wordsWithoutDirectoryWords = $job->getWordsCount() - $directory->getMinWordsCount();
            $costWriter = $this->netlinkingService->calculatorNetlinkingPrice()->getWriterCost($job->getScheduleTask(), $job->getAffectedToUser(), $wordsWithoutDirectoryWords);
        }

        $transactionReward = $this->transactionService->handling(
            $job->getAffectedToUser(),
            new TransactionDescriptionModel(
                'job.writerReward',
                [
                    '%url%' => $directory->getUrl(),
                    '%projectId%' => $netlinkingProject->getId(),
                    '%projectUrl%' => $netlinkingProject->getUrl()
                ]
            ),
            $costWriter,
            0,
            null,
            [Job::TRANSACTION_TAG_REWARD]
        );

        $job->setCostWriter($costWriter);
        $job->addTransaction($transactionReward);


        $this->em->flush();
    }

    /**
     * @param Job $job
     * @param $comment
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function rejectJob(Job $job, $comment)
    {
        $job->setComment($comment);

        if (!$job->getExchangeProposition()) {
            $this->applyTransition($job, Job::TRANSITION_REJECT);

            return;
        }

        $articleComment = new CopywritingArticleComment();
        $articleComment
            ->setComment($job->getComment())
            ->setUser($this->security->getUser())
        ;
        $this->copywritingOrderService->decline($job->getExchangeProposition()->getCopywritingOrders(), $articleComment);
        $this->em->flush();
    }

    /**
     * @return NetlinkingService
     */
    public function netlinkingService()
    {
        return $this->netlinkingService;
    }

    /**
     * @param Job $job
     * @param User $user
     * @param string $comment
     *
     * @return NetlinkingProjectComments
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setNetlinkingComment($job, $user, $comment)
    {
        if ($job->getNetlinkingProjectComment()) {
            $netlinkingProjectComment = $job->getNetlinkingProjectComment();
        } else {
            $netlinkingProjectComment = new NetlinkingProjectComments();
            $netlinkingProjectComment
                ->setUser($user)
                ->setJob($job);

            $job->setNetlinkingProjectComment($netlinkingProjectComment);

            $this->em->persist($netlinkingProjectComment);
        }

        $netlinkingProjectComment->setComment($comment);

        $this->em->flush();

        return $netlinkingProjectComment;
    }
}
