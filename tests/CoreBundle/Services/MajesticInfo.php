<?php

namespace Tests\CoreBundle\Services;

/**
 * Class MajesticInfo

 */
class MajesticInfo extends \CoreBundle\Services\MajesticInfo
{
    public function getUrlInfo($domain)
    {
        $response = file_get_contents(__DIR__.'/FakeResponse/Majestic.json');
        $response = str_replace('%domain%', $domain, $response);

        return json_decode($response, true);
    }
}
