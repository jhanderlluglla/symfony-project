<?php

namespace CoreBundle\Services\Metrics;

use CoreBundle\Entity\AbstractMetricsEntity;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\MetricsHistory;
use CoreBundle\Entity\Site;
use CoreBundle\Entity\Traits\MetricsTrait;
use CoreBundle\Services\AwisInfo;
use CoreBundle\Services\BwaInfo;
use CoreBundle\Services\ExchangeSiteService;
use CoreBundle\Services\GoogleAnalyticsInfo;
use CoreBundle\Services\MajesticInfo;
use CoreBundle\Services\MementoInfo;
use CoreBundle\Services\MozInfo;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class MetricsManager
{
    /** @var EntityManager */
    private $em;

    /** @var LoggerInterface */
    private $monolog;

    /** @var BwaInfo */
    private $bwaInfo;

    /** @var MajesticInfo */
    private $majesticInfo;

    /** @var Semrush */
    private $semrushService;

    /** @var AwisInfo */
    private $awisInfo;

    /** @var MementoInfo */
    private $mementoInfo;

    /** @var GoogleAnalyticsInfo */
    private $googleAnalyticsInfo;

    /** @var MozInfo */
    private $mozInfo;

    /** @var ExchangeSiteService */
    private $exchangeSiteService;

    /**
     * MetricsManager constructor.
     *
     * @param EntityManager $em
     * @param LoggerInterface $monolog
     * @param BwaInfo $bwaInfo
     * @param MajesticInfo $majesticInfo
     * @param Semrush $semrushService
     * @param AwisInfo $awisInfo
     * @param MementoInfo $mementoInfo
     * @param GoogleAnalyticsInfo $googleAnalyticsInfo
     * @param MozInfo $mozInfo
     * @param ExchangeSiteService $exchangeSiteService
     */
    public function __construct(
        EntityManager $em,
        LoggerInterface $monolog,
        BwaInfo $bwaInfo,
        MajesticInfo $majesticInfo,
        Semrush $semrushService,
        AwisInfo $awisInfo,
        MementoInfo $mementoInfo,
        GoogleAnalyticsInfo $googleAnalyticsInfo,
        MozInfo $mozInfo,
        ExchangeSiteService $exchangeSiteService
    ) {
        $this->em = $em;
        $this->monolog = $monolog;
        $this->bwaInfo = $bwaInfo;
        $this->majesticInfo = $majesticInfo;
        $this->semrushService = $semrushService;
        $this->awisInfo = $awisInfo;
        $this->mementoInfo = $mementoInfo;
        $this->googleAnalyticsInfo = $googleAnalyticsInfo;
        $this->mozInfo = $mozInfo;
        $this->exchangeSiteService = $exchangeSiteService;
    }

    /**
     * @param array $domains - []
     */
    public function initMozDataMetrics($domains)
    {
        $this->mozInfo->batchRetrieveData($domains);
    }

    /**
     * @param Site $site
     *
     * @return MetricsHistory
     */
    public function updateMetrics(Site $site)
    {
        $domain = $site->getDomain();

        $metrics = new MetricsHistory();

        try {
            $metrics
                ->setSite($site)
                ->setMajesticTrustFlow($this->majesticInfo->getTrustFlow($domain))
                ->setMajesticRefDomains($this->majesticInfo->getRefDomains($domain))
                ->setMajesticCitation($this->majesticInfo->getCitationFlow($domain))
                ->setMajesticBacklinks($this->majesticInfo->getBacklinks($domain))
                ->setMajesticEduBacklinks($this->majesticInfo->getEduBacklinks($domain))
                ->setMajesticGovBacklinks($this->majesticInfo->getGovBacklinks($domain))
                ->setAlexaRank($this->awisInfo->getAlexaRank($domain))
                ->setGoogleAnalytics($this->googleAnalyticsInfo->getInfo($site->getUrl()))
                ->setMozPageAuthority($this->mozInfo->getPageAuthority($domain))
                ->setMozDomainAuthority($this->mozInfo->getPageAuthority($domain))
                ->setSemrushTraffic($this->semrushService->getSemrushTraffic($domain, $site->getLanguage()))
                ->setSemrushKeyword($this->semrushService->getSemrushKeyword($domain, $site->getLanguage()))
                ->setSemrushTrafficCost($this->semrushService->getSemrushTrafficCost($domain, $site->getLanguage()))
                ->setBwaAge(new \DateTime($this->bwaInfo->getDomainCreation($domain)))
                ->setArchiveAge($this->mementoInfo->getFirstSnapshotDate($site->getUrl()))
            ;
        } catch (\Exception $e) {
            $this->monolog->critical($e->getMessage(), [
                'site' => $site->getId(),
                'domain' => $domain
            ]);
        }

        return $metrics;
    }

    /**
     * @param Site $site
     * @param AbstractMetricsEntity $metricsEntity
     *
     * @throws \Exception
     */
    public function softUpdateMetrics(Site $site, AbstractMetricsEntity $metricsEntity)
    {
        $metrics = null;
        if ($site->getUpdateMetricsAt() && $site->getUpdateMetricsAt()->diff(new \DateTime())->days < 7) {
            $metrics = $this->em->getRepository(MetricsHistory::class)->getLatest($site);
        }
        if (!$metrics) {
            $metrics = $this->updateMetrics($site);
        }

        $this->updateMetricsEntitiesByMetrics($metricsEntity, $metrics);
    }

    /**
     * @param MetricsHistory $metricsHistory
     */
    public function updateMetricsEntitiesConnectedToSite(MetricsHistory $metricsHistory)
    {
        $site = $metricsHistory->getSite();

        $exchangeSiteRepository = $this->em->getRepository(ExchangeSite::class);
        $directoryRepository = $this->em->getRepository(Directory::class);

        $exchangeSites = $exchangeSiteRepository->getEntitiesBySite($site);
        $directories = $directoryRepository->getEntitiesBySite($site);

        foreach ($directories as $directory) {
            $this->updateMetricsEntitiesByMetrics($directory, $metricsHistory);
        }

        foreach ($exchangeSites as $exchangeSite) {
            $this->updateMetricsEntitiesByMetrics($exchangeSite, $metricsHistory);
        }
    }

    /**
     * @param Site $site
     */
    public function updateTTF(Site $site)
    {
        $exchangeSiteRepository = $this->em->getRepository(ExchangeSite::class);
        $directoryRepository = $this->em->getRepository(Directory::class);

        $exchangeSites = $exchangeSiteRepository->getEntitiesBySite($site);
        $directories = $directoryRepository->getEntitiesBySite($site);

        $ttfCategories = $this->majesticInfo->getTopicalTrustFlow($site->getUrl());

        foreach ($directories as $directory) {
            $directoryRepository->updateTtfCategories($directory, $ttfCategories);
        }

        foreach ($exchangeSites as $exchangeSite) {
            $exchangeSiteRepository->updateTtfCategories($exchangeSite, $ttfCategories);
        }
    }

    /**
     * @param MetricsHistory $metricsHistory
     * @param AbstractMetricsEntity $entity
     */
    public function updateMetricsEntitiesByMetrics(AbstractMetricsEntity $entity, MetricsHistory $metricsHistory)
    {
        foreach (MetricsTrait::getAllMetrics() as $metric => $value) {
            $newValue = call_user_func([$metricsHistory, 'get' . ucfirst($metric)]);
            call_user_func([$entity, 'set' . ucfirst($metric)], $newValue);
        }
    }
}
