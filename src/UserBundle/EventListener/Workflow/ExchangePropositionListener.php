<?php

namespace UserBundle\EventListener\Workflow;

use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Job;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\ExchangePropositionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use CoreBundle\Entity\ExchangeProposition;
use UserBundle\Services\ExchangeSite\CalculatorPrice;
use UserBundle\Services\NetlinkingService;

/**
 * Class ExchangePropositionListener
 * @package UserBundle\EventListener
 */
class ExchangePropositionListener
{

    /** @var Workflow */
    protected $exchangePropositionWorkflow;

    /** @var EntityManager $em */
    protected $em;

    /** @var ExchangePropositionService $exchangePropositionService */
    protected $exchangePropositionService;

    /** @var TranslatorInterface $translator */
    private $translator;

    /** @var CalculatorPrice $calculatorPrice */
    private $calculatorPrice;

    /** @var NetlinkingService $netlinkingService */
    private $netlinkingService;

    /**
     * ExchangePropositionListener constructor.
     *
     * @param EntityManager $entityManager
     * @param Workflow $exchangePropositionWorkflow
     * @param ExchangePropositionService $exchangePropositionService
     * @param TranslatorInterface $translator
     * @param CalculatorPrice $calculatorPrice
     * @param NetlinkingService $netlinkingService
     */
    public function __construct(
        EntityManager $entityManager,
        Workflow $exchangePropositionWorkflow,
        ExchangePropositionService $exchangePropositionService,
        TranslatorInterface $translator,
        CalculatorPrice $calculatorPrice,
        NetlinkingService $netlinkingService
    ) {
        $this->em = $entityManager;
        $this->exchangePropositionWorkflow = $exchangePropositionWorkflow;
        $this->exchangePropositionService = $exchangePropositionService;
        $this->translator = $translator;
        $this->calculatorPrice = $calculatorPrice;
        $this->netlinkingService = $netlinkingService;
    }

    /**
     * @param WorkflowEvent $event
     * @throws \Exception
     */
    public function onAssignedWriter(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
        $proposal->setUpdatedAt(new \DateTime());
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onImpossible(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
        $proposal->setUpdatedAt(new \DateTime());

        if ($proposal->getType() === ExchangeProposition::OWN_TYPE) {
            return;
        }

        $this->exchangePropositionService->refund(
            $proposal,
            new TransactionDescriptionModel('proposal.impossible', [
                '%url%' => $proposal->getExchangeSite()->getUrl(),
                '%comment%' => $proposal->getImpossibleComment()
            ])
        );
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onExpire(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
        $proposal->setUpdatedAt(new \DateTime());

        $this->exchangePropositionService->refund(
            $proposal,
            new TransactionDescriptionModel('proposal.refund_cost', ['%url%' => $proposal->getExchangeSite()->getUrl()])
        );
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAccept(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
        $proposal->setUpdatedAt(new \DateTime());
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Exception
     */
    public function onPublish(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();

        $proposal
            ->setViewed(false)
            ->setUpdatedAt(new \DateTime())
            ->setPublishedAt(new \DateTime())
            ->setComments('<a target="_blank" href="' . $proposal->getPagePublish() . '">' . $proposal->getPagePublish() . '</a>');
        ;

        if ($proposal->getJob() !== null) {
            $backLink = $proposal->getJob()->getDirectoryBacklink();
            $backLink
                ->setStatus(DirectoryBacklinks::STATUS_FOUND)
                ->setDateFound(new \DateTime())
                ->setBacklink($proposal->getPagePublish())
            ;
            $this->em->flush();

            if ($this->netlinkingService->checkCompleted($proposal->getJob()->getNetlinkingProject())) {
                $this->netlinkingService->finishedProject($proposal->getJob()->getNetlinkingProject());
            }
        }

        $this->calculatorPrice->payUser($proposal);
    }

    /**
     * @param WorkflowEvent $event
     * @throws \Exception
     */
    public function onChange(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();

        $proposal
            ->setUpdatedAt(new \DateTime())
            ->setViewed(false);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptChanges(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
    }

    /**
     * @param WorkflowEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function onRefuse(WorkflowEvent $event)
    {
        /** @var ExchangeProposition $proposal */
        $proposal = $event->getSubject();
        $proposal
            ->setUpdatedAt(new \DateTime())
            ->setViewed(false);

        $this->exchangePropositionService->refund(
            $proposal,
            new TransactionDescriptionModel('proposal.refund_cost', ['%url%' => $proposal->getExchangeSite()->getUrl()]),
            ['comment' => $proposal->getComments()]
        );
    }
}
