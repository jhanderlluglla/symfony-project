<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\User;
use CoreBundle\Entity\UserSetting;
use CoreBundle\Exceptions\UnknownUserSetting;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AccessManager
{
    /** @var TokenStorage */
    private $tokenStorage;

    /** @var UserSettingService */
    private $userSettingService;

    public function __construct(TokenStorage $tokenStorage, UserSettingService $userSettingService)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userSettingService = $userSettingService;
    }

    /**
     * @param User|null $user
     *
     * @return User
     */
    private function getUser(User $user = null)
    {
        if (!$user) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return $user;
    }

    /**
     * @param string $namePermission
     * @param User|null $user
     *
     * @return bool
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    public function canAccess($namePermission, User $user = null)
    {
        $user = $this->getUser($user);

        if ($this->userSettingService->getValue($namePermission, $user) === '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $namePermission
     * @param bool $value
     * @param User|null $user
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setAccess($namePermission, $value, User $user = null)
    {
        $user = $this->getUser($user);
        $this->userSettingService->setValue($namePermission, $value === true ? '1' : '0', $this->getUser($user));
    }

    /**
     * @param User|null $user
     * @param array $namePermissions
     *
     * @return array
     */
    public function getPermissionList(User $user = null, $namePermissions = null)
    {
        $user = $this->getUser($user);

        if ($namePermissions === null) {
            $namePermissions = UserSetting::getPermissions();
        }

        $permissions = [];

        /** @var UserSetting $setting */
        foreach ($this->userSettingService->getSettings($namePermissions, $user) as $setting) {
            $permissions[$setting->getName()] = $setting->getValue() === '1' ? true : false;
        }

        foreach (array_diff($namePermissions, array_keys($permissions)) as $item) {
            $permissions[$item] = false;
        }

        return $permissions;
    }

    private function canActionWriterAdmin($namePermission, User $user = null)
    {
        $user = $this->getUser($user);

        try {
            return $user->isSuperAdmin() || ($user->isWriterAdmin() && $this->canAccess($namePermission, $user));
        } catch (UnknownUserSetting $e) {
            return false;
        }
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canManageCopywritingProject(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_MANAGE_COPYWRITING_PROJECT, $user);
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canManageNetlinkingProject(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_MANAGE_NETLINKING_PROJECT, $user);
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canManageWebmasterUser(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_MANAGE_WEBMASTER_USER, $user);
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canManageWriterUser(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_MANAGE_WRITER_USER, $user);
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canManageEarning(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_MANAGE_EARNING, $user);
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function canAnswerMessage(User $user = null)
    {
        return $this->canActionWriterAdmin(UserSetting::PERMISSION_ANSWER_MESSAGE, $user);
    }
}
