<?php

namespace CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Criteria;

/**
 * WaitingOrder
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\WaitingOrderRepository")
 */
class WaitingOrder
{

    const STATUS_WAITING = 'waiting';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ASSIGNED = 'assigned';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var CopywritingOrder $copywritingOrder
     *
     * @ORM\OneToOne(targetEntity="CopywritingOrder", inversedBy="waitingOrder", cascade={"persist"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $copywritingOrder;

    /**
     * @var ArrayCollection $candidates
     *
     * @ORM\OneToMany(targetEntity="Candidate", mappedBy="waitingOrder", cascade={"persist", "remove"})
     */
    private $candidates;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices = {
     *          WaitingOrder::STATUS_WAITING,
     *          WaitingOrder::STATUS_ACCEPTED,
     *          WaitingOrder::STATUS_REJECTED,
     *          WaitingOrder::STATUS_ASSIGNED,
     *      }
     * )
     */
    private $status = self::STATUS_WAITING;


    public function __construct()
    {
        $this->candidates = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CopywritingOrder
     */
    public function getCopywritingOrder()
    {
        return $this->copywritingOrder;
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     */
    public function setCopywritingOrder($copywritingOrder)
    {
        $this->copywritingOrder = $copywritingOrder;
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
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return ArrayCollection
     */
    public function getCandidates()
    {
        return $this->candidates;
    }

    /**
     * @param ArrayCollection $candidates
     *
     * @return WaitingOrder
     */
    public function setCandidates($candidates)
    {
        foreach ($candidates as $candidate){
            $candidate->setWaitingOrder($this);
        }
        $this->candidates = $candidates;
        return $this;
    }

    /**
     * Add Candidate
     *
     * @param Candidate $candidate
     *
     * @return WaitingOrder
     */
    public function addCandidate(Candidate $candidate)
    {
        $this->candidates->add($candidate);
        $candidate->setWaitingOrder($this);

        return $this;
    }

    /**
     * Remove Candidate
     *
     * @param Candidate $candidate
     *
     * @return WaitingOrder
     */
    public function removeCandidate(Candidate $candidate)
    {
        $this->candidates->remove($candidate);
        $candidate->setWaitingOrder(null);

        return $this;
    }

    /**
     * @param User $user
     * @return Candidate|null
     */
    public function getCandidateByUser(User $user)
    {
        $expr = Criteria::expr();
        $criteria = new Criteria();
        $criteria
            ->where($expr->andX($expr->eq('user', $user)));

        return $this->getCandidates()->matching($criteria)->first() ?: null;
    }

    /**
     * @return bool
     */
    public function hasAllRejected()
    {
        $expr = Criteria::expr();
        $criteria = new Criteria();
        $criteria
            ->where($expr->andX($expr->neq('action', Candidate::ACTION_REJECT)));

        return $this->getCandidates()->matching($criteria)->first() ? false : true;
    }

    /**
     * @return \DateTime
     */
    public function getDeadline()
    {
        $deadline = new \DateTime();
        foreach ($this->candidates as $candidate){
            if($candidate->getDeadline() > $deadline){
                $deadline = $candidate->getDeadline();
            }
        }

        return $deadline;
    }
}
