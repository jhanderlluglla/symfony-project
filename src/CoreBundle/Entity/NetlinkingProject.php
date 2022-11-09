<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Interfaces\LanguageInterface;
use CoreBundle\Entity\Interfaces\SiteUrlInterface;
use CoreBundle\Entity\Traits\ExternalIdTrait;
use CoreBundle\Entity\Traits\LanguageTrait;
use CoreBundle\Entity\Traits\SiteTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NetlinkingProject
 *
 * @ORM\Table(name="netlinking_project")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\NetlinkingProjectRepository")
 */
class NetlinkingProject implements SiteUrlInterface, LanguageInterface
{
    use ExternalIdTrait;
    use SiteTrait;
    use LanguageTrait;

    const STATUS_NO_START     = 'nostart';
    const STATUS_WAITING      = 'waiting';
    const STATUS_IN_PROGRESS  = 'in_progress';
    const STATUS_FINISHED     = 'finished';
    const STATUS_REJECTED     = 'rejected';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DirectoriesList $directoryList
     *
     * @ORM\ManyToOne(targetEntity="DirectoriesList", cascade={"persist"}, inversedBy="netlinkingProject")
     * @ORM\JoinColumn(name="directory_list_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    private $directoryList;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="exchangeSite")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * @var User $affectedToUser
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="affected_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     **/
    private $affectedToUser;

    /**
     * @var User $affectedByUser
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="affected_by_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     **/
    private $affectedByUser;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     *
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    private $url;

    /**
     * @var integer
     *
     * @ORM\Column(name="frequency_directory", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $frequencyDirectory;

    /**
     * @var integer
     *
     * @ORM\Column(name="frequency_day", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $frequencyDay;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="affected_at", type="datetime", nullable=true)
     */
    private $affectedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var ArrayCollection $anchor
     *
     * @ORM\OneToMany(targetEntity="Anchor", mappedBy="netlinkingProject", cascade={"persist", "remove"})
     */
    private $anchor;

    /**
     * @var Job $jobs
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="netlinkingProject", cascade={"persist", "remove"})
     */
    private $jobs;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": DirectoriesList::CONTAINS_ALL})
     *
     * @Assert\Choice({
     *     DirectoriesList::CONTAINS_ONLY_BLOG,
     *     DirectoriesList::CONTAINS_ONLY_DIRECTORY,
     *     DirectoriesList::CONTAINS_ALL})
     */
    private $containsType = DirectoriesList::CONTAINS_ALL;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private $wordsCount;

    /**
     * @var ArrayCollection $scheduleTasks
     *
     * @ORM\OneToMany(targetEntity="ScheduleTask", mappedBy="netlinkingProject")
     */
    private $scheduleTasks;

    /**
     * NetlinkingProject constructor.
     */
    public function __construct()
    {
        $this->status = self::STATUS_NO_START;

        $this->createdAt = new \DateTime();

        $this->scheduleTasks = new ArrayCollection();
        $this->anchor = new ArrayCollection();
        $this->jobs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return NetlinkingProject
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return User
     */
    public function getAffectedToUser()
    {
        return $this->affectedToUser;
    }

    /**
     * @param User $affectedToUser
     *
     * @return NetlinkingProject
     */
    public function setAffectedToUser($affectedToUser)
    {
        $this->affectedToUser = $affectedToUser;
        return $this;
    }

    /**
     * @return User
     */
    public function getAffectedByUser()
    {
        return $this->affectedByUser;
    }

    /**
     * @param User $affectedByUser
     *
     * @return NetlinkingProject
     */
    public function setAffectedByUser($affectedByUser)
    {
        $this->affectedByUser = $affectedByUser;

        return $this;
    }

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
     * @return NetlinkingProject
     */
    public function setUrl($url)
    {
        $this->url = rtrim($url, '/');

        return $this;
    }

    /**
     * @return int
     */
    public function getFrequencyDirectory()
    {
        return $this->frequencyDirectory;
    }

    /**
     * @param int $frequencyDirectory
     *
     * @return NetlinkingProject
     */
    public function setFrequencyDirectory($frequencyDirectory)
    {
        $this->frequencyDirectory = $frequencyDirectory;

        return $this;
    }

    /**
     * @return int
     */
    public function getFrequencyDay()
    {
        return (int) $this->frequencyDay;
    }

    /**
     * @param int $frequencyDay
     *
     * @return NetlinkingProject
     */
    public function setFrequencyDay($frequencyDay)
    {
        $this->frequencyDay = $frequencyDay;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAffectedAt()
    {
        return $this->affectedAt;
    }

    /**
     * @param \DateTime $affectedAt
     *
     * @return NetlinkingProject
     */
    public function setAffectedAt($affectedAt)
    {
        $this->affectedAt = $affectedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTime $startedAt
     *
     * @return NetlinkingProject
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

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
     * @return NetlinkingProject
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @return NetlinkingProject
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param \DateTime $finishedAt
     *
     * @return NetlinkingProject
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;

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
     * @param string $comment
     *
     * @return NetlinkingProject
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

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
     * @return NetlinkingProject
     */
    public function setDirectoryList($directoryList)
    {
        $this->directoryList = $directoryList;
        $this->setContainsType($directoryList->getContainsType());
        $this->setWordsCount($directoryList->getWordsCount());

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @param ArrayCollection $anchor
     *
     * @return NetlinkingProject
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;

        return $this;
    }

    /**
     * @return array
     */
    public function getAnchorList()
    {
        $anchors = [];

        /** @var Anchor $anchor */
        foreach ($this->anchor as $anchor) {
            $anchors[] = $anchor->getName();
        }

        return $anchors;
    }

    /**
     * Add anchor
     *
     * @param Anchor $anchor
     *
     * @return NetlinkingProject
     */
    public function addAnchor(Anchor $anchor)
    {
        if (!$this->anchor->contains($anchor)) {
            $this->anchor->add($anchor);

            $anchor->setNetlinkingProject($this);
        }

        return $this;
    }

    /**
     * Remove anchor
     *
     * @param Anchor $anchor
     */
    public function removeAnchor(Anchor $anchor)
    {
        if ($this->anchor->contains($anchor)) {
            $this->anchor->removeElement($anchor);
        }

        return $this;
    }

    /**
     * @return Job
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * @param $jobs
     * @return NetlinkingProject
     */
    public function setJobs($jobs)
    {
        $this->jobs = $jobs;

        return $this;
    }

    /**
     * Add job
     *
     * @param Job $job
     *
     * @return NetlinkingProject
     */
    public function addJobs(Job $job)
    {
        if (!$this->jobs->contains($job)) {
            $this->jobs->add($job);

            $job->setNetlinkingProject($this);
        }

        return $this;
    }

    /**
     * Remove job
     *
     * @param Job $job
     */
    public function removeJobs(Job $job)
    {
        if ($this->jobs->contains($job)) {
            $this->jobs->removeElement($job);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return NetlinkingProject
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return array
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_NO_START,
            self::STATUS_WAITING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_FINISHED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * @return string
     */
    public function getContainsType()
    {
        return $this->containsType;
    }

    /**
     * @param string $containsType
     *
     * @return NetlinkingProject
     */
    public function setContainsType($containsType)
    {
        $this->containsType = $containsType;

        return $this;
    }

    /**
     * @return int
     */
    public function getWordsCount()
    {
        return $this->wordsCount;
    }

    /**
     * @param int $countWords
     *
     * @return NetlinkingProject
     */
    public function setWordsCount($countWords)
    {
        $this->wordsCount = $countWords;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getScheduleTasks()
    {
        return $this->scheduleTasks;
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    public function getLateDays()
    {
        return $this->affectedAt->diff(new \DateTime())->days;
    }
}
