<?php

namespace CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserBundle\Services\BackLinksCheckerService;

/**
 * Class NetLinkingCommand
 *
 * @package Command
 */
class NetLinkingCommand extends Command
{
    private $backLinksChecker;

    public function __construct(BackLinksCheckerService $backLinksChecker, $name = null)
    {
        $this->backLinksChecker = $backLinksChecker;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('app:netlinking')
            ->setDescription('Start netlinking jobs')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->backLinksChecker->newCheck();
    }
}
