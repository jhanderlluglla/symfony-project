<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use CoreBundle\Entity\UserSetting;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Class UserSettingRepository
 *
 * @package CoreBundle\Entity
 */
class UserSettingRepository extends BaseRepository implements FilterableRepositoryInterface
{

    /**
     * @var array
     */
    protected $filters = ['user', 'name'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('us');

        $this->prepare($filters, $qb);

        return $qb;
    }


    /**
     * @param User $user
     * @param string $settingName
     *
     * @return null|UserSetting
     */
    public function getSetting(User $user, $settingName)
    {
        try {
            return $this->filter(['user' => $user, 'name' => $settingName])->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
