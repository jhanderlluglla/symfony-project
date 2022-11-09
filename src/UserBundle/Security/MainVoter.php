<?php

namespace UserBundle\Security;

use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;
use CoreBundle\Entity\CopywritingOrder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MainVoter extends Voter
{
    const SHOW_LIST = 'show_list';
    const SHOW = 'show';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const ENDIS = 'endis';
    const IMPOSSIBLE = 'impossible';

    const EXCHANGE_SITE = 'exchangeSite';
    const COPYWRITING_ARTICLE = 'copywritingArticle';
    const COPYWRITING_ORDER = 'copywritingOrder';
    const COPYWRITING_PROJECT = 'copywritingProject';
    const DIRECTORY_LIST = 'directoryList';
    const AFFILIATION = 'affiliation';
    const COPYWRITING_SITES = 'copywritingSites';

    const ACTIONS = [self::SHOW, self::EDIT, self::DELETE, self::ENDIS, self::SHOW_LIST, self::IMPOSSIBLE];
    const PREFIXES = [
        self::EXCHANGE_SITE,
        self::COPYWRITING_ARTICLE,
        self::COPYWRITING_ORDER,
        self::COPYWRITING_PROJECT,
        self::DIRECTORY_LIST,
        self::AFFILIATION,
        self::COPYWRITING_SITES,
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
        if (!in_array($parts[0], self::PREFIXES) || !in_array($parts[1], self::ACTIONS)) {
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
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $prefix = $this->explodeAction($attribute)[0];
        $action = $this->explodeAction($attribute)[1];
        switch ($prefix) {
            case self::EXCHANGE_SITE:
                return $subject->getUser() === $user;

            case self::COPYWRITING_ARTICLE:
                return $subject->getOrder()->getCustomer() === $user
                    ||
                    ($subject->getOrder()->getCopywriter() === $user && in_array($subject->getOrder()->getStatus(), [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED]))
                    ||
                    $this->accessManager->canManageCopywritingProject($user);

            case self::COPYWRITING_ORDER:
                switch ($action) {
                    case self::SHOW_LIST:
                        return !$user->hasRole(User::ROLE_WRITER_NETLINKING) && !($user->hasRole(User::ROLE_WRITER_ADMIN) && !$this->accessManager->canManageCopywritingProject());
                    case self::DELETE:
                        return $this->accessManager->canManageCopywritingProject($user) || ($subject->getCustomer() === $user && $subject->getStatus() == CopywritingOrder::STATUS_WAITING);
                    case self::IMPOSSIBLE:
                        return $this->accessManager->canManageCopywritingProject($user) || ($subject->getCopywriter() === $user && in_array($subject->getStatus(), [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED]));
                    default:
                        return
                            $subject->getCustomer() === $user
                            || $this->accessManager->canManageCopywritingProject($user);
                }
            case self::COPYWRITING_PROJECT:
                return $subject->getCustomer() === $user;

            case self::DIRECTORY_LIST:
                return $subject->getUser() === $user;

            case self::AFFILIATION:
                return $user->isShowAffiliation();

            case self::COPYWRITING_SITES:
                return $subject->getUser() === $user;
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
