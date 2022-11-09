<?php
namespace UserBundle\Security;

use CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SearchVoter extends Voter
{
    const SEARCH_NETLINKING_PROJECT = 'search.netlinking_project';
    const SEARCH_USERS = 'search.users';
    const SEARCH_EXCHANGE_SITE = 'search.exchange_site';

    const ACTIONS = [
        self::SEARCH_NETLINKING_PROJECT,
        self::SEARCH_USERS,
        self::SEARCH_EXCHANGE_SITE,
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ACTIONS)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isSuperAdmin() && $attribute === self::SEARCH_NETLINKING_PROJECT) {
            return true;
        }

        return false;
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
