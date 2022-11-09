<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeProposition;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProposalsMoveTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:proposals_move_transaction')
            ->setDescription('After use - delete!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $proposalRepository = $em->getRepository(ExchangeProposition::class);
        $list = $proposalRepository
            ->createQueryBuilder('ep')
            ->andWhere('ep.buyerTransaction IS NOT NULL OR ep.sellerTransaction IS NOT NULL')
            ->getQuery()
            ->getResult()
        ;

        if (count($list) === 0) {
            $output->writeln('Proposals for processing not found. If you received this message on the prod server, then delete me');

            return;
        }

        $transactionService = $this->getContainer()->get('core.service.transaction');

        /** @var ExchangeProposition $proposal */
        foreach ($list as $proposal) {
            $output->writeln('Proposal: #'.$proposal->getId());
            if ($proposal->getBuyerTransaction()) {
                $transactionService->addTagToTransaction($proposal->getBuyerTransaction(), ExchangeProposition::TRANSACTION_TAG_BUY);
                $proposal->addTransaction($proposal->getBuyerTransaction());

                $output->writeln("\tmove buyer transaction: #".$proposal->getBuyerTransaction()->getId());
            }
            if ($proposal->getSellerTransaction()) {
                $transactionService->addTagToTransaction($proposal->getSellerTransaction(), ExchangeProposition::TRANSACTION_TAG_REWARD);
                $proposal->addTransaction($proposal->getSellerTransaction());

                $output->writeln("\tmove seller transaction: #".$proposal->getSellerTransaction()->getId());
            }
        }

        $em->flush();

        $output->writeln('Finish her!');
    }
}
