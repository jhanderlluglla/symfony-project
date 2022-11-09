<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiWhoisOutput
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiWhoisOutput
{
    /** @var string */
    public $domain;
    /** @var string */
    public $domain_id;
    /** @var string[] */
    public $status;
    /** @var bool */
    public $registered;
    /** @var bool */
    public $available;
    /** @var string */
    public $created_on;
    /** @var string */
    public $updated_on;
    /** @var string */
    public $expires_on;
    /** @var BwaApiWhoisRegistrar|null */
    public $registrar;
    /** @var BwaApiWhoisContact|null */
    public $registrant_contact;
    /** @var BwaApiWhoisContact|null */
    public $admin_contact;
    /** @var BwaApiWhoisContact|null */
    public $technical_contact;
    /** @var BwaApiWhoisNameServer[] */
    public $nameservers = array();

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {

            if (!property_exists($this, $key)) {
                throw new \Exception("Invalid property {$key}.");
            }

            $this->{$key} = $value;
        }

        if (!empty($data['registrar'])) {
            $this->registrar = new BwaApiWhoisRegistrar($data['registrar']);
        }

        foreach (array('registrant_contact', 'admin_contact', 'technical_contact') as $contactType) {
            if (!empty($data[$contactType])) {
                $this->{$contactType} = new BwaApiWhoisContact($data[$contactType]);
            }
        }

        if (!empty($data['nameservers'])) {
            $this->nameservers = array();
            foreach ($data['nameservers'] as $nameserver) {
                $this->nameservers[] = new BwaApiWhoisNameServer($nameserver);
            }
        }
    }
}