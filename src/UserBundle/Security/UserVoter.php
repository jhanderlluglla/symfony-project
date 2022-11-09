<?php

namespace UserBundle\Security;

use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const ACTION_SHOW_WEBMASTER = 'show_webmaster';
    const ACTION_SHOW_SEO = 'show_seo';
    const ACTION_SHOW_ALL = 'all';
    const ACTION_EDIT = 'edit';
    const ACTION_MODIFY_BALANCE = 'modify_balance';
    const ACTION_DELETE = 'delete';
    const ACTION_ENDIS = 'endis';
    const ACTION_ADD = 'add';
    const ACTION_CHANGE_PASSWORD = "change_password";

    const ACTIONS = [
        self::ACTION_EDIT,
        self::ACTION_DELETE,
        self::ACTION_MODIFY_BALANCE,
        self::ACTION_SHOW_ALL,
        self::ACTION_SHOW_WEBMASTER,
        self::ACTION_SHOW_SEO,
        self::ACTION_ENDIS,
        self::ACTION_ADD,
        self::ACTION_CHANGE_PASSWORD
    ];

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
        if ($parts[0] !== User::class || !in_array($parts[1], self::ACTIONS)) {
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

        $action = $this->explodeAction($attribute)[1];
        switch ($action) {
            case self::ACTION_SHOW_WEBMASTER:
                return $this->accessManager->canManageWebmasterUser();
            case self::ACTION_SHOW_SEO:
                return $this->accessManager->canManageWriterUser();
            case self::ACTION_SHOW_ALL:
                return $this->accessManager->canManageWebmasterUser() && $this->accessManager->canManageWriterUser();
            case self::ACTION_ADD:
            case self::ACTION_EDIT:
            case self::ACTION_ENDIS:
                return ($subject->hasRole([User::ROLE_WRITER, User::ROLE_WRITER_COPYWRITING, User::ROLE_WRITER_NETLINKING]) && $this->accessManager->canManageWriterUser()
                    || ($subject->isWebmaster() && $this->accessManager->canManageWebmasterUser()));
            case self::ACTION_MODIFY_BALANCE:
                return $this->accessManager->canManageEarning();
            case self::ACTION_CHANGE_PASSWORD:
                return $user->isSuperAdmin() || $user === $subject;
            default:
                return false;
        }
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