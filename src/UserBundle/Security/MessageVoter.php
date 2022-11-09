<?php

namespace UserBundle\Security;

use CoreBundle\Entity\Message;
use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MessageVoter extends Voter
{
    const ACTION_SHOW = 'show';
    const ACTION_REPLY = 'reply';

    const ACTIONS = [self::ACTION_SHOW, self::ACTION_REPLY];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /** @var AccessManager */
    private $accessManager;

    /**
     * InvoiceVoter constructor.
     * @param AccessDecisionManagerInterface $decisionManager
     * @param AccessManager $accessManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, AccessManager $accessManager)
    {
        $this->decisionManager = $decisionManager;
        $this->accessManager = $accessManager;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        $parts = $this->explodeAction($attribute);
        if ($parts[0] !== Message::class || !in_array($parts[1], self::ACTIONS)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Message $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $action = $this->explodeAction($attribute)[1];
        switch ($action) {
            case self::ACTION_SHOW:
            case self::ACTION_REPLY:
                $belong = $subject->getSendUser() === $user || $subject->getReceiveUser() === $user;
                if ($belong && !$subject->isTaken()) {
                    return true;
                }
                if ($subject->isTaken()) {
                    if ($user->isWriterAdmin()) {
                        return $this->accessManager->canAnswerMessage() && ($belong || $subject->getSendUser()->isWebmaster() || $subject->getReceiveUser()->isWebmaster());
                    } else {
                        return $belong;
                    }
                } else {
                    return (($subject->getSendUser()->isSuperAdmin() && $subject->getReceiveUser()->isWebmaster()) || ($subject->getReceiveUser()->isSuperAdmin() && $subject->getSendUser()->isWebmaster()))
                            && $this->accessManager->canAnswerMessage();
                }
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param string $action
     * @return array
     */
    protected function explodeAction($action)
    {
        return explode('.', $action);
    }
}