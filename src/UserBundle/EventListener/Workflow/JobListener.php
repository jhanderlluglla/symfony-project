<?php

namespace UserBundle\EventListener\Workflow;

use CoreBundle\Entity\Job;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\JobService;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

/**
 * Class JobListener
 *
 * @package UserBundle\EventListener
 */
class JobListener
{

    /** @var Workflow */
    private $jobWorkflow;

    /** @var EntityManager */
    private $em;

    /** @var TransactionService */
    private $transactionService;

    /** @var JobService */
    private $jobService;

    /**
     * ExchangePropositionListener constructor.
     *
     * @param EntityManager $entityManager
     * @param Workflow $jobWorkflow
     * @param TransactionService $transactionService
     * @param JobService $jobService
     */
    public function __construct(
        EntityManager $entityManager,
        Workflow $jobWorkflow,
        TransactionService $transactionService,
        JobService $jobService
    ) {
        $this->em = $entityManager;
        $this->jobWorkflow = $jobWorkflow;
        $this->transactionService = $transactionService;
        $this->jobService = $jobService;
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Exception
     */
    public function onTakeToWork(WorkflowEvent $event)
    {
        /** @var Job $job */
        $job = $event->getSubject();

        $job->setTakeAt(new \DateTime());

        if ($job->getExchangeProposition()) {
            return;
        }

        $netlinkingProject = $job->getNetlinkingProject();

        $transaction = $this->transactionService->creditWithCheck(
            $netlinkingProject->getUser(),
            new TransactionDescriptionModel('job.hold', ['%netlinkingId%' => $netlinkingProject->getId(), '%jobId%' => $job->getId()]),
            $job->getCostWebmaster(),
            [],
            [Job::TRANSACTION_TAG_HOLD]
        );
        $transaction->setHidden(true);
        $job->addTransaction($transaction);


        $this->em->flush();
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Exception
     */
    public function onImpossible(WorkflowEvent $event)
    {
        /** @var Job $job */
        $job = $event->getSubject();

        if ($job->getExchangeProposition()) {
            return;
        }

        if ($job->getStatus() === Job::STATUS_IN_PROGRESS) {
            $this->jobService->holdMoneyReturns($job);
        }
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Exception
     */
    public function onExpiredHold(WorkflowEvent $event)
    {
        /** @var Job $job */
        $job = $event->getSubject();

        if ($job->getExchangeProposition()) {
            return;
        }

        $this->jobService->holdMoneyReturns($job);
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onComplete(WorkflowEvent $event)
    {
        /** @var Job $job */
        $job = $event->getSubject();

        $job->setCompletedAt(new \DateTime());

        if ($job->getExchangeProposition()) {
            return;
        }

        $netlinkingProject = $job->getNetlinkingProject();

        $buyerTransactionDetails = new TransactionDescriptionModel('job.payment', ['%projectId%' => $netlinkingProject->getId(), '%projectUrl%' => $netlinkingProject->getUrl()]);

        if ($job->getStatus() === Job::STATUS_IN_PROGRESS) { // Update hold-transaction
            $holdTransaction = $job->getHoldTransaction();
            if (!$holdTransaction) {
                throw new \LogicException('Job [in_progress] must contain a hold transaction');
            }

            $this->transactionService->removeTagFromTransaction($holdTransaction, Job::TRANSACTION_TAG_HOLD);
            $this->transactionService->addTagToTransaction($holdTransaction, Job::TRANSACTION_TAG_BUY);
            $holdTransaction->loadDescriptionModel($buyerTransactionDetails);
            $holdTransaction->setHidden(false);
        } else {
            $buyerTransaction = $this->transactionService->creditWithCheck(
                $netlinkingProject->getUser(),
                $buyerTransactionDetails,
                $job->getCostWebmaster(),
                [],
                [Job::TRANSACTION_TAG_BUY]
            );
            $job->addTransaction($buyerTransaction);
        }

        $this->jobService->rewardWriter($job);
        $this->jobService->createBackLinkForJob($job);

        $this->em->flush();
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Exception
     */
    public function onReject(WorkflowEvent $event)
    {
        /** @var Job $job */
        $job = $event->getSubject();

        $job->setRejectedAt(new \DateTime());

        if ($job->getExchangeProposition()) {
            return;
        }

        if ($job->getStatus() === Job::STATUS_COMPLETED) {
            $writerTransaction = $job->getTransactionsByTag(Job::TRANSACTION_TAG_REWARD)->last();
            $buyerTransaction = $job->getTransactionsByTag(Job::TRANSACTION_TAG_BUY)->last();

            $refundDetails = new TransactionDescriptionModel(
                'job.reject',
                [
                    '%projectId%' => $job->getNetlinkingProject()->getId(),
                    '%projectUrl%' => $job->getNetlinkingProject()->getUrl()
                ]
            );
            $refundTransactionWriter = $this->transactionService->refund(
                $writerTransaction,
                $refundDetails,
                [Job::TRANSACTION_TAG_REJECT]
            );
            $refundTransactionBuyer = $this->transactionService->refund(
                $buyerTransaction,
                $refundDetails,
                [Job::TRANSACTION_TAG_REJECT]
            );
            $job->addTransaction($refundTransactionWriter);
            $job->addTransaction($refundTransactionBuyer);
        }

        $this->em->flush();
    }
}
