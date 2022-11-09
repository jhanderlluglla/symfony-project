<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserPaymentData
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\UserPaymentDataRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="user_type_unq", columns={"user_id", "type"})
 *     }
 * )
 */
class UserPaymentData
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
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="paymentData")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(choices={"paypal", "swift", "iban", "company"});
     * @Assert\NotBlank()
     *
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     */
    private $value;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return UserPaymentData
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return UserPaymentData
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return UserPaymentData
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
