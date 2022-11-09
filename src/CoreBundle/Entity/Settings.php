<?php

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * StaticPage
 *
 * @ORM\Table(name="settings")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\SettingsRepository")
 * @UniqueEntity(fields="identificator", message="settings.identificator_exists")
 */
class Settings
{
    const CORRECTOR_PRICE_PER_100_WORDS = 'corrector_price_100_words';
    const REDUCED_CORRECTOR_PRICE_PER_100_WORDS = 'reduced_corrector_price_100_words';
    const WRITER_PRICE_PER_100_WORDS = 'writer_price_100_words';
    const PRICE_PER_100_WORDS = 'price_100_words';
    const DEFAULT_WORDS_PER_DAY = 'default_words_per_day';
    const WORDS_PER_DAY = 'words_per_day';
    const DEFAULT_DIRECTORY_ZERO_WORDS_COUNT = 'default_directory_zero_words_count';
    const CREDIT_EXCHANGE_RATE = 'credit_exchange_rate';
    const PRICE_PER_IMAGE = 'price_image';
    const WRITER_PRICE_PER_IMAGE = 'writer_price_image';
    const EXPRESS_RATE = 'express_rate';
    const WRITER_EXPRESS_RATE = 'writer_express_rate';
    const CORRECTOR_EXPRESS_RATE = 'corrector_express_rate';
    const PRICE_YOU_LIKE_WRITERS = 'price_you_like_writers';
    const WRITER_PRICE_YOU_LIKE_WRITERS = 'writer_price_you_like_writers';
    const PRICE_TOP_WRITERS = 'price_top_writers';
    const WRITER_PRICE_TOP_WRITERS = 'writer_price_top_writers';
    const PRICE_BEST_WRITERS = 'price_best_writers';
    const WRITER_PRICE_BEST_WRITERS = 'writer_price_best_writers';
    const COMMISSION_PERCENT = 'commission_percent';
    const WITHDRAW_PERCENT = 'withdraw_percent';
    const PRICE_ON_HOMEPAGE = 'price_on_homepage';
    const MINIMUM_WITHDRAW = 'minimum_withdraw';
    const WITHDRAW_PER_MONTH = 'withdraw_per_month';
    const PLUGIN_MORE_INFORMATION = 'plugin_more_information';
    const INVOICE_EURL = 'invoice_eurl';
    const INVOICE_SIRET = 'invoice_siret';
    const INVOICE_FIRST_NAME = 'invoice_first_name';
    const INVOICE_LAST_NAME = 'invoice_last_name';
    const INVOICE_HEADQUARTES_ADDRESS = 'invoice_headquarters_address';
    const INVOICE_AREA_ADDRESS = 'invoice_area_address';
    const INVOICE_POSTAL_CODE = 'invoice_postal_code';
    const INVOICE_COUNTRY = 'invoice_country';
    const INVOICE_IBAN = 'invoice_iban';
    const INVOICE_BIC_SWIFT = 'invoice_bic_swift';
    const INVOICE_VAT_NUMBER = 'invoice_vat_number';
    const WEBMASTER_ADDITIONAL_PAY = 'webmaster_additional_pay';
    const TARIFF_WEB = 'tarifweb';
    const PRICE_FOR_META_DESCRIPTION = 'price_for_meta_description';
    const WRITER_REWARD_FOR_META_DESCRIPTION = 'writer_reward_for_meta_description';
    const EMAIL = 'email';
    const EMAIL_LANGUAGE = 'email_language';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="identificator", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank()
     */
    private $identificator;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=1000000)
     *
     * @Assert\NotBlank()
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
     * @return Settings
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentificator()
    {
        return $this->identificator;
    }

    /**
     * @param string $identificator
     *
     * @return Settings
     */
    public function setIdentificator($identificator)
    {
        $this->identificator = $identificator;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Settings
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}