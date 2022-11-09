<?php

namespace Tests\CoreBundle\ExchangeProposition;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Services\CalculatorPriceService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DomCrawler\Crawler;
use Tests\AbstractTest;

abstract class BaseExchangePropositionTest extends AbstractTest
{

    /**
     * @param User $userBuyer
     * @param ExchangeSite $exchangeSite
     * @param $type
     * @param $formData
     *
     * @return ExchangeProposition
     */
    protected function createExchangePropositionTest(User $userBuyer, ExchangeSite $exchangeSite, $type, $formData)
    {
        $this->setUser($userBuyer);

        $serviceCalculatePrice = $this->container()->get('core.service.calculator_price_service');

        $moneyBeforeCreate = $userBuyer->getBalance();

        $articlePrice = 0;
        $epAfterCreateStatus = ExchangeProposition::STATUS_AWAITING_WEBMASTER;
        $articleAuthorType = ExchangeProposition::ARTICLE_AUTHOR_BUYER;
        switch ($type) {
            case ExchangeSite::ACTION_WRITING_EREFERER:
                $articlePrice = $serviceCalculatePrice->getBasePrice($exchangeSite->getMinWordsNumber(), CalculatorPriceService::TOTAL_KEY)
                    + $serviceCalculatePrice->getImagesPrice($exchangeSite->getMaxImagesNumber(), CalculatorPriceService::TOTAL_KEY);
                $epAfterCreateStatus = ExchangeProposition::STATUS_AWAITING_WRITER;
                $articleAuthorType = ExchangeProposition::ARTICLE_AUTHOR_WRITER;
                break;

            case ExchangeSite::ACTION_WRITING_WEBMASTER:
                $articlePrice = $this->em()->getRepository(Settings::class)->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY);
                $articleAuthorType = ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER;
                break;
        }

        $this->sendGet('/bo/exchange-site-find-modal', ['id' => $exchangeSite->getId(), 'type' => $type]);

        $data = $this->getJsonResponse();

        $crawler = new Crawler(null, 'http://site.lc/bo/exchange-site-find-modal');
        $crawler->addContent($data['body']);
        $form = $crawler->filter('[name="user_' . $type . '"]')->form();
        $form->setValues(['user_'.$type => $formData]);
        $this->client()->submit($form);

        $response = $this->getJsonResponse();

        self::assertArrayHasKey('exchangePropositionId', $response, 'The response does not contain the ID of the created ExchangeProposition');

        /** @var ExchangeProposition $proposal */
        $proposal = $this->getObjectOf(ExchangeProposition::class, ['id' => $response['exchangePropositionId']]);

        self::assertNotNull($proposal, 'ExchangeProposition not found');
        self::assertNotNull($proposal->getArticleAuthorType(), $articleAuthorType, 'Invalid articleAuthorType');
        self::assertEquals($proposal->getStatus(), $epAfterCreateStatus, 'Invalid status after creation');

        $moneyAfterCreate = $userBuyer->getBalance();


        self::assertNotNull($proposal->getBuyerTransaction(), 'BuyerTransaction cannot be NULL');

        $transaction = $proposal->getBuyerTransaction()->getDetails();

        self::assertEquals($moneyAfterCreate, $moneyBeforeCreate - $articlePrice - $exchangeSite->getCredits());

        // Transaction check
        self::assertArrayHasKey(ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE, $transaction, 'Transaction must contain the key "'.ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE.'", when buying through Ereferer');

        switch ($type) {
            case ExchangeSite::ACTION_WRITING_EREFERER:
                self::assertArrayHasKey(CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE, $transaction, 'Transaction must contain the key "'.CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE.'", when buying through Ereferer');
                self::assertEquals($transaction[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE] + $transaction[CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE], $proposal->getBuyerTransaction()->getCredit(), 'Invalid transaction amount');
                self::assertEquals($transaction[CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE], $articlePrice, 'Invalid "'.CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE.'"" amount');
                self::assertEquals($transaction[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE], $exchangeSite->getCredits(), 'Invalid "'.ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE.'" amount');
                break;
            case ExchangeSite::ACTION_WRITING_WEBMASTER:
                self::assertArrayHasKey(ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY, $transaction, 'Transaction must contain the key "'.ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY.'", when the article is written by webmaster');
                self::assertEquals($transaction[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE] + $transaction[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY], $proposal->getBuyerTransaction()->getCredit(), 'Invalid transaction amount');
                self::assertEquals($transaction[ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY], $articlePrice, 'Invalid "'. ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY . '" amount');
                self::assertEquals($transaction[ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE], $exchangeSite->getCredits(), 'Invalid "'.ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE.'" amount');
                break;
        }

        return $proposal;
    }

    /**
     * @param User $userAdmin
     * @param User $userWriter
     * @param ExchangeProposition $exchangeProposition
     */
    protected function writerAssignmentTest(ExchangeProposition $exchangeProposition, User $userAdmin, User $userWriter)
    {
        $this->setUser($userAdmin);

        $copywritingOrder = $exchangeProposition->getCopywritingOrders();

        $requestData = [
            'orderIds' => [$copywritingOrder->getId()],
            'copywriter' => $userWriter->getId(),
        ];

        $this->sendPost('bo/copywriting/ajax/assign', $requestData);

        $response = $this->getJsonResponse();

        self::assertEquals($response['result'], 'success', 'Error assignment writer');
        self::assertEquals($exchangeProposition->getStatus(), ExchangeProposition::STATUS_IN_PROGRESS, 'Status of ExchangeProposition must be ' . ExchangeProposition::STATUS_IN_PROGRESS);
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param string $article - path to file
     */
    protected function writingArticleTest(ExchangeProposition $exchangeProposition, $article)
    {
        $copywritingOrder = $exchangeProposition->getCopywritingOrders();
        $this->em()->refresh($copywritingOrder);

        $this->setUser($copywritingOrder->getCopywriter());

        $data = [
            'text' => file_get_contents(__DIR__.'/../Data/articles/'.$article)
        ];

        $response = $this->sendForm('/bo/copywriting/article/'.$copywritingOrder->getArticle()->getId().'/edit', 'copywriting_article', $data, [], '[name="copywriting_article[validateAndSave]"]');

        self::assertContains('Redirecting to /bo/copywriting/order/list', $response);
        self::assertEquals($copywritingOrder->getStatus(), CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN, 'Status of CopywritingOrder must be ' . CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
    }

    /**
     * @param User $userAdmin
     * @param ExchangeProposition $exchangeProposition
     */
    protected function approveArticleTest(ExchangeProposition $exchangeProposition, User $userAdmin)
    {
        $this->setUser($userAdmin);

        $copywritingOrder = $exchangeProposition->getCopywritingOrders();

        $response = $this->sendForm('/bo/copywriting/article/'.$copywritingOrder->getArticle()->getId().'/edit', 'copywriting_article', [], [], '[name="copywriting_article[validateAndSave]"]');

        self::assertContains('Redirecting to /bo/copywriting/order/list', $response);
        self::assertEquals($copywritingOrder->getStatus(), CopywritingOrder::STATUS_COMPLETED, 'Status of CopywritingOrder must be ' . CopywritingOrder::STATUS_COMPLETED);
        self::assertEquals($exchangeProposition->getStatus(), ExchangeProposition::STATUS_ACCEPTED, 'Status of ExchangeProposition must be ' . ExchangeProposition::STATUS_ACCEPTED);
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     */
    protected function publishedArticleTest(ExchangeProposition $exchangeProposition)
    {
        $seller = $exchangeProposition->getExchangeSite()->getUser();
        $sellerBalanceBeforePublished = $seller->getBalance();

        $this->setUser($seller);

        $url = $exchangeProposition->getExchangeSite()->getUrl().'/test.html';

        $exchangeProposition
            ->setPagePublish($url)
            ->setComments('<a target="_blank" href="' . $url . '">' . $url . '</a>');

        $this->container()->get('state_machine.exchange_proposition')->apply($exchangeProposition, ExchangeProposition::TRANSITION_PUBLISH);

        self::assertEquals($exchangeProposition->getStatus(), ExchangeProposition::STATUS_PUBLISHED, 'Status of CopywritingOrder must be ' . ExchangeProposition::STATUS_PUBLISHED);

        // Transaction check
        self::assertNotNull($exchangeProposition->getSellerTransaction(), 'SellerTransaction cannot be NULL');
        $details = $exchangeProposition->getSellerTransaction()->getDetails();
        self::assertArrayHasKey(ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT, $details, 'SellerTransaction details must contain "'.ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT.'"');
        self::assertArrayHasKey(ExchangeProposition::TRANSACTION_DETAIL_COMMISSION, $details, 'SellerTransaction details must contain "'.ExchangeProposition::TRANSACTION_DETAIL_COMMISSION.'"');
        self::assertEquals($exchangeProposition->getCredits(), $exchangeProposition->getSellerTransaction()->getDebit(), 'Invalid transaction amount');
        self::assertEquals($sellerBalanceBeforePublished + $exchangeProposition->getCredits(), $seller->getBalance(), 'Incorrect seller payment');
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     */
    protected function impossibleArticleTest(ExchangeProposition $exchangeProposition)
    {
        $this->setUser($exchangeProposition->getCopywritingOrders()->getCopywriter());

        $refundAmount = $this->container()->get('core.service.exchange_proposition')->calculateRefundAmount($exchangeProposition);

        $epStatusEnd = $exchangeProposition->getStatus() === ExchangeProposition::STATUS_PUBLISHED ? ExchangeProposition::STATUS_PUBLISHED : ExchangeProposition::STATUS_IMPOSSIBLE;
        $buyerBalanceBeforeImpossible = $exchangeProposition->getUser()->getBalance();

        $this->sendPost('/bo/exchange-site-proposals-task-impossible/' . $exchangeProposition->getId(), ['comment' => 'Test reason']);

        $response = $this->getJsonResponse();

        self::assertEquals($response['status'], true, 'Incorrect response');
        self::assertEquals($exchangeProposition->getStatus(), $epStatusEnd, 'Status of ExchangeProposition must be ' . $epStatusEnd);
        self::assertEquals($exchangeProposition->getCopywritingOrders()->getStatus(), CopywritingOrder::STATUS_IMPOSSIBLE, 'Status of CopywritingOrder must be ' . CopywritingOrder::STATUS_IMPOSSIBLE);
        self::assertEquals($buyerBalanceBeforeImpossible + $refundAmount, $exchangeProposition->getUser()->getBalance(), 'Buyer balance update error');
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param string $status
     */
    protected function refuseArticleTest(ExchangeProposition $exchangeProposition, $status = 'success')
    {
        $this->setUser($exchangeProposition->getExchangeSite()->getUser());

        $refundAmount = $this->container()->get('core.service.exchange_proposition')->calculateRefundAmount($exchangeProposition);

        $epStatusEnd = $exchangeProposition->getStatus() === ExchangeProposition::STATUS_PUBLISHED ? ExchangeProposition::STATUS_PUBLISHED : ExchangeProposition::STATUS_REFUSED;
        $epStatusBeforeRefuse = $exchangeProposition->getStatus();
        if ($exchangeProposition->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WRITER) {
            $coStatusBeforeRefuse = $exchangeProposition->getCopywritingOrders()->getStatus();
        }
        $buyerBalanceBeforeRefuse = $exchangeProposition->getUser()->getBalance();

        $this->sendPost('/bo/exchange-site-proposals-refuse', ['comment' => 'Test reason', 'id' => $exchangeProposition->getId()]);

        $response = $this->getJsonResponse();

        if ($status === 'fail') {
            self::assertEquals($response['status'], $status, 'Incorrect response');
            self::assertEquals($exchangeProposition->getStatus(), $epStatusBeforeRefuse, 'Status of ExchangeProposition should not change');
            self::assertEquals($buyerBalanceBeforeRefuse, $exchangeProposition->getUser()->getBalance(), 'Buyer balance should not change');
        } else {
            self::assertEquals($response['result'], $status, 'Incorrect response');
            self::assertEquals($exchangeProposition->getStatus(), $epStatusEnd, 'Status of ExchangeProposition must be ' . $epStatusEnd);
            self::assertEquals($buyerBalanceBeforeRefuse + $refundAmount, $exchangeProposition->getUser()->getBalance(), 'Buyer balance update error');
        }


        if ($exchangeProposition->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WRITER) {
            self::assertEquals($exchangeProposition->getCopywritingOrders()->getStatus(), $coStatusBeforeRefuse, 'Status of CopywritingOrder should not change');
        }
    }


    /**
     * @param ExchangeProposition $exchangeProposition
     * @param bool $isExpired
     *
     * @throws \Exception
     */
    protected function expiredArticleTest(ExchangeProposition $exchangeProposition, $isExpired = false)
    {
        $refundAmount = $this->container()->get('core.service.exchange_proposition')->calculateRefundAmount($exchangeProposition);

        $toExpiredStatuses = [ExchangeProposition::STATUS_AWAITING_WEBMASTER, ExchangeProposition::STATUS_ACCEPTED];

        $epStatusEnd = in_array($exchangeProposition->getStatus(), $toExpiredStatuses) ? ExchangeProposition::STATUS_EXPIRED : $exchangeProposition->getStatus();
        $epStatusBeforeExpired = $exchangeProposition->getStatus();
        $coStatusBeforeExpired = $exchangeProposition->getCopywritingOrders()->getStatus();
        $buyerBalanceBeforeExpired = $exchangeProposition->getUser()->getBalance();

        $command = $this->container()->get('core.command.apply_expired_propositions');
        $command->setContainer($this->container());

        $buffer = new BufferedOutput();
        $input = new ArrayInput([]);

        $command->run($input, $buffer);

        $output = $buffer->fetch();

        if ($isExpired) {
            self::assertContains('#' . $exchangeProposition->getId(), $output, 'ExchangeProposition not found in console output');
            self::assertEquals($exchangeProposition->getStatus(), $epStatusEnd, 'Status of ExchangeProposition must be ' . $epStatusEnd);
            self::assertEquals($buyerBalanceBeforeExpired + $refundAmount, $exchangeProposition->getUser()->getBalance(), 'Buyer balance update error');
        } else {
            self::assertNotContains('#' . $exchangeProposition->getId(), $output, 'ExchangeProposition is not expired but present in the output');
            self::assertEquals($exchangeProposition->getStatus(), $epStatusBeforeExpired, 'Status of ExchangeProposition should not change');
            self::assertEquals($buyerBalanceBeforeExpired, $exchangeProposition->getUser()->getBalance(), 'Buyer balance should not change');
        }

        self::assertEquals($exchangeProposition->getCopywritingOrders()->getStatus(), $coStatusBeforeExpired, 'Status of CopywritingOrder should not change');
    }

    public function acceptProposalTest(ExchangeProposition $exchangeProposition)
    {
        $this->setUser($exchangeProposition->getExchangeSite()->getUser());

        $this->sendGet('/bo/exchange-site-proposals-accept', ['id' => $exchangeProposition->getId()]);

        $response = $this->getJsonResponse();

        self::assertEquals($response['result'], 'success', 'Incorrect response');
        self::assertEquals($exchangeProposition->getStatus(), ExchangeProposition::STATUS_ACCEPTED, 'Status of ExchangeProposition must be ' . ExchangeProposition::STATUS_ACCEPTED);
    }

    /**
     * @param $exchangeSite
     * @param $userBuyer
     * @param $userAdmin
     * @param $userWriter
     */
    public function loadBaseData(&$exchangeSite, &$userBuyer, &$userAdmin, &$userWriter)
    {
        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->getObjectOf(ExchangeSite::class, $exchangeSite);

        /** @var User $userBuyer */
        $userBuyer = $this->getObjectOf(User::class, ['email' => $userBuyer]);

        /** @var User $userAdmin */
        $userAdmin = $this->getObjectOf(User::class, ['email' => $userAdmin]);

        /** @var User $userWriter */
        $userWriter = $this->getObjectOf(User::class, ['email' => $userWriter]);
    }
}
