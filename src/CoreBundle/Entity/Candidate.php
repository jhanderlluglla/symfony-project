<?php

namespace CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Candidate
 *
 * @ORM\Table(name="candidate")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CandidateRepository")
 */
class Candidate
{
    const ACTION_ACCEPT = "accept";
    const ACTION_REJECT = "reject";
    const ACTION_ASSIGNED_EXPIRED = "assigned";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     */
    private $user;

    /**
     * @var WaitingOrder $waitingOrder
     *
     * @ORM\ManyToOne(targetEntity="WaitingOrder", cascade={"persist"}, inversedBy="candidates")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     **/
    private $waitingOrder;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $deadline;

    /**
     * @var string $action
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice(
     *      choices = {
     *          Candidate::ACTION_ACCEPT,
     *          Candidate::ACTION_REJECT,
     *      }
     * )
     */
    private $action;

    /**
     * Get id
     *
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
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return WaitingOrder
     */
    public function getWaitingOrder()
    {
        return $this->waitingOrder;
    }

    /**
     * @param WaitingOrder $waitingOrder
     *
     * @return Candidate
     */
    public function setWaitingOrder($waitingOrder)
    {
        $this->waitingOrder = $waitingOrder;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * @param \DateTime $deadline
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }
}

