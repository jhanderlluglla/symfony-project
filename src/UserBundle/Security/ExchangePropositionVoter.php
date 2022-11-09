<?php

namespace UserBundle\Security;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ExchangePropositionVoter extends AbstractVoter
{
    /**
     * @param string $attribute
     * @param ExchangeProposition $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ACTION_DELETE:
                return $user->isWriterAdmin() && $this->accessManager->canManageNetlinkingProject();
            case self::ACTION_SHOW_LIST:
                return !($user->isWriterAdmin() && !$this->accessManager->canManageNetlinkingProject());
        }

        throw new \LogicException('This code should not be reached!');
    }
}
