<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Entity\UserSetting;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\WorkflowTransitionEntityException;
use CoreBundle\Model\TransactionDescriptionModel;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Workflow\StateMachine;

class ExchangePropositionService
{
    /** @var EntityManager $em */
    private $em;

    /** @var TransactionService $transactionService */
    private $transactionService;

    /** @var StateMachine $exchangePropositionWorkflow */
    private $exchangePropositionWorkflow;

    /** @var Mailer $mailer */
    private $mailer;

    /** @var UserSettingService $userSettingService */
    private $userSettingService;

    /** @var CalculatorPriceService $calculatorPriceService*/
    private $calculatorPriceService;

    /**
     * ExchangePropositionService constructor.
     *
     * @param EntityManager $entityManager
     * @param TransactionService $transactionService
     * @param StateMachine $exchangePropositionWorkflow
     * @param Mailer $mailer
     * @param UserSettingService $userSettingService
     * @param CalculatorPriceService $calculatorPriceService
     */
    public function __construct(EntityManager $entityManager, TransactionService $transactionService, StateMachine $exchangePropositionWorkflow, Mailer $mailer, UserSettingService $userSettingService, CalculatorPriceService $calculatorPriceService)
    {
        $this->em = $entityManager;
        $this->transactionService = $transactionService;
        $this->exchangePropositionWorkflow = $exchangePropositionWorkflow;
        $this->mailer = $mailer;
        $this->userSettingService = $userSettingService;
        $this->calculatorPriceService = $calculatorPriceService;
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param array $generateDetails
     *
     * @return mixed
     */
    public function calculateRefundAmount(ExchangeProposition $exchangeProposition, &$generateDetails = [])
    {
        $refundPrice = 0;

        if (!$exchangeProposition->getBuyerTransaction()) {
            return $refundPrice;
        }

        if ($exchangeProposition->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_BUYER) {
            if ($exchangeProposition->getStatus() !== ExchangeProposition::STATUS_PUBLISHED) {
                $refundPrice = $exchangeProposition->getBuyerTransaction()->getCredit();
            }
        } else {
            $findKey = [];
            $detailsTransaction = $exchangeProposition->getBuyerTransaction()->getDetails();

            if ($exchangeProposition->getStatus() !== ExchangeProposition::STATUS_PUBLISHED) {
                $findKey[] = ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE;
                if ($exchangeProposition->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER) {
                    $findKey[] = ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY;
                }
            }

            if ($exchangeProposition->getCopywritingOrders() && $exchangeProposition->getCopywritingOrders()->getStatus() !== CopywritingOrder::STATUS_COMPLETED) {
                $findKey[] = CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE;
            }

            foreach ($findKey as $key) {
                if (isset($detailsTransaction[$key])) {
                    $generateDetails[$key] = $detailsTransaction[$key];
                    $refundPrice += $detailsTransaction[$key];
                }
            }
        }

        return $refundPrice;
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param TransactionDescriptionModel $description
     * @param array $details
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function refund(ExchangeProposition $exchangeProposition, TransactionDescriptionModel $description, $details = [])
    {
        if ($exchangeProposition->getType() === ExchangeProposition::OWN_TYPE) {
            return;
        }

        $refundPrice = $this->calculateRefundAmount($exchangeProposition, $details);

        if ($refundPrice === 0) {
            return;
        }

        $transaction = $this->transactionService->handling(
            $exchangeProposition->getUser(),
            $description,
            $refundPrice,
            0,
            $details,
            [ExchangeProposition::TRANSACTION_TAG_REFUND]
        );

        $exchangeProposition->addTransaction($transaction);
        if ($exchangeProposition->getCopywritingOrders()) {
            $exchangeProposition->getCopywritingOrders()->addTransaction($transaction);
        }
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param $transition
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function applyTransition(ExchangeProposition $exchangeProposition, $transition)
    {
        if ($this->exchangePropositionWorkflow->can($exchangeProposition, $transition)) {
            $this->exchangePropositionWorkflow->apply($exchangeProposition, $transition);
            $this->em->flush();
        } else {
            throw new WorkflowTransitionEntityException($exchangeProposition, $transition);
        }
    }

    /**
     * @param User $user
     * @param ExchangeProposition[] $proposals
     *
     * @return bool
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    public function sendReminder(User $user, $proposals)
    {
        $frequency = $this->userSettingService->getValue(UserSetting::NOTIFICATION_PROPOSAL_FREQUENCY, $user);

        $proposalsRemind = [];

        /** @var ExchangeProposition $proposal */
        foreach ($proposals as $proposal) {
            $days = (new \DateTime())->diff($proposal->getCreatedAt())->days;

            if ($days !== 0 && $days % $frequency === 0) {
                $replace = [
                    '%siteUrl%' => $proposal->getExchangeSite()->getDomain(),
                    '%credits%' => $proposal->getCredits(),
                    '%days%' => $this->getDaysForResponse($proposal->getCreatedAt()),
                ];

                $proposalsRemind[] = $this->mailer->translator()->trans('reminder.proposal_row', $replace, 'exchange_site_proposals');
            }
        }

        if (count($proposalsRemind) === 0) {
            return false;
        }

        $replace = [
            '%link%' => $this->mailer->router()->generate('user_exchange_site_proposals'),
            '%proposalList%' => implode("<br>\n", $proposalsRemind)
        ];

        return $this->mailer->sendToUser(User::NOTIFICATION_NEW_PROPOSAL_REMINDER, $user, $replace);
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return float
     */
    public function getDaysForResponse(\DateTime $createdAt)
    {
        $tpsReacWebmaster = $this->em->getRepository(Settings::class)->getSettingValue('tps_reac_webmaster');
        $int = ($createdAt->getTimestamp() + ($tpsReacWebmaster * 86400)) - time();

        return floor($int/86400);
    }

    /**
     * @param string $authorType
     * @param ExchangeSite $exchangeSite
     * @param null|float $totalPrice
     * @param null|integer $countWords
     *
     * @return array
     */
    public function getTransactionDetails($authorType, ExchangeSite $exchangeSite, &$totalPrice = null, $countWords = null)
    {
        $result = [
            ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $exchangeSite->getCredits()
        ];

        $totalPrice = $exchangeSite->getCredits();

        if ($countWords === null) {
            $countWords = $exchangeSite->getMinWordsNumber();
        }

        switch ($authorType) {
            case ExchangeProposition::ARTICLE_AUTHOR_WRITER:
                $basePrice = $this->calculatorPriceService->getBasePrice($countWords, CalculatorPriceService::TOTAL_KEY);
                $imagesPrice = $this->calculatorPriceService->getImagesPrice($exchangeSite->getMaxImagesNumber(), CalculatorPriceService::TOTAL_KEY);
                $metaDescriptionPrice = $this->calculatorPriceService->getMetaDescriptionPrice($exchangeSite->getMetaDescription(), CalculatorPriceService::TOTAL_KEY);
                $priceForOrder = round($basePrice + $imagesPrice + $metaDescriptionPrice, 2);

                $result += [
                    ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $exchangeSite->getCredits(),
                    CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE => $priceForOrder,
                    'words'=> $countWords,
                    'wordsPrice' => $basePrice,
                    'images' => $exchangeSite->getMaxImagesNumber(),
                    'imagesPrice' => $imagesPrice,
                    CopywritingProject::TRANSACTION_DETAIL_PAYMENT_FOR_META_DESCRIPTION => $metaDescriptionPrice,
                ];

                $totalPrice += $priceForOrder;
                break;

            case ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER:
                $webmasterAdditionalPay = floatval($this->em->getRepository(Settings::class)->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY));
                $result += [
                    ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY => floatval($webmasterAdditionalPay),
                ];

                $totalPrice += $webmasterAdditionalPay;

                break;
        }

        return $result;
    }

    /**
     * @param User $userBuyer
     * @param TransactionDescriptionModel $transactionDescription
     * @param string $authorType
     * @param ExchangeSite $exchangeSite
     * @param null|integer $wordsCount
     *
     * @return \CoreBundle\Entity\Transaction
     *
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function paymentForExchangeProposal($userBuyer, TransactionDescriptionModel $transactionDescription, $authorType, ExchangeSite $exchangeSite, $wordsCount = null)
    {
        $details = $this->getTransactionDetails($authorType, $exchangeSite, $totalPrice, $wordsCount);

        if ($totalPrice === 0) {
            return null;
        }

        if ($totalPrice > $userBuyer->getBalance()) {
            throw new NotEnoughMoneyDetailException($userBuyer->getBalance(), $totalPrice);
        }

        $tags = [ExchangeProposition::TRANSACTION_TAG_BUY];

        if ($authorType === ExchangeProposition::ARTICLE_AUTHOR_WRITER) {
            $tags[] = CopywritingOrder::TRANSACTION_TAG_BUY;
        }

        $transaction = $this->transactionService->handling(
            $userBuyer,
            $transactionDescription,
            0,
            $totalPrice,
            $details,
            $tags
        );

        return $transaction;
    }
}
