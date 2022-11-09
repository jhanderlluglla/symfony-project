<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WithdrawRequest
 *
 * @ORM\Table(name="withdraw_request")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\WithdrawRequestRepository")
 */
class WithdrawRequest
{
    use ExternalIdTrait;

    const STATUS_WAITING = 'waiting';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

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
     * @Assert\GreaterThanOrEqual(100)
     * @Assert\NotBlank()
     * @Assert\LessThanOrEqual(99999999.99)
     */
    private $withdrawAmount;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $commissionPercent;

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
     *
     * @Assert\Choice(
     *      choices = {
     *          WithdrawRequest::STATUS_WAITING,
     *          WithdrawRequest::STATUS_REJECTED,
     *          WithdrawRequest::STATUS_ACCEPTED,
     *      }
     * )
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\File(mimeTypes={ "application/pdf" })
     */
    private $invoice;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression(
     *     "this.getSwift() && this.getIban() || this.getPaypal()",
     *     message="withdraw_request.choice_required"
     * )
     */
    private $paypal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression(
     *     "this.getPaypal() || this.getSwift()",
     *     message="withdraw_request.choice_required"
     * )
     */
    private $swift;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression(
     *     "this.getPaypal() || this.getIban()",
     *     message="withdraw_request.choice_required"
     * )
     */
    private $iban;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $reviewComment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reviewedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=255)
     *
     */
    private $companyName;

    public function __construct()
    {
        $this->status = self::STATUS_WAITING;
        $this->createdAt = new \DateTime();
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
    public function getWithdrawAmount()
    {
        return $this->withdrawAmount;
    }

    /**
     * @param float $withdrawAmount
     */
    public function setWithdrawAmount($withdrawAmount)
    {
        $this->withdrawAmount = $withdrawAmount;
    }

    /**
     * @return int
     */
    public function getCommissionPercent()
    {
        return $this->commissionPercent;
    }

    /**
     * @param int $commissionPercent
     */
    public function setCommissionPercent($commissionPercent)
    {
        $this->commissionPercent = $commissionPercent;
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
     * @return string
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param string $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return string
     */
    public function getPaypal()
    {
        return $this->paypal;
    }

    /**
     * @param string $paypal
     */
    public function setPaypal($paypal)
    {
        $this->paypal = $paypal;
    }

    /**
     * @return string
     */
    public function getSwift()
    {
        return $this->swift;
    }

    /**
     * @param string $swift
     */
    public function setSwift($swift)
    {
        $this->swift = $swift;
    }

    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @param string $iban
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
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

    /**
     * @return float|int
     */
    public function getAmountWithCommission()
    {
        return $this->withdrawAmount - $this->withdrawAmount * $this->commissionPercent / 100;
    }

    /**
     * @return string
     */
    public function getReviewComment()
    {
        return $this->reviewComment;
    }

    /**
     * @param string $reviewComment
     */
    public function setReviewComment($reviewComment)
    {
        $this->reviewComment = $reviewComment;
    }

    /**
     * @return \DateTime
     */
    public function getReviewedAt()
    {
        return $this->reviewedAt;
    }

    /**
     * @param \DateTime $reviewedAt
     */
    public function setReviewedAt($reviewedAt)
    {
        $this->reviewedAt = $reviewedAt;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    public function getType()
    {
        return $this->getSwift() && $this->getIban() ? 'bank' : 'paypal';
    }

    /**
     * @Assert\IsTrue(message="withdraw_request.not_blank")
     */
    public function isCompanyName()
    {
        return $this->getCompanyName() || $this->getPaypal();
    }
}

