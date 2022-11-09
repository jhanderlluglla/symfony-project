<?php

namespace CoreBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ExternalIdTrait
{
    /**
     * @var integer
     *
     * @ORM\Column(name="external_id", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $externalId;

    /**
     * @return int
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param int $externalId
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }
}
