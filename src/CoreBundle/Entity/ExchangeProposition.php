<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ExchangeProposition
 *
 * @ORM\Table(name="exchange_proposition")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ExchangePropositionRepository")
 */
class ExchangeProposition extends AbstractEntityTransaction
{

    use ExternalIdTrait;

    public const TRANSITION_ASSIGNED_WRITER = 'assigned_writer';
    public const TRANSITION_IMPOSSIBLE = 'impossible';
    public const TRANSITION_EXPIRE = 'expire';
    public const TRANSITION_ACCEPT = 'accept';
    public const TRANSITION_PUBLISH = 'publish';
    public const TRANSITION_CHANGE = 'change';
    public const TRANSITION_ACCEPT_CHANGES = 'accept_changes';
    public const TRANSITION_REFUSE = 'refuse';


    public const STATUS_AWAITING_WEBMASTER = 'awaiting_webmaster'; // old: 0
    public const STATUS_AWAITING_WRITER = 'awaiting_writer'; // old: 10
    public const STATUS_IN_PROGRESS = 'in_progress'; // old: 30
    public const STATUS_CHANGED = 'changed'; // old: 50
    public const STATUS_REFUSED = 'refused'; // old: 100
    public const STATUS_EXPIRED = 'expired'; // old: 110
    public const STATUS_IMPOSSIBLE = 'impossible'; // old: 111
    public const STATUS_ACCEPTED = 'accepted'; // old: 200
    public const STATUS_PUBLISHED = 'published'; // old: 201

    public const TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE = 'ep_exchangeSitePrice';
    public const TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY = 'ep_webmasterAdditionalPay';
    public const TRANSACTION_DETAIL_COMMISSION = 'ep_commission';
    public const TRANSACTION_DETAIL_COMMISSION_PERCENT = 'ep_commissionPercent';

    public const TRANSACTION_TAG_BUY = 'exchange_proposition_buy';
    public const TRANSACTION_TAG_REWARD = 'exchange_proposition_reward';
    public const TRANSACTION_TAG_REFUND = 'exchange_proposition_refund';

    const MODIFICATION_STATUS_0 = 0;
    const MODIFICATION_STATUS_1 = 1; // Request for modification has been sent
    const MODIFICATION_STATUS_2 = 2; // Webmaster-seller (partner) has changed the article
    const MODIFICATION_STATUS_3 = 3; // Modification request was rejected
    const MODIFICATION_STATUS_4 = 4; // Article updated on the site, but requires confirmation

    const OWN_TYPE = "own";
    const EXTERNAL_TYPE = "external";

    public const ARTICLE_AUTHOR_WRITER = 'writer';
    public const ARTICLE_AUTHOR_BUYER = 'buyer';
    public const ARTICLE_AUTHOR_WEBMASTER = 'webmaster';

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
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="exchangeProposition")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    private $user;

    /**
     * @var ExchangeSite $exchangeSite
     *
     * @ORM\ManyToOne(targetEntity="ExchangeSite", cascade={"persist"}, inversedBy="exchangeProposition")
     * @ORM\JoinColumn(name="exchange_site_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $exchangeSite;

    /**
     * @var Job $job
     *
     * @ORM\OneToOne(targetEntity="Job", cascade={"persist", "remove"}, inversedBy="exchangeProposition")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $job;

    /** @var string
     *
     * @ORM\Column(type="string", options={"default":ExchangeProposition::EXTERNAL_TYPE})
     **/
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"default": 1})
     */
    private $redac;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="string", options={"default": ExchangeProposition::STATUS_AWAITING_WRITER})
     *
     * @Assert\Choice({
     *     ExchangeProposition::STATUS_AWAITING_WEBMASTER, ExchangeProposition::STATUS_AWAITING_WRITER,
     *     ExchangeProposition::STATUS_IN_PROGRESS, ExchangeProposition::STATUS_CHANGED,
     *     ExchangeProposition::STATUS_IMPOSSIBLE, ExchangeProposition::STATUS_EXPIRED,
     *     ExchangeProposition::STATUS_ACCEPTED, ExchangeProposition::STATUS_REFUSED,
     *     ExchangeProposition::STATUS_PUBLISHED})
     */
    private $status = ExchangeProposition::STATUS_AWAITING_WRITER;

    /**
     * @var string
     *
     * @ORM\Column(name="page_publish", type="string", length=512, nullable=true)
     */
    private $pagePublish;

    /**
     * @var string
     *
     * @ORM\Column(name="words_number", type="integer", options={"unsigned":true})
     *
     * @Assert\NotBlank()
     */
    private $wordsNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="links_number", type="integer", options={"unsigned":true})
     *
     * @Assert\NotBlank()
     */
    private $linksNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="images_number", type="integer", options={"unsigned":true})
     *
     * @Assert\NotBlank()
     */
    private $imagesNumber;

    /**
     * @var float
     *
     * @ORM\Column(name="credits", type="decimal", precision=10, scale=2, options={"unsigned":true})
     */
    private $credits;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $price;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accepted_at", type="datetime", nullable=true)
     */
    private $acceptedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedAt;

    /**
     * @var integer
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $isSelf;

    /**
     * @var string
     *
     * @ORM\Column(name="instructions", type="text", nullable=true)
     */
    private $instructions;

    /**
     * @var array
     *
     * @ORM\Column(name="check_links", type="json_array")
     */
    private $checkLinks;

    /**
     * @var CopywritingOrder $copywritingOrders
     *
     * @ORM\OneToOne(targetEntity="CopywritingOrder", mappedBy="exchangeProposition", cascade={"persist", "remove"})
     */
    private $copywritingOrders;

    /**
     * @var string
     *
     * @ORM\Column(name="document_link", type="string", length=512, nullable=true)
     */
    private $documentLink;

    /**
     * @var string
     *
     * @ORM\Column(name="document_image", type="string", length=512, nullable=true)
     */
    private $documentImage;

    /**
     * @var integer
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $viewed;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", nullable=true)
     */
    private $comments;

    /**
     * @var string
     *
     * @ORM\Column(name="plaintext", type="text", nullable=true)
     */
    private $plaintext;

    /**
     * @var integer
     *
     * @ORM\Column(name="modification_status", type="integer", nullable=true)
     */
    private $modificationStatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="modification_close", type="integer", nullable=true)
     */
    private $modificationClose;

    /**
     * @var integer
     *
     * @ORM\Column(name="modification_refuse_comment", type="text", nullable=true)
     */
    private $modificationRefuseComment;

    /**
     * @var integer
     *
     * @ORM\Column(name="modification_comment", type="text", nullable=true)
     */
    private $modificationComment;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate_stars", type="integer", nullable=true)
     */
    private $rateStars;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate_comment", type="text", nullable=true)
     */
    private $rateComment;

    /**
     * @var integer
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $impossibleComment;

    /**
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Transaction", cascade={"persist"})
     */
    private $buyerTransaction;

    /**
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Transaction", cascade={"persist"})
     */
    private $sellerTransaction;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Choice({
     *     ExchangeProposition::ARTICLE_AUTHOR_BUYER,
     *     ExchangeProposition::ARTICLE_AUTHOR_WRITER,
     *     ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER
     * })
     * @Assert\NotNull()
     */
    private $articleAuthorType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Choice(callback={"CoreBundle\Entity\ExchangeSite", "getAvailableResponseCode"})
     */
    private $publicationResponseCode;

    /**
     * ExchangeProposition constructor.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->createdAt = new \DateTime;
        $this->updatedAt = new \DateTime;

        $this->modificationStatus = self::MODIFICATION_STATUS_0;
        $this->wordsNumber = 0;
        $this->linksNumber = 0;
        $this->imagesNumber = 0;
        $this->checkLinks = [];

        $this->type = self::EXTERNAL_TYPE;
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
     * @param User $user
     *
     * @return ExchangeProposition
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
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
     * @return ExchangeProposition
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return ExchangeSite
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param ExchangeSite $exchangeSite
     *
     * @return ExchangeProposition
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;

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
     * @return ExchangeProposition
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return ExchangeProposition
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getRedac()
    {
        return $this->redac;
    }

    /**
     * @param int $redac
     *
     * @return ExchangeProposition
     */
    public function setRedac($redac)
    {
        $this->redac = $redac;

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
     * @return ExchangeProposition
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getPagePublish()
    {
        return $this->pagePublish;
    }

    /**
     * @param string $pagePublish
     *
     * @return ExchangeProposition
     */
    public function setPagePublish($pagePublish)
    {
        $this->pagePublish = $pagePublish;

        return $this;
    }

    /**
     * @return string
     */
    public function getWordsNumber()
    {
        return $this->wordsNumber;
    }

    /**
     * @param string $wordsNumber
     *
     * @return ExchangeProposition
     */
    public function setWordsNumber($wordsNumber)
    {
        $this->wordsNumber = $wordsNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinksNumber()
    {
        return $this->linksNumber;
    }

    /**
     * @param string $linksNumber
     *
     * @return ExchangeProposition
     */
    public function setLinksNumber($linksNumber)
    {
        $this->linksNumber = $linksNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getImagesNumber()
    {
        return $this->imagesNumber;
    }

    /**
     * @param string $imagesNumber
     *
     * @return ExchangeProposition
     */
    public function setImagesNumber($imagesNumber)
    {
        $this->imagesNumber = $imagesNumber;

        return $this;
    }

    /**
     * @return float
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * @param float $credits
     *
     * @return ExchangeProposition
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return ExchangeProposition
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return CopywritingOrder
     */
    public function getCopywritingOrders()
    {
        return $this->copywritingOrders;
    }

    /**
     * @param CopywritingOrder $copywritingOrders
     *
     * @return ExchangeProposition
     */
    public function setCopywritingOrders($copywritingOrders)
    {
        $this->copywritingOrders = $copywritingOrders;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAcceptedAt()
    {
        return $this->acceptedAt;
    }

    /**
     * @param \DateTime $acceptedAt
     *
     * @return ExchangeProposition
     */
    public function setAcceptedAt($acceptedAt)
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTime $publishedAt
     * @return ExchangeProposition
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getisSelf()
    {
        return $this->isSelf;
    }

    /**
     * @param int $isSelf
     *
     * @return ExchangeProposition
     */
    public function setIsSelf($isSelf)
    {
        $this->isSelf = $isSelf;

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
     * @return ExchangeProposition
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return array
     */
    public function getCheckLinks()
    {
        return $this->checkLinks;
    }

    /**
     * @param array $checkLinks
     *
     * @return ExchangeProposition
     */
    public function setCheckLinks($checkLinks)
    {
        $this->checkLinks = $checkLinks;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentLink()
    {
        return $this->documentLink;
    }

    /**
     * @param string $documentLink
     *
     * @return ExchangeProposition
     */
    public function setDocumentLink($documentLink)
    {
        $this->documentLink = $documentLink;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentImage()
    {
        return $this->documentImage;
    }

    /**
     * @param string $documentImage
     *
     * @return ExchangeProposition
     */
    public function setDocumentImage($documentImage)
    {
        $this->documentImage = $documentImage;

        return $this;
    }

    /**
     * @return int
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * @param int $viewed
     *
     * @return ExchangeProposition
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;

        return $this;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     *
     * @return ExchangeProposition
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlaintext()
    {
        return $this->plaintext;
    }

    /**
     * @param string $plaintext
     *
     * @return ExchangeProposition
     */
    public function setPlaintext($plaintext)
    {
        $this->plaintext = $plaintext;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationStatus()
    {
        return $this->modificationStatus;
    }

    /**
     * @param int $modificationStatus
     *
     * @return ExchangeProposition
     */
    public function setModificationStatus($modificationStatus)
    {
        $this->modificationStatus = $modificationStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationClose()
    {
        return $this->modificationClose;
    }

    /**
     * @param int $modificationClose
     *
     * @return ExchangeProposition
     */
    public function setModificationClose($modificationClose)
    {
        $this->modificationClose = $modificationClose;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationRefuseComment()
    {
        return $this->modificationRefuseComment;
    }

    /**
     * @param int $modificationRefuseComment
     *
     * @return ExchangeProposition
     */
    public function setModificationRefuseComment($modificationRefuseComment)
    {
        $this->modificationRefuseComment = $modificationRefuseComment;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationComment()
    {
        return $this->modificationComment;
    }

    /**
     * @param int $modificationComment
     *
     * @return ExchangeProposition
     */
    public function setModificationComment($modificationComment)
    {
        $this->modificationComment = $modificationComment;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateStars()
    {
        return $this->rateStars ? $this->rateStars:3;
    }

    /**
     * @param int $rateStars
     *
     * @return ExchangeProposition
     */
    public function setRateStars($rateStars)
    {
        $this->rateStars = $rateStars;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateComment()
    {
        return $this->rateComment;
    }

    /**
     * @param int $rateComment
     *
     * @return ExchangeProposition
     */
    public function setRateComment($rateComment)
    {
        $this->rateComment = $rateComment;

        return $this;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     * @return ExchangeProposition
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return int
     */
    public function getImpossibleComment()
    {
        return $this->impossibleComment;
    }

    /**
     * @param int $impossibleComment
     */
    public function setImpossibleComment($impossibleComment)
    {
        $this->impossibleComment = $impossibleComment;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function canSellerRead($user)
    {
        return $this->getExchangeSite()->getUser()->getId() == $user->getId() || $user->isSuperAdmin();
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function canBuyerRead($user)
    {
        return $this->getUser()->getId() == $user->getId();
    }

    /**
     * @return array
     */
    public function getCheckLinksUrls()
    {
        $result = [];

        if (!empty($this->documentLink)) {
            return is_array($this->getCheckLinks()) ? array_values($this->getCheckLinks()): [];
        }

        foreach ($this->getCheckLinks() as $link) {
            $result[] = trim($link['url'], "/");
        }

        return $result;
    }

    /**
     * todo: delete this method, use addTransaction
     *
     * @param Transaction $transaction
     *
     * @return $this
     */
    public function setBuyerTransaction(?Transaction $transaction)
    {
        $this->buyerTransaction = $transaction;
        $this->addTransaction($transaction);

        return $this;
    }

    /**
     * @return Transaction
     */
    public function getBuyerTransaction()
    {
        return $this->getTransactionsByTag(self::TRANSACTION_TAG_BUY)->last();
    }

    /**
     * todo: delete this method, use addTransaction
     *
     * @param Transaction $transaction
     *
     * @return $this
     */
    public function setSellerTransaction(?Transaction $transaction)
    {
        $this->sellerTransaction = $transaction;
        $this->addTransaction($transaction);

        return $this;
    }

    /**
     * @return Transaction
     */
    public function getSellerTransaction()
    {
        return $this->getTransactionsByTag(self::TRANSACTION_TAG_REWARD)->last();
    }

    /**
     * @param string $articleAuthorType
     *
     * @return $this
     */
    public function setArticleAuthorType($articleAuthorType)
    {
        $this->articleAuthorType = $articleAuthorType;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleAuthorType()
    {
        return $this->articleAuthorType;
    }

    /**
     * @return string
     */
    public function getPublicationResponseCode()
    {
        return $this->publicationResponseCode;
    }

    /**
     * @param string $publicationResponseCode
     *
     * @return ExchangeProposition
     */
    public function setPublicationResponseCode($publicationResponseCode)
    {
        $this->publicationResponseCode = $publicationResponseCode;

        return $this;
    }
}
