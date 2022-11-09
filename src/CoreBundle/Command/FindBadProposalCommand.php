<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindBadProposalCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:find_bad_proposal')
            ->setDescription('Apply expired propositions')
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
        $exchangePropositionRepository = $em->getRepository(ExchangeProposition::class);

        for ($i = 0; $i < 7550; ++$i) {
            try {
                $exchangePropositionRepository->find($i);
            } catch (\Exception $exception) {
                echo 'Bad item EP: ', $i, ' | ', $exception->getMessage(), "\n";
            }
        }

        $copywritingOrderRepository = $em->getRepository(CopywritingOrder::class);
        for ($i = 0; $i < 25000; ++$i) {
            try {
                $copywritingOrderRepository->find($i);
            } catch (\Exception $exception) {
                echo 'Bad item CO: ', $i, ' | ', $exception->getMessage(), "\n";
            }
        }
    }
}
