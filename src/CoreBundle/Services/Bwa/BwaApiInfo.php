<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiInfoBwaApiInfo
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiInfo extends BwaApiResponse
{
    /** @var string */
    public $email;
    /** @var int */
    public $requestsLeft;
}