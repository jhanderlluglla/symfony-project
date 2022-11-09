<?php

namespace UserBundle\Entity;

use CoreBundle\Entity\Anchor;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\NetlinkingProject;

/**
 * Class NetlinkingFlowEntity
 *
 * @package UserBundle\Entity
 */
class NetlinkingFlowEntity
{

    /**
     * @var DirectoriesList
     */
    private $directoryList;

    /**
     * @var NetlinkingUrlFlowEntity[]
     */
    private $urls = [];

    /**
     * @var integer
     */
    private $frequencyDirectory;

    /**
     * @var integer
     */
    private $frequencyDay;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var NetlinkingUrlAnchorsFlowEntity[]
     */
    private $urlAnchors = [];

    /**
     * @return DirectoriesList
     */
    public function getDirectoryList()
    {
        return $this->directoryList;
    }

    /**
     * @param DirectoriesList $directoryList
     *
     * @return NetlinkingFlowEntity
     */
    public function setDirectoryList($directoryList)
    {
        $this->directoryList = $directoryList;

        return $this;
    }

    /**
     * @return NetlinkingUrlFlowEntity[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param mixed $urls
     *
     * @return NetlinkingFlowEntity
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;

        return $this;
    }

    /**
     * @param NetlinkingUrlFlowEntity $url
     *
     * @return NetlinkingFlowEntity
     */
    public function addUrls($url)
    {
        $this->urls[] = $url;

        return $this;
    }

    /**
     * @return integer
     */
    public function getFrequencyDirectory()
    {
        return $this->frequencyDirectory;
    }

    /**
     * @param mixed $frequencyDirectory
     *
     * @return NetlinkingFlowEntity
     */
    public function setFrequencyDirectory($frequencyDirectory)
    {
        $this->frequencyDirectory = $frequencyDirectory;

        return $this;
    }

    /**
     * @return integer
     */
    public function getFrequencyDay()
    {
        return $this->frequencyDay;
    }

    /**
     * @param mixed $frequencyDay
     *
     * @return NetlinkingFlowEntity
     */
    public function setFrequencyDay($frequencyDay)
    {
        $this->frequencyDay = $frequencyDay;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     *
     * @return NetlinkingFlowEntity
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return array
     */
    public function getUrlAnchors()
    {
        return $this->urlAnchors;
    }

    /**
     * @param array $urlAnchors
     *
     * @return NetlinkingFlowEntity
     */
    public function setUrlAnchors($urlAnchors)
    {
        $this->urlAnchors = $urlAnchors;

        return $this;
    }

    /**
     * @param NetlinkingUrlAnchorsFlowEntity $urlAnchor
     *
     * @return NetlinkingFlowEntity
     */
    public function addUrlAnchors($urlAnchor)
    {
        $this->urlAnchors[] = $urlAnchor;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return null|NetlinkingUrlAnchorsFlowEntity
     */
    public function hasUrlAnchor($url)
    {
        /** @var NetlinkingUrlAnchorsFlowEntity $urlAnchor */
        foreach ($this->getUrlAnchors() as $urlAnchor) {
            if ($urlAnchor->getUrl() == $url) {
                return $urlAnchor;
            }
        }

        return null;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     */
    public function fill($netlinkingProject)
    {
        $netlinkingUrlAnchorsFlowEntity = new NetlinkingUrlAnchorsFlowEntity();
        $netlinkingUrlAnchorsFlowEntity->setUrl($netlinkingProject->getUrl());

        $anchors = $netlinkingProject->getAnchor();

        $anchorsArr = [];
        $directoryAnchors = [];
        $exchangeSitesAnchors = [];

        /** @var Anchor $anchor */
        foreach ($anchors as $anchor) {
            $netlinkingAnchorFlowEntity = new NetlinkingAnchorFlowEntity();
            $netlinkingAnchorFlowEntity->setAnchor($anchor->getName());

            if(!is_null($anchor->getDirectory())){
                $netlinkingAnchorFlowEntity
                    ->setUrl($anchor->getDirectory()->getName())
                    ->setWebmasterAnchor($anchor->getDirectory()->getWebmasterAnchor())
                    ->setDirectory($anchor->getDirectory()->getId())
                ;
                $directoryAnchors[$anchor->getDirectory()->getId()] = $netlinkingAnchorFlowEntity;

            }

            if(!is_null($anchor->getExchangeSite())){
                $netlinkingAnchorFlowEntity
                    ->setUrl($anchor->getExchangeSite()->getUrl())
                    ->setWebmasterAnchor($anchor->getExchangeSite()->getWebmasterAnchor())
                    ->setExchangeSite($anchor->getExchangeSite()->getId())
                ;
                $exchangeSitesAnchors[$anchor->getExchangeSite()->getId()] = $netlinkingAnchorFlowEntity;
            }

            $anchorsArr[] = $netlinkingAnchorFlowEntity;
        }

        $netlinkingUrlAnchorsFlowEntity
            ->setAnchors($anchorsArr)
            ->setDirectoryAnchors($directoryAnchors)
            ->setExchangeAnchors($exchangeSitesAnchors)
        ;

        $netlinkingUrlFlowEntity = new NetlinkingUrlFlowEntity();
        $netlinkingUrlFlowEntity->setUrl($netlinkingProject->getUrl());

        $this
            ->setDirectoryList($netlinkingProject->getDirectoryList())
            ->setFrequencyDirectory($netlinkingProject->getFrequencyDirectory())
            ->setFrequencyDay($netlinkingProject->getFrequencyDay())
            ->setComment($netlinkingProject->getComment())
            ->addUrlAnchors($netlinkingUrlAnchorsFlowEntity)
            ->addUrls($netlinkingUrlFlowEntity)
        ;
    }

    /**
     * @param integer $directoryId
     *
     * @return string
     */
    public function getAnchorSpecificDirectory($directoryId)
    {

        /** @var NetlinkingUrlAnchorsFlowEntity $urlAnchor */
        foreach ($this->getUrlAnchors() as $urlAnchor) {

            /** @var NetlinkingAnchorFlowEntity $anchor */
            foreach ($urlAnchor->getAnchors() as $anchor) {
                if ($anchor->getDirectory() == $directoryId) {
                    return $anchor->getAnchor();
                }
            }
        }

        return '';
    }
}
