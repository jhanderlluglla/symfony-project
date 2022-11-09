<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\Test\LoadCopywritingProjectData;
use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test\LoadUserData;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Transaction;
use CoreBundle\Entity\User;
use Tests\AbstractTest;

class ArticleTest extends AbstractTest
{
    /**
     * @dataProvider paymentAndRejectProvider
     *
     * @param CopywritingOrder $copywritingOrder
     */
    public function testPaymentAndReject($copywritingOrder)
    {
        $fixtures = [
            LoadUserData::class,
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            ORM\LoadTransactionTagData::class,
            LoadCopywritingProjectData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        if (is_array($copywritingOrder)) {
            /** @var CopywritingOrder $copywritingOrder */
            $copywritingOrder = $this->getObjectOf(CopywritingOrder::class, $copywritingOrder);
        }

        /** @var User $corrector */
        $corrector = $this->getObjectOf(User::class, ['email' => 'admin-1@test.com']);

        $article = $copywritingOrder->getArticle();

        $this->setUser($corrector);

        $workflowCopywritingOrder = $this->container()->get('state_machine.copywriting_order');
        $copywriterArticleProcessor = $this->container()->get('user.copywriting.article_processor');

        $calculateCorrectEarn = $copywriterArticleProcessor->countCorrectorEarn($article);
        $calculateWriterEarn = $copywriterArticleProcessor->calculateWriterEarn($article);

        $correctorBalanceOld = $corrector->getBalance();
        $writerBalanceOld = $copywritingOrder->getCopywriter()->getBalance();
        $customerBalanceOld = $copywritingOrder->getCustomer()->getBalance();
        $imagesPerArticleOld = $copywritingOrder->getImagesPerArticleTo();

        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_COMPLETE_TRANSITION);

        $imagesRate = $this->em()->getRepository(Settings::class)->getSettingValue(Settings::PRICE_PER_IMAGE);

        $correctEarn = $article->getCorrectorEarn();
        $writerEarn = $article->getWriterEarn();
        $customerRestore = ($imagesPerArticleOld - $article->getTotalImagesNumber()) * $imagesRate;
        $customerRestore = $customerRestore > 0 ? $customerRestore : 0;

        //Check calculate earns
        self::assertEquals($correctEarn, $calculateCorrectEarn->getTotalForAdmin(), 'Error calculate correct earn');
        self::assertEquals($writerEarn, $calculateWriterEarn->getTotalForWriter(), 'Error calculate writer earn');

        //Check update balance
        self::assertEquals($correctorBalanceOld + $correctEarn, $corrector->getBalance(), 'Error update correct-user balance');
        self::assertEquals($writerBalanceOld + $writerEarn, $copywritingOrder->getCopywriter()->getBalance(), 'Error update writer-user balance');
        self::assertEquals($customerBalanceOld + $customerRestore, $copywritingOrder->getCustomer()->getBalance(), 'Error update customer balance');

        $this->checkExistenceTransaction($corrector, $correctEarn);
        $this->checkExistenceTransaction($copywritingOrder->getCopywriter(), $writerEarn);

        //
        // Reject article
        //
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_DECLINE_TRANSITION);

        self::assertEquals($writerBalanceOld, $copywritingOrder->getCopywriter()->getBalance(), 'Error restore writer balance');
        self::assertEquals($correctorBalanceOld, $corrector->getBalance(), 'Error restore corrector balance');
        self::assertEquals($customerBalanceOld + $customerRestore, $copywritingOrder->getCustomer()->getBalance(), 'Webmaster\'s balance should not change');

        $this->checkExistenceTransaction($corrector, -$correctEarn);
        $this->checkExistenceTransaction($copywritingOrder->getCopywriter(), -$writerEarn);
        $this->checkExistenceTransaction($copywritingOrder->getCustomer(), $customerRestore);

        //
        // Submit article to webmaster after decline
        //
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN);
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_COMPLETE_TRANSITION);

        //Check update balance
        self::assertEquals($correctorBalanceOld + $correctEarn, $corrector->getBalance(), 'Error update correct-user balance: submit after decline');
        self::assertEquals($writerBalanceOld + $writerEarn, $copywritingOrder->getCopywriter()->getBalance(), 'Error update writer-user balance: submit after decline');
        self::assertEquals($customerBalanceOld + $customerRestore, $copywritingOrder->getCustomer()->getBalance(), 'Webmaster\'s balance should not change: submit after decline');

        $this->checkExistenceTransaction($corrector, $correctEarn);
        $this->checkExistenceTransaction($copywritingOrder->getCopywriter(), $writerEarn);
    }

    /**
     * @param User $user
     * @param float $sum
     *
     * @return void
     */
    private function checkExistenceTransaction($user, $sum)
    {
        if ($sum === 0) {
            return;
        }

        $sumStr = ($sum > 0 ? '+' : '') . $sum;
        $sumAbs = abs($sum);

        $transactionRepository = $this->em()->getRepository(Transaction::class);
        $transaction = $transactionRepository->findOneBy(
            [
                'user' => $user,
                'debit' => ($sum > 0) ? $sumAbs : 0,
                'credit' => ($sum < 0) ? $sumAbs : 0,
            ],
            ['createdAt' => 'DESC']
        );

        if (!$transaction) {
            self::fail('Transaction not found: '. $user->getUsername() . ' | ' . $sumStr);
        } else {
            $message = 'Invalid transaction: '. $user->getUsername() . ' | ' . $sumStr . ' ≠ ' . ($transaction->getDebit() > 0 ? $transaction->getDebit() : '-'.$transaction->getCredit());
            self::assertEquals($sumAbs, ($sum > 0 ? $transaction->getDebit() : $transaction->getCredit()), $message);
        }
    }

    public function paymentAndRejectProvider()
    {
        return [
            [['title' => 'P#1-O#1: submitted_to_admin']],
            [['title' => 'P#2-O#2: submitted_to_admin (image:1:0:2)']],
            [['title' => 'P#4-O#1: submitted_to_admin (metaDescription:1)']],
        ];
    }

    /**
     * @dataProvider imageProcessingDataProvider
     *
     * @param $writerSend
     * @param $correctorSend
     * @param $writerSendSecond
     * @param $correctorSendSecond
     *
     * @throws \Exception
     */
    public function testImageProcessing($writerSend, $correctorSend, $writerSendSecond, $correctorSendSecond)
    {
        $fixtures = [
            LoadUserData::class,
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        /** @var User $customer */
        $customer = $this->getObjectOf(User::class, ['email' => 'webmaster-1@test.com']);
        /** @var User $writer */
        $writer = $this->getObjectOf(User::class, ['email' => 'writer-1@test.com']);
        /** @var User $corrector */
        $corrector = $this->getObjectOf(User::class, ['email' => 'admin-1@test.com']);


        $article = new CopywritingArticle();
        $article
            ->setText($writerSend)
        ;

        $order = new CopywritingOrder();
        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setImagesPerArticleFrom(0)
            ->setImagesPerArticleTo(5)
            ->setArticle($article)
            ->setCopywriter($writer)
            ->setCustomer($customer)
            ->setWordsNumber(100)
            ->setTakenAt(new \DateTime('+1 day'))
            ->setCreatedAt(new \DateTime())
        ;

        $article->setOrder($order);

        $workflow = $this->container()->get('state_machine.copywriting_order');

        $workflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN);

        $articleProcessor = $this->container()->get('user.copywriting.article_processor');
        $writerImages = $articleProcessor->getImagesFromText($writerSend);

        self::assertArraySubset($writerImages, $article->getImagesByWriter(), 'Writer image definition error: A');
        self::assertEquals(count($writerImages), count($article->getImagesByWriter()), 'Writer image definition error: B');

        $this->setUser($corrector);

        $article->setText($correctorSend);
        $workflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);

        $images = $articleProcessor->getImagesFromText($correctorSend);
        $newWriterImages = array_intersect($writerImages, $images);
        $adminImages = array_diff($images, $newWriterImages);

        self::assertArraySubset($newWriterImages, $article->getImagesByWriter(), 'Writer image definition error after admin confirmation: A');
        self::assertEquals(count($newWriterImages), count($article->getImagesByWriter()), 'Writer image definition error after admin confirmation: B');
        self::assertArraySubset($adminImages, $article->getImagesByAdmin(), 'Corrector image definition error: A');
        self::assertEquals(count($adminImages), count($article->getImagesByAdmin()), 'Corrector image definition error: B');

        //Declined Article
        $order->setStatus(CopywritingOrder::STATUS_DECLINED);

        $this->setUser($writer);
        $article->setText($writerSendSecond);
        $workflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN);

        $images = $articleProcessor->getImagesFromText($writerSendSecond);
        $writerImagesAfterDecline = array_diff($images, $adminImages);
        $adminImagesAfterDecline = array_intersect($images, $adminImages);

        self::assertArraySubset($writerImagesAfterDecline, $article->getImagesByWriter(), 'Decline: Writer image definition error: A');
        self::assertEquals(count($writerImagesAfterDecline), count($article->getImagesByWriter()), 'Decline: Writer image definition error: B');
        self::assertArraySubset($adminImagesAfterDecline, $article->getImagesByAdmin(), 'Decline: Corrector image definition error: A');
        self::assertEquals(count($adminImagesAfterDecline), count($article->getImagesByAdmin()), 'Decline: Corrector image definition error: B');

        $this->setUser($corrector);
        $article->setText($correctorSendSecond);
        $workflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);

        $images = $articleProcessor->getImagesFromText($correctorSendSecond);
        $newWriterImagesAfterDecline = array_intersect($writerImagesAfterDecline, $images);
        $adminImagesAfterDecline = array_diff($images, $newWriterImagesAfterDecline);

        self::assertArraySubset($newWriterImagesAfterDecline, $article->getImagesByWriter(), 'Decline: Writer image definition error after admin confirmation: A');
        self::assertEquals(count($newWriterImagesAfterDecline), count($article->getImagesByWriter()), 'Decline: Writer image definition error after admin confirmation: B');
        self::assertArraySubset($adminImagesAfterDecline, $article->getImagesByAdmin(), 'Decline: Corrector image definition error after admin confirmation: A');
        self::assertEquals(count($adminImagesAfterDecline), count($article->getImagesByAdmin()), 'Decline: Corrector image definition error after admin confirmation: B');
    }

    public function imageProcessingDataProvider()
    {
        $content = file_get_contents(__DIR__.'/Data/articles/article_text.html');
        return [
            [
                'writerSend' => '<div><img src="/writer_1.png" /><img src="/writer_2.png" />'.$content.'</div>',
                'adminSend' => '<div><img src="/writer_1.png" /><img src="/admin_1.png" /><img src="/admin_2.png" />'.$content.'</div>',
                'writerSendSecond' => '<div><img src="/writer_1.png" /><img src="/writer_3.png" /><img src="/admin_2.png" />'.$content.'</div>',
                'adminSendSecond' =>'<div><img src="/writer_1.png" /><img src="/writer_3.png" /><img src="/admin_2.png" />'.$content.'</div>',
            ],
            [
                'writerSend' => '<div><img src="/writer_1.png" /><img src="/writer_2.png" />'.$content.'</div>',
                'adminSend' => '<div><img src="/writer_1.png" /><img src="/writer_2.png" /><img src="/admin_1.png" />'.$content.'</div>',
                'writerSendSecond' => '<div>'.$content.'</div>',
                'adminSendSecond' =>'<div><img src="/admin_3.png" />'.$content.'</div>',
            ],
            [
                'writerSend' => '<div>'.$content.'</div>',
                'adminSend' => '<div><img src="/writer_1.png" /><img src="/admin_1.png" />'.$content.'</div>',
                'writerSendSecond' => '<div><img src="/writer_2.png" /><img src="/admin_1.png" />'.$content.'</div>',
                'adminSendSecond' =>'<div><img src="/writer_2.png" /><img src="/admin_1.png" />'.$content.'</div>',
            ],
            [
                'writerSend' => '<div><img src="/writer_1.png" /><img src="/writer_2.png" />'.$content.'</div>',
                'adminSend' => '<div><img src="/writer_1.png" /><img src="/writer_2.png" />'.$content.'</div>',
                'writerSendSecond' => '<div><img src="/writer_2.png" /><img src="/writer_3.png" />'.$content.'</div>',
                'adminSendSecond' =>'<div><img src="/writer_2.png" />'.$content.'</div>',
            ],
        ];
    }
}
