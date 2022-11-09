<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\MetricsHistory;
use CoreBundle\Services\GoogleNewsInfo;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateGoogleNewsCommand
 * @package CoreBundle\Command
 */
class UpdateGoogleNewsCommand extends ContainerAwareCommand
{
    const GOOGLE_NEWS_REQUEST_LIMIT = 250;

    /** @var OutputInterface */
    private $output;

    /** @var LoggerInterface */
    private $monolog;

    /** @var EntityManager */
    private $em;

    protected function configure()
    {
        $this->setName('app:update-google-news')
            ->setDescription('Retrieve data from google-news API and update all exchange websites')
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
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->monolog = $this->getContainer()->get("monolog.logger.cron");
        $this->output = $output;

        $metricsHistory = $this->em->getRepository(MetricsHistory::class)->metricsForGoogleNewsUpdate(self::GOOGLE_NEWS_REQUEST_LIMIT);

        $this->getGoogleNews($metricsHistory);
    }

    /**
     * @param array $metricsHistory
     */
    private function getGoogleNews(array $metricsHistory)
    {
        /** @var GoogleNewsInfo $googleNews */
        $googleNews = $this->getContainer()->get('core.service.google_news_info');
        $metricHistoryService = $this->getContainer()->get('core.service.metrics_manager');

        try {
            /** @var MetricsHistory $metricHistory */
            foreach ($metricsHistory as $metricHistory) {
                $site = $metricHistory->getSite();
                $domain = $site->getDomain();
                $googleNewsSource = $googleNews->isSource($domain);
                if ($googleNewsSource === false) {
                    continue;
                }
                $metricHistory->setGoogleNews($googleNewsSource);

                $metricHistoryService->updateMetricsEntitiesConnectedToSite($metricHistory);

                $this->log("Google news of site: {$site->getUrl()}, ID:{$site->getId()} was updated");
            }
            $this->em->flush();

            $this->log('All sites updated');
        } catch (\Exception $exception) {
            $this->monolog->error('Can not update exchangeSite', [
                'message' => $exception->getMessage(),
                'TRACE' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * @param string $message
     */
    private function log($message)
    {
        $this->output->writeln($message);
        $this->monolog->info($message);
    }
}
