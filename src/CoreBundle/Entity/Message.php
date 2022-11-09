<?php


namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message
 *
 * @ORM\Table(name="message")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\MessageRepository")
 */
class Message
{
    use ExternalIdTrait;

    const READ_YES = 1;
    const READ_NO = 0;

    const RECIPIENT_GROUP_TYPE_ALL           = 'all';
    const RECIPIENT_GROUP_TYPE_ADMINISTRATOR = 'administrator';
    const RECIPIENT_GROUP_TYPE_SEO           = 'seo';
    const RECIPIENT_GROUP_TYPE_WEBMASTER     = 'webmaster';

    const MESSAGE_TYPE_INCOMING = 'incoming';
    const MESSAGE_TYPE_OUTGOING = 'outgoing';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Message")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parentMessageId;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="send_user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $sendUser;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="receive_user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $receiveUser;

    /**
     * @ORM\Column(name="subject", type="string", length=255)
     * @Assert\NotBlank()
     **/
    private $subject;

    /**
     * @ORM\Column(name="content", type="text")
     * @Assert\NotBlank()
     **/
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_read", type="boolean", nullable=true)
     */
    private $isRead;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    private $answered = false;

    /**
     * @var \DateTime $readAt
     *
     * @ORM\Column(name="read_at", type="datetime", nullable=true)
     */
    private $readAt;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $taken = false;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();

        $this->isRead = self::READ_NO;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Message
     */
    public function getParentMessageId()
    {
        return $this->parentMessageId;
    }

    /**
     * @param Message $parentMessageId
     *
     * @return Message
     */
    public function setParentMessageId($parentMessageId)
    {
        $this->parentMessageId = $parentMessageId;

        return $this;
    }

    /**
     * @return User
     */
    public function getSendUser()
    {
        return $this->sendUser;
    }

    /**
     * @param User $sendUser
     *
     * @return Message
     */
    public function setSendUser($sendUser)
    {
        $this->sendUser = $sendUser;

        return $this;
    }

    /**
     * @return User
     */
    public function getReceiveUser()
    {
        return $this->receiveUser;
    }

    /**
     * @param User $receiveUser
     *
     * @return Message
     */
    public function setReceiveUser($receiveUser)
    {
        $this->receiveUser = $receiveUser;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     *
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return int
     */
    public function getIsRead()
    {
        return $this->isRead == self::READ_YES;
    }

    /**
     * @param int $isRead
     *
     * @return Message
     */
    public function setIsRead($isRead)
    {
        $this->isRead = $isRead;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReadAt()
    {
        return $this->readAt;
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
     * @return Message
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @param mixed $readAt
     *
     * @return Message
     */
    public function setReadAt($readAt)
    {
        $this->readAt = $readAt;

        return $this;
    }

    /**
     * @param integer $read
     * @param User    $user
     *
     * @return string
     */
    public function getClassIsRead($read, $user)
    {
        if($read == self::READ_YES || $this->isUserSender($user)) {
            return 'read';
        }

        return 'unread';
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isUserReceiver($user){
        if($this->getReceiveUser() == $user){
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isUserSender($user){
        if($this->getSendUser() == $user){
            return true;
        }
        return false;
    }

    /**
     * @param bool $taken
     */
    public function setTaken($taken)
    {
        $this->taken = $taken;
    }

    public function isTaken()
    {
        return $this->taken;
    }

    /**
     * @return bool
     */
    public function isAnswered()
    {
        return $this->answered;
    }

    /**
     * @param bool $isAnswered
     *
     * @return Message
     */
    public function setAnswered($isAnswered)
    {
        $this->answered = $isAnswered;

        return $this;
    }
}
