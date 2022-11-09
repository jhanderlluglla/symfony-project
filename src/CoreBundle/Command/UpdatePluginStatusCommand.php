<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeSite;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserBundle\Services\CopywritingArticleProcessor;

class UpdatePluginStatusCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:update-plugin-status')
            ->setDescription('Updating plugin status for every site')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var CopywritingArticleProcessor $copywritingArticleProcessor */
        $copywritingArticleProcessor = $this->getContainer()->get('user.copywriting.article_processor');

        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        $exchangeSites = $em->getRepository(ExchangeSite::class)->findSuccessfulPluginStatus();

        /** @var ExchangeSite $site */
        foreach ($exchangeSites as $site) {
            try {
                $response = $copywritingArticleProcessor->testPluginConnection($site);
                $message = "{$site->getUrl()}, ID:{$site->getId()} connection not established";

                $context = ['response' => $response];

                if ($response['status'] === false) {
                    $site->setPluginStatus(false);
                    $monolog->warning($message, $context);
                } else {
                    if (!empty($response['plugin_url'])) {
                        $site->setPluginUrl($response['plugin_url']);
                        $site->setPluginStatus(true);
                        $message = "{$site->getUrl()}, ID:{$site->getId()} connection established";

                        $monolog->info($message, $context);
                    }
                }
                $em->flush();

                $output->writeln($message);
            } catch (\Exception $e) {
                $monolog->error("Exception while updating the site", [
                    'siteId' => $site->getId(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
