<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Constant\Country;
use CoreBundle\Entity\User;

class CalculatorVat
{
    const PAYPAL_FEES = 2.5; //2.5%

    /**
     * @param $amount
     * @param User $user
     * @return int
     */
    public function calculateVat($amount, $user)
    {
        return round($amount * $this->getVat($user) / 100, 2);
    }

    /**
     * @param User $user
     * @return int
     */
    public function getVat($user)
    {
        if ($user->getCountry() === "FR") {
            return Country::getFrenchTax();
        }
        if ($user->getVatNumber() === null && Country::isEuropeanCountry($user->getCountry())) {
            return Country::getFrenchTax();
        }
        return 0;
    }

    /**
     * @param float $amount
     * @return float|int
     */
    public function getPaypalFees($amount)
    {
        return round($amount * (self::PAYPAL_FEES / 100), 2);
    }
}
