<?php

namespace CoreBundle\Model;

/**
 * Class ArticleEarning
 *
 * @package CoreBundle\Model
 */
class ArticleEarning
{
    /** @var float */
    private $baseEarning;

    /** @var float */
    private $imagesEarning;

    /** @var float */
    private $expressEarning;

    /** @var float */
    private $chooseEarning;

    /** @var float */
    private $metaDescriptionEarning;

    /** @var float */
    private $malus;

    /** @var float  */
    private $bonus;

    /**
     * ArticleEarning constructor.
     *
     * @param float $baseEarning
     * @param float $imagesEarning
     * @param float $expressEarning
     * @param float $chooseEarning - only for writer
     * @param float $metaDescriptionEarning
     * @param float $malus - time penalty / rating malus for writer
     * @param float $bonus - only for writer
     */
    public function __construct($baseEarning, $imagesEarning, $expressEarning, $chooseEarning = 0., $metaDescriptionEarning = 0., $malus = 0., $bonus = 0.)
    {
        $this->baseEarning = $baseEarning;
        $this->imagesEarning = $imagesEarning;
        $this->expressEarning = $expressEarning;
        $this->chooseEarning = $chooseEarning;
        $this->metaDescriptionEarning = $metaDescriptionEarning;
        $this->malus = $malus;
        $this->bonus = $bonus;
    }

    /**
     * @return float
     */
    public function getBaseEarning()
    {
        return $this->baseEarning;
    }

    /**
     * @param float $baseEarning
     *
     * @return ArticleEarning
     */
    public function setBaseEarning($baseEarning)
    {
        $this->baseEarning = $baseEarning;

        return $this;
    }

    /**
     * @return float
     */
    public function getImagesEarning()
    {
        return $this->imagesEarning;
    }

    /**
     * @param float $imagesEarning
     *
     * @return ArticleEarning
     */
    public function setImagesEarning($imagesEarning)
    {
        $this->imagesEarning = $imagesEarning;

        return $this;
    }

    /**
     * @return float
     */
    public function getChooseEarning()
    {
        return $this->chooseEarning;
    }

    /**
     * @param float $chooseEarning
     *
     * @return ArticleEarning
     */
    public function setChooseEarning($chooseEarning)
    {
        $this->chooseEarning = $chooseEarning;

        return $this;
    }

    /**
     * @return float
     */
    public function getExpressEarning()
    {
        return $this->expressEarning;
    }

    /**
     * @param float $expressEarning
     *
     * @return ArticleEarning
     */
    public function setExpressEarning($expressEarning)
    {
        $this->expressEarning = $expressEarning;

        return $this;
    }

    /**
     * @return float
     */
    public function getMalus()
    {
        return $this->malus;
    }

    /**
     * @param float $malus
     *
     * @return ArticleEarning
     */
    public function setMalus($malus)
    {
        $this->malus = $malus;

        return $this;
    }

    /**
     * @return float
     */
    public function getMetaDescriptionEarning()
    {
        return $this->metaDescriptionEarning;
    }

    /**
     * @param float $metaDescriptionEarning
     *
     * @return ArticleEarning
     */
    public function setMetaDescriptionEarning($metaDescriptionEarning)
    {
        $this->metaDescriptionEarning = $metaDescriptionEarning;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalForAdmin()
    {
        $total = $this->baseEarning + $this->imagesEarning + $this->expressEarning + $this->chooseEarning + $this->metaDescriptionEarning - $this->malus;

        return $total < 0 ? 0 : $total;
    }

    /**
     * @return float
     */
    public function getTotalForWriter()
    {
        $total = $this->baseEarning + $this->imagesEarning + $this->expressEarning + $this->chooseEarning + $this->metaDescriptionEarning;

        return $total < 0 ? 0 : $total;
    }

    /**
     * @return float
     */
    public function getBonus(): ?float
    {
        return $this->bonus;
    }

    /**
     * @param float $bonus
     *
     * @return ArticleEarning
     */
    public function setBonus(?float $bonus): ArticleEarning
    {
        $this->bonus = $bonus;

        return $this;
    }
}
