<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleComment;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\WorkflowTransitionEntityException;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\Mailer as Mailer;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Workflow\StateMachine;
use UserBundle\Services\CopywritingArticleProcessor;

/**
 * Class CopywritingOrderService
 *
 * @package CoreBundle\Services
 */
class CopywritingOrderService
{
    /** @var EntityManager $em */
    private $em;

    /** @var TransactionService $transactionService */
    private $transactionService;

    /** @var CopywritingArticleProcessor $articleProcessor */
    protected $articleProcessor;

    /** @var Mailer $mailer */
    protected $mailer;

    /** @var StateMachine $copywritingOrderWorkflow */
    protected $copywritingOrderWorkflow;

    /**
     * ExchangePropositionService constructor.
     *
     * @param EntityManager $entityManager
     * @param TransactionService $transactionService
     * @param CopywritingArticleProcessor $articleProcessor
     * @param Mailer $mailer
     * @param StateMachine $copywritingOrderWorkflow
     */
    public function __construct(EntityManager $entityManager, TransactionService $transactionService, CopywritingArticleProcessor $articleProcessor, Mailer $mailer, StateMachine $copywritingOrderWorkflow)
    {
        $this->em = $entityManager;
        $this->transactionService = $transactionService;
        $this->articleProcessor = $articleProcessor;
        $this->mailer = $mailer;
        $this->copywritingOrderWorkflow = $copywritingOrderWorkflow;
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @param TransactionDescriptionModel $description
     * @param array $moreDetails
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function refund(CopywritingOrder $copywritingOrder, TransactionDescriptionModel $description, $moreDetails = [])
    {
        $this->transactionService->handling(
            $copywritingOrder->getCustomer(),
            $description,
            $copywritingOrder->getAmount(),
            0,
            $moreDetails,
            [
                CopywritingOrder::TRANSACTION_TAG_REFUND,
            ]
        );
    }

    /**
     * @param CopywritingOrder $order
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function payment(CopywritingOrder $order)
    {
        $article = $order->getArticle();

        $webmaster = $order->getCustomer();
        $corrector = $order->getApprovedBy();

        /** @var ExchangeProposition $proposition */
        $proposition = $order->getExchangeProposition();

        $this->payWriter($article);

        $imagesPaid = $order->getImagesPerArticleTo();
        $imagesUsed = $article->getTotalImagesNumber();
        $imageAmountDiff = $imagesPaid - $imagesUsed;

        if (is_null($order->getDeclinedAt()) && $imageAmountDiff > 0) {
            $imagesRate = $this->em->getRepository(Settings::class)->getSettingValue(Settings::PRICE_PER_IMAGE);

            $amountChange = $imageAmountDiff * $imagesRate;
            $reFound = $imageAmountDiff . ' x ' . $imagesRate . '€ = ' . $amountChange . '€' ;

            $transaction = $this->transactionService->handling(
                $webmaster,
                new TransactionDescriptionModel(
                    'copywriting_order.images_cashback',
                    [
                        '%order_title%' => $proposition ? $proposition->getExchangeSite()->getUrl() : $order->getTitle(),
                        '%order_date%' => $order->getCreatedAt()->format('m/d/Y')
                    ]
                ),
                $amountChange,
                0,
                ['imagesPaid' => $imagesPaid, 'imagesUsed' => $imagesUsed, 'reFound' => $reFound],
                [CopywritingOrder::TRANSACTION_TAG_IMAGE_CASHBACK]
            );

            $order->addTransaction($transaction);
        }

        if ($corrector && ($corrector->isAdmin() || $corrector->isWriterAdmin())) {
            $this->payCorrector($article);
        }

        $this->em->flush();
    }


    /**
     * @param CopywritingArticle $article
     *
     * @return void
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function payWriter(CopywritingArticle $article)
    {
        $this->articleProcessor->countWords($article);
        $order = $article->getOrder();

        $earning = $this->articleProcessor->calculateWriterEarn($article);
        $article->setWriterEarn($earning->getTotalForWriter());

        $tags = [CopywritingOrder::TRANSACTION_TAG_REWARD];

        $transaction = $this->transactionService->handling(
            $order->getCopywriter(),
            new TransactionDescriptionModel('copywriting_order.writing_article', ['%order_title%' => $order->getTitle()]),
            $article->getWriterEarn(),
            0,
            [
                'numberOfWords'=> $order->getWordsNumber(),
                'earningForWords'=> $earning->getBaseEarning(),
                'numberOfImages' => count($article->getImagesByWriter()),
                'earningForImages' => $earning->getImagesEarning(),
                'earningForExpress' => $earning->getExpressEarning(),
                'earningForWriterCategory' => $earning->getChooseEarning(),
                CopywritingProject::TRANSACTION_DETAIL_WRITER_BONUS => $earning->getBonus(),
                CopywritingProject::TRANSACTION_DETAIL_WRITER_MALUS =>  $earning->getMalus(),
                CopywritingProject::TRANSACTION_DETAIL_REWARD_FOR_META_DESCRIPTION => $earning->getMetaDescriptionEarning(),
            ],
            $tags
        );

        $order->addTransaction($transaction);
    }


    /**
     * @param CopywritingArticle $article
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function payCorrector(CopywritingArticle $article)
    {
        $earning = $this->articleProcessor->countCorrectorEarn($article);
        $totalEarning = $earning->getTotalForAdmin();
        $order = $article->getOrder();

        $article->setCorrectorEarn($totalEarning);

        if ($earning->getTotalForAdmin() > 0) {
            $tags = [CopywritingOrder::TRANSACTION_TAG_REWARD];

            $transaction = $this->transactionService->handling(
                $order->getApprovedBy(),
                new TransactionDescriptionModel('copywriting_order.checking_article', ['%order_title%' => $order->getTitle()]),
                $totalEarning,
                0,
                [
                    'numberOfWords' => $order->getWordsNumber(),
                    'earningForWords'=> $earning->getBaseEarning(),
                    'numberOfImages' => count($article->getImagesByAdmin()),
                    'earningForImages' => $earning->getImagesEarning(),
                    'earningForExpress' => $earning->getExpressEarning(),
                    'earningMalus' => $earning->getMalus()
                ],
                $tags
            );

            $order->addTransaction($transaction);
        }
    }

    /**
     * @param CopywritingOrder $order
     * @param User $user
     * @param TransactionDescriptionModel $description
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function refundSEO(CopywritingOrder $order, User $user, TransactionDescriptionModel $description)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("user", $user))
            ->andWhere(Criteria::expr()->gt("debit", 0))
            ->orderBy(['createdAt' => 'DESC'])
            ->setMaxResults(1)
        ;

        $transactions = $order->getTransactions();
        $transactions->initialize();
        $transaction = $order->getTransactions()->matching($criteria)->first();
        $this->transactionService->refund($transaction, $description, [CopywritingOrder::TRANSACTION_TAG_REWARD, CopywritingOrder::TRANSACTION_TAG_DECLINE]);
    }

    /**
     * @param CopywritingOrder $order
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function refundWriter(CopywritingOrder $order)
    {
        $this->refundSEO(
            $order,
            $order->getCopywriter(),
            new TransactionDescriptionModel(
                'copywriting_order.article_declined',
                [
                    '%order_id%' => $order->getId(),
                    '%order_title%' => $order->getTitle()
                ]
            )
        );
    }

    /**
     * @param CopywritingOrder $order
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function refundCorrector(CopywritingOrder $order)
    {
        $this->refundSEO(
            $order,
            $order->getApprovedBy(),
            new TransactionDescriptionModel(
                'copywriting_order.article_declined',
                [
                    '%order_id%' => $order->getId(),
                    '%order_title%' => $order->getTitle()
                ]
            )
        );
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @param $transition
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function applyTransition(CopywritingOrder $copywritingOrder, $transition)
    {
        if ($this->copywritingOrderWorkflow->can($copywritingOrder, $transition)) {
            $this->copywritingOrderWorkflow->apply($copywritingOrder, $transition);
            $this->em->flush();
        } else {
            throw new WorkflowTransitionEntityException($copywritingOrder, $transition);
        }
    }

    /**
     * @param CopywritingOrder $order
     * @param CopywritingArticleComment$comment
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function decline(CopywritingOrder $order, CopywritingArticleComment $comment)
    {
        $article = $order->getArticle();
        $article->addComment($comment);

        $this->applyTransition($order, CopywritingOrder::TRANSITION_DECLINE_TRANSITION);

        $this->em->flush();
    }

    /**
     * @return EntityManager
     */
    public function em()
    {
        return $this->em;
    }

    /**
     * @return CopywritingArticleProcessor
     */
    public function articleProcessor()
    {
        return $this->articleProcessor;
    }

    /**
     * @return Mailer
     */
    public function mailer()
    {
        return $this->mailer;
    }

    /**
     * @return TransactionService
     */
    public function transactionService()
    {
        return $this->transactionService;
    }
}
