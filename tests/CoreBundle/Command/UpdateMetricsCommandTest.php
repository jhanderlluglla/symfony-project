<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\Test\LoadDirectoryData;
use CoreBundle\DataFixtures\Test\LoadExchangeSiteData;
use CoreBundle\DataFixtures\Test\LoadNetlinkingProjectData;
use CoreBundle\Entity\AbstractMetricsEntity;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\MetricsHistory;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Site;
use CoreBundle\Entity\Traits\MetricsTrait;
use CoreBundle\Helpers\ArrayHelper;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\AbstractTest;

class UpdateMetricsCommandTest extends AbstractTest
{
    private const IGNORE_METRICS_UPDATE_FIELD = ['googleIndexedPages'];

    public function testUpdateMetrics()
    {
        $this->loadFixtures(
            [
                LoadExchangeSiteData::class,
                LoadNetlinkingProjectData::class,
                LoadDirectoryData::class,
            ]
        );

        # Update Metrics Command
        $command = $this->container()->get('core.command.update_metrics');
        $command->setContainer($this->container());

        $buffer = new BufferedOutput();
        $command->run(new ArrayInput([]), $buffer);
        $outputUpdateMetrics = $buffer->fetch();

        # Update Google News
        $command = $this->container()->get('core.command.update_google_news');
        $command->setContainer($this->container());

        $buffer = new BufferedOutput();
        $command->run(new ArrayInput([]), $buffer);
        $outputUpdateGoogleNews = $buffer->fetch();

        $sites = $this->em()->getRepository(Site::class)->findAll();

        foreach ($sites as $site) {
            self::assertContains('Site '.$site->getHost().', ID:'.$site->getId().' will be updated', $outputUpdateMetrics);
            self::assertContains('Google news of site: '.$site->getUrl().', ID:'.$site->getId().' was updated', $outputUpdateGoogleNews);
        }

        $metricsArray = $this->em()->getRepository(MetricsHistory::class)->findAll();

        self::assertCount(count($sites), $metricsArray);

        foreach ($metricsArray as $metrics) {
            $this->checkMetricHistory($metrics);
            $exchangeSites = $this->em()->getRepository(ExchangeSite::class)->getEntitiesBySite($metrics->getSite());
            $directories = $this->em()->getRepository(Directory::class)->getEntitiesBySite($metrics->getSite());
            $this->checkEqualsMetricsAndMetricsEntity(array_merge($exchangeSites, $directories), $metrics);
        }
    }

    /**
     * @param MetricsHistory $metricsHistory
     */
    private function checkMetricHistory(MetricsHistory $metricsHistory)
    {
        foreach (MetricsTrait::getAllMetrics() as $metricName => $value) {
            if (in_array($metricName, self::IGNORE_METRICS_UPDATE_FIELD)) {
                continue;
            }

            self::assertNotNull(call_user_func([$metricsHistory, 'get' . ucfirst($metricName)]), 'Metrics "'.$metricName.'" can not be null');
        }
    }

    /**
     * @param AbstractMetricsEntity[] $entities
     * @param MetricsHistory $metrics
     */
    private function checkEqualsMetricsAndMetricsEntity($entities, MetricsHistory $metrics)
    {
        foreach ($entities as $entity) {
            foreach (MetricsTrait::getAllMetrics() as $metricName => $value) {
                self::assertEquals(
                    call_user_func([$metrics, 'get'.ucfirst($metricName)]),
                    call_user_func([$entity, 'get'.ucfirst($metricName)])
                );
            }

            self::assertNotNull($entity->getMajesticTtfCategories());
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testFrequency()
    {
        $this->loadFixtures(
            [
                LoadExchangeSiteData::class,
                LoadNetlinkingProjectData::class,
                LoadDirectoryData::class,
            ]
        );

        $siteRepository = $this->em()->getRepository(Site::class);
        $sites = ArrayHelper::toAssoc($this->em()->getRepository(Site::class)->findAll(), 'host');

        /** @var NetlinkingProject[] $netlinkingPojects */
        $netlinkingPojects = ArrayHelper::toAssoc($this->em()->getRepository(NetlinkingProject::class)->findAll(), 'comment');

        // All sites have a null updateMetricsAt - update all sites
        self::checkFrequencyArray($siteRepository->getUpdateFrequency(), 3, ['google.com' => 14, 'site.fr' => 14, 'abc.com' => 14]);

        // Netlinking project awaiting assign writer
        $netlinkingPojects['Project 2']->setStatus(NetlinkingProject::STATUS_WAITING);
        $this->em()->flush();
        self::checkFrequencyArray($siteRepository->getUpdateFrequency(), 4, ['google.com' => 14, 'site.fr' => 14, 'abc.com' => 14, 'site.com' => 7]);


        // Netlinking project not started
        $this->changeAllEntities($sites, ['updateMetricsAt' => new \DateTime('-7 days')]);
        self::assertCount(1, $siteRepository->getUpdateFrequency());


        // Netlinking project in_progress
        $netlinkingPojects['Project 1']->setStatus(NetlinkingProject::STATUS_IN_PROGRESS);
        $this->em()->flush();
        self::checkFrequencyArray($siteRepository->getUpdateFrequency(), 2, ['google.com' => 7]);


        // Netlinking project finished and webmaster spent >= 250 euro
        $this->changeAllEntities($netlinkingPojects, ['finishedAt' => new \DateTime(), 'status' => NetlinkingProject::STATUS_FINISHED]);
        self::assertCount(1, $siteRepository->getUpdateFrequency());


        // Netlinking project finished 180 days ago and webmaster spent >= 250 euro
        $netlinkingPojects['Project 1']->setFinishedAt(new \DateTime('-180 days'));
        $this->em()->flush();
        self::assertCount(1, $siteRepository->getUpdateFrequency());


        // Netlinking project finished 190 days ago and webmaster spent >= 250 euro
        $netlinkingPojects['Project 1']->setFinishedAt(new \DateTime('-190 days'));
        $this->em()->flush();
        self::assertCount(0, $siteRepository->getUpdateFrequency());
    }

    /**
     * @param object[] $sites
     * @param array $propertyValues
     */
    public function changeAllEntities($sites, $propertyValues)
    {
        foreach ($sites as $site) {
            foreach ($propertyValues as $property => $value) {
                call_user_func([$site, 'set' . ucfirst($property)], $value);
            }
        }
        try {
            $this->em()->flush();
        } catch (OptimisticLockException $e) {
        };
    }

    /**
     * @param $frequencyArray
     * @param $count
     * @param $expectedFrequency
     */
    public static function checkFrequencyArray($frequencyArray, $count, $expectedFrequency = [])
    {
        self::assertCount($count, $frequencyArray);
        $frequencyArray = ArrayHelper::toAssoc($frequencyArray, '[host]');
        foreach ($expectedFrequency as $key => $value) {
            self::assertEquals(intval($frequencyArray[$key]['frequency']), $value, 'Host: ' . $key);
        }
    }
}
