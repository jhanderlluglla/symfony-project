<?php

namespace CoreBundle\Services;

use CoreBundle\Services\Bwa\BwaApi;

/**
 * Class BwaInfo
 *
 * @package CoreBundle\Services
 */
class BwaInfo
{

    /**
     * @var BwaApi
     */
    protected $bwaApi;

    /**
     * BwaInfo constructor.
     *
     * @param string $accessKeyId
     * @param string $secretAccessKey
     */
    public function __construct($accessKeyId, $secretAccessKey)
    {
        $this->bwaApi = new BwaApi($accessKeyId, $secretAccessKey);
    }

    /**
     * @param string $domain
     *
     * @return false|mixed|string
     */
    public function getDomainCreation($domain)
    {
        $domainex = explode(".", $domain);

        if (count($domainex) == 3) {
            $domainuse = $domainex[1] . "." . $domainex[2];
        } elseif (count($domainex) == 4) {
            $domainuse = $domainex[2] . "." . $domainex[3];
        } else {
            $domainuse = $domain;
        }

        $whoisQuerySync = $this->bwaApi->whoisQuerySynchronous($domainuse);

        $date = array();
        if ($whoisQuerySync->success) {
            $date = explode(" ", $whoisQuerySync->output->created_on);

            if (empty($date[0]) && $whoisQuerySync->rawOutput) {
                preg_match('/Creation Date:(.*)/', $whoisQuerySync->rawOutput[0], $matches);
                if (isset($matches[1])) {
                    $date[0] = (new \DateTime($matches[1]))->format('Y-m-d');
                }
            }
        }

        return !empty($date[0]) ? trim($date[0], "-") : date('Y-m-d');
    }
}
