<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Job;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobHoldingRefundCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:job-holding-refund')
            ->setDescription('Return withheld money. Run every minute.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $jobService = $this->getContainer()->get('core.service.job');

        /** @var Job $job */
        foreach ($em->getRepository(Job::class)->getOverTimeInProgressJobs() as $job) {
            try {
                $jobService->applyTransition($job, Job::TRANSITION_EXPIRED_HOLD);

                $message = 'Job #'.$job->getId().' is expired hold money';
            } catch (\Exception $exception) {
                $message = 'Error! Job #'.$job->getId().': '.$exception->getMessage();
            }

            $output->writeln($message);
            $monolog->info($message);
        }
    }
}
