<?php

namespace UserBundle\Services\BonusCalculator;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;

class CopywritingAdminBonusCalculator
{

    const PENALTY_FOR_DELAY = 0.01;

    /**
     * @param CopywritingArticle $article
     * @return float|int|mixed
     */
    public function calculate($article)
    {
        $late = $article->getOrder()->getLateDays();

        return $late > 1 ? ($late - 1) * self::PENALTY_FOR_DELAY : 0;
    }
}
