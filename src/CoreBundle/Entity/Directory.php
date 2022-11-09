<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Interfaces\SiteUrlInterface;
use CoreBundle\Entity\Traits\ExternalIdTrait;
use Symfony\Component\Validator\Constraints as Assert;
use CoreBundle\Entity\Constant\Language;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Directory
 *
 * @ORM\Table(name="directory")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\DirectoryRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class Directory extends AbstractMetricsEntity implements StateInterface, SiteUrlInterface
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
     * @var User $webmasterPartner
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     **/
    private $webmasterPartner;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\DirectoriesList", mappedBy="directories", cascade={"persist"})
     */
    private $directoriesList;

    /**
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="directory")
     * @ORM\JoinTable(name="directory_category",
     *      joinColumns={@ORM\JoinColumn(name="directory_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     *
     */
    private $categories;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="tariff_extra_webmaster", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tariffExtraWebmaster;

    /**
     * @var float
     *
     * @ORM\Column(name="tariff_extra_seo", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tariffExtraSeo;

    /**
     * @var float
     *
     * @ORM\Column(name="tariff_webmaster_partner", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tariffWebmasterPartner;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 1})
     */
    private $active;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $webmasterAnchor;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $webmasterOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $instructions;

    /**
     * @var string
     *
     * @ORM\Column(name="ndd_target", type="text", nullable=true)
     */
    private $nddTarget;

    /**
     * @var integer
     *
     * @ORM\Column(name="page_count", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $pageCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="page_rank", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $pageRank;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_referring_domain", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $totalReferringDomain;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_backlink", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $totalBacklink;

    /**
     * @var integer
     *
     * @ORM\Column(name="validation_time", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $validationTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="validation_rate", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $validationRate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $acceptInnerPages;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $acceptLegalInfo;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $acceptCompanyWebsites;

    /**
     * @var string
     *
     * @ORM\Column(name="link_submission", type="text", nullable=true)
     */
    private $linkSubmission;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $vipState;

    /**
     * @var string
     *
     * @ORM\Column(name="vip_text", type="text", nullable=true)
     */
    private $vipText;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_words_count", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $minWordsCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_words_count", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $maxWordsCount;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $personalAccountWebmaster;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var ArrayCollection $anchor
     *
     * @ORM\OneToMany(targetEntity="Anchor", mappedBy="directory", cascade={"persist", "remove"})
     */
    private $anchor;


    /**
     * @var ArrayCollection|PersistentCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="DirectoryTtfCategory",
     *     mappedBy="directory",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $majesticTtfCategories;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": Language::FR})
     *
     * @Assert\Choice(callback={"CoreBundle\Entity\Constant\Language", "getAll"})
     */
    protected $language = Language::FR;

    /**
     * Directory constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->directoriesList = new ArrayCollection();
        $this->anchor = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->majesticTtfCategories = new ArrayCollection();
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
    public function getWebmasterPartner()
    {
        return $this->webmasterPartner;
    }

    /**
     * @param User $webmasterPartner
     *
     * @return Directory
     */
    public function setWebmasterPartner($webmasterPartner)
    {
        $this->webmasterPartner = $webmasterPartner;
        return $this;
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
     *
     * @return Directory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getTariffExtraWebmaster()
    {
        return $this->tariffExtraWebmaster ? $this->tariffExtraWebmaster:0;
    }

    /**
     * @param float $tariffExtraWebmaster
     *
     * @return Directory
     */
    public function setTariffExtraWebmaster($tariffExtraWebmaster)
    {
        $this->tariffExtraWebmaster = $tariffExtraWebmaster;

        return $this;
    }

    /**
     * @return float
     */
    public function getTariffExtraSeo()
    {
        return $this->tariffExtraSeo;
    }

    /**
     * @param float $tariffExtraSeo
     *
     * @return Directory
     */
    public function setTariffExtraSeo($tariffExtraSeo)
    {
        $this->tariffExtraSeo = $tariffExtraSeo;

        return $this;
    }

    /**
     * @return float
     */
    public function getTariffWebmasterPartner()
    {
        return $this->tariffWebmasterPartner;
    }

    /**
     * @param float $tariffWebmasterPartner
     *
     * @return Directory
     */
    public function setTariffWebmasterPartner($tariffWebmasterPartner)
    {
        $this->tariffWebmasterPartner = $tariffWebmasterPartner;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return Directory
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool
     */
    public function getWebmasterAnchor()
    {
        return $this->webmasterAnchor;
    }

    /**
     * @param bool $webmasterAnchor
     *
     * @return Directory
     */
    public function setWebmasterAnchor($webmasterAnchor)
    {
        $this->webmasterAnchor = $webmasterAnchor;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebmasterOrder()
    {
        return $this->webmasterOrder;
    }

    /**
     * @param string $webmasterOrder
     *
     * @return Directory
     */
    public function setWebmasterOrder($webmasterOrder)
    {
        $this->webmasterOrder = $webmasterOrder;

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
     * @return Directory
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return string
     */
    public function getNddTarget()
    {
        return $this->nddTarget;
    }

    /**
     * @param string $nddTarget
     *
     * @return Directory
     */
    public function setNddTarget($nddTarget)
    {
        $this->nddTarget = $nddTarget;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount < 1 ? 1:$this->pageCount;
    }

    /**
     * @param int $pageCount
     *
     * @return Directory
     */
    public function setPageCount($pageCount)
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalReferringDomain()
    {
        return $this->totalReferringDomain;
    }

    /**
     * @param int $totalReferringDomain
     *
     * @return Directory
     */
    public function setTotalReferringDomain($totalReferringDomain)
    {
        $this->totalReferringDomain = $totalReferringDomain;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalBacklink()
    {
        return $this->totalBacklink;
    }

    /**
     * @param int $totalBacklink
     *
     * @return Directory
     */
    public function setTotalBacklink($totalBacklink)
    {
        $this->totalBacklink = $totalBacklink;

        return $this;
    }

    /**
     * @return int
     */
    public function getValidationTime()
    {
        return $this->validationTime;
    }

    /**
     * @param int $validationTime
     *
     * @return Directory
     */
    public function setValidationTime($validationTime)
    {
        $this->validationTime = $validationTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getValidationRate()
    {
        return $this->validationRate;
    }

    /**
     * @param int $validationRate
     *
     * @return Directory
     */
    public function setValidationRate($validationRate)
    {
        $this->validationRate = $validationRate;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAcceptInnerPages()
    {
        return $this->acceptInnerPages;
    }

    /**
     * @param bool $acceptInnerPages
     *
     * @return Directory
     */
    public function setAcceptInnerPages($acceptInnerPages)
    {
        $this->acceptInnerPages = $acceptInnerPages;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAcceptLegalInfo()
    {
        return $this->acceptLegalInfo;
    }

    /**
     * @param bool $acceptLegalInfo
     *
     * @return Directory
     */
    public function setAcceptLegalInfo($acceptLegalInfo)
    {
        $this->acceptLegalInfo = $acceptLegalInfo;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAcceptCompanyWebsites()
    {
        return $this->acceptCompanyWebsites;
    }

    /**
     * @param bool $acceptCompanyWebsites
     *
     * @return Directory
     */
    public function setAcceptCompanyWebsites($acceptCompanyWebsites)
    {
        $this->acceptCompanyWebsites = $acceptCompanyWebsites;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkSubmission()
    {
        return $this->linkSubmission;
    }

    /**
     * @param string $linkSubmission
     *
     * @return Directory
     */
    public function setLinkSubmission($linkSubmission)
    {
        $this->linkSubmission = $linkSubmission;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVipState()
    {
        return $this->vipState;
    }

    /**
     * @param bool $vipState
     *
     * @return Directory
     */
    public function setVipState($vipState)
    {
        $this->vipState = $vipState;
        return $this;
    }

    /**
     * @return string
     */
    public function getVipText()
    {
        return $this->vipText;
    }

    /**
     * @param string $vipText
     *
     * @return Directory
     */
    public function setVipText($vipText)
    {
        $this->vipText = $vipText;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinWordsCount()
    {
        return $this->minWordsCount === null ? 0 : $this->minWordsCount;
    }

    /**
     * @param int $minWordsCount
     *
     * @return Directory
     */
    public function setMinWordsCount($minWordsCount)
    {
        $this->minWordsCount = $minWordsCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxWordsCount()
    {
        return $this->maxWordsCount;
    }

    /**
     * @param int $maxWordsCount
     *
     * @return Directory
     */
    public function setMaxWordsCount($maxWordsCount)
    {
        $this->maxWordsCount = $maxWordsCount;

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
     * @return Directory
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageRank()
    {
        return $this->pageRank;
    }

    /**
     * @param int $pageRank
     *
     * @return Directory
     */
    public function setPageRank($pageRank)
    {
        $this->pageRank = $pageRank;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPersonalAccountWebmaster()
    {
        return $this->personalAccountWebmaster;
    }

    /**
     * @param bool $personalAccountWebmaster
     *
     * @return Directory
     */
    public function setPersonalAccountWebmaster($personalAccountWebmaster)
    {
        $this->personalAccountWebmaster = $personalAccountWebmaster;

        return $this;
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
     * @return Directory
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
     * @return Directory
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
     * @return Directory
     */
    public function removeDirectoriesList(DirectoriesList $directoriesList)
    {
        if ($this->directoriesList->contains($directoriesList)) {
            $this->directoriesList->removeElement($directoriesList);
        }

        return $this;
    }

    /**
     * @return Directory
     */
    public function removeFromAllDirectoryLists()
    {
        /** @var DirectoriesList $directoryList */
        foreach ($this->directoriesList as $directoryList) {
            $directoryList->removeDirectories($this);
            $this->directoriesList->removeElement($directoryList);
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @param ArrayCollection $anchor
     *
     * @return Directory
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;

        return $this;
    }

    /**
     * Add anchor
     *
     * @param Anchor $anchor
     *
     * @return Directory
     */
    public function addAnchor(Anchor $anchor)
    {
        if (!$this->anchor->contains($anchor)) {
            $this->anchor->add($anchor);
        }

        return $this;
    }

    /**
     * Remove anchor
     *
     * @param Anchor $anchor
     *
     * @return Directory
     */
    public function removeAnchor(Anchor $anchor)
    {
        if ($this->anchor->contains($anchor)) {
            $this->anchor->removeElement($anchor);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param mixed $categories
     *
     * @return Directory
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Add category
     *
     * @param Category $category
     *
     * @return Directory
     */
    public function addCategories(Category $category)
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    /**
     * Remove category
     *
     * @param Category $category
     * @return $this
     */
    public function removeCategories(Category $category)
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCategoriesFormatted()
    {
        if (!$this->categories->isEmpty()) {
            $categories = [];

            /** @var Category $view */
            foreach ($this->categories as $view) {
                $categories[] = $view->getName();
            }

            return implode('/ ', $categories);
        }

        return '';
    }

    /**
     * @param string $name
     *
     * @return DirectoryTtfCategory|null
     */
    public function getTtfCategory(string $name)
    {
        /** @var DirectoryTtfCategory $majesticTtfCategory */
        foreach ($this->majesticTtfCategories as $majesticTtfCategory) {
            $category = $majesticTtfCategory->getCategory();
            if ($category->getName() === $name) {
                return $majesticTtfCategory;
            }
        }

        return null;
    }

    /**
     * @param DirectoryTtfCategory $ttfCategory
     */
    public function addTtfCategory($ttfCategory)
    {
        if(!$this->majesticTtfCategories->contains($ttfCategory)) {
            $ttfCategory->setDirectory($this);
            $this->majesticTtfCategories->add($ttfCategory);
        }
    }

    /**
     * @return Directory
     */
    public function removeTtfCategories()
    {
        $this->majesticTtfCategories->clear();

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->parseHost($this->getName());
    }

    /**
     * @return string
     */
    public function getSubmissionUrl()
    {
        return !empty($this->getLinkSubmission()) ? $this->getLinkSubmission():$this->getName();
    }

    /**
     * @return bool
     */
    public function hasWebmasterPartner()
    {
        return !is_null($this->webmasterPartner);
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->parseDomain($this->name);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->name;
    }
}
