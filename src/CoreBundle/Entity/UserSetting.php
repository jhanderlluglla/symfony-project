<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserSetting
 *
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\UserSettingRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="user_type_unq", columns={"user_id", "name"})
 *     }
 * )
 */
class UserSetting
{
    public const PREFIX_FOR_SETTING = 'user_';

    public const NOTIFICATION_PROPOSAL_FREQUENCY = 'notification_proposal_frequency';

    public const PERMISSION_MANAGE_COPYWRITING_PROJECT = 'permission_manage_copywriting_project';
    public const PERMISSION_MANAGE_NETLINKING_PROJECT = 'permission_manage_netlinking_project';
    public const PERMISSION_MANAGE_WEBMASTER_USER = 'permission_manage_webmaster_user';
    public const PERMISSION_MANAGE_WRITER_USER = 'permission_manage_writer_user';
    public const PERMISSION_ANSWER_MESSAGE = 'permission_answer_message';
    public const PERMISSION_MANAGE_EARNING = 'permission_manage_earning';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, inversedBy="settings")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     */
    private $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return UserSetting
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Use the UserSettingService to set and get the value !!!!! Do not use UserSetting::getValue
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Use the UserSettingService to set and get the value !!!!! Do not use UserSetting::setValue
     *
     * @param string $value
     *
     * @return UserSetting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return UserSetting
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string[]
     */
    public static function getSettings()
    {
        return [UserSetting::NOTIFICATION_PROPOSAL_FREQUENCY];
    }

    /**
     * @return string[]
     */
    public static function getPermissions()
    {
        return [
            UserSetting::PERMISSION_MANAGE_COPYWRITING_PROJECT,
            UserSetting::PERMISSION_MANAGE_NETLINKING_PROJECT,
            UserSetting::PERMISSION_MANAGE_WEBMASTER_USER,
            UserSetting::PERMISSION_MANAGE_WRITER_USER,
            UserSetting::PERMISSION_ANSWER_MESSAGE,
            UserSetting::PERMISSION_MANAGE_EARNING,
        ];
    }
}
