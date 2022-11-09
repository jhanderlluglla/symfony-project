<?php

namespace Tests\CoreBundle\ExchangeProposition;

use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Transaction;

class CalculateRefundTest extends BaseExchangePropositionTest
{
    public function testCalculateRefund()
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        $calculate = $this->container()->get('core.service.exchange_proposition');

        $redactionPrice = 25;
        $webmasterWriterPrice = $redactionPrice;
        $exchangeSitePrice = 75;

        $exchangeProposition = new ExchangeProposition();
        $exchangeProposition->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WRITER);

        $copywriterOrder = new CopywritingOrder();
        $exchangeProposition->setCopywritingOrders($copywriterOrder);

        $buyerTransaction = new Transaction();
        $buyerTransaction->setCredit($redactionPrice + $exchangeSitePrice);
        $buyerTransaction->setDetails([
            ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE => $exchangeSitePrice,
            CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE => $redactionPrice,
            ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY => $webmasterWriterPrice,
        ]);

        $exchangeProposition->addTransaction($buyerTransaction);

        // ---------------- Writing by Ereferer

        // Case 1.1 - CopywritingOrder - completed, ExchangeProposition - accepted
        $copywriterOrder->setStatus(CopywritingOrder::STATUS_COMPLETED);
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_ACCEPTED);

        $refund = $calculate->calculateRefundAmount($exchangeProposition);

        self::assertEquals($refund, $exchangeSitePrice, 'Fail case 1.1');

        // Case 1.2 - CopywritingOrder - progress, ExchangeProposition - accepted
        $copywriterOrder->setStatus(CopywritingOrder::STATUS_PROGRESS);
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_ACCEPTED);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);

        self::assertEquals($refund, $exchangeSitePrice + $redactionPrice, 'Fail case 1.2');

        // Case 1.3 - CopywritingOrder - progress, ExchangeProposition - published
        $copywriterOrder->setStatus(CopywritingOrder::STATUS_PROGRESS);
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_PUBLISHED);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);

        self::assertEquals($refund, $redactionPrice, 'Fail case 1.3');


        // ---------------- Sending your article
        $exchangeProposition->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_BUYER);
        $exchangeProposition->setCopywritingOrders(null);
        $buyerTransaction->setCredit($exchangeSitePrice);

        // Case 2.1 - ExchangeProposition - published
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_PUBLISHED);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);
        self::assertEquals($refund, 0, 'Fail case 2.1');

        // Case 2.2 - ExchangeProposition - in progress
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_IN_PROGRESS);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);
        self::assertEquals($refund, $exchangeSitePrice, 'Fail case 2.2');

        // ---------------- Writing by Webmaster
        $exchangeProposition->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER);
        $exchangeProposition->setCopywritingOrders(null);

        // Case 3.1 - ExchangeProposition - published
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_PUBLISHED);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);
        self::assertEquals($refund, 0, 'Fail case 3.1');

        // Case 3.2 -ExchangeProposition - in progress
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_IN_PROGRESS);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);
        self::assertEquals($refund, $webmasterWriterPrice + $exchangeSitePrice, 'Fail case 3.2');

        // ---------- Free

        // Case 4.1 - ExchangeProposition - free
        $exchangeProposition = new ExchangeProposition();
        $exchangeProposition->setStatus(ExchangeProposition::STATUS_IN_PROGRESS);
        $refund = $calculate->calculateRefundAmount($exchangeProposition);
        self::assertEquals($refund, 0, 'Fail case 2.3');
    }
}
