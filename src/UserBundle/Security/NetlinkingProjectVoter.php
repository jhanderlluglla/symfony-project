<?php

namespace UserBundle\Security;

use CoreBundle\Entity\Job;
use CoreBundle\Services\AccessManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;

/**
 * Class NetlinkingProjectVoter
 *
 * @package UserBundle\Voter
 */
class NetlinkingProjectVoter extends Voter
{
    const VIEW   = 'view';
    const EDIT   = 'edit';
    const DELETE = 'delete';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var AccessManager */
    private $accessManager;

    /** @var EntityManager */
    private $em;


    /**
     * NetlinkingProjectVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     * @param AccessManager $accessManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, AccessManager $accessManager, EntityManager $entityManager)
    {
        $this->decisionManager = $decisionManager;
        $this->accessManager = $accessManager;
        $this->em = $entityManager;
    }

    /**
     * @param string            $attribute
     * @param NetlinkingProject $subject
     *
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof NetlinkingProject) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param NetlinkingProject $subject
     * @param TokenInterface $token
     *
     * @return bool
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($this->decisionManager->decide($token, [User::ROLE_SUPER_ADMIN]) || $this->accessManager->canManageNetlinkingProject($user)) {
            return true;
        }

        /** @var NetlinkingProject $netlinkingProject */
        $netlinkingProject = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($netlinkingProject, $user);
            case self::EDIT:
                return $this->canEdit($netlinkingProject, $user);
            case self::DELETE:
                return $this->canDelete($netlinkingProject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param User $user
     *
     * @return bool
     */
    private function canView(NetlinkingProject $netlinkingProject, User $user)
    {
        if (($user === $netlinkingProject->getUser()) || ($user === $netlinkingProject->getAffectedToUser())) {
            return true;
        } else {
            try {
                return $this->em->getRepository(Job::class)
                    ->filter(['netlinkingProject' => $netlinkingProject, 'affectedToUser' => $user])
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult() ? true : false;
            } catch (NonUniqueResultException $e) {
                return false;
            }
        }
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param User              $user
     *
     * @return bool
     */
    private function canEdit(NetlinkingProject $netlinkingProject, User $user)
    {
        return $user === $netlinkingProject->getUser();
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param User              $user
     *
     * @return bool
     */
    private function canDelete(NetlinkingProject $netlinkingProject, User $user)
    {
        return $this->canEdit($netlinkingProject, $user);
    }
}