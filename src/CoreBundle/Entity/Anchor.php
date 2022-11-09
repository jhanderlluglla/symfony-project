<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Anchors
 *
 * @ORM\Table(name="anchor")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\AnchorRepository")
 */
class Anchor
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var NetlinkingProject $netlinkingProject
     *
     * @ORM\ManyToOne(targetEntity="NetlinkingProject", cascade={"persist"}, inversedBy="anchor")
     * @ORM\JoinColumn(name="netlinking_project_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $netlinkingProject;

    /**
     * @var Directory $directory
     *
     * @ORM\ManyToOne(targetEntity="Directory", cascade={"persist"}, inversedBy="anchor")
     * @ORM\JoinColumn(name="directory_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $directory;

    /**
     * @var ExchangeSite
     *
     * @ORM\ManyToOne(targetEntity="ExchangeSite", cascade={"persist"}, inversedBy="anchor")
     * @ORM\JoinColumn(name="exchange_site_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $exchangeSite;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text")
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Anchor constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return NetlinkingProject
     */
    public function getNetlinkingProject()
    {
        return $this->netlinkingProject;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return Anchor
     */
    public function setNetlinkingProject($netlinkingProject)
    {
        $this->netlinkingProject = $netlinkingProject;

        return $this;
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
     *
     * @return Anchor
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Anchor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return Anchor
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
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
     * @return $this
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;

        return $this;
    }
}
