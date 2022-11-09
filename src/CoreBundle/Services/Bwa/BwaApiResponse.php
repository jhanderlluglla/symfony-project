<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiResponse
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiResponse
{
    /** @var int */
    public $success;

    /** @var string|null */
    public $message;

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