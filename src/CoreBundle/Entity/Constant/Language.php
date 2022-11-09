<?php

namespace CoreBundle\Entity\Constant;

/**
 * Class LanguageType
 * @package CoreBundle\Entity\Constant
 */
class Language
{
    public const FR = 'fr';
    public const EN = 'en';
    public const ES = 'es';
    public const IT = 'it';
    public const PT = 'pt';
    public const DE = 'de';

    /**
     * @return array
     */
    public static function getAll()
    {
        return [
            self::FR,
            self::EN,
            self::ES,
            self::IT,
            self::PT,
            self::DE,
        ];
    }

    /**
     * @return array
     */
    public static function getOptions()
    {
        return [
            self::FR => ['name' => 'FR'],
            self::EN => ['name' => 'EN'],
            self::ES => ['name' => 'ES'],
            self::IT => ['name' => 'IT'],
            self::PT => ['name' => 'PT'],
            self::DE => ['name' => 'DE'],
        ];
    }

    /**
     * @param $language
     *
     * @return bool
     */
    public static function validate($language)
    {
        return in_array($language, self::getAll());
    }
}
