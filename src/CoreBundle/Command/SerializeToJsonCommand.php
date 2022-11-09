<?php

namespace CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SerializeToJsonCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('app:json-array')
            ->setDescription('Migrate field from serialize to json')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $connect = $em->getConnection();

        foreach ($connect->fetchAll('SELECT `id`, `check_links` FROM `exchange_proposition`') as $item) {
            try {
                $unserialize = unserialize($item['check_links']);
            } catch (\Exception $exception) {
                continue;
            }

            $connect->executeQuery('UPDATE `exchange_proposition` SET `check_links` = :check_links WHERE id = :id', ['id' => $item['id'], 'check_links' => json_encode($unserialize, JSON_UNESCAPED_UNICODE)]);
        }


        foreach ($connect->fetchAll('SELECT `id`, `links` FROM `copywriting_order`') as $item) {
            try {
                $unserialize = unserialize($item['links']);
            } catch (\Exception $exception) {
                continue;
            }
            $connect->executeQuery('UPDATE `copywriting_order` SET `links` = :links WHERE id = :id', ['id' => $item['id'], 'links' => json_encode($unserialize, JSON_UNESCAPED_UNICODE)]);
        }
    }
}
