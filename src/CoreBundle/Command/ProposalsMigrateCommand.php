<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Transaction;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\ExchangePropositionRepository;
use CoreBundle\Services\CalculatorPriceService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class ProposalsMigrateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:proposals-migrate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $copywritingCalculatorPrice = $this->getContainer()->get('core.service.calculator_price_service');
        $exchangeProposalService = $this->getContainer()->get('core.service.exchange_proposition');

        /** @var ExchangePropositionRepository $proposalsRepository */
        $proposalsRepository = $em->getRepository(ExchangeProposition::class);

        $qb = $proposalsRepository->filter([])
            ->andWhere('ep.buyerTransaction IS NULL')
        ;

        $result = $qb->getQuery()->getResult();

        $webmasterAdditionalPay = floatval(
            $em->getRepository(Settings::class)->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY)
        );

        $transactionService = $this->getContainer()->get('core.service.transaction');

        /** @var ExchangeProposition $item */
        try {
            $i = 0;
            foreach ($result as $item) {
                ++$i;
                $transactionBuyer = new Transaction();
                $transactionBuyer->setUser($item->getUser());
                $transactionService->addTagToTransaction($transactionBuyer, ExchangeProposition::TRANSACTION_TAG_BUY);

                $item->addTransaction($transactionBuyer);

                $sitePrice = floatval($item->getExchangeSite()->getCredits());

                $details = [ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $sitePrice];

                if ($item->getCopywritingOrders()) {
                    $item->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WRITER);

                    $details = $exchangeProposalService->getTransactionDetails(
                        ExchangeProposition::ARTICLE_AUTHOR_WRITER,
                        $item->getExchangeSite()
                    );

                    $name = 'proposal.writing_ereferer';

                    $basePrice = $copywritingCalculatorPrice->getBasePrice(
                        $item->getExchangeSite()->getMinWordsNumber(),
                        CalculatorPriceService::TOTAL_KEY
                    );
                    $imagesPrice = $copywritingCalculatorPrice->getImagesPrice(
                        $item->getExchangeSite()->getMaxImagesNumber(),
                        CalculatorPriceService::TOTAL_KEY
                    );
                    $priceForOrder = $basePrice + $imagesPrice;
                    $totalPrice = $priceForOrder + $item->getExchangeSite()->getCredits();

                    $transactionBuyer->setCredit($totalPrice);
                } elseif ($item->getDocumentLink()) {
                    $item->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_BUYER);
                    $transactionBuyer->setCredit($sitePrice);
                    $name = 'proposal.pay_for_proposition';
                } else {
                    $transactionBuyer->setCredit($sitePrice + $webmasterAdditionalPay);
                    $item->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER);
                    $details[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY] = $webmasterAdditionalPay;
                    $name = 'proposal.pay_for_proposition';
                }

                $transactionBuyer
                    ->setDescription($name)
                    ->setMarks(['%url%' => $item->getExchangeSite()->getUrl()])
                    ->setDetails($details)
                ;


                if ($item->getStatus() === ExchangeProposition::STATUS_PUBLISHED && $item->getType() !== ExchangeProposition::OWN_TYPE) {
                    $costs = $item->getCredits();

                    $buyerTransactionDetails = $item->getBuyerTransaction()->getDetails();
                    if (!isset($buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE])) {
                        throw new \InvalidArgumentException(
                            'The transaction must contain the field "'.ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE.'"',
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }

                    $exchangeSitePrice = $buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE];

                    $moreDetails = [ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $exchangeSitePrice];
                    if ($item->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER) {
                        if (!isset($buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY])) {
                            throw new \InvalidArgumentException(
                                'The transaction must contain the field "'.ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY.'"',
                                Response::HTTP_UNPROCESSABLE_ENTITY
                            );
                        }
                        $epWebmasterAdditionalPay = $buyerTransactionDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY];
                        $moreDetails[] = $exchangeSitePrice;
                        $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY] = $epWebmasterAdditionalPay;
                        if ($exchangeSitePrice > 0) {
                            $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION] = $exchangeSitePrice + $epWebmasterAdditionalPay - $costs;
                            $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT] = round(
                                (1 - ($costs - $epWebmasterAdditionalPay) / $exchangeSitePrice) * 100
                            );
                        }
                    } else {
                        if ($exchangeSitePrice > 0) {
                            $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION] = $exchangeSitePrice - $costs;
                            $moreDetails[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT] = round(
                                (1 - $costs / $exchangeSitePrice) * 100
                            );
                        }
                    }

                    $transactionSeller = new Transaction();
                    $item->addTransaction($transactionSeller);
                    $transactionSeller
                        ->setUser($item->getExchangeSite()->getUser())
                        ->setDebit($costs)
                        ->setDetails($moreDetails)
                        ->setDescription('proposal.money_from_proposal')
                        ->setMarks(['%url%' => $item->getExchangeSite()->getUrl()])
                    ;
                    $transactionService->addTagToTransaction($transactionBuyer, ExchangeProposition::TRANSACTION_TAG_REWARD);
                }
                if ($i % 100 === 0) {
                    $em->flush();
                }
            }
            $em->flush();
        } catch (\Exception $exception) {
            echo $exception->getMessage(), ' | ', $exception->getFile(), ':', $exception->getLine();
            return;
        }
    }
}
