<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Repository\CopywritingOrderRepository;
use CoreBundle\Repository\SettingsRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class ChooseWriterService
{
    /**
     * @var EntityManager
     */
    protected $entityManager;


    /** @var string $avatarLocalPath */
    protected $avatarLocalPath;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * ChooseWriterService constructor.
     *
     * @param EntityManager       $entityManager
     * @param string              $avatarLocalPath
     * @param TranslatorInterface $translator
     */
    public function __construct($entityManager, $avatarLocalPath, $translator)
    {
        $this->entityManager = $entityManager;
        $this->avatarLocalPath = $avatarLocalPath;
        $this->translator = $translator;
    }

    /**
     * @param User $user
     *
     * @param string $language
     *
     * @return array
     */
    public function getWritersForChoose(User $user, $language = Language::EN)
    {
        $writersWithRatings = $this->entityManager->getRepository(User::class)->getLikes($user, null, $language);

        $this->calculatePercent($writersWithRatings);
        $this->setAvatars($writersWithRatings);
        $sortedWriters = $this->sortWriters($writersWithRatings);
        $this->setDeadlineForWriters($sortedWriters);

        $result = $this->getYouLikeWriters($sortedWriters);
        $result += $this->getWritersInCategory($sortedWriters);

        return $result;
    }

    /**
     * @param $writers
     * @return mixed
     */
    private function sortWriters($writers)
    {
        uasort($writers, array($this, 'compareCallback'));

        return $writers;
    }

    /**
     * @param $firstItem
     * @param $secondItem
     * @return int
     */
    private function compareCallback($firstItem, $secondItem)
    {
        if ($firstItem['like_percent'] === $secondItem['like_percent']) {
            return 0;
        }

        return ($firstItem['like_percent'] > $secondItem['like_percent']) ? -1 : 1;
    }

    /**
     * @param $writers
     */
    private function calculatePercent(&$writers)
    {
        foreach ($writers as &$writer) {
            $total = $writer['likes'] + $writer['dislikes']; //+ $writer['unknown'];

            if ($total !== 0) {
                $likesPercent = $writer['likes'] / $total;
            } else {
                $likesPercent = 0;
            }

            $writer['like_percent'] = $likesPercent;
        }
    }

    /**
     * @param $writers
     * @return array
     */
    private function getWritersInCategory($writers)
    {
        $topWriters = [];
        $bestWriters = [];

        foreach ($writers as $writer) {
            if ($writer['like_percent'] > 0.9 && $writer['like_percent'] <= 0.95) {
                $topWriters[] = $writer;
            }
            if ($writer['like_percent'] > 0.95) {
                $bestWriters[] = $writer;
            }
        }

        return [CopywritingProject::TOP_WRITERS => $topWriters, CopywritingProject::BEST_WRITERS => $bestWriters];
    }

    /**
     * @param $writers
     * @return array
     */
    private function getYouLikeWriters($writers)
    {
        $result = [];
        foreach ($writers as $writer) {
            if ($writer['youLikeWriters']) {
                $result[] = $writer;
            }
        }

        return [CopywritingProject::YOU_LIKE_WRITERS => $result];
    }

    /**
     * @param $writers
     */
    public function setDeadlineForWriters(&$writers)
    {

        $copywriters = array_column($writers, 0);

        $copywritersIds = [];
        foreach ($copywriters as $copywriter) {
            $copywritersIds[] = $copywriter->getId();
        }

        /** @var CopywritingOrderRepository $copywritingOrderRepository */
        $copywritingOrderRepository = $this->entityManager->getRepository(CopywritingOrder::class);

        $wordsOfOrders = $copywritingOrderRepository->getWordsOfOrders($copywritersIds);
        foreach ($writers as &$writer) {
            $copywriter = $writer[0];

            if (key_exists($copywriter->getId(), $wordsOfOrders)) {
                $deadline = $this->getDeadlineForWriter($copywriter->getWordsPerDay(), $wordsOfOrders[$copywriter->getId()]);
            } else {
                $deadline = $this->getDeadlineForWriter($copywriter->getWordsPerDay(), null);
            }

            $today = new \DateTime();
            $interval = $today->diff($deadline);
            $writer['deadline'] = $interval->d;
        }
    }

    /**
     * @param $wordsPerDay
     * @param null $wordsOfOrders
     * @return \DateTime
     */
    public function getDeadlineForWriter($wordsPerDay, $wordsOfOrders = null)
    {
        if (is_null($wordsPerDay)) {
            /** @var SettingsRepository $settingsRepository */
            $settingsRepository = $this->entityManager->getRepository(Settings::class);

            $wordsPerDay = $settingsRepository->getSettingValue(Settings::DEFAULT_WORDS_PER_DAY);
        }

        $wordsPerHour = $wordsPerDay / 24;
        $hoursToDeadline = 1;
        if (!is_null($wordsOfOrders)) {
            $hoursToDeadline = round($wordsOfOrders / $wordsPerHour);
        }

        $deadline = new \DateTime();
        $deadline->modify("+ $hoursToDeadline hour");

        return $deadline;
    }

    /**
     * @param $writers
     */
    private function setAvatars(&$writers)
    {
        foreach ($writers as &$writer) {
            $copywriter = $writer[0];
            if ($copywriter->getAvatar() !== null) {
                $writer['avatar'] = $this->avatarLocalPath . DIRECTORY_SEPARATOR . $copywriter->getAvatar();
            } else {
                $writer['avatar'] = '/img/avatar.png';
            }
        }
    }
}
