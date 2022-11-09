<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Comission
 *
 * @ORM\Table(name="comission", uniqueConstraints={@ORM\UniqueConstraint(name="submission_unique", columns={"user_id","netlinking_project_id","directory_id"})})
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ComissionRepository")
 */
class Comission
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
     * @ORM\ManyToOne(targetEntity="NetlinkingProject", cascade={"persist"})
     * @ORM\JoinColumn(name="netlinking_project_id", referencedColumnName="id")
     **/
    private $netlinkingProject;

    /**
     * @var Directory $directory
     *
     * @ORM\ManyToOne(targetEntity="Directory")
     * @ORM\JoinColumn(name="directory_id", referencedColumnName="id")
     **/
    private $directory;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="comission")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     **/
    private $user;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Comission constructor.
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
     * @return Comission
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
     * @return Comission
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Comission
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return Comission
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

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
     * @return Comission
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}