<?php

namespace UserBundle\Services;

use CoreBundle\Entity\Job;
use CoreBundle\Entity\User;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\CopywritingOrder;
use UserBundle\Services\BonusCalculator\CopywritingWriterBonusCalculator;
use UserBundle\Services\BonusCalculator\NetlinkingWriterBonusCalculator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WriterService
 *
 * @package UserBundle\Services
 */
class UserProjectService extends AbstractUserService
{
    /**
     * @var CopywritingWriterBonusCalculator $writerBonusCalculator
     */
    protected $writerBonusCalculator;

    /**
     * @var NetlinkingWriterBonusCalculator $netlinkingWriterBonusCalculator
     */
    protected $netlinkingWriterBonusCalculator;

    /**
     * UserService constructor.
     *
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param CopywritingWriterBonusCalculator       $writerBonusCalculator
     * @param NetlinkingWriterBonusCalculator $netlinkingWriterBonusCalculator
     */
    public function __construct($entityManager, $translator, $writerBonusCalculator, $netlinkingWriterBonusCalculator)
    {
        parent::__construct($entityManager, $translator);
        $this->writerBonusCalculator = $writerBonusCalculator;
        $this->netlinkingWriterBonusCalculator = $netlinkingWriterBonusCalculator;
    }

    /**
     *
     * @return array
     */
    public function getCountProjects($users)
    {

        $jobRepository = $this->entityManager->getRepository(Job::class);
        $userRepository = $this->entityManager->getRepository(User::class);
        $netLinkingRepository = $this->entityManager->getRepository(NetlinkingProject::class);
        $copywritingRepository = $this->entityManager->getRepository(CopywritingOrder::class);
        $userStatisticData = [];

        if (count($users) > 0) {
            $ids = [];
            foreach ($users as $user) {
                $ids[] = $user->getId();
            }

            $netlinkingProjects = $netLinkingRepository->getNetLinkingProjects($ids);
            $copywritingProjects = $copywritingRepository->getOrdersCountByIds($ids);
            $totalSubmissions = $jobRepository->countTotalSubmissions($ids);
            $textLikesDislikes = $userRepository->getProjectLikes($ids);
            $earningBonus = $this->writerBonusCalculator->usersCountBonusForEarning($ids);
            $copywritingRating = $this->writerBonusCalculator->usersCountBonusForRating($ids);
            $netlinkingLikes = $this->netlinkingWriterBonusCalculator->usersBonusByLikes($ids);
            $netlinkingBacklinks = $this->netlinkingWriterBonusCalculator->usersBonusByBacklinks($ids);

            foreach ($ids as $userId) {

                $userStatisticData[$userId]['total'] = 0;
                $userStatisticData[$userId]['success'] = 0;
                $userStatisticData[$userId]['netLinking_current'] = 0;
                $userStatisticData[$userId]['netLinking_completed'] = 0;
                $userStatisticData[$userId]['copywriting_current'] = 0;
                $userStatisticData[$userId]['copywriting_completed'] = 0;
                if (array_key_exists($userId, $totalSubmissions)) {
                    $userStatisticData[$userId]['total'] = $totalSubmissions[$userId]['total'];
                    $userStatisticData[$userId]['success'] = $totalSubmissions[$userId]['success'];
                }
                if (array_key_exists($userId, $netlinkingProjects)) {
                    $userStatisticData[$userId]['netLinking_current'] = $netlinkingProjects[$userId]['current'];
                    $userStatisticData[$userId]['netLinking_completed'] = $netlinkingProjects[$userId]['completed'];
                }
                if (array_key_exists($userId, $copywritingProjects)) {
                    $userStatisticData[$userId]['copywriting_current'] = $copywritingProjects[$userId]['current'];
                    $userStatisticData[$userId]['copywriting_completed'] = $copywritingProjects[$userId]['completed'];
                }

                $userStatisticData[$userId]['text_likes'] = $textLikesDislikes[$userId]['text_likes'];
                $userStatisticData[$userId]['text_dislikes'] = $textLikesDislikes[$userId]['text_dislikes'];
                $userStatisticData[$userId]['text_likes_dif'] = $textLikesDislikes[$userId]['text_likes_dif'];
                $userStatisticData[$userId]['text_dislikes_dif'] = $textLikesDislikes[$userId]['text_dislikes_dif'];
                $userStatisticData[$userId]['earning_bonus'] = $earningBonus[$userId];
                $userStatisticData[$userId]['netlinking_likes'] = $netlinkingLikes[$userId];
                $userStatisticData[$userId]['copywriting_rating'] = $copywritingRating[$userId];
                $userStatisticData[$userId]['netlinking_backlinks'] = $netlinkingBacklinks[$userId];
            }
        }

        return $userStatisticData;
    }

}