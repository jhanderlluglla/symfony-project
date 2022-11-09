<?php

namespace UserBundle\Entity;

/**
 * Class NetlinkingAnchorFlowEntity
 *
 * @package UserBundle\Entity
 */
class NetlinkingAnchorFlowEntity
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
     */
    private $webmasterAnchor;

    /**
     * @var string
     */
    private $anchor;

    /**
     * @var integer
     */
    private $directory;

    /**
     * @var integer
     */
    private $exchangeSite;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return NetlinkingAnchorFlowEntity
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return int
     */
    public function getWebmasterAnchor()
    {
        return $this->webmasterAnchor;
    }

    /**
     * @param int $webmasterAnchor
     *
     * @return NetlinkingAnchorFlowEntity
     */
    public function setWebmasterAnchor($webmasterAnchor)
    {
        $this->webmasterAnchor = $webmasterAnchor;

        return $this;
    }

    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @param string $anchor
     *
     * @return NetlinkingAnchorFlowEntity
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;

        return $this;
    }

    /**
     * @return integer
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param integer $directory
     *
     * @return NetlinkingAnchorFlowEntity
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @return int
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param int $exchangeSite
     * @return $this
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;

        return $this;
    }
}