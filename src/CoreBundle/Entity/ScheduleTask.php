<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ScheduleTask
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ScheduleTaskRepository")
 */
class ScheduleTask
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
     * @var Directory $directory
     *
     * @ORM\ManyToOne(targetEntity="Directory")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $directory;

    /**
     * @var ExchangeSite $exchangeSite
     *
     * @ORM\ManyToOne(targetEntity="ExchangeSite")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $exchangeSite;

    /**
     * @var NetlinkingProject $netlinkingProject
     *
     * @ORM\ManyToOne(targetEntity="NetlinkingProject", inversedBy="scheduleTasks", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $netlinkingProject;

    /**
     * @var Job $job
     *
     * @ORM\OneToOne(targetEntity="Job", cascade={"persist"}, mappedBy="scheduleTask")
     */
    private $job;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $startAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
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
     * @return NetlinkingProject
     */
    public function getNetlinkingProject()
    {
        return $this->netlinkingProject;
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     */
    public function setNetlinkingProject($netlinkingProject)
    {
        $this->netlinkingProject = $netlinkingProject;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * @return \DateTime
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * @param \DateTime $startAt
     */
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;
    }

    /**
     * @return string
     */
    public function getDaysDifference()
    {
        $today = (new \DateTime('today midnight'));
        return $this->startAt->setTime(0,0,0)->diff($today)->days;
    }

    /**
     * @return string
     */
    public function getTaskUrl()
    {
        if ($this->exchangeSite !== null) {
            return $this->exchangeSite->getUrl();
        } elseif ($this->directory !== null) {
            return $this->directory->getName();
        }

        return "";
    }
}
