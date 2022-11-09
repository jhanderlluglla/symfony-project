<?php

namespace CoreBundle\Entity\Traits;

use CoreBundle\Entity\Site;
use Doctrine\ORM\Mapping as ORM;

trait SiteTrait
{
    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="CoreBundle\Entity\Site", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $site;

    /**
     * @return Site
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @param Site $site
     *
     * @return self
     */
    public function setSite(?Site $site): self
    {
        $this->site = $site;

        return $this;
    }
}
