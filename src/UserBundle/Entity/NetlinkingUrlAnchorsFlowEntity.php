<?php

namespace UserBundle\Entity;

/**
 * Class NetlinkingUrlAnchorsFlowEntity
 *
 * @package UserBundle\Entity
 */
class NetlinkingUrlAnchorsFlowEntity
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var NetlinkingAnchorFlowEntity[]
     */
    private $anchors = [];

    /**
     * @var NetlinkingAnchorFlowEntity[]
     */
    private $directoryAnchors = [];

    /**
     * @var NetlinkingAnchorFlowEntity[]
     */
    private $exchangeAnchors = [];

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
     * @return NetlinkingUrlAnchorsFlowEntity
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function getAnchors()
    {
        return $this->anchors;
    }

    /**
     * @param array $anchors
     *
     * @return NetlinkingUrlAnchorsFlowEntity
     */
    public function setAnchors($anchors)
    {
        $this->anchors = $anchors;

        return $this;
    }

    /**
     * @param integer $directoryId
     *
     * @return null|NetlinkingAnchorFlowEntity
     */
    public function hasAnchorForDirectory($directoryId)
    {
        return isset($this->directoryAnchors[$directoryId]) ? $this->directoryAnchors[$directoryId]:null;
    }

    /**
     * @param $exchangeSiteId
     * @return mixed|null
     */
    public function hasAnchorForExchangeSite($exchangeSiteId)
    {
        return isset($this->exchangeAnchors[$exchangeSiteId]) ? $this->exchangeAnchors[$exchangeSiteId] : null;
    }

    /**
     * @return array
     */
    public function getExchangeAnchors()
    {
        return $this->exchangeAnchors;
    }

    /**
     * @param $exchangeAnchors
     *
     * @return NetlinkingUrlAnchorsFlowEntity
     */
    public function setExchangeAnchors($exchangeAnchors)
    {
        $this->exchangeAnchors = $exchangeAnchors;

        return $this;
    }

    /**
     * @return NetlinkingAnchorFlowEntity[]
     */
    public function getDirectoryAnchors()
    {
        return $this->directoryAnchors;
    }

    /**
     * @param NetlinkingAnchorFlowEntity[] $directoryAnchors
     *
     * @return NetlinkingUrlAnchorsFlowEntity
     */
    public function setDirectoryAnchors($directoryAnchors)
    {
        $this->directoryAnchors = $directoryAnchors;

        return $this;
    }
}