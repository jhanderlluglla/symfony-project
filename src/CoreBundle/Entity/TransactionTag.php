<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TransactionTag
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\TransactionTagRepository")
 */
class TransactionTag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public static function getAvailableTags()
    {
        return [
            CopywritingProject::TRANSACTION_TAG_PROJECT,

            CopywritingOrder::TRANSACTION_TAG_REFUND,
            CopywritingOrder::TRANSACTION_TAG_DECLINE,
            CopywritingOrder::TRANSACTION_TAG_EXPRESS_REFUND,
            CopywritingOrder::TRANSACTION_TAG_REWARD,
            CopywritingOrder::TRANSACTION_TAG_BUY,
            CopywritingOrder::TRANSACTION_TAG_IMAGE_CASHBACK,
            CopywritingOrder::TRANSACTION_TAG_FAVORITE_CASHBACK,
            CopywritingOrder::TRANSACTION_TAG_DELETE,
            CopywritingOrder::TRANSACTION_TAG_EDIT,

            ExchangeProposition::TRANSACTION_TAG_BUY,
            ExchangeProposition::TRANSACTION_TAG_REWARD,
            ExchangeProposition::TRANSACTION_TAG_REFUND,

            Job::TRANSACTION_TAG_REJECT,
            Job::TRANSACTION_TAG_BUY,
            Job::TRANSACTION_TAG_REWARD,
            Job::TRANSACTION_TAG_HOLD,
            Job::TRANSACTION_TAG_RETURN_HOLD,

            Affiliation::TRANSACTION_TAG,

            User::TRANSACTION_TAG_PAYOUT,
            User::TRANSACTION_TAG_REPLENISH,
            User::TRANSACTION_TAG_MODIFY_BALANCE ,
            User::TRANSACTION_TAG_WITHDRAW,
            User::TRANSACTION_TAG_WITHDRAW_REJECT,
        ];
    }
}
