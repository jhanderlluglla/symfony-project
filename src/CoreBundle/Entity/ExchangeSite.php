<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Interfaces\SiteUrlInterface;
use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * ExchangeSite
 *
 * @ORM\Table(name="exchange_site")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ExchangeSiteRepository")
 * @UniqueEntity(
 *     fields={"url"},
 *     repositoryMethod="constraintSiteDuplicate",
 *     message="exchange.duplicate_site"
 * )
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class ExchangeSite extends AbstractMetricsEntity implements StateInterface, SiteUrlInterface
{
    use ExternalIdTrait;

    const HIDE_URL_YES = 1;
    const HIDE_URL_NO = 0;

    const ACCEPT_EREF_YES = 1;
    const ACCEPT_EREF_NO = 0;

    const ACCEPT_WEB_YES = 1;
    const ACCEPT_WEB_NO = 0;

    const ACCEPT_SELF_YES = 1;
    const ACCEPT_SELF_NO = 0;

    const TRUSTED_WEBMASTER_YES = 1;
    const TRUSTED_WEBMASTER_NO = 0;

    const ACTION_WRITING_EREFERER = 'writing_ereferer';
    const ACTION_SUBMIT_ARTICLE = 'submit_your_article';
    const ACTION_WRITING_WEBMASTER = 'writing_webmaster';

    const WEBMASTER_ANCHOR_YES = 1;
    const WEBMASTER_ANCHOR_NO  = 0;

    const PRIVATE_SITE = 1;
    const NOT_PRIVATE_SITE = 0;

    const COPYWRITING_TYPE = 'copywriting';
    const EXCHANGE_TYPE = 'exchange';
    const UNIVERSAL_TYPE = 'universal';

    const UNOPTIMIZED = 'unoptimized';
    const SEMIOPTIMIZED = 'semioptimized';
    const OPTIMIZED = 'optimized';

    public const RESPONSE_CODE_INCORRECT_API_KEY = 'incorrect_api_key';
    public const RESPONSE_CODE_INVALID_JSON = 'invalid_json';
    public const RESPONSE_CODE_SUCCESS_CONNECTION = 'success_connection';
    public const RESPONSE_CODE_INCORRECT_POST_ID = 'incorrect_post_id';
    public const RESPONSE_CODE_INCORRECT_POST = 'incorrect_post';
    public const RESPONSE_CODE_POST_NOT_EDITABLE = 'post_not_editable';
    public const RESPONSE_CODE_ERROR_UPDATE_POST = 'error_update_post';
    public const RESPONSE_CODE_ERROR_IMPORT_IMAGE = 'error_import_image';
    public const RESPONSE_CODE_ERROR_IMPORT_THUMBNAIL = 'error_import_thumbnail';
    public const RESPONSE_CODE_PUBLISH_SUCCESS = 'publish_success';
    public const RESPONSE_CODE_IMPOSSIBLE = 'impossible'; // inner; when the request was not sent to the site
    public const RESPONSE_CODE_UNKNOWN_ERROR = 'unknown_error'; // inner; when the response code is unknown
    public const RESPONSE_CODE_PUBLISH_PENDING = 'publish_pending';

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
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="exchangeSite")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\DirectoriesList", mappedBy="exchangeSite", cascade={"persist"})
     */
    private $directoriesList;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     *
     * @Assert\NotBlank(groups={"copywriting", "Default"})
     * @Assert\Url(groups={"copywriting", "Default"})
     */
    private $url;

    /**
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="ExchangeSiteTtfCategory",
     *     mappedBy="exchangeSite",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $majesticTtfCategories;

    /**
     * @var integer
     *
     * @ORM\Column(name="hide_url", type="boolean", nullable=true)
     */
    private $hideUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="credits", type="integer", options={"unsigned":true})
     *
     * @Assert\Expression(
     *     "this.getMaximumCredits() >= value",
     *     message="exchange.credits.less_then_max_credits"
     * )
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\NotBlank()
     */
    private $credits = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="maximum_credits", type="integer", options={"unsigned":true, "default": 0})
     *
     * @Assert\NotBlank()
     */
    private $maximumCredits;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="accept_eref", type="boolean", nullable=true)
     *
     * @Assert\Expression(
     *     "this.checkFormOfWriting()",
     *     message="exchange.form_of_writing"
     * )
     */
    private $acceptEref;

    /**
     * @var integer
     *
     * @ORM\Column(name="accept_web", type="boolean", nullable=true)
     */
    private $acceptWeb;

    /**
     * @var string
     *
     * @ORM\Column(name="tags", type="string", nullable=true)
     *
     * @Assert\NotBlank
     */
    private $tags;

    /**
     * @var integer
     *
     * @ORM\Column(name="accept_self", type="boolean", nullable=true)
     */
    private $acceptSelf;

    /**
     * @var boolean
     *
     * @ORM\Column(name="nofollow_link", type="boolean", nullable=true)
     */
    private $nofollowLink = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sponsorised_article", type="boolean", nullable=true)
     */
    private $sponsorisedArticle = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="additional_external_link", type="boolean", nullable=true)
     */
    private $additionalExternalLink = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_additional_external_link", type="integer", nullable=true)
     *
     */
    private $countAdditionalExternalLink;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_words_number", type="integer", options={"unsigned":true}, nullable=true)
     *
     * @Assert\NotBlank
     * @Assert\GreaterThan(0)
     */
    private $minWordsNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_links_number", type="integer", options={"unsigned":true}, nullable=true)
     *
     * @Assert\NotBlank
     * @Assert\GreaterThan(0)
     */
    private $maxLinksNumber;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $metaTitle = true;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $metaDescription = true;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
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
     */
    private $headerTwoStart;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
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
     */
    private $headerThreeStart;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     * @Assert\Range(min="0")
     */
    private $headerThreeEnd;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $boldText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $italicText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $quotedText;

    /**
     * @var boolean|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ulTag;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_images_number", type="integer", options={"unsigned":true}, nullable=true)
     * @Assert\Expression(
     *     "value <= this.getMaxImagesNumber() or !this.getMaxImagesNumber()",
     *     message="Wrong range for images"
     * )
     * @Assert\Range(min="0")
     * @Assert\NotBlank
     */
    private $minImagesNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_images_number", type="integer", options={"unsigned":true}, nullable=true)
     *
     * @Assert\Range(min="0")
     * @Assert\NotBlank
     */
    private $maxImagesNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="publication_rules", type="text", nullable=true)
     */
    private $publicationRules;

    /**
     * @var integer
     *
     * @ORM\Column(name="trusted_webmaster", type="boolean", nullable=true)
     */
    private $trustedWebmaster;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="string", unique=true, length=255, nullable=true)
     */
    private $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="plugin_url", type="text", nullable=true)
     */
    private $pluginUrl;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $pluginStatus = false;

    /**
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="exchangeSites")
     * @ORM\JoinTable(name="exchange_site_category",
     *      joinColumns={@ORM\JoinColumn(name="exchange_site_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @Assert\Count(min=1, max=2)
     */
    private $categories;

    /**
     * WP categories
     *
     * @ORM\ManyToMany(targetEntity="Rubric", inversedBy="exchangeSites", cascade={"persist"})
     * @ORM\JoinTable(name="exchange_site_rubric",
     *      joinColumns={@ORM\JoinColumn(name="exchange_site_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="rubric_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     *
     */
    private $rubrics;

    /**
     * @var ArrayCollection $exchangeProposition
     *
     * @ORM\OneToMany(targetEntity="ExchangeProposition", mappedBy="exchangeSite", cascade={"persist", "remove"})
     */
    private $exchangeProposition;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice({"unoptimized", "semioptimized", "optimized"})
     */
    private $authorizedAnchor;

    /**
     * @var ArrayCollection $anchor
     *
     * @ORM\OneToMany(targetEntity="Anchor", mappedBy="exchangeSite", cascade={"persist", "remove"})
     */
    private $anchor;

    /**
     * @var integer
     *
     * @ORM\Column(name="webmaster_anchor", type="boolean", nullable=true)
     */
    private $webmasterAnchor;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default":ExchangeSite::EXCHANGE_TYPE}, nullable=true)
     *
     * @Assert\Choice(
     *      choices = {
     *          ExchangeSite::COPYWRITING_TYPE,
     *          ExchangeSite::EXCHANGE_TYPE,
     *          ExchangeSite::UNIVERSAL_TYPE
     *      }
     * )
     */
    private $siteType;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": 1})
     */
    private $autoPublish = true;

    /**
     * ExchangeSite constructor.
     */
    public function __construct()
    {
        $this->active = self::ACTIVE_YES;
        $this->hideUrl = self::HIDE_URL_NO;
        $this->acceptEref = self::ACCEPT_EREF_NO;
        $this->acceptWeb = self::ACCEPT_WEB_NO;
        $this->acceptSelf = self::ACCEPT_SELF_NO;
        $this->trustedWebmaster = self::TRUSTED_WEBMASTER_NO;
        $this->webmasterAnchor = self::WEBMASTER_ANCHOR_YES;
        $this->metaTitle = false;
        $this->metaDescription = false;
        $this->headerOneSet = false;
        $this->authorizedAnchor = self::UNOPTIMIZED;

        $this->createdAt = new \DateTime();

        $this->categories = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
        $this->exchangeProposition = new ArrayCollection();
        $this->directoriesList = new ArrayCollection();

        $this->majesticTtfCategories = new ArrayCollection();

        $this->maximumCredits = 0;
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
     * @return ExchangeSite
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHiddenUrl()
    {
        if ($this->hideUrl ) {

            return $this->hideUrl($this->url);
        }

        return $this->url;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->parseDomain($this->url);
    }

    /**
     * @param string $url
     *
     * @return ExchangeSite
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }


    /**
     * @param string $name
     * @return ExchangeSiteTtfCategory|null
     */
    public function getTtfCategory($name)
    {
        /** @var ExchangeSiteTtfCategory $majesticTtfCategory */
        foreach ($this->majesticTtfCategories as $majesticTtfCategory) {
            $category = $majesticTtfCategory->getCategory();
            if ($category->getName() === $name) {
                return $majesticTtfCategory;
            }
        }

        return null;
    }

    /**
     * @param ExchangeSiteTtfCategory $ttfCategory
     */
    public function addTtfCategory($ttfCategory)
    {
        if(!$this->majesticTtfCategories->contains($ttfCategory)) {
            $ttfCategory->setExchangeSite($this);
            $this->majesticTtfCategories->add($ttfCategory);
        }
    }

    /**
     * @return ExchangeSite
     */
    public function removeTtfCategories()
    {
        $this->majesticTtfCategories->clear();

        return $this;
    }

    /**
     * @return string
     */
    public function getHideUrl()
    {
        return $this->hideUrl == self::HIDE_URL_YES;
    }

    /**
     * @param string $hideUrl
     *
     * @return ExchangeSite
     */
    public function setHideUrl($hideUrl)
    {
        $this->hideUrl = $hideUrl;

        return $this;
    }

    /**
     * @return int
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * @param int $credits
     *
     * @return ExchangeSite
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @param float $credits
     *
     * @return ExchangeSite
     */
    public function incCredits($credits)
    {
        $this->credits += $credits;

        return $this;
    }

    /**
     * @param float $credits
     *
     * @return ExchangeSite
     */
    public function decCredits($credits)
    {
        $this->credits -= $credits;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaximumCredits()
    {
        return $this->maximumCredits;
    }

    /**
     * @param int $maximumCredits
     *
     * @return ExchangeSite
     */
    public function setMaximumCredits($maximumCredits)
    {
        $this->maximumCredits = $maximumCredits;

        return $this;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param int $active
     *
     * @return ExchangeSite
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptEref()
    {
        return $this->acceptEref == self::ACCEPT_EREF_YES;
    }

    /**
     * @param int $acceptEref
     *
     * @return ExchangeSite
     */
    public function setAcceptEref($acceptEref)
    {
        $this->acceptEref = $acceptEref;

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptWeb()
    {
        return $this->acceptWeb == self::ACCEPT_WEB_YES;
    }

    /**
     * @param int $acceptWeb
     *
     * @return ExchangeSite
     */
    public function setAcceptWeb($acceptWeb)
    {
        $this->acceptWeb = $acceptWeb;

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptSelf()
    {
        return $this->acceptSelf == self::ACCEPT_SELF_YES;
    }

    /**
     * @param int $acceptSelf
     *
     * @return ExchangeSite
     */
    public function setAcceptSelf($acceptSelf)
    {
        $this->acceptSelf = $acceptSelf;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getNofollowLink()
    {
        return $this->nofollowLink;
    }

    /**
     * @param boolean $nofollowLink
     *
     * @return ExchangeSite
     */
    public function setNofollowLink($nofollowLink)
    {
        $this->nofollowLink = $nofollowLink;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSponsorisedArticle()
    {
        return $this->sponsorisedArticle;
    }

    /**
     * @param boolean $sponsorisedArticle
     *
     * @return ExchangeSite
     */
    public function setSponsorisedArticle($sponsorisedArticle)
    {
        $this->sponsorisedArticle = $sponsorisedArticle;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAdditionalExternalLink()
    {
        return $this->additionalExternalLink;
    }

    /**
     * @param boolean $additionalExternalLink
     *
     * @return ExchangeSite
     */
    public function setAdditionalExternalLink($additionalExternalLink)
    {
        $this->additionalExternalLink = $additionalExternalLink;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCountAdditionalExternalLink()
    {
        return $this->countAdditionalExternalLink;
    }

    /**
     * @param integer $countAdditionalExternalLink
     *
     * @return ExchangeSite
     */
    public function setCountAdditionalExternalLink($countAdditionalExternalLink)
    {
        $this->countAdditionalExternalLink = $countAdditionalExternalLink;

        return $this;
    }

    /**
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     *
     * @return ExchangeSite
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return string
     */
    public function getMinWordsNumber()
    {
        return $this->minWordsNumber;
    }

    /**
     * @param string $minWordsNumber
     *
     * @return ExchangeSite
     */
    public function setMinWordsNumber($minWordsNumber)
    {
        $this->minWordsNumber = $minWordsNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxLinksNumber()
    {
        return $this->maxLinksNumber;
    }

    /**
     * @param string $maxLinksNumber
     *
     * @return ExchangeSite
     */
    public function setMaxLinksNumber($maxLinksNumber)
    {
        $this->maxLinksNumber = $maxLinksNumber;

        return $this;
    }

    /**
     * @return bool
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param bool $metaTitle
     *
     * @return ExchangeSite
     */
    public function setMetaTitle(?bool $metaTitle)
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param bool $metaDescription
     *
     * @return ExchangeSite
     */
    public function setMetaDescription(?bool $metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHeaderOneSet()
    {
        return $this->headerOneSet;
    }

    /**
     * @param bool $headerOneSet
     *
     * @return ExchangeSite
     */
    public function setHeaderOneSet(?bool $headerOneSet)
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
     * @return ExchangeSite
     */
    public function setHeaderTwoStart(?int $headerTwoStart)
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
     * @return ExchangeSite
     */
    public function setHeaderTwoEnd(?int $headerTwoEnd)
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
     * @return ExchangeSite
     */
    public function setHeaderThreeStart(?int $headerThreeStart)
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
     * @return ExchangeSite
     */
    public function setHeaderThreeEnd(?int $headerThreeEnd)
    {
        $this->headerThreeEnd = $headerThreeEnd;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getBoldText()
    {
        return $this->boldText;
    }

    /**
     * @param bool|null $boldText
     *
     * @return ExchangeSite
     */
    public function setBoldText(?bool $boldText)
    {
        $this->boldText = $boldText;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getItalicText()
    {
        return $this->italicText;
    }

    /**
     * @param bool|null $italicText
     *
     * @return ExchangeSite
     */
    public function setItalicText(?bool $italicText)
    {
        $this->italicText = $italicText;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getQuotedText()
    {
        return $this->quotedText;
    }

    /**
     * @param bool|null $quotedText
     *
     * @return ExchangeSite
     */
    public function setQuotedText(?bool $quotedText)
    {
        $this->quotedText = $quotedText;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getUlTag()
    {
        return $this->ulTag;
    }

    /**
     * @param bool|null $ulTag
     *
     * @return ExchangeSite
     */
    public function setUlTag(?bool $ulTag)
    {
        $this->ulTag = $ulTag;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxImagesNumber()
    {
        return $this->maxImagesNumber;
    }

    /**
     * @param int $maxImagesNumber
     *
     * @return ExchangeSite
     */
    public function setMaxImagesNumber(?int $maxImagesNumber)
    {
        $this->maxImagesNumber = $maxImagesNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getMinImagesNumber()
    {
        return $this->minImagesNumber;
    }

    /**
     * @param string $minImagesNumber
     *
     * @return ExchangeSite
     */
    public function setMinImagesNumber($minImagesNumber)
    {
        $this->minImagesNumber = $minImagesNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicationRules()
    {
        return $this->publicationRules;
    }

    /**
     * @param string $publicationRules
     *
     * @return ExchangeSite
     */
    public function setPublicationRules($publicationRules)
    {
        $this->publicationRules = $publicationRules;

        return $this;
    }

    /**
     * @return int
     */
    public function getTrustedWebmaster()
    {
        return $this->trustedWebmaster == self::TRUSTED_WEBMASTER_YES;
    }

    /**
     * @param int $trustedWebmaster
     *
     * @return ExchangeSite
     */
    public function setTrustedWebmaster($trustedWebmaster)
    {
        $this->trustedWebmaster = $trustedWebmaster;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return ExchangeSite
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return $this->pluginUrl;
    }

    /**
     * @param string $pluginUrl
     *
     * @return ExchangeSite
     */
    public function setPluginUrl($pluginUrl)
    {
        $this->pluginUrl = $pluginUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPluginStatus()
    {
        return $this->pluginStatus;
    }

    /**
     * @param bool $pluginStatus
     *
     * @return ExchangeSite
     */
    public function setPluginStatus($pluginStatus)
    {
        $this->pluginStatus = $pluginStatus;

        return $this;
    }

    /**
     * @param ArrayCollection $categories
     *
     * @return ExchangeSite
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Add Category
     *
     * @param Category $category
     *
     * @return ExchangeSite
     */
    public function addCategory(Category $category)
    {
        $category->addExchangeSite($this);
        $this->categories->add($category);

        return $this;
    }

    /**
     * Remove Category
     *
     * @param Category $category
     */
    public function removeCategory(Category $category)
    {
        $category->removeExchangeSite($this);
        $this->categories->removeElement($category);
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return string
     */
    public function getCategoriesFormatted()
    {
        if (!$this->categories->isEmpty()) {
            $category = [];

            /** @var Category $view */
            foreach ($this->categories as $view) {
                $category[] = $view->getName();
            }

            return implode('/ ', $category);
        }

        return '';
    }

    /**
     * Add Rubric
     *
     * @param Rubric $rubric
     *
     * @return ExchangeSite
     */
    public function addRubric(Rubric $rubric)
    {
        $elements = $this->rubrics->filter(function ($entry) use ($rubric) {
            return $entry->getExtId() == $rubric->getExtId();
        });

        if (count($elements) === 0) {
            $rubric->addExchangeSite($this);
            $this->rubrics->add($rubric);
        }

        return $this;
    }

    /**
     * Remove Rubric
     *
     * @param Rubric $rubric
     */
    public function removeRubric(Rubric $rubric)
    {
        $rubric->removeExchangeSite($this);
        $this->rubrics->removeElement($rubric);
    }

    /**
     * @return ArrayCollection
     */
    public function getRubrics()
    {
        return $this->rubrics;
    }

    /**
     * @return string
     */
    public function getRubricsFormatted()
    {
        if (!$this->rubrics->isEmpty()) {
            $rubrics = [];

            /** @var Rubric $view */
            foreach ($this->rubrics as $view) {
                $rubrics[] = $view->getName();
            }

            return implode('/ ', $rubrics);
        }

        return '';
    }

    /**
     * @param ArrayCollection $rubrics
     *
     * @return ExchangeSite
     */
    public function setRubrics($rubrics)
    {
        $this->rubrics = $rubrics;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getExchangeProposition()
    {
        return $this->exchangeProposition;
    }

    /**
     * @param ArrayCollection $exchangeProposition
     *
     * @return ExchangeSite
     */
    public function setExchangeProposition($exchangeProposition)
    {
        $this->exchangeProposition = $exchangeProposition;

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
     * @return ExchangeSite
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizedAnchor()
    {
        return $this->authorizedAnchor;
    }

    /**
     * @param string $authorizedAnchor
     */
    public function setAuthorizedAnchor($authorizedAnchor)
    {
        $this->authorizedAnchor = $authorizedAnchor;
    }

    /**
     * @return ArrayCollection
     */
    public function getDirectoriesList()
    {
        return $this->directoriesList;
    }

    /**
     * @param ArrayCollection $directoriesList
     *
     * @return ExchangeSite
     */
    public function setDirectoriesList($directoriesList)
    {
        $this->directoriesList = $directoriesList;

        return $this;
    }

    /**
     * Add DirectoriesList
     *
     * @param DirectoriesList $directoriesList
     *
     * @return ExchangeSite
     */
    public function addDirectoriesList(DirectoriesList $directoriesList)
    {
        if (!$this->directoriesList->contains($directoriesList)) {
            $this->directoriesList->add($directoriesList);
        }

        return $this;
    }

    /**
     * Remove DirectoriesList
     *
     * @param DirectoriesList $directoriesList
     *
     * @return ExchangeSite
     */
    public function removeDirectoriesList(DirectoriesList $directoriesList)
    {
        if ($this->directoriesList->contains($directoriesList)) {
            $this->directoriesList->removeElement($directoriesList);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->parseHost($this->getUrl());
    }

    /**
     * @return string
     */
    public function getHiddenHost()
    {
        return $this->parseHost($this->getHiddenUrl());
    }

    /**
     * @param Anchor $anchor
     * @return $this
     */
    public function addAnchor(Anchor $anchor)
    {
        if (!$this->anchor->contains($anchor)) {
            $this->anchor->add($anchor);
        }

        return $this;
    }

    /**
     * @param Anchor $anchor
     * @return $this
     */
    public function removeAnchor(Anchor $anchor)
    {
        if ($this->anchor->contains($anchor)) {
            $this->anchor->removeElement($anchor);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getWebmasterAnchor()
    {
        return $this->webmasterAnchor == self::WEBMASTER_ANCHOR_YES;
    }

    /**
     * @param int $webmasterAnchor
     *
     * @return ExchangeSite
     */
    public function setWebmasterAnchor($webmasterAnchor)
    {
        $this->webmasterAnchor = $webmasterAnchor;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDomain();
    }

    /**
     * @return string
     */
    public function getSiteType()
    {
        return $this->siteType;
    }

    /**
     * @param string $siteType
     *
     * @return ExchangeSite
     */
    public function setSiteType($siteType)
    {
        $this->siteType = $siteType;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getUrl();
    }

    /**
     * @param null $type
     *
     * @return bool
     */
    public function hasPlugin($type = null)
    {
        if ($type === ExchangeSite::EXCHANGE_TYPE) {
            return $this->pluginStatus && $this->autoPublish;
        }

       return $this->pluginStatus;
    }

    /**
     * @return bool
     */
    public function checkFormOfWriting()
    {
        return $this->acceptEref || $this->acceptWeb || $this->acceptSelf;
    }

    /**
     * @param bool $autoPublish
     *
     * @return $this
     */
    public function setAutoPublish($autoPublish)
    {
        $this->autoPublish = $autoPublish;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoPublish()
    {
        return $this->autoPublish;
    }

    /**
     * @return array
     */
    public static function getAvailableResponseCode()
    {
        return [
            ExchangeSite::RESPONSE_CODE_INCORRECT_API_KEY,
            ExchangeSite::RESPONSE_CODE_INVALID_JSON,
            ExchangeSite::RESPONSE_CODE_SUCCESS_CONNECTION,
            ExchangeSite::RESPONSE_CODE_INCORRECT_POST_ID,
            ExchangeSite::RESPONSE_CODE_INCORRECT_POST,
            ExchangeSite::RESPONSE_CODE_POST_NOT_EDITABLE,
            ExchangeSite::RESPONSE_CODE_ERROR_UPDATE_POST,
            ExchangeSite::RESPONSE_CODE_ERROR_IMPORT_IMAGE,
            ExchangeSite::RESPONSE_CODE_ERROR_IMPORT_THUMBNAIL,
            ExchangeSite::RESPONSE_CODE_PUBLISH_SUCCESS,
            ExchangeSite::RESPONSE_CODE_PUBLISH_PENDING,
        ];
    }
}
