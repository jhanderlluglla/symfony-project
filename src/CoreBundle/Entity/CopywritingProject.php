<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\Traits\ExternalIdTrait;
use CoreBundle\Entity\Traits\LanguageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * CopywritingProject
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CopywritingProjectRepository")
 * @ORM\Table(name="copywriting_project")
 * @ORM\HasLifecycleCallbacks()
 */
class CopywritingProject
{
    use ExternalIdTrait;

    use LanguageTrait;

    const NO_SELECTION = 'no_selection';
    const YOU_LIKE_WRITERS = 'you_like_writers';
    const TOP_WRITERS = 'top_writers';
    const BEST_WRITERS = 'best_writers';

    const WRITER_CATEGORIES = [self::NO_SELECTION, self::YOU_LIKE_WRITERS, self::TOP_WRITERS, self::BEST_WRITERS];

    const MAX_TITLE_LENGTH = 250;
    const MAX_DESCRIPTION_LENGTH = 2500;

    public const TRANSACTION_DETAIL_NUMBER_OF_ARTICLES = 'numberOfArticles';
    public const TRANSACTION_DETAIL_PAYMENT_FOR_META_DESCRIPTION = 'paymentForMetaDescription';
    public const TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION = 'priceForMetaDescription';
    public const TRANSACTION_DETAIL_REWARD_FOR_META_DESCRIPTION = 'rewardForMetaDescription';
    public const TRANSACTION_DETAIL_WRITER_BONUS = 'writerBonus';
    public const TRANSACTION_DETAIL_WRITER_MALUS = 'writerMalus';

    public const TRANSACTION_TAG_PROJECT = 'copywriting_project';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"template"})
     */
    private $id;

    /**
     * @var User $customer
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="orderedProjects")
     * @ORM\JoinColumn(onDelete="SET NULL")
     **/
    private $customer;

    /**
     * @var ArrayCollection $orders
     *
     * @ORM\OneToMany(targetEntity="CopywritingOrder", mappedBy="project", cascade={"persist", "remove"})
     * @Assert\Valid()
     * @Groups({"template"})
     */
    private $orders;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Assert\Length(max=CoreBundle\Entity\CopywritingProject::MAX_TITLE_LENGTH)
     * @Groups({"template"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Assert\Length(max=CoreBundle\Entity\CopywritingProject::MAX_DESCRIPTION_LENGTH)
     * @Groups({"template"})
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_template", type="boolean", nullable=true)
     */
    private $template = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recurrent", type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $recurrent;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Range(min="2", max="90")
     * @Groups({"template"})
     */
    private $recurrentPeriod;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression(
     *     "!value || this.getOrders().count() < value",
     *     message="copywriting_project.recurrent_total.higher_then_articles_number"
     * )
     * @Groups({"template"})
     */
    private $recurrentTotal;

    /**
     * @var string
     *
     * @ORM\Column(name="writer_category", type="string", nullable=true)
     * @Groups({"template"})
     */
    private $writerCategory;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @param ExecutionContextInterface $context
     * @param $payload
     * @Assert\Callback
     */
    public function validateOrdersAmount(ExecutionContextInterface $context, $payload)
    {
        if ($this->getAmount() > $this->customer->getBalance()) {
            $context->buildViolation('validation.amount')
                ->setTranslationDomain('copywriting')
                ->addViolation();
        }
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
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param User $customer
     *
     * @return CopywritingProject
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
        foreach ($this->orders as $order) {
            $order->setCustomer($customer);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param mixed $orders
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    /**
     * @param CopywritingOrder $order
     *
     * @return CopywritingProject
     */
    public function addOrder(CopywritingOrder $order)
    {
        $this->orders->add($order);
        $order->setProject($this);
        $order->setCustomer($this->customer);

        return $this;
    }

    /**
     * @param CopywritingOrder $order
     *
     * @return CopywritingProject
     */
    public function removeOrder(CopywritingOrder $order)
    {
        $this->orders->remove($order);
        $order->setProject(null);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return CopywritingProject
     */
    public function setTitle($title)
    {
        $this->title = $title;

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
     * @return CopywritingProject
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTemplate()
    {
        return $this->template;
    }

    /**
     * @param bool $template
     *
     * @return CopywritingProject
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
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
     * @Groups({"template"})
     * @return int
     *
     */
    public function getAmount()
    {
        $amount = 0;
        foreach ($this->orders as $order) {
            $amount += $order->getAmount();
        }

        return $amount;
    }

    /**
     * @return bool
     */
    public function isRecurrent()
    {
        return $this->recurrent;
    }

    /**
     * @param bool $recurrent
     */
    public function setRecurrent($recurrent)
    {
        $this->recurrent = $recurrent;
    }

    /**
     * @return int
     */
    public function getRecurrentPeriod()
    {
        return $this->recurrentPeriod;
    }

    /**
     * @param int $recurrentPeriod
     */
    public function setRecurrentPeriod($recurrentPeriod)
    {
        $this->recurrentPeriod = $recurrentPeriod;
    }

    /**
     * @return int
     */
    public function getRecurrentTotal()
    {
        return $this->recurrentTotal;
    }

    /**
     * @param int $recurrentTotal
     */
    public function setRecurrentTotal(int $recurrentTotal)
    {
        $this->recurrentTotal = $recurrentTotal;
    }

    /**
     * @return string
     */
    public function getWriterCategory()
    {
        return $this->writerCategory;
    }

    /**
     * @param string $writerCategory
     */
    public function setWriterCategory($writerCategory)
    {
        $this->writerCategory = $writerCategory;
    }
}
