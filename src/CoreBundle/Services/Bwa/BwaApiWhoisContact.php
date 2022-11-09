<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiWhoisContact
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiWhoisContact
{
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $organization;
    /** @var string */
    public $address;
    /** @var string */
    public $city;
    /** @var string */
    public $zip;
    /** @var string */
    public $state;
    /** @var string */
    public $country;
    /** @var string */
    public $country_code;
    /** @var string */
    public $phone;
    /** @var string */
    public $fax;
    /** @var string */
    public $email;
    /** @var string */
    public $created_on;
    /** @var string */
    public $updated_on;
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