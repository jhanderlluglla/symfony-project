<?php

namespace CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use CoreBundle\Validator as AppAssert;

/**
 * CopywritingArticleNonconform
 *
 * @ORM\Entity()
 */
class CopywritingArticleNonconform
{

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var CopywritingArticle $article
     *
     * @ORM\ManyToOne(targetEntity="CopywritingArticle", inversedBy="nonconforms", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $article;

    /**
     * @var string
     *
     * @ORM\Column(name="rule", type="text")
     */
    private $rule;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="text")
     */
    private $error;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="text")
     */
    private $reason;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CopywritingArticle
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param CopywritingArticle|null $article
     */
    public function setArticle(?CopywritingArticle $article)
    {
        $this->article = $article;
    }

    /**
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param string $rule
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }
}