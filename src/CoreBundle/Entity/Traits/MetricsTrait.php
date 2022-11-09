<?php

namespace CoreBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait MetricsTrait
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $majesticTrustFlow;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $majesticCitation;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $majesticRefDomains;

    /**
     * @var integer
     *
     * @ORM\Column(type="bigint", nullable=true)
     *
     */
    protected $majesticBacklinks;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $majesticEduBacklinks;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $majesticGovBacklinks;

    /**
     * @var integer
     *
     * @ORM\Column(name="alexa_rank", type="integer", options={"unsigned":true}, nullable=true)
     *
     */
    protected $alexaRank;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     *
     */
    protected $bwaAge;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     *
     */
    protected $archiveAge;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $googleNews;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $googleAnalytics;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $mozPageAuthority;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $mozDomainAuthority;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $googleIndexedPages;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $semrushTraffic;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $semrushKeyword;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    protected $semrushTrafficCost;

    /**
     * @return integer
     */
    public function getMajesticTrustFlow()
    {
        return $this->majesticTrustFlow;
    }

    /**
     * @param integer $majesticTrustFlow
     *
     * @return $this
     */
    public function setMajesticTrustFlow($majesticTrustFlow)
    {
        $this->majesticTrustFlow = $majesticTrustFlow;

        return $this;
    }

    /**
     * @return string
     */
    public function getMajesticCitation()
    {
        return $this->majesticCitation;
    }

    /**
     * @param string $majesticCitation
     *
     * @return $this
     */
    public function setMajesticCitation($majesticCitation)
    {
        $this->majesticCitation = $majesticCitation;

        return $this;
    }

    /**
     * @return int
     */
    public function getMajesticRefDomains()
    {
        return $this->majesticRefDomains;
    }

    /**
     * @param int $majesticRefDomains
     *
     * @return $this
     */
    public function setMajesticRefDomains($majesticRefDomains)
    {
        $this->majesticRefDomains = $majesticRefDomains;

        return $this;
    }

    /**
     * @return int
     */
    public function getMajesticBacklinks()
    {
        return $this->majesticBacklinks;
    }

    /**
     * @param int $majesticBacklinks
     *
     * @return $this
     */
    public function setMajesticBacklinks($majesticBacklinks)
    {
        $this->majesticBacklinks = $majesticBacklinks;

        return $this;
    }

    /**
     * @return int
     */
    public function getMajesticEduBacklinks()
    {
        return $this->majesticEduBacklinks;
    }

    /**
     * @param int $majesticEduBacklinks
     *
     * @return $this
     */
    public function setMajesticEduBacklinks($majesticEduBacklinks)
    {
        $this->majesticEduBacklinks = $majesticEduBacklinks;

        return $this;
    }

    /**
     * @return int
     */
    public function getMajesticGovBacklinks()
    {
        return $this->majesticGovBacklinks;
    }

    /**
     * @param int $majesticGovBacklinks
     *
     * @return $this
     */
    public function setMajesticGovBacklinks($majesticGovBacklinks)
    {
        $this->majesticGovBacklinks = $majesticGovBacklinks;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlexaRank()
    {
        return $this->alexaRank;
    }

    /**
     * @param string $alexaRank
     *
     * @return $this
     */
    public function setAlexaRank($alexaRank)
    {
        $this->alexaRank = $alexaRank;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBwaAge()
    {
        return $this->bwaAge;
    }

    /**
     * @param \DateTime $bwaAge
     *
     * @return $this
     */
    public function setBwaAge($bwaAge)
    {
        $this->bwaAge = $bwaAge;

        return $this;
    }


    /**
     * @return \DateTime
     */
    public function getArchiveAge()
    {
        return $this->archiveAge;
    }

    /**
     * @param \DateTime $archiveAge
     *
     * @return $this
     */
    public function setArchiveAge($archiveAge)
    {
        if ($archiveAge) {
            $this->archiveAge = $archiveAge;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getGoogleNews()
    {
        return $this->googleNews;
    }

    /**
     * @param int $googleNews
     *
     * @return $this
     */
    public function setGoogleNews($googleNews)
    {
        $this->googleNews = $googleNews;

        return $this;
    }

    /**
     * @return bool
     */
    public function getGoogleAnalytics()
    {
        return $this->googleAnalytics;
    }

    /**
     * @param bool $googleAnalytics
     *
     * @return $this
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = $googleAnalytics;

        return $this;
    }

    /**
     * @return int
     */
    public function getMozPageAuthority()
    {
        return $this->mozPageAuthority;
    }

    /**
     * @param int $mozPageAuthority
     *
     * @return $this
     */
    public function setMozPageAuthority($mozPageAuthority)
    {
        $this->mozPageAuthority = $mozPageAuthority;

        return $this;
    }


    /**
     * @return int
     */
    public function getMozDomainAuthority()
    {
        return $this->mozDomainAuthority;
    }

    /**
     * @param int $mozDomainAuthority
     *
     * @return $this
     */
    public function setMozDomainAuthority($mozDomainAuthority)
    {
        $this->mozDomainAuthority = $mozDomainAuthority;

        return $this;
    }


    /**
     * @return int
     */
    public function getGoogleIndexedPages()
    {
        return $this->googleIndexedPages;
    }

    /**
     * @param int $googleIndexedPages
     *
     * @return $this
     */
    public function setGoogleIndexedPages($googleIndexedPages)
    {
        $this->googleIndexedPages = $googleIndexedPages;

        return $this;
    }


    /**
     * @return int
     */
    public function getSemrushTraffic()
    {
        return $this->semrushTraffic;
    }

    /**
     * @param int $semrushTraffic
     *
     * @return $this
     */
    public function setSemrushTraffic($semrushTraffic)
    {
        $this->semrushTraffic = $semrushTraffic;

        return $this;
    }

    /**
     * @return int
     */
    public function getSemrushKeyword()
    {
        return $this->semrushKeyword;
    }

    /**
     * @param int $semrushKeyword
     *
     * @return $this
     */
    public function setSemrushKeyword($semrushKeyword)
    {
        $this->semrushKeyword = $semrushKeyword;

        return $this;
    }

    /**
     * @return int
     */
    public function getSemrushTrafficCost()
    {
        return $this->semrushTrafficCost;
    }

    /**
     * @param int $semrushTrafficCost
     *
     * @return self
     */
    public function setSemrushTrafficCost($semrushTrafficCost)
    {
        $this->semrushTrafficCost = $semrushTrafficCost;

        return $this;
    }

    public static function getAllMetrics()
    {
        return get_class_vars(self::class);
    }
}
