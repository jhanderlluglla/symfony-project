<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Interfaces\LanguageInterface;
use CoreBundle\Entity\Interfaces\SiteUrlInterface;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Site;
use CoreBundle\Helpers\SiteHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteMigration
 * @package CoreBundle\Command
 */
class SiteMigrationCommand extends ContainerAwareCommand
{
    /** @var EntityManager */
    private $em;

    /** @var OutputInterface */
    private $output;

    /** @var Site[] */
    private $sites = [];

    protected function configure()
    {
        $this->setName('app:site-migration');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->output = $output;

        foreach ($this->em->getRepository(Site::class)->findAll() as $site) {
            $this->addSite($site);
        }

        $exchangeSites = $this->em->getRepository(ExchangeSite::class)->createQueryBuilder('e')->andWhere('e.site IS NULL')->getQuery()->getResult();
        $this->updateEntityList($exchangeSites);

        $directories = $this->em->getRepository(Directory::class)->createQueryBuilder('e')->andWhere('e.site IS NULL')->getQuery()->getResult();
        $this->updateEntityList($directories);

        $netlinkingProjects = $this->em->getRepository(NetlinkingProject::class)->createQueryBuilder('e')->andWhere('e.site IS NULL')->getQuery()->getResult();
        $this->fixUrlForList($netlinkingProjects);
        $this->updateEntityList($netlinkingProjects);

        $this->em->flush();

        $this->output->writeln("\e[32mDone\e[0m");
    }

    /**
     * @param array $entityList
     */
    public function updateEntityList($entityList)
    {
        /** @var SiteUrlInterface|LanguageInterface $entity */
        foreach ($entityList as $entity) {
            if (strpos($entity->getUrl(), 'test-start-popup') !== false) {
                $a = 1;
            }
            $host = SiteHelper::prepareHost(parse_url($entity->getUrl(), PHP_URL_HOST));
            if (!$host) {
                $this->output->writeln("\e[0;31mError parse host\e[0m \"". addslashes($entity->getUrl()).'" '.get_class($entity).'#'.$entity->getId());
                continue;
            }
            if (!SiteHelper::validationHost($host)) {
                $this->output->writeln("\e[0;31mHost invalid\e[0m \"".$host.'" ('. addslashes($entity->getUrl()).') '.get_class($entity).'#'.$entity->getId());
                continue;
            }

            $site = $this->getSite($host, $entity->getLanguage());
            if (!$site) {
                $site = new Site();
                $site
                    ->setHost($host)
                    ->setLanguage($entity->getLanguage())
                    ->setScheme(parse_url($entity->getUrl(), PHP_URL_SCHEME))
                ;
                $this->addSite($site);
            }

            $entity->setSite($site);
        }
    }

    /**
     * @param $url
     *
     * @return mixed|string|string[]|null
     */
    public function fixUrl($url)
    {
        $fixUrl = trim($url);
        $fixUrl = strtr($fixUrl, [
            'htpp' => 'http',
            'HTTP' => 'http',
            'htps' => 'https',
        ]);
        if (substr_count($fixUrl, 'http') > 1) {
            $fixUrl = preg_replace('~^\s*https?://.*?(?=http)~ui', '', $fixUrl);
        }
        $fixUrl = preg_replace('~(https?)(:+|;+)//?\.?~ui', '$1://', $fixUrl);
        $host = parse_url($fixUrl, PHP_URL_HOST);

        if (!$host || substr_count($host, '.') === 0) {
            return null;
        }

        $fixHost = SiteHelper::prepareHost($host);
        $fixHost = strtr($fixHost, ['+' => '-', '_' => '-']);
        $fixHost = preg_replace('~\s~ui', '', $fixHost);
        $fixHost = preg_replace('~[,.]+$~ui', '', $fixHost);

        $partsHost = explode('.', $fixHost);
        $domainName =  $partsHost[substr_count($fixHost, '.')];
        $domainName = preg_replace('~\d~', '', $domainName);
        switch ($domainName) {
            case 'f':
            case 'frdf':
                $domainName = 'fr';
                break;
        }
        $partsHost[substr_count($fixHost, '.')] = $domainName;

        $fixHost = implode('.', $partsHost);

        $fixUrl = str_replace($host, $fixHost, $fixUrl);

        return $fixUrl;
    }

    /**
     * @param $list
     */
    public function fixUrlForList($list)
    {
        /** @var Directory|ExchangeSite $entity */
        foreach ($list as $entity) {
            if ($entity instanceof NetlinkingProject && $entity->getId() === 18) {
                $a = 1;
            }
            $fixUrl = $this->fixUrl($entity->getUrl());
            $validFixUrl = SiteHelper::validationHost(SiteHelper::prepareHost(parse_url($fixUrl, PHP_URL_HOST)));
            if ($fixUrl !== $entity->getUrl() && $validFixUrl) {
                $this->output->writeln("\e[0;34mFix url\e[0m for ".get_class($entity).' #'.$entity->getId().': "'.$fixUrl."\"\t\t<====\t\t\"".addslashes($entity->getUrl()).'"');
            }

            if (!$validFixUrl) {
                $this->output->writeln("\e[0;33mERROR\e[0m fix url for ".get_class($entity).' #'.$entity->getId().': "'.$fixUrl."\"\t\t<====\t\t\"".addslashes($entity->getUrl()).'"');
            } else {
                $entity->setUrl($fixUrl);
            }
        }
    }

    /**
     * @param $host
     * @param $language
     *
     * @return Site|null
     */
    private function getSite($host, $language)
    {
        if (!isset($this->sites[$host])) {
            return null;
        }
        $sites = $this->sites[$host];
        foreach ($sites as $site) {
            if ($site['language'] === $language) {
                return $site['object'];
            }
        }

        return null;
    }

    /**
     * @param Site $site
     */
    private function addSite(Site $site)
    {
        $this->sites[$site->getHost()][] = ['language' => $site->getLanguage(), 'object' => $site];
    }
}
