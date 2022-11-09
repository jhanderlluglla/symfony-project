<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\Test\LoadCopywritingProjectData;
use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test\LoadUserData;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use Tests\AbstractTest;
use UserBundle\Services\BonusCalculator\CopywritingWriterBonusCalculator;

class CopywritingWriterBonusCalculatorTest extends AbstractTest
{
    /**
     * @var CopywritingWriterBonusCalculator $bonusCalculator
     */
    private $bonusCalculator;

    protected function setUp()
    {
        parent::setUp();
        $fixtures = [
            LoadUserData::class,
            ORM\LoadSettings::class,
            LoadCopywritingProjectData::class,
        ];

        $this->loadFixtures($fixtures);
        $this->bonusCalculator = $this->container()->get("user.copywriting.writer_bonus_calculator");
    }

    /**
     * @dataProvider calculateProvider
     *
     * @param CopywritingOrder $copywritingOrder
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testCalculate($copywritingOrder)
    {
        /** @var CopywritingOrder $copywritingOrder */
        $copywritingOrder = $this->getObjectOf(CopywritingOrder::class, $copywritingOrder);

        /** @var CopywritingArticle $copywritingArticle */
        $copywritingArticle = $copywritingOrder->getArticle();

        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 0.00);
        self::assertEquals(CopywritingWriterBonusCalculator::MIN_BONUS_RATE, $totalRate, 'Case 0');

        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 50.00);
        self::assertEquals(CopywritingWriterBonusCalculator::MAX_BONUS_RATE, $totalRate, 'Case 1');

        $copywritingOrder->setTakenAt(new \DateTime());
        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 0.50);
        self::assertGreaterThanOrEqual(0.50, $totalRate, 'Case 2');

        $copywritingOrder
            ->setTakenAt((new \DateTime())->modify("-7 days"))
            ->setTimeInProgress(604800)
        ;
        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 0.80);
        self::assertEquals(CopywritingWriterBonusCalculator::MIN_BONUS_RATE, $totalRate, 'Case 3 - Minimal rate after 6 days');

        $copywritingOrder
            ->setTakenAt((new \DateTime())->modify("-5 days"))
            ->setTimeInProgress(172800)
            ->setDeclinedAt((new \DateTime())->modify("-3 days"))
        ;
        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 0.70);
        self::assertGreaterThanOrEqual(0.85, $totalRate, 'Case 5');

        $copywritingOrder
            ->setTakenAt((new \DateTime())->modify("-5 days"))
            ->setTimeInProgress(172800)
            ->setDeclinedAt((new \DateTime())->modify("-3 days"))
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
        ;
        $totalRate = $this->bonusCalculator->calculate($copywritingArticle, 0.70);
        self::assertGreaterThanOrEqual(0.7, $totalRate, 'Case 6');
    }

    /**
     * @dataProvider bonusProvider
     * @param $user
     */
    public function testCountBonusForRating($user)
    {
        /** @var User $user */
        $user = $this->getObjectOf(User::class, $user);
        $copywritingOrders = $user->getTakenOrders();
        $copywritingRatings = [];

        /** @var CopywritingOrder $copywritingOrder */
        foreach ($copywritingOrders as $copywritingOrder) {
            $copywritingRatings[] = $copywritingOrder->getRating();
        }

        $ratingBonus = $this->bonusCalculator->countBonusForRating($user);
        $ratingEarn = $this->findRatingElementByValue(95);
        self::assertEquals($ratingEarn, $ratingBonus);
    }

    /**
     * @param $ratingBonus
     * @return array
     */
    private function findRatingElementByValue($ratingBonus)
    {
        $element = array_filter(CopywritingWriterBonusCalculator::RATING_BONUSES, function ($element) use ($ratingBonus) {
            return $element['value'] === $ratingBonus;
        });
        return current($element)['earn'];
    }

    public function calculateProvider()
    {
        return [
            [['title' => 'P#1-O#1: submitted_to_admin']],
        ];
    }

    public function bonusProvider()
    {
        return[
            [['email' => 'writer-1@test.com']],
        ];
    }
}
