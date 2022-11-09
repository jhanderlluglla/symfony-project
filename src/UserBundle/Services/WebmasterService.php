<?php

namespace UserBundle\Services;

use CoreBundle\Entity\User;
use CoreBundle\Entity\Settings;

/**
 * Class WebmasterService
 *
 * @package UserBundle\Services
 */
class WebmasterService extends AbstractUserService
{


    /**
     * @param User $user
     *
     * @return float
     */
    public function getCompensation($user)
    {
        return $this->getWebmasterTariff($user);
    }

    /**
     * @param User $user
     *
     * @return float
     */
    public function getWebmasterTariff($user)
    {
        $tariffWebmaster = $user->getSpending();
        if (empty($tariffWebmaster)) {
            $tariffWebmaster = floatval($this->entityManager->getRepository(Settings::class)->getSettingValue(Settings::TARIFF_WEB));
        }

        return $tariffWebmaster;
    }
}