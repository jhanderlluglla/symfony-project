<?php

namespace CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Validator\Constraints as Assert;
use CoreBundle\Validator as AppAssert;

/**
 * CopywritingArticle
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CopywritingArticleRepository")
 *
 * @AppAssert\TextCorrectness()
 */
class CopywritingArticle
{

    public const REVIEW_TIME = 1800; // 1800s = 30m

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var CopywritingOrder $order
     *
     * @ORM\OneToOne(targetEntity="CopywritingOrder", inversedBy="article", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $order;

    /**
     * @var ArrayCollection $comments
     *
     * @ORM\OneToMany(targetEntity="CopywritingArticleComment", mappedBy="article", cascade={"persist", "remove"})
     */
    private $comments;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_title", type="string", length=500, nullable=true)
     * @Assert\Length(
     *     min = 60,
     *     max = 85
     * )
     */
    private $metaTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_desc", type="text", nullable=true)
     * @CoreBundle\Validator\Length(
     *     min = 125,
     *     max = 155
     * )
     */
    private $metaDesc;

    /**
     * @var float
     *
     * @ORM\Column(name="corrector_earn", type="float", precision=10, scale=0, nullable=true)
     */
    private $correctorEarn;

    /**
     * @var float
     *
     * @ORM\Column(name="writer_earn", type="float", precision=10, scale=0, nullable=true)
     */
    private $writerEarn;

    /**
     * @var string
     *
     * @ORM\Column(name="image_sources", type="array", nullable=true)
     */
    private $imageSources;

    /**
     * @var string
     *
     * @ORM\Column(name="front_image", type="text", nullable=true)
     */
    private $frontImage;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="CopywritingArticleNonconform",
     *     mappedBy="article",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    private $nonconforms;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    private $wordsNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    private $headerTwoNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    private $headerThreeNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    private $keywordsNumber;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $metaTitleKeywords = [];

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $headerOneKeywords = [];

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $headerTwoKeywords = [];

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $headerThreeKeywords = [];

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $imagesNumber;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $missedKeywords = [];

    /**
     * @ORM\ManyToMany(targetEntity="Rubric", inversedBy="articles")
     *
     */
    private $rubrics;

    /**
     * @var bool $consulted
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $consulted;

    /**
     * @var User $adminReview
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $adminReview;

    /**
     * @var \DateTime $adminReviewAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $adminReviewAt;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $imagesByWriter;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $imagesByAdmin;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oldProjectId; //delete after migration from old db

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->rubrics = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->nonconforms = new ArrayCollection();
        $this->consulted = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CopywritingOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param CopywritingOrder $order
     */
    public function setOrder(CopywritingOrder $order)
    {
        $this->order = $order;
    }

    /**
     * @return ArrayCollection
     */
    public function getNonconforms()
    {
        return $this->nonconforms;
    }

    /**
     * @param $rule
     * @return bool
     */
    public function isNonconformExist($rule)
    {
        $nonconforms = $this->nonconforms->filter(function ($entry) use ($rule) {
                return $entry->getRule() === $rule;
        });

        return !$nonconforms->isEmpty();
    }

    /**
     * @param $rule
     * @return mixed
     */
    public function getNonconform($rule)
    {
        $nonconforms = $this->nonconforms->filter(function ($entry) use ($rule) {
            return $entry->getRule() === $rule;
        });

        return current($nonconforms->toArray());
    }

    /**
     * @param $tag
     * @param $tagOccurrences
     * @return bool
     */
    public function isTagRangeValid($tag, $tagOccurrences)
    {
        return $this->order->getTagRangeStart($tag) <= $tagOccurrences->length && $this->order->getTagRangeEnd($tag) >= $tagOccurrences->length;
    }

    /**
     * @param array $nonconforms
     */
    public function setNonconforms($nonconforms)
    {
        $this->nonconforms = $nonconforms;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return CopywritingArticle
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param string $metaTitle
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
    }

    /**
     * @return string
     */
    public function getMetaDesc()
    {
        return $this->metaDesc;
    }

    /**
     * @param string $metaDesc
     */
    public function setMetaDesc($metaDesc)
    {
        $this->metaDesc = $metaDesc;
    }

    /**
     * @return float
     */
    public function getCorrectorEarn()
    {
        return $this->correctorEarn;
    }

    /**
     * @param float $correctorEarn
     */
    public function setCorrectorEarn($correctorEarn)
    {
        $this->correctorEarn = round($correctorEarn, 2);
    }

    /**
     * @return float
     */
    public function getWriterEarn()
    {
        return $this->writerEarn;
    }

    /**
     * @param float $writerEarn
     */
    public function setWriterEarn($writerEarn)
    {
        $this->writerEarn = round($writerEarn, 2);
    }

    /**
     * @return string
     */
    public function getImageSources()
    {
        return $this->imageSources;
    }

    /**
     * @param string $imageSources
     */
    public function setImageSources($imageSources)
    {
        $this->imageSources = $imageSources;
    }

    /**
     * @return string
     */
    public function getFrontImage()
    {
        return $this->frontImage;
    }

    /**
     * @param string $frontImage
     */
    public function setFrontImage($frontImage)
    {
        $this->frontImage = $frontImage;
    }

    /**
     * @param CopywritingArticleNonconform $nonconform
     * @return $this
     */
    public function addNonconform(CopywritingArticleNonconform $nonconform)
    {
        $this->nonconforms->add($nonconform);
        $nonconform->setArticle($this);
        return $this;
    }

    /**
     * @param CopywritingArticleNonconform $nonconform
     * @return $this
     */
    public function removeNonconform(CopywritingArticleNonconform $nonconform)
    {
        if ($this->nonconforms->contains($nonconform)) {
            $nonconform->setArticle(null);

            $this->nonconforms->removeElement($nonconform);
        }
        return $this;
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
     */
    public function setWordsNumber($wordsNumber)
    {
        $this->wordsNumber = $wordsNumber;
    }

    /**
     * @return int
     */
    public function getHeaderTwoNumber()
    {
        return $this->headerTwoNumber;
    }

    /**
     * @param int $headerTwoNumber
     */
    public function setHeaderTwoNumber($headerTwoNumber)
    {
        $this->headerTwoNumber = $headerTwoNumber;
    }

    /**
     * @return int
     */
    public function getHeaderThreeNumber()
    {
        return $this->headerThreeNumber;
    }

    /**
     * @param int $headerThreeNumber
     */
    public function setHeaderThreeNumber($headerThreeNumber)
    {
        $this->headerThreeNumber = $headerThreeNumber;
    }

    /**
     * @return int
     */
    public function getKeywordsNumber()
    {
        return $this->keywordsNumber;
    }

    /**
     * @param int $keywordsNumber
     */
    public function setKeywordsNumber($keywordsNumber)
    {
        $this->keywordsNumber = $keywordsNumber;
    }

    /**
     * @return array
     */
    public function getMetaTitleKeywords()
    {
        return $this->metaTitleKeywords;
    }

    /**
     * @return string
     */
    public function getMetaTitleKeywordsFormatted()
    {
        return $this->metaTitleKeywords ? implode(', ', $this->metaTitleKeywords) : '';
    }

    /**
     * @param array $metaTitleKeywords
     */
    public function setMetaTitleKeywords($metaTitleKeywords)
    {
        $this->metaTitleKeywords = $metaTitleKeywords;
    }

    /**
     * @return array
     */
    public function getHeaderOneKeywords()
    {
        return $this->headerOneKeywords;
    }

    /**
     * @return string
     */
    public function getHeaderOneKeywordsFormatted()
    {
        return $this->headerOneKeywords ? implode(', ', $this->headerOneKeywords) : '';
    }

    /**
     * @param array $headerOneKeywords
     */
    public function setHeaderOneKeywords($headerOneKeywords)
    {
        $this->headerOneKeywords = $headerOneKeywords;
    }

    /**
     * @return array
     */
    public function getHeaderTwoKeywords()
    {
        return $this->headerTwoKeywords;
    }

    /**
     * @return string
     */
    public function getHeaderTwoKeywordsFormatted()
    {
        return $this->headerTwoKeywords ? implode(', ', $this->headerTwoKeywords) : '';
    }

    /**
     * @param array $headerTwoKeywords
     */
    public function setHeaderTwoKeywords($headerTwoKeywords)
    {
        $this->headerTwoKeywords = $headerTwoKeywords;
    }

    /**
     * @return array
     */
    public function getHeaderThreeKeywords()
    {
        return $this->headerThreeKeywords;
    }

    /**
     * @return string
     */
    public function getHeaderThreeKeywordsFormatted()
    {
        return $this->headerThreeKeywords ? implode(', ', $this->headerThreeKeywords) : '';
    }

    /**
     * @param array $headerThreeKeywords
     */
    public function setHeaderThreeKeywords($headerThreeKeywords)
    {
        $this->headerThreeKeywords = $headerThreeKeywords;
    }

    /**
     * @return int
     */
    public function getImagesNumber()
    {
        return $this->imagesNumber;
    }

    /**
     * @return array
     */
    public function getMissedKeywords()
    {
        return $this->missedKeywords;
    }

    /**
     * @param array $missedKeywords
     */
    public function setMissedKeywords($missedKeywords)
    {
        $this->missedKeywords = $missedKeywords;
    }

    /**
     * @return string
     */
    public function getMissedKeywordsFormatted()
    {
        return $this->missedKeywords ? implode(', ', $this->missedKeywords) : '';
    }

    /**
     * @param int $imagesNumber
     */
    public function setImagesNumber($imagesNumber)
    {
        $this->imagesNumber = $imagesNumber;
    }

    /**
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param ArrayCollection $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    public function addComment($comment)
    {
        $comment->setArticle($this);
        $this->comments->add($comment);

        return $this;
    }

    public function removeComment($comment)
    {
        $comment->setArticle(null);
        $this->comments->remove($comment);

        return $this;
    }

    public function getAdminComments()
    {
        $adminComments = [];

        /** @var CopywritingArticleComment $comment */
        foreach ($this->comments as $comment) {
            if ($comment->getUser()->isAdmin()) {
                $adminComments[] = $comment;
            }
        }

        return $adminComments;
    }

    public function getWebmasterComments()
    {
        $webmasterComments = [];

        /** @var CopywritingArticleComment $comment */
        foreach ($this->comments as $comment) {
            if ($comment->getUser()->isWebmaster()) {
                $webmasterComments[] = $comment;
            }
        }

        return $webmasterComments;
    }

    /**
     * @param $tag
     * @param $count
     * @return bool
     */
    public function setTagCount($tag, $count)
    {
        switch ($tag) {
            case 'h2':
                $this->setHeaderTwoNumber($count);
                break;
            case 'h3':
                $this->setHeaderThreeNumber($count);
                break;
            case 'img':
                $this->setImagesNumber($count);
                break;
            default:
                return false;
        }
    }

    /**
     * @param $tag
     * @param $keywords
     * @return bool
     */
    public function setKeywordsInTag($tag, $keywords)
    {
        $keywords = array_unique($keywords);

        switch ($tag) {
            case 'h1':
                $this->setHeaderOneKeywords($keywords);
                break;
            case 'h2':
                $this->setHeaderTwoKeywords($keywords);
                break;
            case 'h3':
                $this->setHeaderThreeKeywords($keywords);
                break;
            case 'title':
                $this->setMetaTitleKeywords($keywords);
                break;
            default:
                return false;
        }
    }

    /**
     * Add Rubric
     *
     * @param Rubric $rubric
     *
     * @return CopywritingArticle
     */
    public function addRubric(Rubric $rubric)
    {
        $rubric->addArticle($this);
        $this->rubrics->add($rubric);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getRubrics()
    {
        return $this->rubrics;
    }

    /**
     * @param ArrayCollection $rubrics
     *
     * @return CopywritingArticle
     */
    public function setRubrics($rubrics)
    {
        $this->rubrics = $rubrics;

        return $this;
    }

    /**
     * @return array
     */
    public function getRubricsExtIds()
    {
        $extIds = [];

        /** @var Rubric $rubric */
        foreach ($this->rubrics as $rubric) {
            $extIds[] = $rubric->getExtId();
        }

        return $extIds;
    }

    /**
     * @return bool
     */
    public function isConsulted()
    {
        return $this->consulted;
    }

    /**
     * @param bool $consulted
     */
    public function setConsulted($consulted)
    {
        $this->consulted = $consulted;
    }

    /**
     * @return User
     */
    public function getAdminReview()
    {
        return $this->adminReview;
    }

    /**
     * @param User $admin
     *
     * @return CopywritingArticle
     * @throws \Exception
     */
    public function setAdminReview($admin)
    {
        $this->adminReview = $admin;
        $this->setAdminReviewAt(new \DateTime());

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalImagesNumber()
    {
        return $this->frontImage ? $this->getImagesNumber() + 1 : $this->getImagesNumber();
    }

    /**
     * @return array
     */
    public function getImagesByWriter()
    {
        return is_null($this->imagesByWriter) ? [] : $this->imagesByWriter;
    }

    /**
     * @param array $imagesByWriter
     */
    public function setImagesByWriter($imagesByWriter)
    {
        $this->imagesByWriter = $imagesByWriter;
    }

    /**
     * @return array
     */
    public function getImagesByAdmin()
    {
        return is_null($this->imagesByAdmin) ? [] : $this->imagesByAdmin;
    }

    /**
     * @param array $imagesByAdmin
     */
    public function setImagesByAdmin($imagesByAdmin)
    {
        $this->imagesByAdmin = $imagesByAdmin;
    }

    /**
     * @return \DateTime
     */
    public function getAdminReviewAt()
    {
        return $this->adminReviewAt;
    }

    /**
     * @param \DateTime $adminReviewAt
     *
     * @return CopywritingArticle
     */
    public function setAdminReviewAt($adminReviewAt)
    {
        $this->adminReviewAt = $adminReviewAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNowReview()
    {
        return $this->getAdminReviewAt() && (time() - $this->getAdminReviewAt()->getTimestamp()) < self::REVIEW_TIME;
    }
}
