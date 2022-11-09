<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BotUrls
 *
 * @ORM\Table(name="bot_urls")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\BotUrlsRepository")
 */
class BotUrls
{
    const TREATHED_YES = 1;
    const TREATHED_NO  = 0;


    /**
     * @var string
     *
     * @ORM\Column(name="analyzed_url", type="string", length=255, nullable=true)
     * @ORM\Id
     */
    private $analyzedUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="search_url", type="string", length=255, nullable=true)
     */
    private $searchUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="payed", type="boolean")
     */
    private $isTreated = self::TREATHED_NO;

    /**
     * @return string
     */
    public function getAnalyzedUrl()
    {
        return $this->analyzedUrl;
    }

    /**
     * @param string $analyzedUrl
     *
     * @return BotUrls
     */
    public function setAnalyzedUrl($analyzedUrl)
    {
        $this->analyzedUrl = $analyzedUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * @param string $searchUrl
     *
     * @return BotUrls
     */
    public function setSearchUrl($searchUrl)
    {
        $this->searchUrl = $searchUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTreated()
    {
        return $this->isTreated;
    }

    /**
     * @param bool $isTreated
     *
     * @return BotUrls
     */
    public function setIsTreated($isTreated)
    {
        $this->isTreated = $isTreated;

        return $this;
    }
}