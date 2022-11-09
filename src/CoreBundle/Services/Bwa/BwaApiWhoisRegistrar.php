<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiWhoisRegistrar
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiWhoisRegistrar
{
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $organization;
    /** @var string */
    public $url;

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