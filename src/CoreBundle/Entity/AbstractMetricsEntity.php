<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Interfaces\LanguageInterface;
use CoreBundle\Entity\Traits\LanguageTrait;
use CoreBundle\Entity\Traits\MetricsTrait;
use CoreBundle\Entity\Traits\SiteTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * Class AbstractMetricsEntity
 *
 * @package CoreBundle\Entity
 */
abstract class AbstractMetricsEntity implements LanguageInterface
{
    /**
     * Hook SoftDeleteable behavior
     * updates deletedAt field
     */
    use SoftDeleteableEntity;

    use MetricsTrait;

    use LanguageTrait;

    use SiteTrait;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned":true, "default": 0})
     */
    protected $numberOfFails = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_update", type="datetime", nullable=true)
     */
    protected $lastUpdate;

    /**
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param \DateTime $lastUpdate
     *
     * @return $this
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAge()
    {
        return $this->getBwaAge();
    }

    /**
     * @param \DateTime $age
     *
     * @return $this
     */
    public function setAge($age)
    {
        return $this->setBwaAge($age);
    }

    /**
     * @return string
     */
    public function getMajesticTtfCategories()
    {
        return $this->majesticTtfCategories;
    }

    /**
     * @param array $majesticTtfCategories
     *
     * @return $this
     */
    public function setMajesticTtfCategories($majesticTtfCategories)
    {
        $this->majesticTtfCategories = $majesticTtfCategories;

        return $this;
    }

    /**
     * @return string
     */
    public function getMajesticTtfCategoriesFormatted()
    {
        if (!$this->majesticTtfCategories->isEmpty()) {
            $category = [];

            /** @var TtfCategory $view */
            foreach ($this->majesticTtfCategories as $view) {
                $category[] = $view->getCategory().': '.$view->getRate();
            }

            return implode('; ', $category);
        }

        return '';
    }

    /**
     * @return int
     */
    public function getNumberOfFails()
    {
        return $this->numberOfFails;
    }

    /**
     * @param int $numberOfFails
     */
    public function setNumberOfFails($numberOfFails)
    {
        $this->numberOfFails = $numberOfFails;
    }

    public function incNumberOfFails()
    {
        $this->numberOfFails++;
    }

    /**
     * @return string
     */
    protected function hideUrl($url)
    {
        $hidUrl = '';
        $urlAr = parse_url($url);
        $hidUrl .= $urlAr['scheme'] . '://';
        $hostArr = explode('.', $urlAr['host']);
        $firstSymbol = $hostArr[0][0];
        if ($hostArr[0] == 'www') {
            $hidUrl .= 'www.';
            $firstSymbol = $hostArr[1][0];
        }
        $hidUrl .= $firstSymbol . '*******.' . end($hostArr);

        return $hidUrl;
    }

    /**
     * @return string
     */
    protected function parseDomain($url)
    {
        $urlInfo = parse_url($url);
        $domain = str_ireplace("www.", "", $urlInfo["host"]);

        return $domain;
    }

    /**
     * @return string
     */
    public function parseHost($url)
    {
        $urlData = parse_url($url);

        return isset($urlData['host']) ? $urlData['host']: $url;
    }
}
