<?php

namespace UserBundle\Services;

use CoreBundle\Entity\User;
use CoreBundle\Entity\Settings;

/**
 * Class WriterService
 *
 * @package UserBundle\Services
 */
class WriterService extends AbstractUserService
{

    /**
     * @param User $user
     *
     * @return float
     */
    public function getCompensation($user)
    {
        $spending = floatval($this->entityManager->getRepository(Settings::class)->getSettingValue('remuneration'));
        if(!is_null($user) && !empty($user->getSpending())){
            $spending = $user->getSpending();
        }

        return $spending;
    }

    /**
     * @param User $user
     *
     * @return float
     */
    public function getCopyWriterRate($user)
    {
        $writerTariffRedaction = $user->getCopyWriterRate();
        if (!$writerTariffRedaction) {
            $writerTariffRedaction = floatval($this->entityManager->getRepository(Settings::class)->getSettingValue('writer_price_100_words'));
        }

        return $writerTariffRedaction;
    }
}