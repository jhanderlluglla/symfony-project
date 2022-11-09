<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\User;
use CoreBundle\Repository\ExchangePropositionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendReminderPropositionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:send_reminder_propositions')
            ->setDescription('Sends a reminder of new propositions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        $exchangePropositionService = $this->getContainer()->get('core.service.exchange_proposition');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var ExchangePropositionRepository $exchangePropositionRepository */
        $exchangePropositionRepository = $em->getRepository(ExchangeProposition::class);

        $proposals = $exchangePropositionRepository->getPropositionsForRemind()->getQuery()->getResult();

        $sortProposals = [];

        /** @var ExchangeProposition $proposal */
        foreach ($proposals as $proposal) {
            $key = $proposal->getExchangeSite()->getUser()->getId();
            if (!isset($sortProposals[$key])) {
                $sortProposals[$key] = [];
            }

            $sortProposals[$key][] = $proposal;
        }

        /** @var ExchangeProposition[] $arrayProposals */
        foreach ($sortProposals as $arrayProposals) {
            /** @var User $user */
            $user = $arrayProposals[0]->getExchangeSite()->getUser();
            if ($exchangePropositionService->sendReminder($user, $arrayProposals)) {
                $message = 'ExchangeProposition: send reminder to user #' . $user->getId(). ' [' . $user->getEmail() . ']';
                $output->writeln($message);
                $monolog->info($message);
            }
        }
    }
}
