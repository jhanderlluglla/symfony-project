<?php

namespace UserBundle\Entity;

/**
 * Class NetlinkingUrlFlowEntity
 *
 * @package UserBundle\Entity
 */
class NetlinkingUrlFlowEntity
{

    /**
     * @var string
     */
    private $url;

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
     * @return NetlinkingUrlFlowEntity
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}