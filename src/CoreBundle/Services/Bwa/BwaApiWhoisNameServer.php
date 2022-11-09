<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiWhoisNameServer
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiWhoisNameServer
{
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $ipv4;
    /** @var string|null */
    public $ipv6;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {

            if (!property_exists($this, $key)) {
                throw new \Exception("Invalid property {$key}.");
            }

            $this->{$key} = $value;
        }
    }
}