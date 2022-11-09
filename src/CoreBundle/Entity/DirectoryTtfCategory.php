<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class DirectoryTtfCategory
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var Directory $exchangeSite
     *
     * @ORM\ManyToOne(targetEntity="Directory", inversedBy="majesticTtfCategories")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     **/
    private $directory;

    /**
     * @var TtfCategory $category
     *
     * @ORM\ManyToOne(targetEntity="TtfCategory", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="cascade")
     **/
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\Range(min = 0, max = 100)
     */
    private $rate;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Directory
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param Directory $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return TtfCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param TtfCategory $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param int $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

}
