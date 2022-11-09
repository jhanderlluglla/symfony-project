<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class ExchangeSiteTtfCategory
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var ExchangeSite $exchangeSite
     *
     * @ORM\ManyToOne(targetEntity="ExchangeSite", inversedBy="majesticTtfCategories")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     **/
    private $exchangeSite;

    /**
     * @var TtfCategory $category
     *
     * @ORM\ManyToOne(targetEntity="TtfCategory", cascade={"persist"})
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
     * @return ExchangeSite
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param ExchangeSite $exchangeSite
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;
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
