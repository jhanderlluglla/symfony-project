<?php

namespace CoreBundle\Entity\Constant;

class Country
{
    private const EUROPEAN_UNION_TAX = [
        "AT" => 20, //Austria
        "BE" => 21, //Belgium
        "BG" => 20, //Bulgaria
        "HR" => 25, //Croatia
        "CY" => 19, //Cyprus
        "CZ" => 21, //Czech Republic
        "DK" => 25, //Denmark
        "EE" => 20, //Estonia
        "FI" => 24, //Finland
        "FR" => 20, //France
        "DE" => 19, //Germany
        "GR" => 24, //Greece
        "HU" => 27, //Hungary
        "IE" => 23, //Ireland
        "IT" => 22, //Italy
        "LV" => 21, //Latvia
        "LT" => 21, //Lithuania
        "LU" => 17, //Luxembourg
        "MT" => 18, //Malta
        "NL" => 21, //Netherlands
        "PL" => 23, //Poland
        "PT" => 23, //Portugal
        "RO" => 20, //Romania
        "SK" => 20, //Slovakia
        'SI' => 22, //Slovenia
        'ES' => 21, //Spain
        'SE' => 25, //Sweden
        'GB' => 20, //United Kingdom
    ];

    /**
     * @return integer
     */
    public static function getFrenchTax()
    {
        return self::EUROPEAN_UNION_TAX['FR'];
    }

    /**
     * @param string $isoCountryCode
     * @return boolean
     */
    public static function isEuropeanCountry($isoCountryCode)
    {
        if (key_exists($isoCountryCode, self::EUROPEAN_UNION_TAX)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $isoCountryCode
     * @return boolean
     */
    public static function isEuropeanCountryExceptFrance($isoCountryCode)
    {
        if (key_exists($isoCountryCode, self::EUROPEAN_UNION_TAX) && $isoCountryCode !== "FR") {
            return true;
        }
        return false;
    }
}
