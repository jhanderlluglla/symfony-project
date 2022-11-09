<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DirectoryBacklinks
 *
 * @ORM\Table(name="directory_backlinks")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\DirectoryBacklinksRepository")
 */
class DirectoryBacklinks
{

    const STATUS_NOT_FOUND_YET = 'not_found_yet';
    const STATUS_FOUND = 'found';
    const STATUS_NOT_FOUND = 'not_found';

    const STATUS_TYPE_CRON = 'cron';
    const STATUS_TYPE_AUTO = 'auto';
    const STATUS_TYPE_MANUALLY = 'manually';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Job $job
     *
     * @ORM\OneToOne(targetEntity="Job", cascade={"persist"}, inversedBy="directoryBacklink")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $job;

    /**
     * @var string
     *
     * @ORM\Column(name="backlink", type="string", length=300, nullable=true)
     */
    private $backlink;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_checked", type="datetime", nullable=true)
     */
    private $dateChecked;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_checked_first", type="datetime", nullable=true)
     */
    private $dateCheckedFirst;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_found", type="datetime", nullable=true)
     */
    private $dateFound;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="status_type", type="string", length=255, nullable=true)
     */
    private $statusType;

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
    public function getBacklink()
    {
        return $this->backlink;
    }

    /**
     * @param string $backlink
     *
     * @return DirectoryBacklinks
     */
    public function setBacklink($backlink)
    {
        $this->backlink = $backlink;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateChecked()
    {
        return $this->dateChecked;
    }

    /**
     * @param \DateTime $dateChecked
     *
     * @return DirectoryBacklinks
     */
    public function setDateChecked($dateChecked)
    {
        $this->dateChecked = $dateChecked;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCheckedFirst()
    {
        return $this->dateCheckedFirst;
    }

    /**
     * @param \DateTime $dateCheckedFirst
     *
     * @return DirectoryBacklinks
     */
    public function setDateCheckedFirst($dateCheckedFirst)
    {
        $this->dateCheckedFirst = $dateCheckedFirst;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return DirectoryBacklinks
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusType()
    {
        return $this->statusType;
    }

    /**
     * @param string $statusType
     *
     * @return DirectoryBacklinks
     */
    public function setStatusType($statusType)
    {
        $this->statusType = $statusType;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateFound()
    {
        return $this->dateFound;
    }

    /**
     * @param \DateTime $dateFound
     *
     * @return DirectoryBacklinks
     */
    public function setDateFound($dateFound)
    {
        $this->dateFound = $dateFound;

        return $this;
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
     *
     * @return DirectoryBacklinks
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }
}
