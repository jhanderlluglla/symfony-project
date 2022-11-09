<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity()
 */
class Rubric
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=64)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var ArrayCollection $exchangeSite
     *
     * @ORM\ManyToMany(targetEntity="ExchangeSite", mappedBy="rubrics")
     */
    private $exchangeSites;

    /**
     * @var ArrayCollection $articles
     *
     * @ORM\ManyToMany(targetEntity="CopywritingArticle", mappedBy="rubrics")
     */
    private $articles;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    private $extId;

    /**
     * Rubric constructor.
     */
    public function __construct()
    {
        $this->exchangeSites = new ArrayCollection();
        $this->articles = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return Rubric
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Rubric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getExchangeSites()
    {
        return $this->exchangeSites;
    }

    /**
     * @param ArrayCollection $exchangeSites
     *
     * @return Rubric
     */
    public function setExchangeSites($exchangeSites)
    {
        $this->exchangeSites = $exchangeSites;

        return $this;
    }

    /**
     * Add ExchangeSite
     *
     * @param ExchangeSite $exchangeSite
     *
     * @return Rubric
     */
    public function addExchangeSite(ExchangeSite $exchangeSite)
    {
        $this->exchangeSites->add($exchangeSite);

        return $this;
    }

    /**
     * Remove ExchangeSite
     *
     * @param ExchangeSite $exchangeSite
     */
    public function removeExchangeSite(ExchangeSite $exchangeSite)
    {
        $this->exchangeSites->removeElement($exchangeSite);
    }

    /**
     * @return mixed
     */
    public function getExtId()
    {
        return $this->extId;
    }

    /**
     * @param mixed $extId
     */
    public function setExtId($extId)
    {
        $this->extId = $extId;
    }

    public function addArticle(CopywritingArticle $article)
    {
        $this->articles->add($article);

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }
}