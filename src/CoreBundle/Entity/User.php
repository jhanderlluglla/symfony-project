<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\Traits\ExternalIdTrait;
use CoreBundle\Exceptions\UnknownNotificationName;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Index;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user", indexes={@Index(columns={"email", "full_name"}, flags={"fulltext"})})
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\UserRepository")
 */
class User extends BaseUser implements StateInterface
{

    use ExternalIdTrait;

    const ROLE_WEBMASTER           = 'ROLE_WEBMASTER';
    const ROLE_WRITER              = 'ROLE_WRITER';
    const ROLE_WRITER_NETLINKING   = 'ROLE_WRITER_NETLINKING';
    const ROLE_WRITER_COPYWRITING  = 'ROLE_WRITER_COPYWRITING';
    const ROLE_WRITER_ADMIN        = 'ROLE_WRITER_ADMIN';
    const ROLE_SUPER_ADMIN         = 'ROLE_SUPER_ADMIN';

    const ROLE_WEBMASTER_STRING    = 'account_type.webmaster';
    const ROLE_WRITER_STRING       = 'account_type.seo';
    const ROLE_SUPER_ADMIN_STRING  = 'account_type.administrator';
    const ROLE_WRITER_ADMIN_STRING  = 'account_type.writer_admin';


    const NOTIFICATION_ON = 1;
    const NOTIFICATION_OFF  = 0;


//    public const NOTIFICATION_ZERO_AMOUNT = 'notification_zero_amount';

    public const NOTIFICATION_NETLINKING_PROJECT_FINISHED = 'notification_netlinking_project_finished';

    public const NOTIFICATION_NEW_PROPOSAL = 'notification_new_proposal';
    public const NOTIFICATION_NEW_PROPOSAL_REMINDER = 'notification_new_proposal_reminder';

    public const NOTIFICATION_CHANGE_PROPOSAL = 'notification_change_proposal';

    public const NOTIFICATION_BACKLINK_FOUND = 'notification_backlink_found';

    public const NOTIFICATION_ARTICLE_READY = 'notification_article_ready';

    public const NOTIFICATION_START_NEW_NETLINKING_PROJECT = 'notification_start_new_netlinking_project';

    public const NOTIFICATION_NEW_MESSAGE = 'notification_new_message';


    public const LETTER_CONFIRMATION_EMAIL = 'letter_confirmation_token';
    public const LETTER_RESET_PASSWORD = 'letter_reset_password';
    public const LETTER_NEW_MESSAGE = 'letter_new_message';
    public const LETTER_NEW_MESSAGE_WITH_CONTENT = 'letter_new_message_with_content';


    const CONNECTED_YES = 1;
    const CONNECTED_NO  = 0;

    const CONTRACT_ACCEPTED_YES = 1;
    const CONTRACT_ACCEPTED_NO  = 0;

    const PROJECT_HIDDEN_EDITOR_YES = 1;
    const PROJECT_HIDDEN_EDITOR_NO  = 0;

    const BONUS_PROJECTS_YES = 1;
    const BONUS_PROJECTS_NO  = 0;

    const CONTRACT_DRAFTING_ACCEPTED_YES = 1;
    const CONTRACT_DRAFTING_ACCEPTED_NO  = 0;

    const TRUSTED_YES = 1;
    const TRUSTED_NO  = 0;

    public const VALIDATION_GROUP_UPDATE_NOTIFICATION = 'update_notification';

    public const TRANSACTION_TAG_PAYOUT = 'user_payout';
    public const TRANSACTION_TAG_REPLENISH = 'user_replenish';
    public const TRANSACTION_TAG_MODIFY_BALANCE = 'user_modify_balance';
    public const TRANSACTION_TAG_WITHDRAW = 'user_withdraw';
    public const TRANSACTION_TAG_WITHDRAW_REJECT = 'user_withdraw_reject';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Assert\NotBlank(groups={"filter"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", nullable=true)
     * @Assert\NotBlank(groups={"Registration", "Default"})
     * @Assert\Length(
     *     min = 2,
     *     max = 75,
     *     groups={"Registration", "Default"}
     * )
     */
    private $fullName = '';

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     *
     * @Assert\Regex(pattern="/^\s+$/", groups={"Registration", "Default"}, match=false)
     *
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", nullable=true)
     * @Assert\Regex(pattern="/^[-+.\d\s\(\)]{2,26}$/", groups={"Registration", "Default"})
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration", "Default"})
     * @Assert\Length(
     *     min = 2,
     *     max = 250,
     *     groups={"Registration", "Default"}
     * )
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", nullable=true)
     * @Assert\Length(
     *     min = 2,
     *     max = 50,
     *     groups={"Registration", "Default"}
     * )
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", nullable=true)
     * @Assert\Length(
     *     min = 2,
     *     max = 250,
     *     groups={"Registration", "Default"}
     * )
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(name="web_site", type="string", nullable=true)
     * @Assert\Length(
     *     min = 2,
     *     max = 250,
     *     groups={"Registration", "Default"}
     * )
     */
    private $webSite;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration", "Default"})
     * @Assert\Length(
     *     min = 1,
     *     max = 75,
     *     groups={"Registration", "Default"}
     * )
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(groups={"Registration", "Default"})
     * @Assert\Country
     **/
    private $country;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="affiliation")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="children")
     * @ORM\JoinColumn(name="affiliation_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $affiliation;

    /**
     * @var ArrayCollection $transactions
     *
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="user", cascade={"persist", "remove"})
     */
    private $transactions;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="decimal", precision=10, scale=2, nullable=true)
     * @Assert\NotBlank(groups={"modify_balance"})
     * @Assert\LessThanOrEqual(99999999.99, groups={"modify_balance"})
     */
    private $balance;

    /**
     * @var float
     *
     * @ORM\Column(name="affiliation_tariff", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $affiliationTariff;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 2147483647})
     *
     * @Assert\NotNull(groups={User::VALIDATION_GROUP_UPDATE_NOTIFICATION})
     */
    private $notificationSettings = 2147483647;

    /**
     * @var ArrayCollection $exchangeSite
     *
     * @ORM\OneToMany(targetEntity="ExchangeSite", mappedBy="user", cascade={"persist", "remove"})
     */
    private $exchangeSite;

    /**
     * @var ArrayCollection $comission
     *
     * @ORM\OneToMany(targetEntity="Comission", mappedBy="user", cascade={"persist", "remove"})
     */
    private $comission;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="attempts", type="datetime", nullable=true)
     */
    private $attempts;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="day_of_birth", type="date", nullable=true)
     */
    private $dayOfBirth;

    /**
     * @var int
     *
     * @ORM\Column(name="connected", type="boolean", nullable=true)
     */
    private $connected;

    /**
     * @var float
     *
     * @ORM\Column(name="spending", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $spending;

    /**
     * @var int
     *
     * @ORM\Column(name="contract_accepted", type="boolean")
     */
    private $contractAccepted;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_payment_date", type="date", nullable=true)
     */
    private $lastPaymentDate;

    /**
     * @var float
     *
     * @ORM\Column(name="amount_last_payment", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amountLastPayment;


    /**
     * @var int
     *
     * @ORM\Column(name="project_hidden_editor", type="boolean")
     */
    private $projectHiddenEditor;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_rate", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $discountRate;

    /**
     * @var int
     *
     * @ORM\Column(name="bonus_projects", type="boolean")
     */
    private $bonusProjects;

    /**
     * @var int
     *
     * @ORM\Column(name="contract_drafting_accepted", type="boolean")
     */
    private $contractDraftingAccepted;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $vatNumber;

    /**
     * @var int
     *
     * @ORM\Column(name="trusted", type="boolean")
     */
    private $trusted;

    /**
     * @var ArrayCollection $exchangeProposition
     *
     * @ORM\OneToMany(targetEntity="ExchangeProposition", mappedBy="user", cascade={"persist", "remove"})
     */
    private $exchangeProposition;

    /**
     * @var ArrayCollection $orderedProjects
     *
     * @ORM\OneToMany(targetEntity="CopywritingProject", mappedBy="customer", cascade={"persist", "remove"})
     */
    private $orderedProjects;

    /**
     * @var ArrayCollection $takenOrders
     *
     * @ORM\OneToMany(targetEntity="CopywritingOrder", mappedBy="copywriter", cascade={"persist", "remove"})
     */
    private $takenOrders;

    /**
     * @var float
     *
     * @ORM\Column(name="copy_writer_rate", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $copyWriterRate;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $avatar;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=true)
     */
    private $wordsPerDay; //only for writer field

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $isShowAffiliation;

    /**
     * @var UserPaymentData $paymentData
     *
     * @ORM\OneToMany(targetEntity="UserPaymentData", mappedBy="user", cascade={"persist", "remove"})
     */
    private $paymentData;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": Language::EN})
     *
     * @Assert\Choice(callback={"CoreBundle\Entity\Constant\Language", "getAll"})
     */
    private $locale = Language::EN;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="CoreBundle\Entity\UserSetting", mappedBy="user", cascade={"persist", "remove"})
     */
    private $settings;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default":Language::EN})
     *
     * @Assert\Choice(callback={"CoreBundle\Entity\Constant\Language", "getAll"})
     */
    private $workLanguage = Language::EN;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=true)
     */
    private $ip;

    /**
     * @var bool
     *
     * @ORM\Column(name="show_credit", type="boolean", nullable=true)
     */
    private $showCredit;

    /**
     * @var int
     *
     * @ORM\Column(name="credit", type="integer", nullable=true)
     */
    private $credit;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->createdAt = new \DateTime();

        $this->children = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->exchangeSite = new ArrayCollection();
        $this->exchangeProposition = new ArrayCollection();
        $this->orderedProjects = new ArrayCollection();
        $this->takenOrders = new ArrayCollection();
        $this->comission = new ArrayCollection();
        $this->paymentData = new ArrayCollection();
        $this->settings = new ArrayCollection();

        $this->connected        = self::CONNECTED_NO;
        $this->contractAccepted = self::CONTRACT_ACCEPTED_NO;
        $this->projectHiddenEditor = self::PROJECT_HIDDEN_EDITOR_NO;
        $this->bonusProjects = self::BONUS_PROJECTS_NO;
        $this->contractDraftingAccepted = self::CONTRACT_ACCEPTED_NO;
        $this->trusted = self::TRUSTED_NO;

        $this->isShowAffiliation = false;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     *
     * @return User
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     *
     * @return User
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return User
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return User
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     *
     * @return User
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebSite()
    {
        return $this->webSite;
    }

    /**
     * @param string $webSite
     *
     * @return User
     */
    public function setWebSite($webSite)
    {
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        $this->email = $email;

        $this->setUsername($email);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     *
     * @return User
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * @param mixed $affiliation
     *
     * @return User
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param ArrayCollection $transactions
     *
     * @return User
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;

        return $this;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance > 0 ? $this->balance:0;
    }

    /**
     * @param float $balance
     *
     * @return User
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @param float $balance
     *
     * @return User
     */
    public function incBalance($balance)
    {
        $this->balance += $balance;

        return $this;
    }

    /**
     * @param float $balance
     *
     * @return User
     */
    public function decBalance($balance)
    {
        $this->balance-= $balance;

        return $this;
    }

    /**
     * @return float
     */
    public function getAffiliationTariff()
    {
        return $this->affiliationTariff;
    }

    /**
     * @param float $affiliationTariff
     *
     * @return User
     */
    public function setAffiliationTariff($affiliationTariff)
    {
        $this->affiliationTariff = !empty($affiliationTariff) ? (double)$affiliationTariff:0;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param ArrayCollection $exchangeSite
     *
     * @return User
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return User
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param \DateTime $attempts
     *
     * @return User
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDayOfBirth()
    {
        return $this->dayOfBirth;
    }

    /**
     * @param \DateTime $dayOfBirth
     *
     * @return User
     */
    public function setDayOfBirth($dayOfBirth)
    {
        $this->dayOfBirth = $dayOfBirth;

        return $this;
    }

    /**
     * @return int
     */
    public function getConnected()
    {
        return $this->connected;
    }

    /**
     * @param int $connected
     *
     * @return User
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpending()
    {
        return $this->spending;
    }

    /**
     * @param float $spending
     *
     * @return User
     */
    public function setSpending($spending)
    {
        $this->spending = $spending;

        return $this;
    }

    /**
     * @return int
     */
    public function getContractAccepted()
    {
        return $this->contractAccepted;
    }

    /**
     * @param int $contractAccepted
     *
     * @return User
     */
    public function setContractAccepted($contractAccepted)
    {
        $this->contractAccepted = $contractAccepted;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastPaymentDate()
    {
        return $this->lastPaymentDate;
    }

    /**
     * @param \DateTime $lastPaymentDate
     *
     * @return User
     */
    public function setLastPaymentDate($lastPaymentDate)
    {
        $this->lastPaymentDate = $lastPaymentDate;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmountLastPayment()
    {
        return $this->amountLastPayment;
    }

    /**
     * @param float $amountLastPayment
     *
     * @return User
     */
    public function setAmountLastPayment($amountLastPayment)
    {
        $this->amountLastPayment = !empty($amountLastPayment) ? (double) $amountLastPayment:0;

        return $this;
    }

    /**
     * @return int
     */
    public function getProjectHiddenEditor()
    {
        return $this->projectHiddenEditor == self::PROJECT_HIDDEN_EDITOR_YES;
    }

    /**
     * @param int $projectHiddenEditor
     *
     * @return User
     */
    public function setProjectHiddenEditor($projectHiddenEditor)
    {
        $this->projectHiddenEditor = $projectHiddenEditor;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountRate()
    {
        return $this->discountRate;
    }

    /**
     * @param float $discountRate
     *
     * @return User
     */
    public function setDiscountRate($discountRate)
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    /**
     * @return int
     */
    public function getBonusProjects()
    {
        return $this->bonusProjects;
    }

    /**
     * @param int $bonusProjects
     *
     * @return User
     */
    public function setBonusProjects($bonusProjects)
    {
        $this->bonusProjects = $bonusProjects;

        return $this;
    }

    /**
     * @return int
     */
    public function getContractDraftingAccepted()
    {
        return $this->contractDraftingAccepted;
    }

    /**
     * @param int $contractDraftingAccepted
     *
     * @return User
     */
    public function setContractDraftingAccepted($contractDraftingAccepted)
    {
        $this->contractDraftingAccepted = $contractDraftingAccepted;

        return $this;
    }

    /**
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber
     *
     * @return User
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return int
     */
    public function getTrusted()
    {
        return $this->trusted == self::TRUSTED_YES;
    }

    /**
     * @param int $trusted
     *
     * @return User
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getExchangeProposition()
    {
        return $this->exchangeProposition;
    }

    /**
     * @param ArrayCollection $exchangeProposition
     *
     * @return User
     */
    public function setExchangeProposition($exchangeProposition)
    {
        $this->exchangeProposition = $exchangeProposition;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTakenOrders()
    {
        return $this->takenOrders;
    }

    /**
     * @param ArrayCollection $takenOrders
     *
     * @return User
     */
    public function setTakenOrders($takenOrders)
    {
        $this->takenOrders = $takenOrders;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getOrderedProjects()
    {
        return $this->orderedProjects;
    }

    /**
     * @param ArrayCollection $orderedProjects
     *
     * @return User
     */
    public function setOrderedProjects($orderedProjects)
    {
        $this->orderedProjects = $orderedProjects;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountTypeString()
    {
        if ($this->hasRole(User::ROLE_SUPER_ADMIN)) {
            return self::ROLE_SUPER_ADMIN_STRING;
        }

        if ($this->hasRole(User::ROLE_WRITER) || $this->hasRole(User::ROLE_WRITER_ADMIN)) {
            return self::ROLE_WRITER_STRING;
        }

        if ($this->hasRole(User::ROLE_WEBMASTER)) {
            return self::ROLE_WEBMASTER_STRING;
        }

        return 'account_type.none';
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->enabled;
    }

    /**
     * @param int $active
     *
     * @return User
     */
    public function setActive($active)
    {
        $this->setEnabled($active);

        return $this;
    }

    /**
     * @return int
     */
    public function getNotificationSettings()
    {
        return $this->notificationSettings;
    }

    /**
     * @param $notificationSettings
     *
     * @return User
     */
    public function setNotificationSettings($notificationSettings)
    {
        $this->notificationSettings = $notificationSettings;

        return $this;
    }

    /**
     * @param $notificationName
     *
     * @return integer - User::NOTIFICATION_ON or User::NOTIFICATION_OFF (1 or 0)
     *
     * @throws UnknownNotificationName
     */
    public function isNotificationEnabled($notificationName)
    {
        return ($this->notificationSettings >> self::getNotificationBitPos($notificationName) & 1 === 1) ? self::NOTIFICATION_ON : self::NOTIFICATION_OFF;
    }

    /**
     * @param $notificationName
     * @param integer $status - User::NOTIFICATION_ON or User::NOTIFICATION_OFF (1 or 0)
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setNotificationEnabled($notificationName, $status)
    {
        if ($status === User::NOTIFICATION_ON) {
            $this->notificationSettings |= (1 << self::getNotificationBitPos($notificationName));
        } else {
            $this->notificationSettings &= ~(1 << self::getNotificationBitPos($notificationName));
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function canTakeToWork()
    {
        /** @var CopywritingOrder $order */
        foreach ($this->takenOrders as $order)
        {
            if ($order->isInProgress()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return float
     */
    public function getCopyWriterRate()
    {
        return $this->copyWriterRate;
    }

    /**
     * @param float $copyWriterRate
     *
     * @return User
     */
    public function setCopyWriterRate($copyWriterRate)
    {
        $this->copyWriterRate = $copyWriterRate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWriterAdmin()
    {
        return $this->hasRole(self::ROLE_WRITER_ADMIN);
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isWriterAdmin() || $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function isWebmaster()
    {
        return $this->hasRole(self::ROLE_WEBMASTER);
    }

    /**
     * @return bool
     */
    public function isWriter()
    {
        return $this->hasRole(User::ROLE_WRITER);
    }

    /**
     * @return bool
     */
    public function isWriterNetlinking()
    {
        return $this->hasRole([User::ROLE_WRITER, User::ROLE_WRITER_NETLINKING]);
    }

    /**
     * @return bool
     */
    public function isWriterCopywriting()
    {
        return $this->hasRole([User::ROLE_WRITER, User::ROLE_WRITER_COPYWRITING]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            foreach ($role as $r) {
                if (parent::hasRole($r)) {
                    return true;
                }
            }

            return false;
        } else {
            return parent::hasRole($role);
        }
    }

    /**
     * @param double $commonWebmasterTariff
     *
     * @return bool
     */
    public function webmasterCanPay($commonWebmasterTariff = 0)
    {
        $tariffWebmaster = ($this->getSpending() > 0) ? $this->getSpending() : $commonWebmasterTariff;

        return $this->getBalance() >= $tariffWebmaster;
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param $avatar
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return int
     */
    public function getWordsPerDay()
    {
        return $this->wordsPerDay;
    }

    /**
     * @param int $wordsPerDay
     *
     * @return User
     */
    public function setWordsPerDay($wordsPerDay)
    {
        $this->wordsPerDay = $wordsPerDay;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowAffiliation()
    {
        return $this->isShowAffiliation;
    }

    /**
     * @param bool $isShowAffiliation
     */
    public function setIsShowAffiliation($isShowAffiliation)
    {
        $this->isShowAffiliation = $isShowAffiliation;
    }

    /**
     * @param null $type
     *
     * @return UserPaymentData[] | ArrayCollection | UserPaymentData
     */
    public function getPaymentData($type = null)
    {
        if ($type !== null) {
            return $this->paymentData->matching(
                Criteria::create()->andWhere(
                    Criteria::expr()->eq('type', $type)
                )
            )->first();
        }

        $result = [];

        /** @var UserPaymentData $v */
        foreach ($this->paymentData as $v) {
            $result[$v->getType()] = $v;
        }

        return $result;
    }

    /**
     * Add payment data
     *
     * @param UserPaymentData $paymentData
     *
     * @return User
     */
    public function addPaymentData(UserPaymentData $paymentData)
    {
        if (!$this->paymentData->contains($paymentData)) {
            $this->paymentData->add($paymentData);

            $paymentData->setUser($this);
        }

        return $this;
    }

    /**
     * Remove paymend data
     *
     * @param UserPaymentData $paymentData
     *
     * @return User
     */
    public function removePaymentData(UserPaymentData $paymentData)
    {
        if ($this->paymentData->contains($paymentData)) {
            $this->paymentData->removeElement($paymentData);
        }

        return $this;
    }

    /**
     * @param $type
     * @param $value
     *
     * @return User
     */
    public function addPaymentType($type, $value)
    {
        $paymentData = $this->getPaymentData($type);
        if (!$paymentData) {
            $paymentData = new UserPaymentData();
            $paymentData->setType($type);
            $this->addPaymentData($paymentData);
        }

        $paymentData->setValue($value);

        return $this;
    }

    /**
     * @param WithdrawRequest $withdrawRequest
     *
     * @return User
     */
    public function addPaymentDataFromWithdraw(WithdrawRequest $withdrawRequest)
    {
        if ($withdrawRequest->getIban()) {
            $this->addPaymentType('iban', $withdrawRequest->getIban());
        }

        if ($withdrawRequest->getSwift()) {
            $this->addPaymentType('swift', $withdrawRequest->getSwift());
        }

        if ($withdrawRequest->getPaypal()) {
            $this->addPaymentType('paypal', $withdrawRequest->getPaypal());
        }

        if ($withdrawRequest->getCompanyName()) {
            $this->addPaymentType('company', $withdrawRequest->getCompanyName());
        }

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param $notificationName
     *
     * @return int
     *
     * @throws UnknownNotificationName
     */
    public static function getNotificationBitPos($notificationName)
    {
        $notification = self::getNotifications($notificationName);

        if ($notification) {
            return $notification['bit'];
        }

        throw new UnknownNotificationName($notificationName);
    }

    /**
     * @return null|string
     */
    public function getCountryName()
    {
        return Intl::getRegionBundle()->getCountryName($this->getCountry());
    }

    /**
     * @param null $name
     *
     * @return array|mixed|null
     */
    public static function getNotifications($name = null)
    {
        $notifications = [
//        User::NOTIFICATION_ZERO_AMOUNT => ['bit' => 0],
            User::NOTIFICATION_NETLINKING_PROJECT_FINISHED => ['bit' => 1],
            User::NOTIFICATION_NEW_PROPOSAL => ['bit' => 2],
            User::NOTIFICATION_CHANGE_PROPOSAL => ['bit' => 3],
            User::NOTIFICATION_BACKLINK_FOUND => ['bit' => 4],
            User::NOTIFICATION_ARTICLE_READY => ['bit' => 5],
            User::NOTIFICATION_START_NEW_NETLINKING_PROJECT => ['bit' => 6, 'roles' => [User::ROLE_SUPER_ADMIN]],
            User::NOTIFICATION_NEW_MESSAGE => ['bit' => 7],
            User::NOTIFICATION_NEW_PROPOSAL_REMINDER => ['bit' => 8],
        ];

        if ($name) {
            return isset($notifications[$name]) ? $notifications[$name] : null;
        }

        return $notifications;
    }

    /**
     * @return string
     */
    public function getWorkLanguage()
    {
        return $this->workLanguage;
    }

    /**
     * @param string $workLanguage
     *
     * @return User
     */
    public function setWorkLanguage($workLanguage)
    {
        $this->workLanguage = $workLanguage;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return User
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getHiddenEmail()
    {
        $emailParts = explode('@', $this->getEmail());
        $lengthLogin = mb_strlen($emailParts[0]);
        $hideSize = $lengthLogin / 3;

        return mb_substr($emailParts[0], 0, $hideSize) . '***' . mb_substr($emailParts[0], $hideSize * 2, $lengthLogin) . '@' . $emailParts[1];
    }

    /**
     * @return boolean
     */
    public function getShowCredit()
    {
        return $this->showCredit;
    }

    /**
     * @param boolean $showCredit
     *
     * @return User
     */
    public function setShowCredit($showCredit)
    {
        $this->showCredit = $showCredit;

        return $this;
    }

    /**
     * @return int
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param int $credit
     *
     * @return User
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @return int
     */
    public function getEuroCredit()
    {
        return $this->credit * 10;
    }

}
