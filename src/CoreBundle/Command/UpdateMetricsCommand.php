<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Site;
use CoreBundle\Services\Metrics\MetricsManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateMetricsCommand
 * @package CoreBundle\Command
 */
class UpdateMetricsCommand extends ContainerAwareCommand
{
    /** @var LoggerInterface */
    private $monolog;

    /** @var OutputInterface */
    private $output;


    protected function configure()
    {
        $this
            ->setName('app:update-metrics')
            ->setDescription('Retrieve data from all APIs and update all sites')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->monolog = $this->getContainer()->get('monolog.logger.cron');

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var MetricsManager $metricsManager */
        $metricsManager = $this->getContainer()->get('core.service.metrics_manager');

        $sites = $em->getRepository(Site::class)->getSitesForMetricsUpdate();

        if (empty($sites)) {
            $this->log('No sites to update');

            return;
        }

        $siteDomains = array_map(function ($site) {
            return $site->getDomain();
        }, $sites);

        try {
            $iteration = 0;

            $metricsManager->initMozDataMetrics($siteDomains);

            /** @var Site $site */
            foreach ($sites as $site) {
                try {
                    $metrics = $metricsManager->updateMetrics($site);
                    $em->persist($metrics);

                    $metricsManager->updateMetricsEntitiesConnectedToSite($metrics);
                    $metricsManager->updateTTF($site);

                    $this->updatePrice($site);

                    $site->setUpdateMetricsAt($metrics->getCreatedAt());

                    if ($iteration % 10 === 0) {
                        $em->flush();
                    }

                    $this->log("Site {$site->getHost()}, ID:{$site->getId()} will be updated");

                    $iteration++;
                } catch (\Exception $e) {
                    $this->monolog->error('Can not update site', [
                        'siteId' => $site->getId(),
                        'message' => $e->getMessage(),
                        'TRACE' => $e->getTraceAsString()
                    ]);

                    $em->flush();
                }
            }
            $em->flush();

            $this->log('All Sites are updated');
        } catch (ORMException $e) {
            $this->monolog->error('ORMException', [
                'message' => $e->getMessage(),
                'TRACE' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * @param $message
     */
    private function log($message)
    {
        $this->output->writeln($message);
        $this->monolog->info($message);
    }

    /**
     * @param Site $site
     *
     * @throws \Exception
     */
    private function updatePrice(Site $site)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $exchangeSiteService = $this->getContainer()->get('core.service.exchange_site');
        $exchangeSites = $em->getRepository(ExchangeSite::class)->getEntitiesBySite($site);
        foreach ($exchangeSites as $exchangeSite) {
            $exchangeSiteService->updatePrice($exchangeSite);
        }
    }
}
