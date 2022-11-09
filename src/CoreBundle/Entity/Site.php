<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Interfaces\LanguageInterface;
use CoreBundle\Entity\Traits\LanguageTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Site
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\SiteRepository")
 */
class Site implements LanguageInterface
{
    use LanguageTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $host;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     */
    private $scheme;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updateMetricsAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return Site
     */
    public function setHost(?string $host): Site
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     *
     * @return Site
     */
    public function setScheme(?string $scheme): Site
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return preg_replace('~^www.~ui', '', $this->host);
    }

    /**
     * @return \DateTime
     */
    public function getUpdateMetricsAt(): ?\DateTime
    {
        return $this->updateMetricsAt;
    }

    /**
     * @param \DateTime $updateMetricsAt
     *
     * @return Site
     */
    public function setUpdateMetricsAt(?\DateTime $updateMetricsAt): Site
    {
        $this->updateMetricsAt = $updateMetricsAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getScheme().'://'.$this->getHost();
    }
}
