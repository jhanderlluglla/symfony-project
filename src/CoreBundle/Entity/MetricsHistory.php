<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\CreatedAtTrait;
use CoreBundle\Entity\Traits\MetricsTrait;
use CoreBundle\Entity\Traits\SiteTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * MetricsHistory
 *
 * @ORM\Table(name="metrics_history")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\MetricsHistoryRepository")
 */
class MetricsHistory
{

    use MetricsTrait;

    use CreatedAtTrait;

    use SiteTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * todo: delete after switching to site (SiteMigrationCommand)
     *
     * @var Directory $directory
     *
     * @ORM\ManyToOne(targetEntity="Directory")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $directory;

    /**
     * todo: delete after switching to site (SiteMigrationCommand)
     *
     * @var ExchangeSite $exchangeSite
     *
     * @ORM\ManyToOne(targetEntity="ExchangeSite")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $exchangeSite;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Directory
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param Directory $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return ExchangeSite
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param ExchangeSite $exchangeSite
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;
    }
}
