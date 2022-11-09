<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;

class AccessManagerExtension extends \Twig_Extension
{

    /**
     * @var AccessManager
     */
    private $accessManager;

    /**
     * TransactionExtensions constructor.
     *
     * @param AccessManager $accessManager
     */
    public function __construct(AccessManager $accessManager)
    {
        $this->accessManager = $accessManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'canAccess',
                [$this, 'canAccess'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'accessManager',
                [$this, 'accessManager'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param $namePermission
     * @param User|null $user
     *
     * @return \string
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    public function canAccess($namePermission, User $user = null)
    {
        return $this->accessManager->canAccess($namePermission, $user);
    }

    /**
     * @return AccessManager
     */
    public function accessManager()
    {
        return $this->accessManager;
    }
}
