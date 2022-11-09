<?php

namespace UserBundle\Security;

use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DirectoryBacklinksVoter extends Voter
{
    const ACTION_SHOW = 'show';

    const ACTIONS = [self::ACTION_SHOW];

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var AccessManager */
    private $accessManager;

    /**
     * BacklinksVoter constructor.
     *
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
        if ($parts[0] !== DirectoryBacklinks::class || !in_array($parts[1], self::ACTIONS)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param User $subject
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

        if ($user->isWriterAdmin() && !$this->accessManager->canManageNetlinkingProject()) {
            return false;
        }

        return true;


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