<?php

namespace UserBundle\Services\BonusCalculator;

use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Job;
use CoreBundle\Repository\DirectoryBacklinksRepository;
use CoreBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManager;
use CoreBundle\Entity\User;

/**
 * Class NetlinkingWriterBonusCalculator
 *
 * @package UserBundle\Services\BonusCalculator
 */
class NetlinkingWriterBonusCalculator
{

    const MIN_BONUS = 0.45;
    const MAX_BONUS = 1;

    const LIKES_BONUSES = [
        ['value' => 60, 'earn' => -0.20],
        ['value' => 65, 'earn' => -0.15],
        ['value' => 70, 'earn' => -0.10],
        ['value' => 75, 'earn' => -0.05],
        ['value' => 90, 'earn' => 0.15],
        ['value' => 85, 'earn' => 0.10],
        ['value' => 80, 'earn' => 0.05],
    ];

    const MONEYS_BONUSES = [
        ['value' => 400, 'earn' => 0.20],
        ['value' => 300, 'earn' => 0.15],
        ['value' => 200, 'earn' => 0.10],
        ['value' => 100, 'earn' => 0.05],
    ];

    const BACKLINKS_BONUSES = [
        ['value' => 75, 'earn' => -0.20],
        ['value' => 80, 'earn' => -0.15],
        ['value' => 85, 'earn' => -0.05],
        ['value' => 90, 'earn' => 0.10],
        ['value' => 85, 'earn' => 0.05],
    ];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * CopywritingBonusCalculator constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param User $copywriter
     * @param $compensation
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function calculate($copywriter, $compensation)
    {
        if(is_null($copywriter)){
            return $compensation;
        }

        $bonusByLikes = $this->getBonusByLikes($copywriter);

        $bonusByMoney = $this->getBonusByMoney($copywriter);

        $bonusByBacklinks = $this->getBonusByBacklinks($copywriter);

        $compensationWithBonus = $compensation + $bonusByLikes + $bonusByBacklinks + $bonusByMoney;

        if($compensationWithBonus > self::MAX_BONUS) $compensationWithBonus = self::MAX_BONUS;
        if($compensationWithBonus < self::MIN_BONUS) $compensationWithBonus = self::MIN_BONUS;
        return $compensationWithBonus;
    }

    /**
     * @param $copywriter
     * @return float|int
     */
    public function getBonusByLikes($copywriter)
    {
        $stats = $this->entityManager->getRepository(Job::class)->getLikes($copywriter);

        if($stats['likes'] + $stats['dislikes'] > 0) {
            $percentLikes = $stats['likes'] / ($stats['likes'] + $stats['dislikes']) * 100;

            return $this->calculateBonus(self::LIKES_BONUSES, $percentLikes);
        }
        return 0;
    }

    /**
     * @param $ids
     * @return array
     */
    public function usersBonusByLikes($ids)
    {
        $stats = $this->entityManager->getRepository(Job::class)->usersLikes($ids);
        $userBonus = [];
        foreach ($stats as $bonus) {
            if($bonus['likes'] + $bonus['dislikes'] > 0) {
                $percentLikes = $bonus['likes'] / ($bonus['likes'] + $bonus['dislikes']) * 100;
                $userBonus[$bonus['id']] = $this->calculateBonus(self::LIKES_BONUSES, $percentLikes);
            }

        }
        $bonusByLikes = [];
        foreach ($ids as $userId) {
            $bonusByLikes[$userId] = 0;
            if (array_key_exists($userId, $userBonus)) {
                $bonusByLikes[$userId] = $userBonus[$userId];
            }
        }

        return $bonusByLikes;
    }

    /**
     * @param $copywriter
     * @return float|int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBonusByMoney($copywriter)
    {
        $lastMonthEarningsCopywriting = $this->entityManager->getRepository(User::class)->getCopywriterEarningsForMonth($copywriter);

        /** @var JobRepository $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);

        $today = new \DateTime();
        $lastMonthEarningsDirectory = $jobRepository->getCopywriterEarningsByMonth($copywriter, $today->modify('-1 month'));
        $sumEarningsLastMonth = $lastMonthEarningsCopywriting + $lastMonthEarningsDirectory;

        return $this->calculateBonus(self::MONEYS_BONUSES, $sumEarningsLastMonth);
    }

    /**
     * @param User $copywriter
     * @return float|int
     */
    public function getBonusByBacklinks(User $copywriter)
    {
        if (!$copywriter->isWriterNetlinking()) {

            return 0;
        }
        /** @var DirectoryBacklinksRepository $backlinksRepository */
        $backlinksRepository = $this->entityManager->getRepository(DirectoryBacklinks::class);

        $backlinksFound = $backlinksRepository->getCount($copywriter, DirectoryBacklinks::STATUS_FOUND);
        $backlinksNotFound = $backlinksRepository->getCount($copywriter, DirectoryBacklinks::STATUS_NOT_FOUND);

        if($backlinksFound + $backlinksNotFound > 0) {
            $percentFoundBacklinks = $backlinksFound / ($backlinksFound + $backlinksNotFound) * 100;

            return $this->calculateBonus(self::BACKLINKS_BONUSES, $percentFoundBacklinks);
        }
        return 0;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function usersBonusByBacklinks($ids)
    {
        /** @var DirectoryBacklinksRepository $backlinksRepository */
        $backlinksRepository = $this->entityManager->getRepository(DirectoryBacklinks::class);

        $BackLinks = $backlinksRepository->usersCount($ids);
        $usersBackLinks = [];
        foreach ($BackLinks as $data) {
            if($data['found'] + $data['not_found']> 0) {
                $percentFoundBacklinks = $data['found'] / ($data['found'] + $data['not_found']) * 100;
                $usersBackLinks[$data['id']] = $this->calculateBonus(self::BACKLINKS_BONUSES, $percentFoundBacklinks);
            }
        }

        $backlinksBonus = [];
        foreach ($ids as $userId) {
            $backlinksBonus[$userId] = 0;
            if (array_key_exists($userId, $usersBackLinks)) {
                $backlinksBonus[$userId] = $usersBackLinks[$userId];
            }
        }

        return $backlinksBonus;
    }

    /**
     * @param array $bonuses
     * @param int $value
     * @return int|float
     */
    private function calculateBonus($bonuses, $value)
    {
        foreach ($bonuses as $bonus){
            if($bonus['earn'] < 0 && $value < $bonus['value']){
                return $bonus['earn'];
            }
            if($bonus['earn'] > 0 && $value > $bonus['value']){
                return $bonus['earn'];
            }
        }

        return 0;
    }
}