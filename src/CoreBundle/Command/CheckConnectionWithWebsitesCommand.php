<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckConnectionWithWebsitesCommand extends ContainerAwareCommand
{
    const MAX_CONNECTION_FAILS = 2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function configure()
    {
        $this->setName('app:check-connection-with-sites')
            ->setDescription('Check connection with directories and blogs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->logger = $this->getContainer()->get("monolog.logger.cron");
        $filters = [
            'active' => true,
        ];
        $exchangeSites = $em->getRepository(ExchangeSite::class)->findBy($filters);

        try {
            /** @var ExchangeSite $site */
            foreach ($exchangeSites as $site) {
                $this->checkConnection($site);
            }

            $directories = $em->getRepository(Directory::class)->findBy($filters);

            /** @var Directory $directory */
            foreach ($directories as $directory) {
                $this->checkConnection($directory);
            }
            $em->flush();
        } catch (\Exception $e) {
            $em->flush();
        }
    }

    /**
     * @param $site
     * @throws \Exception
     */
    private function checkConnection($site)
    {
        try {
            $ch = curl_init($site->getHost());
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_errno($ch);
            curl_close($ch);


            if ($error) {
                $this->disableSite($site);
            } else {
                if ($httpCode >= 400) {
                    $this->disableSite($site);
                }
            }
        } catch (\Exception $e) {
            $this->disableSite($site);
            throw new \Exception("Error with saving website");
        }
    }

    /**
     * @param $site
     */
    protected function disableSite($site)
    {
        if ($site->getNumberOfFails() >= self::MAX_CONNECTION_FAILS) {
            $this->logger->error("Disable site id: {$site->getID()}");
            $site->setActive(false);
            $site->setNumberOfFails(0);
        } else {
            $site->incNumberOfFails();
        }
    }
}
