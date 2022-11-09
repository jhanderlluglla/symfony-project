<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Services\Metrics\MetricsManager;
use CoreBundle\Services\MozInfo;
use CoreBundle\Utils\ExchangeSiteUtil;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateExchangeSitesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('app:update-exchange-sites')
            ->setDescription('Retrieve data from all APIs and update all exchange websites')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        /** @var MozInfo $mozInfo */
        $mozInfo = $this->getContainer()->get('core.service.moz_info');

        /** @var MetricsManager $metricsManager */
        $metricsManager = $this->getContainer()->get('core.service.metrics_manager');

        $exchangeSites = $em->getRepository(ExchangeSite::class)->findAll();

        $exchangeSitesDomains = array_map(function ($site) {
            return $site->getDomain();
        }, $exchangeSites);

        try {
            $mozData = $mozInfo->batchRetrieveData($exchangeSitesDomains);

            $iteration = 0;

            /** @var ExchangeSite $site */
            foreach ($exchangeSites as $site) {
                try {
                    $domain = $site->getDomain();

                    $pageAuthority = isset($mozData[$domain]['upa']) ? $mozData[$domain]['upa'] : null;
                    $domainAuthority = isset($mozData[$domain]['pda']) ? $mozData[$domain]['pda'] : null;
                    $site->setMozPageAuthority($pageAuthority);
                    $site->setMozDomainAuthority($domainAuthority);

                    $metricsManager->updateMetrics($site);

                    $bwaAge = $site->getBwaAge() ?? new \DateTime();
                    $age = floatval(ExchangeSiteUtil::dateDifference(new \DateTime(), $bwaAge, '%y.%m'));
                    $credits = ExchangeSiteUtil::creditAlgo($site->getMajesticTrustFlow(), $site->getMajesticRefDomains(), $age);
                    $creditPurchasePrice = $em->getRepository(Settings::class)->getSettingValue('prix_achat_credit');
                    $maximumCredits = max($credits['cred'] * $creditPurchasePrice, $site->getSemrushTraffic()/100, $site->getSemrushTrafficCost()/100);
                    $site->setMaximumCredits($maximumCredits);
                    if ($maximumCredits < $site->getCredits()) {
                        $site->setCredits($maximumCredits);
                    }

                    if ($iteration % 10 === 0) {
                        $em->flush();
                    }

                    $message = "ExchangeSite {$site->getUrl()}, ID:{$site->getId()} will be updated";
                    $output->writeln($message);
                    $monolog->info($message);

                    $iteration++;
                } catch (\Exception $e) {
                    $monolog->error('Can not update exchangeSite', [
                        'exchangeSiteId' => $site->getId(),
                        'message' => $e->getMessage(),
                        'TRACE' => $e->getTraceAsString()
                    ]);

                    $em->flush();
                }
            }
            $em->flush();

            $message = "All ExchangeSites are updated";
            $output->writeln($message);
            $monolog->info($message);
        } catch (ORMException $e) {
            $monolog->error('ORMException', [
                'message' => $e->getMessage(),
                'TRACE' => $e->getTraceAsString()
            ]);
        }
    }
}
