<?php

namespace Tests\CoreBundle\ExchangeProposition;

use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test as Test;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\Settings;
use CoreBundle\Repository\SettingsRepository;
use Tests\AbstractTest;
use UserBundle\Services\BonusCalculator\CopywritingAdminBonusCalculator;

class CopywritingArticleProcessorTest extends AbstractTest
{

    /**
     * @dataProvider countCorrectorEarnDataProvider
     *
     * @param $copywritingOrder
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testCountCorrectorEarn($copywritingOrder)
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            Test\LoadUserData::class,
            Test\LoadCopywritingProjectData::class,
            ORM\LoadTransactionTagData::class,
        ];
        $this->loadFixtures($fixtures);

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->em()->getRepository(Settings::class);
        $settings = $settingsRepository->getSettingsByIdentificators([
            Settings::CORRECTOR_PRICE_PER_100_WORDS,
            Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS,
            Settings::CORRECTOR_EXPRESS_RATE,
            Settings::WRITER_PRICE_PER_IMAGE,
        ]);

        /** @var CopywritingOrder $copywritingOrder */
        $copywritingOrder = $this->getObjectOf(CopywritingOrder::class, $copywritingOrder);
        $copywritingOrder->setWordsNumber(100);

        /** @var CopywritingArticle $copywritingArticle */
        $copywritingArticle = $copywritingOrder->getArticle();
        $copywritingArticleProcessor = $this->container()->get('user.copywriting.article_processor');

        $copywritingOrder->setReadyForReviewAt(new \DateTime());
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::CORRECTOR_PRICE_PER_100_WORDS],
            $articleEarning->getBaseEarning(),
            "Wrong base earn for corrector"
        );

        $date = (new \DateTime())->modify("-2 days");
        $copywritingOrder->setReadyForReviewAt($date);
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS],
            $articleEarning->getBaseEarning(),
            "Wrong reduced base earn for corrector"
        );

        $date = (new \DateTime())->modify("-1 days");
        $date->modify("-23 hour");
        $copywritingOrder->setReadyForReviewAt($date);
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::CORRECTOR_PRICE_PER_100_WORDS],
            $articleEarning->getBaseEarning(),
            "Wrong base earn for corrector"
        );

        $date = (new \DateTime())->modify("-3 days");
        $copywritingOrder->setReadyForReviewAt($date);
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS],
            $articleEarning->getBaseEarning(),
            "Wrong reduced base earn for corrector"
        );

        $copywritingOrder->setExpress(true);
        $copywritingOrder->setDeadline((new \DateTime())->modify("+1 day"));
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::CORRECTOR_EXPRESS_RATE],
            $articleEarning->getExpressEarning(),
            "Wrong express earn for corrector"
        );

        $copywritingOrder->setExpress(false);
        $copywritingOrder->setDeadline((new \DateTime())->modify("+1 day"));
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(0, $articleEarning->getExpressEarning(), "Wrong express earn for corrector");

        $copywritingOrder->setExpress(true);
        $copywritingOrder->setDeadline((new \DateTime())->modify("-1 day"));
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(0, $articleEarning->getExpressEarning(), "Wrong express earn for corrector");

        $copywritingArticle->setImagesByAdmin(["picture1"]);
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            $settings[Settings::WRITER_PRICE_PER_IMAGE],
            $articleEarning->getImagesEarning(),
            "Wrong image earn for corrector"
        );

        $copywritingArticle->setImagesByAdmin([]);
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(0, $articleEarning->getImagesEarning(), "Wrong image earn for corrector");

        $copywritingOrder
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime())
        ;
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(0, $articleEarning->getMalus(), "Wrong malus for corrector");

        $copywritingOrder
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt((new \DateTime())->modify("-1 day"))
        ;
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(0, $articleEarning->getMalus(), "Wrong malus for corrector");

        $copywritingOrder->setTakenAt((new \DateTime())->modify("-2 day"));
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            CopywritingAdminBonusCalculator::PENALTY_FOR_DELAY,
            $articleEarning->getMalus(),
            "Wrong malus for corrector"
        );

        $days = 50;
        $copywritingOrder->setTakenAt((new \DateTime())->modify("-$days day"));
        $articleEarning = $copywritingArticleProcessor->countCorrectorEarn($copywritingArticle);
        self::assertEquals(
            ($days - 1) * CopywritingAdminBonusCalculator::PENALTY_FOR_DELAY,
            $articleEarning->getMalus(),
            "Wrong malus for corrector"
        );
    }

    /**
     * @return array
     */
    public function countCorrectorEarnDataProvider()
    {
        return [
            [['title' => 'P#1-O#1: submitted_to_admin']],
        ];
    }
}
