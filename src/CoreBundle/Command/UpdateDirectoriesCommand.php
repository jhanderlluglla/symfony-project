<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Directory;
use CoreBundle\Services\Metrics\MetricsManager;
use CoreBundle\Services\MozInfo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDirectoriesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('app:update-directories')
            ->setDescription('Retrieve data from all APIs and update all directories')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        /** @var MetricsManager $metricsManager */
        $metricsManager = $this->getContainer()->get('core.service.metrics_manager');

        $directories = $em->getRepository(Directory::class)->findAll();

        $directoriesDomains  = array_map(function ($directory) {
            return $directory->getDomain();
        }, $directories);

        /** @var MozInfo $mozInfo */
        $mozInfo = $this->getContainer()->get('core.service.moz_info');
        $mozData = $mozInfo->batchRetrieveData($directoriesDomains);

        try {
            $iteration = 0;

            /** @var Directory $directory */
            foreach ($directories as $directory) {
                try {
                    $domain = $directory->getDomain();

                    $pageAuthority = isset($mozData[$domain]['upa']) ? $mozData[$domain]['upa'] : null;
                    $domainAuthority = isset($mozData[$domain]['pda']) ? $mozData[$domain]['pda'] : null;
                    $directory->setMozPageAuthority($pageAuthority);
                    $directory->setMozDomainAuthority($domainAuthority);

                    $metricsManager->updateMetrics($directory);

                    if ($iteration % 10 === 0) {
                        $em->flush();
                    }

                    $message = "Directory {$directory->getName()}, ID:{$directory->getId()} will be updated";
                    $output->writeln($message);
                    $monolog->info($message);

                    $iteration++;
                } catch (\Exception $e) {
                    $monolog->error('Can not update directory', [
                        'directoryId' => $directory->getId(),
                        'message' => $e->getMessage(),
                        'TRACE' => $e->getTraceAsString()
                    ]);

                    $em->flush();
                }
            }
            $em->flush();

            $message = "All Directories are updated";
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