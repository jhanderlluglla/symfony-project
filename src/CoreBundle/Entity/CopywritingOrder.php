<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * CopywritingOrder
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CopywritingOrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class CopywritingOrder extends AbstractEntityTransaction
{
    use ExternalIdTrait;

    const TRANSITION_TAKE_TO_WORK = 'take_to_work';
    const TRANSITION_SUBMIT_TO_ADMIN = 'submit_to_admin';
    const TRANSITION_SUBMIT_TO_WEBMASTER = 'submit_to_webmaster';
    const TRANSITION_DECLINE_TRANSITION = 'decline';
    const TRANSITION_COMPLETE_TRANSITION = 'complete';
    const TRANSITION_IMPOSSIBLE = 'impossible';

    const STATUS_WAITING = 'waiting';
    const STATUS_PROGRESS = 'progress';
    const STATUS_SUBMITTED_TO_ADMIN = 'submitted_to_admin';
    const STATUS_SUBMITTED_TO_WEBMASTER = 'submitted_to_webmaster';
    const STATUS_DECLINED = 'declined';
    const STATUS_COMPLETED = 'completed';
    const STATUS_IMPOSSIBLE = 'impossible';

    public const TRANSACTION_TAG_REFUND = 'copywriting_order_refund';
    public const TRANSACTION_TAG_DECLINE = 'copywriting_order_decline';
    public const TRANSACTION_TAG_EXPRESS_REFUND = 'copywriting_order_express_refund';
    public const TRANSACTION_TAG_REWARD = 'copywriting_order_reward';
    public const TRANSACTION_TAG_BUY = 'copywriting_order_buy';
    public const TRANSACTION_TAG_IMAGE_CASHBACK = 'copywriting_order_image_cashback';
    public const TRANSACTION_TAG_FAVORITE_CASHBACK = 'copywriting_order_favorite_cashback';
    public const TRANSACTION_TAG_DELETE = 'copywriting_order_delete';
    public const TRANSACTION_TAG_EDIT = 'copywriting_order_edit';

    public const TRANSACTION_DETAIL_REDACTION_PRICE = 'redactionPrice';

    const MAX_DAYS = 6;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var CopywritingProject $project
     *
     * @ORM\ManyToOne(targetEntity="CopywritingProject", cascade={"persist"}, inversedBy="orders")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $project;

    /**
     * @var User $customer
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="orderedProjects")
     **/
    private $customer;

    /**
     * @var User $copywriter
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="takenOrders")
     * @ORM\JoinColumn(onDelete="SET NULL")
     **/
    private $copywriter;

    /**
     * @var ExchangeProposition $exchangeProposition
     * OLD: prop_id
     *
     * @ORM\OneToOne(targetEntity="ExchangeProposition", cascade={"persist", "remove"}, inversedBy="copywritingOrders")
     * @ORM\JoinColumn(name="exchange_proposition_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    private $exchangeProposition;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=500)
     * @Assert\Length(max="500")
     * @Groups({"template"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Length(max="3000")
     * @Groups({"template"})
     */
    private $instructions;

    /**
     * @var array
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $links = [];

    /**
     * @var ArrayCollection $keywords
     *
     * @ORM\OneToMany(targetEntity="CopywritingKeyword", mappedBy="order", orphanRemoval=true, cascade={"persist", "remove"})
     * @Assert\Expression(
     *     "this.isValidKeywords()",
     *     message="You must provide range for keywords"
     * )
     * @Groups({"template"})
     */
    private $keywords;

    /**
     * @var CopywritingArticleRating $keywords
     *
     * @ORM\OneToOne(targetEntity="CopywritingArticleRating", mappedBy="order", cascade={"persist", "remove"})
     */
    private $rating;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @Assert\Range(min="1")
     * @Groups({"template"})
     */
    private $wordsNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="images_number", type="integer", nullable=true, options={"unsigned":true})
     * @Groups({"template"})
     */
    private $imagesNumber;

    /**
     * @var ArrayCollection $images
     *
     * @ORM\OneToMany(targetEntity="CopywritingImage", mappedBy="order", orphanRemoval=true, cascade={"persist", "remove"})
     * @Groups({"template"})
     */
    private $images;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $metaTitle = true;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $metaDescription = true;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $headerOneSet = true;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Expression(
     *     "value <= this.getHeaderTwoEnd() or !this.getHeaderTwoEnd()",
     *     message="Wrong range for H2"
     * )
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $headerTwoStart;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $headerTwoEnd;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Expression(
     *     "value <= this.getHeaderThreeEnd() or !this.getHeaderThreeEnd()",
     *     message="Wrong range for H3"
     * )
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $headerThreeStart;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $headerThreeEnd;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $boldText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $italicText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $quotedText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $ulTag;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Expression(
     *     "value <= this.getKeywordsPerArticleTo() or !this.getKeywordsPerArticleTo()",
     *     message="Wrong range for keywords"
     * )
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $keywordsPerArticleFrom;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $keywordsPerArticleTo;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $keywordInMetaTitle = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $keywordInHeaderOne = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $keywordInHeaderTwo = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"template"})
     */
    private $keywordInHeaderThree = false;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Expression(
     *     "value <= this.getImagesPerArticleTo() or !this.getImagesPerArticleTo()",
     *     message="Wrong range for images"
     * )
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $imagesPerArticleFrom;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
     * @Groups({"template"})
     */
    private $imagesPerArticleTo;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $viewed = false;

    /**
     * @var boolean
     * TODO: define usage, refactor or delete
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $optimized = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices = {
     *          CopywritingOrder::STATUS_WAITING,
     *          CopywritingOrder::STATUS_PROGRESS,
     *          CopywritingOrder::STATUS_SUBMITTED_TO_WEBMASTER,
     *          CopywritingOrder::STATUS_DECLINED,
     *          CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN,
     *          CopywritingOrder::STATUS_COMPLETED,
     *      }
     * )
     */
    private $status = self::STATUS_WAITING;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $takenAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $readyForReviewAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $approvedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $declinedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $launchedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $completedAt;

    /**
     * @var CopywritingArticle $article
     *
     * @ORM\OneToOne(targetEntity="CopywritingArticle", mappedBy="order", cascade={"persist", "remove"})
     */
    private $article;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $express = false;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $expressBonus;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $writerExpressBonus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deadline;

    /**
     * @var WaitingOrder $waitingOrder
     *
     * @ORM\OneToOne(targetEntity="WaitingOrder", mappedBy="copywritingOrder", cascade={"persist", "remove"})
     */
    private $waitingOrder;

    /**
     * @var User $approvedBy
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $approvedBy;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $timeInProgress = 0;

    /**
     * CopywritingOrder constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->createdAt = new \DateTime();
        $this->keywords = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->express = false;
    }

    /**
     * @return bool
     */
    public function isValidKeywords()
    {
        return count($this->keywords) === 0 || $this->getKeywordsPerArticleFrom();
    }

    /**
     * @param ExecutionContextInterface $context
     * @param $payload
     * @Assert\Callback
     */
    public function validateAmount(ExecutionContextInterface $context, $payload)
    {
        if ($this->getAmount() > $this->getCustomer()->getBalance()) {
            $context->buildViolation('validation.amount')->setTranslationDomain('copywriting')->addViolation();
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
     * @return CopywritingProject
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param CopywritingProject $project
     * @return $this
     */
    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return User
     */
    public function getCopywriter()
    {
        return $this->copywriter;
    }

    /**
     * @param User $copywriter
     * @return $this
     */
    public function setCopywriter($copywriter)
    {
        $this->copywriter = $copywriter;

        if ($this->getExchangeProposition() && $this->getExchangeProposition()->getJob()) {
            $this->getExchangeProposition()->getJob()->setAffectedToUser($copywriter);
        }

        return $this;
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
     * @return CopywritingOrder
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return ExchangeProposition
     */
    public function getExchangeProposition()
    {
        return $this->exchangeProposition;
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     *
     * @return CopywritingOrder
     */
    public function setExchangeProposition($exchangeProposition)
    {
        $this->exchangeProposition = $exchangeProposition;

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
     * @return CopywritingOrder
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     *
     * @return CopywritingOrder
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param array $links
     *
     * @return CopywritingOrder
     */
    public function setLinks($links)
    {
        $this->links = $links;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param ArrayCollection|null $keywords
     *
     * @return CopywritingOrder
     */
    public function setKeywords($keywords)
    {
        if($keywords) {

            $this->keywords->clear();

            foreach ($keywords as $keyword){
                $this->addKeywords($keyword);
            }
        }

        return $this;
    }

    /**
     * @param CopywritingKeyword $keyword
     *
     * @return CopywritingOrder
     */
    public function addKeywords($keyword)
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);

            $keyword->setOrder($this);
        }

        return $this;
    }

    /**
     * @param CopywritingKeyword $keyword
     *
     * @return CopywritingOrder
     */
    public function removeKeywords($keyword)
    {
        if ($this->keywords->contains($keyword)) {
            $this->keywords->removeElement($keyword);
        }

        return $this;
    }

    /**
     * @return CopywritingArticleRating
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param CopywritingArticleRating $rating
     */
    public function setRating($rating)
    {
        if ($rating) {
            $rating->setOrder($this);
        }

        $this->rating = $rating;
    }

    /**
     * @return int
     */
    public function getWordsNumber()
    {
        return $this->wordsNumber;
    }

    /**
     * @param int $wordsNumber
     *
     * @return CopywritingOrder
     */
    public function setWordsNumber($wordsNumber)
    {
        $this->wordsNumber = $wordsNumber;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param ArrayCollection $images
     *
     * @return CopywritingOrder
     */
    public function setImages($images)
    {
        foreach ($images as $image){
            $this->addImages($image);
        }

        return $this;
    }

    /**
     * @param CopywritingImage $image
     *
     * @return CopywritingOrder
     */
    public function addImages($image)
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);

            $image->setOrder($this);
        }

        return $this;
    }

    /**
     * @param CopywritingImage $image
     *
     * @return CopywritingOrder
     */
    public function removeImages($image)
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param bool $metaTitle
     *
     * @return CopywritingOrder
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param bool $metaDescription
     *
     * @return CopywritingOrder
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHeaderOneSet()
    {
        return $this->headerOneSet;
    }

    /**
     * @param bool $headerOneSet
     *
     * @return CopywritingOrder
     */
    public function setHeaderOneSet($headerOneSet)
    {
        $this->headerOneSet = $headerOneSet;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeaderTwoStart()
    {
        return $this->headerTwoStart;
    }

    /**
     * @param int $headerTwoStart
     *
     * @return CopywritingOrder
     */
    public function setHeaderTwoStart($headerTwoStart)
    {
        $this->headerTwoStart = $headerTwoStart;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeaderTwoEnd()
    {
        return $this->headerTwoEnd;
    }

    /**
     * @param int $headerTwoEnd
     *
     * @return CopywritingOrder
     */
    public function setHeaderTwoEnd($headerTwoEnd)
    {
        $this->headerTwoEnd = $headerTwoEnd;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeaderThreeStart()
    {
        return $this->headerThreeStart;
    }

    /**
     * @param int $headerThreeStart
     *
     * @return CopywritingOrder
     */
    public function setHeaderThreeStart($headerThreeStart)
    {
        $this->headerThreeStart = $headerThreeStart;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeaderThreeEnd()
    {
        return $this->headerThreeEnd;
    }

    /**
     * @param int $headerThreeEnd
     *
     * @return CopywritingOrder
     */
    public function setHeaderThreeEnd($headerThreeEnd)
    {
        $this->headerThreeEnd = $headerThreeEnd;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBoldText()
    {
        return $this->boldText;
    }

    /**
     * @param bool $boldText
     *
     * @return CopywritingOrder
     */
    public function setBoldText($boldText)
    {
        $this->boldText = $boldText;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isUlTag()
    {
        return $this->ulTag;
    }

    /**
     * @param bool $ulTag
     *
     * @return CopywritingOrder
     */
    public function setUlTag($ulTag)
    {
        $this->ulTag = $ulTag;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isItalicText()
    {
        return $this->italicText;
    }

    /**
     * @param bool|null $italicText
     *
     * @return CopywritingOrder
     */
    public function setItalicText($italicText)
    {
        $this->italicText = $italicText;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isQuotedText()
    {
        return $this->quotedText;
    }

    /**
     * @param bool|null $quotedText
     *
     * @return CopywritingOrder
     */
    public function setQuotedText($quotedText)
    {
        $this->quotedText = $quotedText;

        return $this;
    }

    /**
     * @return int
     */
    public function getKeywordsPerArticleFrom()
    {
        return $this->keywordsPerArticleFrom;
    }

    /**
     * @param int $keywordsPerArticleFrom
     *
     * @return CopywritingOrder
     */
    public function setKeywordsPerArticleFrom($keywordsPerArticleFrom)
    {
        $this->keywordsPerArticleFrom = $keywordsPerArticleFrom;

        return $this;
    }

    /**
     * @return int
     */
    public function getKeywordsPerArticleTo()
    {
        return $this->keywordsPerArticleTo;
    }

    /**
     * @param int $keywordsPerArticleTo
     *
     * @return CopywritingOrder
     */
    public function setKeywordsPerArticleTo($keywordsPerArticleTo)
    {
        $this->keywordsPerArticleTo = $keywordsPerArticleTo;

        return $this;
    }

    /**
     * @return bool
     */
    public function isKeywordInMetaTitle()
    {
        return $this->keywordInMetaTitle;
    }

    /**
     * @param bool $keywordInMetaTitle
     *
     * @return CopywritingOrder
     */
    public function setKeywordInMetaTitle($keywordInMetaTitle)
    {
        $this->keywordInMetaTitle = $keywordInMetaTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function isKeywordInHeaderOne()
    {
        return $this->keywordInHeaderOne;
    }

    /**
     * @param bool $keywordInHeaderOne
     *
     * @return CopywritingOrder
     */
    public function setKeywordInHeaderOne($keywordInHeaderOne)
    {
        $this->keywordInHeaderOne = $keywordInHeaderOne;

        return $this;
    }

    /**
     * @return bool
     */
    public function isKeywordInHeaderTwo()
    {
        return $this->keywordInHeaderTwo;
    }

    /**
     * @param bool $keywordInHeaderTwo
     *
     * @return CopywritingOrder
     */
    public function setKeywordInHeaderTwo($keywordInHeaderTwo)
    {
        $this->keywordInHeaderTwo = $keywordInHeaderTwo;

        return $this;
    }

    /**
     * @return bool
     */
    public function isKeywordInHeaderThree()
    {
        return $this->keywordInHeaderThree;
    }

    /**
     * @param bool $keywordInHeaderThree
     *
     * @return CopywritingOrder
     */
    public function setKeywordInHeaderThree($keywordInHeaderThree)
    {
        $this->keywordInHeaderThree = $keywordInHeaderThree;

        return $this;
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
     *
     * @return CopywritingOrder
     */
    public function setAmount($amount)
    {
        $this->amount = round($amount, 2);

        return $this;
    }

    /**
     * @return int
     */
    public function getImagesPerArticleFrom()
    {
        return $this->imagesPerArticleFrom;
    }

    /**
     * @param int $imagesPerArticleFrom
     *
     * @return CopywritingOrder
     */
    public function setImagesPerArticleFrom($imagesPerArticleFrom)
    {
        $this->imagesPerArticleFrom = $imagesPerArticleFrom;

        return $this;
    }

    /**
     * @return int
     */
    public function getImagesPerArticleTo()
    {
        return $this->imagesPerArticleTo;
    }

    /**
     * @param int $imagesPerArticleTo
     *
     * @return CopywritingOrder
     */
    public function setImagesPerArticleTo($imagesPerArticleTo)
    {
        $this->imagesPerArticleTo = $imagesPerArticleTo;

        return $this;
    }

    /**
     * @return bool
     */
    public function isViewed()
    {
        return $this->viewed;
    }

    /**
     * @param bool $viewed
     *
     * @return CopywritingOrder
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOptimized()
    {
        return $this->optimized;
    }

    /**
     * @param bool $optimized
     *
     * @return CopywritingOrder
     */
    public function setOptimized($optimized)
    {
        $this->optimized = $optimized;

        return $this;
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
     *
     * @return CopywritingOrder
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     *
     * @return CopywritingOrder
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTakenAt()
    {
        return $this->takenAt;
    }

    /**
     * @param \DateTime $takenAt
     *
     * @return CopywritingOrder
     */
    public function setTakenAt($takenAt)
    {
        $this->takenAt = $takenAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReadyForReviewAt()
    {
        return $this->readyForReviewAt;
    }

    /**
     * @param \DateTime $readyForReviewAt
     */
    public function setReadyForReviewAt($readyForReviewAt)
    {
        $this->readyForReviewAt = $readyForReviewAt;
    }

    /**
     * @return \DateTime
     */
    public function getApprovedAt()
    {
        return $this->approvedAt;
    }

    /**
     * @param \DateTime $approvedAt
     *
     * @return CopywritingOrder
     */
    public function setApprovedAt($approvedAt)
    {
        $this->approvedAt = $approvedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeclinedAt()
    {
        return $this->declinedAt;
    }

    /**
     * @param \DateTime $declinedAt
     *
     * @return CopywritingOrder
     */
    public function setDeclinedAt($declinedAt)
    {
        $this->declinedAt = $declinedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLaunchedAt()
    {
        return $this->launchedAt;
    }

    /**
     * @param \DateTime $launchedAt
     *
     * @return CopywritingOrder
     */
    public function setLaunchedAt($launchedAt)
    {
        $this->launchedAt = $launchedAt;

        return $this;
    }

    /**
     * @return CopywritingArticle
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param CopywritingArticle $article
     *
     * @return CopywritingOrder
     */
    public function setArticle(CopywritingArticle $article)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    /**
     * @param \DateTime $completedAt
     *
     * @return CopywritingOrder
     */
    public function setCompletedAt(\DateTime $completedAt)
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpress()
    {
        return $this->express;
    }

    /**
     * @param bool $express
     * @return $this
     */
    public function setExpress($express)
    {
        $this->express = $express;

        return $this;
    }

    /**
     * @return float
     */
    public function getExpressBonus()
    {
        return $this->expressBonus;
    }

    /**
     * @param float $expressBonus
     * @return $this
     */
    public function setExpressBonus($expressBonus)
    {
        $this->expressBonus = $expressBonus;

        return $this;
    }

    /**
     * @return float
     */
    public function getWriterExpressBonus()
    {
        return $this->writerExpressBonus;
    }

    /**
     * @param float $writerExpressBonus
     */
    public function setWriterExpressBonus($writerExpressBonus)
    {
        $this->writerExpressBonus = $writerExpressBonus;
    }

    /**
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * @param \DateTime $deadline
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
    }

    public function delayDeadline()
    {
        $newDeadline = new \DateTime();

        $this->deadline = $newDeadline->add(new \DateInterval('P1D'));

        return $this;
    }

    /**
     * @return bool
     */
    public function isWaiting()
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * @param string $tag
     *
     * @return bool|int
     */
    public function getTagRangeEnd($tag)
    {
        switch ($tag) {
            case 'h2':
                return $this->getHeaderTwoEnd();
            case 'h3':
                return $this->getHeaderThreeEnd();
            case 'img':
                return $this->getImagesPerArticleToWithFeatured();
            default:
                return false;
        }
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public function isTagRange($tag)
    {
        return $this->getTagRangeStart($tag) || $this->getTagRangeEnd($tag);
    }

    /**
     * @param $tag
     *
     * @return bool|int
     */
    public function getTagRangeStart($tag)
    {
        switch ($tag) {
            case 'h2':
                return $this->getHeaderTwoStart();
            case 'h3':
                return $this->getHeaderThreeStart();
            case 'img':
                return $this->getImagesPerArticleFromWithFeatured();
            default:
                return false;
        }
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public function isKeywordInTagRequired($tag)
    {
        switch ($tag) {
            case 'h1':
                return $this->isHeaderOneSet() && $this->isKeywordInHeaderOne();
            case 'h2':
                return ($this->getHeaderTwoStart() || $this->getHeaderTwoEnd()) && $this->isKeywordInHeaderTwo();
            case 'h3':
                return ($this->getHeaderThreeStart() || $this->getHeaderThreeEnd()) && $this->isKeywordInHeaderThree();
            default:
                return false;
        }
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public function isTagRequired($tag)
    {
        switch ($tag) {
            case 'h1':
                return $this->isHeaderOneSet();
            case 'h2':
                return $this->getHeaderTwoStart() || $this->getHeaderTwoEnd();
            case 'h3':
                return $this->getHeaderThreeStart() || $this->getHeaderThreeEnd();
            case 'strong':
                return $this->isBoldText();
            case 'em':
                return $this->isItalicText();
            case 'blockquote':
                return $this->isQuotedText();
            case 'ul':
                return $this->isUlTag();
            case 'img':
                return $this->getImagesPerArticleFromWithFeatured() > 0 || $this->getImagesPerArticleToWithFeatured() > 0;
            default:
                return false;
        }
    }

    /**
     * @return int
     */
    public function getImagesNumber()
    {
        return $this->imagesNumber;
    }

    /**
     * @param int $imagesNumber
     *
     * @return CopywritingOrder
     */
    public function setImagesNumber($imagesNumber)
    {
        $this->imagesNumber = $imagesNumber;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDaysAtWork()
    {
        if (in_array($this->status, [self::STATUS_DECLINED, self::STATUS_PROGRESS])) {
            return $this->getTakenAt()->diff(new \DateTime)->days;
        }
    }

    /**
     * @return bool
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_PROGRESS;
    }

    /**
     * @return bool
     */
    public function isDeclined()
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * @return bool
     */
    public function isSubmittedToAdmin()
    {
        return $this->status === self::STATUS_SUBMITTED_TO_ADMIN;
    }

    public function isDelayed()
    {
        return $this->createdAt->diff($this->deadline)->days > 0;
    }

    public function __clone() {

        if (!$this->id) {

            $keywords = new ArrayCollection();
            /** @var CopywritingKeyword $keyword */
            foreach ($this->keywords as $keyword) {
                $keywordClone = clone $keyword;
                $keywordClone->setOrder($this);
                $keywords->add($keywordClone);
            }
            $this->keywords = $keywords;

            $images = new ArrayCollection();
            /** @var CopywritingImage $image */
            foreach ($this->images as $image) {
                $imageClone = clone $image;
                $imageClone->setOrder($this);
                $images->add($imageClone);
            }
            $this->images = $images;
        }
    }

    /**
     * @return WaitingOrder
     */
    public function getWaitingOrder()
    {
        return $this->waitingOrder;
    }

    /**
     * @param WaitingOrder $waitingOrder
     */
    public function setWaitingOrder($waitingOrder)
    {
        $this->waitingOrder = $waitingOrder;
    }

    /**
     * @return \DateTime|null
     *
     * @throws \Exception
     */
    public function getWriterDeadline()
    {
        return (new \DateTime())->modify("+" . self::MAX_DAYS . " days -".$this->getTimeInProgress().' seconds');
    }

    /**
     * @return \DateTime|null
     */
    public function getInProgressAt()
    {
        if (is_null($this->takenAt)) {
            return null;
        }

        return $this->declinedAt > $this->takenAt ? $this->declinedAt : $this->takenAt;
    }

    /**
     * @return integer
     */
    public function getLateDays()
    {
        return (int) ($this->getTimeInProgress() / 86400);
    }

    /**
     * @return int
     */
    public function getImagesPerArticleFromWithFeatured()
    {
        return
            $this->exchangeProposition && $this->exchangeProposition->getExchangeSite()->hasPlugin() ?
                $this->getImagesPerArticleFrom() - 1 :
                $this->getImagesPerArticleFrom();
    }

    /**
     * @return int
     */
    public function getImagesPerArticleToWithFeatured()
    {
        return
            $this->exchangeProposition && $this->exchangeProposition->getExchangeSite()->hasPlugin() ?
                $this->getImagesPerArticleTo() - 1 :
                $this->getImagesPerArticleTo();
    }

    /**
     * @return User
     */
    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    /**
     * @param null $time
     *
     * @return int|null
     */
    public function calculateTimeInProgress($time = null)
    {
        $dateStart = $this->getDeclinedAt() ?? $this->getTakenAt();

        return ($time ?? time()) - $dateStart->getTimestamp();
    }

    /**
     * @param User $approvedBy
     */
    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }

    /**
     * @return int
     */
    public function getTimeInProgress(): int
    {
        if ($this->getStatus() === self::STATUS_PROGRESS) {
            $currentTime = time() - $this->getInProgressAt()->getTimestamp();
        } else {
            $currentTime = 0;
        }

        return $this->timeInProgress + $currentTime;
    }

    /**
     * @param int $seconds
     *
     * @return CopywritingOrder
     */
    public function setTimeInProgress(int $seconds): CopywritingOrder
    {
        $this->timeInProgress = $seconds;

        return $this;
    }

    /**
     * @param int $seconds
     *
     * @return CopywritingOrder
     */
    public function addTimeInProgress(int $seconds): CopywritingOrder
    {
        $this->timeInProgress += $seconds;

        return $this;
    }
}
