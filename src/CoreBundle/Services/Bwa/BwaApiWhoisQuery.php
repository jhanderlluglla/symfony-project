<?php

namespace CoreBundle\Services\Bwa;

/**
 * Class BwaApiWhoisQuery
 *
 * @package CoreBundle\Services\Bwa
 */
class BwaApiWhoisQuery extends BwaApiResponse
{
    /** @var BwaApiWhoisOutput */
    public $output;
    /** @var string[] */
    public $rawOutput;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $output = array_key_exists('output', $data) ? $data['output'] : array();
        $this->output = new BwaApiWhoisOutput($output);
    }
}