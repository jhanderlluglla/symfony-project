<?php

namespace CoreBundle\Helpers;

/**
 * Class SiteHelper
 *
 * @package CoreBundle\Helpers
 */
class SiteHelper
{
    /**
     * @param string $host
     *
     * @return string
     */
    public static function prepareHost($host)
    {
        $host = strtolower($host);
        $host = trim($host);

        return $host;
    }

    public static function validationHost($host)
    {
        return preg_match('~^([a-z0-9âàçéêèëîïôûùü]([a-z0-9âàçéêèëîïôûùü-]{1,61}[a-z0-9âàçéêèëîïôûùü]|[a-z0-9âàçéêèëîïôûùü])*\.)+[a-zâàçéêèëîïôûùü]{2,}$~', $host);
    }
}
