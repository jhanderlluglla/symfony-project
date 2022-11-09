<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ReplenishRequest
 *
 * @ORM\Table(name="replenish_request")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ReplenishRequestRepository")
 */
class ReplenishRequest
{
    const STATUS_WAITING = 'waiting';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    const PAYPAL_TYPE = 'paypal';
    const WIRE_TRANSFER_TYPE = 'wire_transfer';

    const PAYPAL_MINIMUM = 20;
    const WIRE_TRANSFER_MINIMUM = 100;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     * @Assert\Expression(
     *     "this.validatePaypal(value)",
     *     message="replenish.insufficient.paypal"
     * )
     * @Assert\Expression(
     *     "this.validateWireTransfer(value)",
     *     message="replenish.insufficient.wire_transfer"
     * )
     * @Assert\NotBlank()
     * @Assert\LessThanOrEqual(99999999.99)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, options={"default":0})
     */
    private $vat;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $paypalFees;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $paymentId;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices = {
     *          ReplenishRequest::PAYPAL_TYPE,
     *          ReplenishRequest::WIRE_TRANSFER_TYPE,
     *      }
     * )
     */
    private $requestType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices = {
     *          ReplenishRequest::STATUS_WAITING,
     *          ReplenishRequest::STATUS_REJECTED,
     *          ReplenishRequest::STATUS_ACCEPTED,
     *      }
     * )
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     *
     */
    private $createdAt;

    /**
     * ReplenishRequest constructor.
     */
    public function __construct()
    {
        $this->status = self::STATUS_WAITING;
        $this->createdAt = new \DateTime();
    }

    /**
     * @param int $value
     * @return bool
     */
    public function validatePaypal($value)
    {
        if ($this->getRequestType() === self::PAYPAL_TYPE && $value < self::PAYPAL_MINIMUM) {
            return false;
        }
        return true;
    }

    /**
     * @param int $value
     * @return bool
     */
    public function validateWireTransfer($value)
    {
        if ($this->getRequestType() === self::WIRE_TRANSFER_TYPE && $value < self::WIRE_TRANSFER_MINIMUM) {
            return false;
        }
        return true;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    }

    /**
     * @return float
     */
    public function getPaypalFees()
    {
        return $this->paypalFees;
    }

    /**
     * @param float $paypalFees
     */
    public function setPaypalFees($paypalFees)
    {
        $this->paypalFees = $paypalFees;
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
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
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @param string $requestType
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
