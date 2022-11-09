<?php

namespace UserBundle\EventListener\Workflow;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\User;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\CopywritingOrderService;
use CoreBundle\Services\ExchangePropositionService;
use CoreBundle\Services\JobService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use JMS\JobQueueBundle\Entity\Job;
use UserBundle\Services\ExchangePropositionProcessor;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity;

/**
 * Class CopywritingOrderListener
 *
 * @package UserBundle\EventListener
 */
class CopywritingOrderListener
{

    /** @var Workflow */
    protected $copywritingOrderWorkflow;

    /** @var TokenStorage $tokenStorage */
    protected $tokenStorage;

    /** @var ExchangePropositionProcessor $exchangePropositionProcessor */
    protected $exchangePropositionProcessor;

    /** @var ExchangePropositionService $exchangePropositionService */
    protected $exchangePropositionService;

    /** @var CopywritingOrderService $copywritingOrderService */
    protected $copywritingOrderService;

    /** @var JobService $jobService */
    protected $jobService;

    /**
     * CopywritingOrderListener constructor.
     * @param Workflow $copywritingOrderWorkflow
     * @param TokenStorage $tokenStorage
     * @param ExchangePropositionProcessor $exchangePropositionProcessor
     * @param ExchangePropositionService $exchangePropositionService
     * @param CopywritingOrderService $copywritingOrderService
     * @param JobService $jobService
     */
    public function __construct(
        Workflow $copywritingOrderWorkflow,
        TokenStorage $tokenStorage,
        ExchangePropositionProcessor $exchangePropositionProcessor,
        ExchangePropositionService $exchangePropositionService,
        CopywritingOrderService $copywritingOrderService,
        JobService $jobService
    ) {
        $this->copywritingOrderWorkflow = $copywritingOrderWorkflow;
        $this->tokenStorage = $tokenStorage;
        $this->exchangePropositionProcessor = $exchangePropositionProcessor;
        $this->exchangePropositionService = $exchangePropositionService;
        $this->copywritingOrderService = $copywritingOrderService;
        $this->jobService = $jobService;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $order = $args->getEntity();

        if (!($order instanceof CopywritingOrder) || $order->getId()) {
            return;
        }

        $startDate = $this->copywritingOrderService->em()->getRepository(CopywritingOrder::class)->countStartDate($order);
        $order->setLaunchedAt($startDate);

        if ($order->isExpress()) {
            $deadline = clone $order->getCreatedAt();
            $deadline->add(new \DateInterval('P1D'));
            $order->setDeadline($deadline);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $order = $args->getEntity();

        if (!($order instanceof CopywritingOrder)) {
            return;
        }

        if ($order->isExpress()) {
            $job = new Job('app:refund:express-order', ['order-id' => $order->getId()]);

            $job->setExecuteAfter($order->getDeadline());

            $this->copywritingOrderService->em()->persist($job);
            $this->copywritingOrderService->em()->flush($job);
        }
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onTakingToWork(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();

        $order
            ->setTakenAt(new \DateTime())
            ->setTimeInProgress(0)
        ;

        $exchangeProposition = $order->getExchangeProposition();
        if ($exchangeProposition !== null) {
            $this->exchangePropositionService->applyTransition($exchangeProposition, ExchangeProposition::TRANSITION_ASSIGNED_WRITER);

            if ($exchangeProposition->getJob()) {
                $this->jobService->applyTransition($exchangeProposition->getJob(), Entity\Job::TRANSITION_TAKE_TO_WORK);
            }
        }

        $article = new CopywritingArticle();
        $article->setOrder($order);

        $this->copywritingOrderService->em()->persist($article);
        $this->copywritingOrderService->em()->flush();
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onSubmitting(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();
        $article = $order->getArticle();

        $this->copywritingOrderService->articleProcessor()->buildReport($article);
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onDeclining(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();

        if ($order->getStatus() === CopywritingOrder::STATUS_COMPLETED) {
            $order->setDeclinedAt(new \DateTime());

            if ($order->getExchangeProposition() && $job = $order->getExchangeProposition()->getJob()) {
                if ($job->getStatus() !== Entity\Job::STATUS_REJECTED) {
                    $this->jobService->applyTransition($job, Entity\Job::TRANSITION_REJECT);
                }
            }

            if ($order->isExpress()) {
                $order->delayDeadline();

                /** @var Job $job */
                $job = new Job('app:refund:express-order', ['order-id' => $order->getId()]);
                $job->setExecuteAfter($order->getDeadline());

                $this->copywritingOrderService->em()->persist($job);
            }

            $this->copywritingOrderService->refundWriter($order);
            $this->copywritingOrderService->refundCorrector($order);

            $this->copywritingOrderService->em()->flush();
        }
    }

    /**
     * @param WorkflowEvent $event
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onSubmittingToAdmin(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();
        /** @var CopywritingArticle $article */
        $article = $order->getArticle();

        $order->setReadyForReviewAt(new \DateTime());
        $images = $this->copywritingOrderService->articleProcessor()->getImagesFromText($article->getText());
        if ($article->getFrontImage() !== null) {
            $images[] = $article->getFrontImage();
        }
        $article->setImagesByWriter(array_diff($images, $article->getImagesByAdmin()));

        if (count($article->getImagesByAdmin()) > 0) {
            $article->setImagesByAdmin(array_intersect($images, $article->getImagesByAdmin()));
        }

        $order->addTimeInProgress($order->calculateTimeInProgress());

        $this->copywritingOrderService->em()->persist($order);
        $this->copywritingOrderService->em()->flush();
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onSubmittingToWebmaster(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();
        $article = $order->getArticle();
        $customer = $order->getCustomer();
        $user = $this->tokenStorage->getToken()->getUser();
        /** @var ExchangeProposition $proposition */
        $proposition = $order->getExchangeProposition();

        $order->setApprovedAt(new \DateTime());
        $order->setApprovedBy($user);


        $images = $this->copywritingOrderService->articleProcessor()->getImagesFromText($article->getText());
        if ($article->getFrontImage() !== null) {
            $images[] = $article->getFrontImage();
        }
        $article->setImagesByWriter(array_intersect($article->getImagesByWriter(), $images)); // Admin could delete the image editor
        $article->setImagesByAdmin(array_diff($images, $article->getImagesByWriter()));

        $this->copywritingOrderService->payment($order);

        if ($order->getDeclinedAt() === null) {
            $order->setImagesPerArticleTo(count($images));
        }

        if ($proposition) {
            $this->exchangePropositionProcessor->buildReport($proposition, $article);
            if ($proposition->getStatus() === ExchangeProposition::STATUS_IN_PROGRESS) {
                $this->exchangePropositionService->applyTransition($proposition, ExchangeProposition::TRANSITION_ACCEPT);
            }

            $response = $this->copywritingOrderService->articleProcessor()->publish($article);
            if ($response === ExchangeSite::RESPONSE_CODE_PUBLISH_SUCCESS
                && $proposition->getStatus() === ExchangeProposition::STATUS_ACCEPTED) {
                $this->exchangePropositionService->applyTransition($proposition, ExchangeProposition::TRANSITION_PUBLISH);
            }

            $this->copywritingOrderService->em()->persist($proposition);
            $this->copywritingOrderService->em()->flush();
        }

        $this->copywritingOrderService->mailer()
            ->sendToUser(
                User::NOTIFICATION_ARTICLE_READY,
                $customer,
                ['%article_link%' => $this->copywritingOrderService->mailer()->router()->generate('copywriting_order_show', ['id' => $order->getId()])]
            );

        if ($order->isExpress()) {

            /** @var Job $job */
            $job = $this->copywritingOrderService->em()->getRepository(Job::class)->getJob('app:refund:express-order', ['order-id' => $order->getId()]);

            $this->copywritingOrderService->em()->remove($job);
            $this->copywritingOrderService->em()->flush($job);
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCompleting(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();
        $order->setCompletedAt(new \DateTime());

        if ($order->getExchangeProposition() && $job = $order->getExchangeProposition()->getJob()) {
            $this->jobService->applyTransition($job, Entity\Job::TRANSITION_COMPLETE);
        }
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onImpossible(WorkflowEvent $event)
    {
        /** @var CopywritingOrder $order */
        $order = $event->getSubject();

        if ($order->getExchangeProposition()) {
            if ($order->getExchangeProposition()->getStatus() !== ExchangeProposition::STATUS_PUBLISHED) {
                if ($order->getExchangeProposition()->getJob()) {
                    $this->jobService->applyTransition($order->getExchangeProposition()->getJob(), Entity\Job::TRANSITION_IMPOSSIBLE);
                }
                $this->exchangePropositionService->applyTransition($order->getExchangeProposition(), ExchangeProposition::TRANSITION_IMPOSSIBLE);
            } else {
                $this->exchangePropositionService->refund(
                    $order->getExchangeProposition(),
                    new TransactionDescriptionModel('proposal.refund_cost', ['%url%' => $order->getExchangeProposition()->getExchangeSite()->getUrl()])
                );
            }
        }

        if (!$order->getExchangeProposition() || $order->getExchangeProposition()->getType() === ExchangeProposition::OWN_TYPE) {
            $this->copywritingOrderService->refund(
                $order,
                new TransactionDescriptionModel('copywriting_order.article_impossible', [
                    '%order_title%' => $order->getTitle(),
                    '%order_id%' => $order->getId()
                ])
            );
        }
    }
}
