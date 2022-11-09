<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use CoreBundle\Model\TransactionDescriptionModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\TransactionRepository")
 */
class Transaction
{
    use ExternalIdTrait;

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
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="transactions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500)
     */
    private $description;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $details;

    /**
     * @var float
     *
     * @ORM\Column(name="debit", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $debit;

    /**
     * @var float
     *
     * @ORM\Column(name="credit", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $credit;

    /**
     * @var float
     *
     * @ORM\Column(name="solder", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $solder;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Transaction
     *
     * @ORM\ManyToOne(targetEntity="CoreBundle\Entity\Transaction")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $parent;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\TransactionTag")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $tags;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     *
     */
    private $marks;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $hidden = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime;
        $this->tags = new ArrayCollection();
    }

    /**
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
     * @param mixed $user
     *
     * @return Transaction
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Transaction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param null $name
     *
     * @return mixed|array
     */
    public function getDetails($name = null)
    {
        if ($name) {
            return isset($this->details[$name]) ? $this->details[$name] : null;
        } else {
            return $this->details;
        }
    }

    /**
     * @param array $details
     *
     * @return Transaction
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasDetails($name)
    {
        return $this->details === null ? false : key_exists($name, $this->details);
    }

    /**
     * @return float
     */
    public function getDebit()
    {
        return $this->debit;
    }

    /**
     * @param float $debit
     *
     * @return Transaction
     */
    public function setDebit($debit)
    {
        $this->debit = $debit;

        return $this;
    }

    /**
     * @param float $debit
     *
     * @return Transaction
     */
    public function debit($debit)
    {
        $this->getUser()->incBalance($debit);
        $this
            ->setDebit($debit)
            ->setCredit(0)
            ->setSolder($this->getUser()->getBalance())
        ;

        return $this;
    }

    /**
     * @return float
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param float $credit
     *
     * @return Transaction
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @param float $credit
     *
     * @return Transaction
     */
    public function credit($credit)
    {
        $this->getUser()->decBalance($credit);
        $this
            ->setCredit($credit)
            ->setDebit(0)
            ->setSolder($this->getUser()->getBalance())
        ;

        return $this;
    }

    /**
     * @return float
     */
    public function getSolder()
    {
        return $this->solder;
    }

    /**
     * @param float $solder
     *
     * @return Transaction
     */
    public function setSolder($solder)
    {
        $this->solder = $solder;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Transaction
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return TransactionTag[]|PersistentCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param TransactionTag[] $tags
     *
     * @return Transaction
     */
    public function setTags($tags)
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    /**
     * @param TransactionTag $tag
     *
     * @return Transaction
     */
    public function addTag(TransactionTag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    /**
     * @param TransactionTag $tag
     *
     * @return $this
     */
    public function removeTag(TransactionTag $tag)
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    /**
     * @return Transaction
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Transaction $parent
     *
     * @return Transaction
     */
    public function setParent(Transaction $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     *
     * @return Transaction
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return array
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * @param array $marks
     *
     * @return self
     */
    public function setMarks($marks)
    {
        $this->marks = $marks;

        return $this;
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return self
     */
    public function setMark($key, $value)
    {
        $this->marks[$key] = $value;

        return $this;
    }

    /**
     * @param TransactionDescriptionModel $transactionDescriptionModel
     *
     * @return Transaction
     */
    public function loadDescriptionModel(TransactionDescriptionModel $transactionDescriptionModel)
    {
        $this->setDescription($transactionDescriptionModel->getIdTranslate());
        $this->setMarks($transactionDescriptionModel->getMarks());

        return $this;
    }
}
