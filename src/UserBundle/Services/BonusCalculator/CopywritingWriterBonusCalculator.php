<?php

namespace UserBundle\Services\BonusCalculator;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\User;
use CoreBundle\Repository\JobRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;

/**
 * Class CopywritingWriterBonusCalculator
 *
 * @package UserBundle\Services\BonusCalculator
 */
class CopywritingWriterBonusCalculator
{
    const LATE_DAYS_FOR_MINIMAL_RATE = 7; // >=
    const LATE_DAYS_WITHOUT_ALL_BONUS = 6; // >=
    const LATE_DAYS_WITHOUT_RATING_BONUS = 5; // >=

    const MIN_BONUS_RATE = 0.5;
    const MAX_BONUS_RATE = 1;

    const MONEYS_BONUSES = [
        ['value' => 400, 'earn' => 0.20],
        ['value' => 300, 'earn' => 0.15],
        ['value' => 200, 'earn' => 0.10],
        ['value' => 100, 'earn' => 0.05],
    ];

    const RATING_BONUSES = [
        ['value' => 65, 'earn' => -0.20],
        ['value' => 70, 'earn' => -0.15],
        ['value' => 75, 'earn' => -0.10],
        ['value' => 80, 'earn' => -0.05],
        ['value' => 95, 'earn' => 0.15],
        ['value' => 90, 'earn' => 0.10],
        ['value' => 85, 'earn' => 0.05],
    ];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * CopywritingBonusCalculator constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->userRepository = $this->em->getRepository(User::class);
    }

    /**
     * @param CopywritingArticle $article
     * @param float $baseRate
     * @param float $totalBonusRate
     *
     * @return float|int|mixed
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function calculate($article, $baseRate, &$totalBonusRate = 0)
    {
        $order = $article->getOrder();
        $copywriter = $order->getCopywriter();

        $late = $order->getLateDays();

        $bonusForEarning = 0;

        if ($late >= self::LATE_DAYS_FOR_MINIMAL_RATE) {
            $totalBonusRate = self::MIN_BONUS_RATE - $baseRate;
            return self::MIN_BONUS_RATE;
        }

        if ($late < self::LATE_DAYS_WITHOUT_ALL_BONUS) {
            $bonusForEarning = $this->countBonusForEarning($copywriter);
        }

        $bonusForRating = $this->countBonusForRating($copywriter);

        if ($bonusForRating > 0 && $late >= self::LATE_DAYS_WITHOUT_RATING_BONUS) {
            $bonusForRating = 0;
        }

        $totalBonusRate = $bonusForEarning + $bonusForRating;

        $totalRate = $baseRate + $totalBonusRate;
        if ($totalRate < self::MIN_BONUS_RATE) {
            $totalRate = self::MIN_BONUS_RATE;
        }
        if ($totalRate > self::MAX_BONUS_RATE) {
            $totalRate = self::MAX_BONUS_RATE;
        }

        return $totalRate;
    }

    /**
     * @param User $copywriter
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countBonusForEarning(User $copywriter)
    {
        $lastMonthEarningsCopywriting = $this->userRepository->getCopywriterEarningsForMonth($copywriter);

        /** @var JobRepository $jobRepository */
        $jobRepository = $this->em->getRepository(Job::class);

        $monthAgo = (new \DateTime())->modify("-1 month");
        $lastMonthEarningsDirectory = $jobRepository->getCopywriterEarningsByMonth($copywriter, $monthAgo);
        $sumEarningsLastMonth = $lastMonthEarningsCopywriting + $lastMonthEarningsDirectory;

        return $this->calculateBonus(self::MONEYS_BONUSES, $sumEarningsLastMonth);
    }

    /**
     * @param  array $ids
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function usersCountBonusForEarning($ids)
    {
        $lastMonthEarningsCopywriting = $this->userRepository->usersCopywriterEarningsForMonth($ids);
        $userCopywriting = [];
        foreach ($lastMonthEarningsCopywriting as $copywriting) {
            $userCopywriting[$copywriting['id']] = $copywriting['earning_copywriting'];
        }
        /** @var JobRepository $jobRepository */
        $jobRepository = $this->em->getRepository(Job::class);

        $monthAgo = (new \DateTime())->modify("-1 month");
        $lastMonthEarningsDirectory = $jobRepository->usersCopywriterEarningsByMonth($ids, $monthAgo);
        $userDirectory = [];
        foreach ($lastMonthEarningsDirectory as $directory ) {
            $userDirectory[$directory['id']] = $directory['earning_directory'];
        }

        $earningBonus = [];
        foreach ($ids as $userId) {
            $earningBonus[$userId] = 0;
            if (array_key_exists($userId, $userCopywriting)) {
                $earningBonus[$userId] += $userCopywriting[$userId];
            }
            if (array_key_exists($userId, $userDirectory)) {
                $earningBonus[$userId] += $userDirectory[$userId];
            }
            if ($earningBonus[$userId] != 0) {
                $earningBonus[$userId] = $this->calculateBonus(self::MONEYS_BONUSES, $earningBonus[$userId]);
            }
        }

        return $earningBonus;
    }

    /**
     * @param User $copywriter
     * @return float|int|mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countBonusForRating($copywriter)
    {
        $copywriterRating = $this->userRepository->getAverageCopywriterRating($copywriter);

        if (is_null($copywriterRating)) {
            return 0;
        }
        return $this->calculateBonus(self::RATING_BONUSES, $copywriterRating);
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function usersCountBonusForRating($ids)
    {
        $userCopywriterRating = $this->userRepository->usersAverageCopywriterRating($ids);
        $copywriterRating = [];
        foreach ($ids as $userId) {
            $copywriterRating[$userId] = 0;
            if (array_key_exists($userId, $userCopywriterRating)) {
                $copywriterRating[$userId] = $this->calculateBonus(self::RATING_BONUSES, $userCopywriterRating[$userId]);
            }
        }

        return $copywriterRating;
    }

    /**
     * @param array $bonuses
     * @param int $value
     * @return int|float
     */
    private function calculateBonus($bonuses, $value)
    {
        foreach ($bonuses as $bonus) {
            if ($bonus['earn'] < 0 && $value < $bonus['value']) {
                return $bonus['earn'];
            }
            if ($bonus['earn'] > 0 && $value > $bonus['value']) {
                return $bonus['earn'];
            }
        }

        return 0;
    }
}
