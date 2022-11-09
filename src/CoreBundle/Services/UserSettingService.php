<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Entity\UserSetting;
use CoreBundle\Exceptions\UnknownUserSetting;
use CoreBundle\Repository\UserSettingRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class UserSettingService
 *
 * @package CoreBundle\Services
 */
class UserSettingService
{
    /** @var EntityManager */
    private $em;

    /** @var TokenStorage */
    private $tokenStorage;

    /**
     * UserSettingService constructor.
     *
     * @param EntityManager $em
     * @param TokenStorage $tokenStorage
     */
    public function __construct(EntityManager $em, TokenStorage $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    private function getUser(User $user = null)
    {
        if ($user === null) {
            $user = $this->tokenStorage->getToken()->getUser();

            if ($user === null) {
                throw new UnprocessableEntityHttpException();
            }
        }

        return $user;
    }

    /**
     * @param $settingName
     * @param User|null $user
     * @return UserSetting|string
     *
     * @throws UnknownUserSetting
     */
    private function getUserSetting($settingName, User $user = null)
    {
        $user = $this->getUser($user);

        $userSetting = $this->em->getRepository(UserSetting::class)->getSetting($user, $settingName);

        if ($userSetting) {
            return $userSetting;
        }

        try {
            /** @var Settings $setting */
            $setting = $this->em->getRepository(Settings::class)->filter(['identificator' => UserSetting::PREFIX_FOR_SETTING.$settingName])->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
        }

        if (!$setting) {
            throw new UnknownUserSetting($settingName);
        }

        $userSetting = new UserSetting();
        $userSetting->setName($settingName);
        $userSetting->setValue($setting->getValue());
        $userSetting->setUser($user);

        return $userSetting;
    }

    /**
     * @param $settingName
     * @param User|null $user
     *
     * @return null
     *
     * @throws UnknownUserSetting
     */
    public function getValue($settingName, User $user = null)
    {
        $userSetting = $this->getUserSetting($settingName, $user);

        if (!$userSetting) {
            throw new UnknownUserSetting($settingName);
        }

        return $userSetting->getValue();
    }

    /**
     * @param $settingName
     * @param $value
     * @param User|null $user
     *
     * @return null
     *
     * @throws UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setValue($settingName, $value, User $user = null)
    {
        $userSetting = $this->getUserSetting($settingName, $user);

        if (!$userSetting) {
            throw new UnknownUserSetting($settingName);
        }

        if ($value != $userSetting->getValue()) {
            $userSetting->setValue($value);
            $this->em->persist($userSetting);
            $this->em->flush();
        }
    }

    /**
     * @param null $settingsName
     * @param User|null $user
     *
     * @return array|null
     */
    public function getSettings($settingsName = null, User $user = null)
    {
        $userSettingRepository = $this->em->getRepository(UserSetting::class);

        $setProperties = [];
        $array = [];

        $user = $this->getUser($user);
        if ($user->getId()) {
            if (!$settingsName) {
                $qb = $userSettingRepository->filter(['user' => $user]);
            } else {
                $dbSettingNames = [];
                foreach ($settingsName as $name) {
                    $dbSettingNames[] = UserSetting::PREFIX_FOR_SETTING . $name;
                }
                $qb = $userSettingRepository->filter(['user' => $user]);
            }

            $array = $qb->getQuery()->getResult();
            /** @var UserSetting $value */
            foreach ($array as $value) {
                $setProperties[] = $value->getName();
            }
        }
        $diff = array_diff($settingsName, $setProperties);

        if (count($diff) === 0) {
            return $array;
        }

        foreach ($diff as $k => $value) {
            $diff[$k] = UserSetting::PREFIX_FOR_SETTING . $value;
        }

        $defaultSettings = $this->em->getRepository(Settings::class)->filter(['identificator' => $diff])->getQuery()->getResult();

        /** @var Settings $setting */
        foreach ($defaultSettings as $setting) {
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
            $userSetting->setName(preg_replace('~^' . UserSetting::PREFIX_FOR_SETTING .'~', '', $setting->getIdentificator()));
            $userSetting->setValue($setting->getValue());
            $array[] = $userSetting;
        }

        return $array;
    }
}
