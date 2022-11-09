<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Invoice
 *
 * @ORM\Table(name="invoice")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\InvoiceRepository")
 */
class Invoice
{

    use ExternalIdTrait;

    public const SERVICE_PAYPAL = 'paypal';

    public const SERVICE_WIRE_TRANSFER = 'wire_transfer';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="payer", referencedColumnName="id", nullable=false)
     **/
    private $user;

    /**
     * @var int
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     * @Assert\NotBlank()
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotNull()
     */
    private $vat; //TVA

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(message="File can't be empty")
     * @Assert\File(mimeTypes={ "application/pdf" })
     */
    private $file;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Number can't be empty")
     */
    private $number;

    /**
     * @var int
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $fees;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Choice({Invoice::SERVICE_PAYPAL, Invoice::SERVICE_WIRE_TRANSFER})
     */
    private $service;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $servicePaymentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime
     */
    private $createdAt;

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
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
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
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
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
    public function getTotal()
    {
        return $this->getAmount() + $this->getVat();
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * @param int $fees
     */
    public function setFees($fees)
    {
        $this->fees = $fees;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $service
     *
     * @return self
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return string
     */
    public function getServicePaymentId()
    {
        return $this->servicePaymentId;
    }

    /**
     * @param string $servicePaymentId
     *
     * @return self
     */
    public function setServicePaymentId($servicePaymentId)
    {
        $this->servicePaymentId = $servicePaymentId;

        return $this;
    }
}
