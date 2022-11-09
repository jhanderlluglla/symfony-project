<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use CoreBundle\Entity\Traits\LanguageTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="category")

 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CategoryRepository")
 */
class Category
{
    use ExternalIdTrait;

    use LanguageTrait;

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
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var ArrayCollection $exchangeSite
     *
     * @ORM\ManyToMany(targetEntity="ExchangeSite", mappedBy="categories")
     */
    private $exchangeSites;

    /**
     * @var ArrayCollection $directory
     *
     * @ORM\ManyToMany(targetEntity="\CoreBundle\Entity\Directory", mappedBy="categories")
     */
    private $directory;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->exchangeSites = new ArrayCollection();
        $this->directory = new ArrayCollection();
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
     * @return Category
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
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param mixed $lft
     *
     * @return Category
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @param mixed $lvl
     *
     * @return Category
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param mixed $rgt
     *
     * @return Category
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param mixed $root
     *
     * @return Category
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     *
     * @return Category
     */
    public function setParent(Category $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     *
     * @return Category
     */
    public function setChildren($children)
    {
        $this->children = $children;

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
     * @return Category
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
     * @return Category
     */
    public function addExchangeSite(ExchangeSite $exchangeSite)
    {
        if (!$this->exchangeSites->contains($exchangeSite)) {
            $this->exchangeSites->add($exchangeSite);
        }

        return $this;
    }

    /**
     * Remove ExchangeSite
     *
     * @param ExchangeSite $exchangeSite
     */
    public function removeExchangeSite(ExchangeSite $exchangeSite)
    {
        if ($this->exchangeSites->contains($exchangeSite)) {
            $this->exchangeSites->removeElement($exchangeSite);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param ArrayCollection $directory
     *
     * @return Category
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Add Directory
     *
     * @param Directory $directory
     *
     * @return Category
     */
    public function addDirectory(Directory $directory)
    {
        if (!$this->directory->contains($directory)) {
            $this->directory->add($directory);
        }

        return $this;
    }

    /**
     * Remove Directory
     *
     * @param Directory $directory
     */
    public function removeDirectory(Directory $directory)
    {
        if ($this->directory->contains($directory)) {
            $this->directory->removeElement($directory);
        }
    }

    /**
     * @return string
     */
    public function getMultiselectName()
    {
        return  ($this->getLvl() > 1 ? '   >   ':'') . $this->getName();
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return '|' . str_repeat('-', $this->getLvl() * 4) .' '. $this->getName();
    }
}
