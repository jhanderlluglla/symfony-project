<?php

namespace CoreBundle\Exceptions;

class NotEnoughMoneyDetailException extends NotEnoughMoneyException
{
    /** @var float */
    private $availableMoney;

    /** @var float */
    private $needMoney;

    /** @var float */
    private $missingMoney;

    public function __construct($availableMoney, $needMoney)
    {
        $this->availableMoney = $availableMoney;
        $this->needMoney = $needMoney;
        $this->missingMoney = ($needMoney - $availableMoney);
        parent::__construct(
            'Not enough money: available ' . $availableMoney . ', need ' . $needMoney . ', missing ' . $this->missingMoney
        );
    }

    /**
     * @return float
     */
    public function getAvailableMoney()
    {
        return $this->availableMoney;
    }

    /**
     * @return float
     */
    public function getNeedMoney()
    {
        return $this->needMoney;
    }

    /**
     * @return float
     */
    public function getMissingMoney()
    {
        return $this->missingMoney;
    }
}
