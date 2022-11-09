<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Job
 *
 * @ORM\Table(name="job")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\JobRepository")
 */
class Job extends AbstractEntityTransaction
{
    use ExternalIdTrait;

    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_EXPIRED_HOLD = 'expired_hold';
    const STATUS_IMPOSSIBLE = 'impossible';  // Old: SUBMISSION_IMPOSSIBLE = 3
    const STATUS_COMPLETED = 'completed';  // Old: SUBMISSION_SUCCESS = 2 | success
    const STATUS_REJECTED = 'rejected';

    const TRANSITION_TAKE_TO_WORK = 'take_to_work';
    const TRANSITION_EXPIRED_HOLD = 'expired_hold';
    const TRANSITION_IMPOSSIBLE = 'impossible';
    const TRANSITION_COMPLETE = 'complete';
    const TRANSITION_REJECT = 'reject';

    public const TRANSACTION_TAG_REJECT = 'job_reject';
    public const TRANSACTION_TAG_BUY = 'job_buy';
    public const TRANSACTION_TAG_REWARD = 'job_reward';
    public const TRANSACTION_TAG_HOLD = 'job_hold';
    public const TRANSACTION_TAG_RETURN_HOLD = 'job_return_hold';

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
     * @ORM\ManyToOne(targetEntity="NetlinkingProject", cascade={"persist"}, inversedBy="jobs")
     * @ORM\JoinColumn(name="netlinking_project_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $netlinkingProject;

    /**
     * @var ScheduleTask $scheduleTask
     *
     * @ORM\OneToOne(targetEntity="ScheduleTask", cascade={"persist"}, inversedBy="job")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $scheduleTask; //we can remove 'nullable=true' in fact, need for backward compatibility with old database

    /**
     * @var User $affectedToUser
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="affected_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     **/
    private $affectedToUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="affected_at", type="datetime", nullable=true)
     */
    private $affectedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": Job::STATUS_NEW})
     */
    private $status = Job::STATUS_NEW;

    /**
     * @var float
     *
     * @ORM\Column(name="cost_writer", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $costWriter;

    /**
     * @var float
     *
     * @ORM\Column(name="cost_webmaster", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $costWebmaster;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $takeAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $completedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $rejectedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rating;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rating_added_at", type="datetime", nullable=true)
     */
    private $ratingAddedAt;

    /**
     * @var NetlinkingProjectComments
     *
     * @ORM\OneToOne(targetEntity="NetlinkingProjectComments", cascade={"persist", "remove"}, mappedBy="job")
     */
    private $netlinkingProjectComment;

    /**
     * @var DirectoryBacklinks
     *
     * @ORM\OneToOne(targetEntity="DirectoryBacklinks", cascade={"persist", "remove"}, mappedBy="job")
     */
    private $directoryBacklink;

    /**
     * @var ExchangeProposition $exchangeProposition
     *
     * @ORM\OneToOne(targetEntity="ExchangeProposition", cascade={"persist", "remove"}, mappedBy="job")
     */
    private $exchangeProposition;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        parent::__construct();

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
     * @return Job
     */
    public function setNetlinkingProject($netlinkingProject)
    {
        $this->netlinkingProject = $netlinkingProject;

        return $this;
    }

    /**
     * @return ScheduleTask
     */
    public function getScheduleTask()
    {
        return $this->scheduleTask;
    }

    /**
     * @param ScheduleTask $scheduleTask
     * @return Job
     */
    public function setScheduleTask($scheduleTask)
    {
        $this->scheduleTask = $scheduleTask;

        return $this;
    }

    /**
     * @return float
     */
    public function getCostWriter()
    {
        return $this->costWriter;
    }

    /**
     * @param float $costWriter
     *
     * @return Job
     */
    public function setCostWriter($costWriter)
    {
        $this->costWriter = $costWriter;

        return $this;
    }

    /**
     * @return float
     */
    public function getCostWebmaster()
    {
        return $this->costWebmaster;
    }

    /**
     * @param float $costWebmaster
     *
     * @return Job
     */
    public function setCostWebmaster($costWebmaster)
    {
        $this->costWebmaster = $costWebmaster;

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
     * @return Job
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
     * @return Job
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTakeAt()
    {
        return $this->takeAt;
    }

    /**
     * @param \DateTime $takeAt
     *
     * @return Job
     */
    public function setTakeAt($takeAt)
    {
        $this->takeAt = $takeAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * @param \DateTime $completedAt
     *
     * @return Job
     */
    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;

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
     * @return Job
     */
    public function setAffectedToUser($affectedToUser)
    {
        $this->affectedToUser = $affectedToUser;

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
     * @return Job
     */
    public function setAffectedAt($affectedAt)
    {
        $this->affectedAt = $affectedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRejectedAt()
    {
        return $this->rejectedAt;
    }

    /**
     * @param bool $rejectedAt
     *
     * @return Job
     */
    public function setRejectedAt($rejectedAt)
    {
        $this->rejectedAt = $rejectedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRating()
    {
        return $this->rating;
    }

    /**
     * @param bool $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
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
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return \DateTime
     */
    public function getRatingAddedAt()
    {
        return $this->ratingAddedAt;
    }

    /**
     * @param \DateTime $ratingAddedAt
     */
    public function setRatingAddedAt($ratingAddedAt)
    {
        $this->ratingAddedAt = $ratingAddedAt;
    }

    /**
     * @return NetlinkingProjectComments
     */
    public function getNetlinkingProjectComment()
    {
        return $this->netlinkingProjectComment;
    }

    /**
     * @param NetlinkingProjectComments $netlinkingProjectComment
     */
    public function setNetlinkingProjectComment($netlinkingProjectComment)
    {
        $this->netlinkingProjectComment = $netlinkingProjectComment;
    }

    /**
     * @return DirectoryBacklinks
     */
    public function getDirectoryBacklink()
    {
        return $this->directoryBacklink;
    }

    /**
     * @param DirectoryBacklinks $directoryBacklink
     */
    public function setDirectoryBacklink($directoryBacklink)
    {
        $this->directoryBacklink = $directoryBacklink;
    }

    /**
     * @return ExchangeProposition
     */
    public function getExchangeProposition()
    {
        return $this->exchangeProposition;
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     */
    public function setExchangeProposition($exchangeProposition)
    {
        $this->exchangeProposition = $exchangeProposition;
    }

    /**
     * @return Transaction|null
     */
    public function getHoldTransaction()
    {
        return $this->getTransactionsByTag(Job::TRANSACTION_TAG_HOLD)->last();
    }

    /**
     * @return int
     */
    public function getWordsCount()
    {
        if (!$this->getScheduleTask()->getDirectory()) {
            return 0;
        }
        $directoryListWordsCount = $this->getNetlinkingProject()->getWordsCount();
        $directoryMinWords = $this->getScheduleTask()->getDirectory()->getMinWordsCount();

        return $directoryListWordsCount > $directoryMinWords ? $directoryListWordsCount : $directoryMinWords;
    }
}
