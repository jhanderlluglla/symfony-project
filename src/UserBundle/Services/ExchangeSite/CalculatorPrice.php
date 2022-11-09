<?php
namespace UserBundle\Services\ExchangeSite;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use http\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class CalculatorPrice
{
    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * CalculatorPrice constructor.
     * @param TransactionService $transactionService
     * @param TranslatorInterface $translator
     */
    public function __construct(TransactionService $transactionService, TranslatorInterface $translator, EntityManager $entityManager)
    {
        $this->transactionService = $transactionService;
        $this->translator = $translator;
        $this->em = $entityManager;
    }

    /**
     * @param float|int $price
     * @return float|int
     */
    public function getPriceWithCommission($price)
    {
        $commissionPercent = $this->em->getRepository(Settings::class)->getSettingValue(Settings::COMMISSION_PERCENT);

        return $price - ($price * ($commissionPercent / 100));
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @throws \Exception
     */
    public function payUser($exchangeProposition)
    {
        if ($exchangeProposition->getType() === ExchangeProposition::OWN_TYPE || $exchangeProposition->getCredits() <= 0 || !$exchangeProposition->getBuyerTransaction()) {
            return;
        }

        $exchangeSite = $exchangeProposition->getExchangeSite();
        $costs = round($exchangeProposition->getCredits(), 2);

        $buyerTransactionDetails = $exchangeProposition->getBuyerTransaction()->getDetails();

        if (!isset($buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE])) {
            throw new \InvalidArgumentException('The transaction must contain the field "'.ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE.'"', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $exchangeSitePrice = $buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE];

        $moreDetails = [ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $exchangeSitePrice];
        if ($exchangeProposition->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER) {
            if (!isset($buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY])) {
                throw new \InvalidArgumentException('The transaction must contain the field "'.ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY.'"', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $epWebmasterAdditionalPay = $buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY];
            $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY] = $epWebmasterAdditionalPay;
            if ($exchangeSitePrice > 0) {
                $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION] = round($exchangeSitePrice + $epWebmasterAdditionalPay - $costs, 2);
                $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT] = round((1 - ($costs - $epWebmasterAdditionalPay) / $exchangeSitePrice) * 100);
            }
        } else {
            if ($exchangeSitePrice > 0) {
                $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION] = round($exchangeSitePrice - $costs, 2);
                $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT] = round((1 - $costs / $exchangeSitePrice) * 100);
            }
        }


        $transaction = $this->transactionService->handling(
            $exchangeSite->getUser(),
            new TransactionDescriptionModel('proposal.money_from_proposal', ['%url%' => $exchangeSite->getUrl()]),
            $costs,
            0,
            $moreDetails,
            [ExchangeProposition::TRANSACTION_TAG_REWARD]
        );

        $exchangeProposition->addTransaction($transaction);
    }
}
