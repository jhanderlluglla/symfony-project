<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NetlinkingSeparateCommentCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('app:netlinking_separate_command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $copywritingProjectQb = $em->getRepository(CopywritingProject::class)->createQueryBuilder('cp');
        $copywritingProjectQb->andWhere('cp.description LIKE \'%Rule of the site%\'');
        $translator = $this->getContainer()->get('translator');

        /** @var CopywritingProject $project */
        foreach ($copywritingProjectQb->getQuery()->getResult() as $project) {
            if($project->getId() === 33744) {
                $a = 1;
            }
            if (preg_match('~Rule of the site: (.*)Customer\'s instructions: (.*)~', $project->getDescription(), $matches)) {
                $ruleOfSite = $matches[1];
                $customerInstruction = $matches[2];
            } elseif (preg_match('~Rule of the site: (.*)~', $project->getDescription(), $matches)) {
                $ruleOfSite = $matches[1];
                $customerInstruction = null;
            } else {
                $ruleOfSite = null;
                $customerInstruction = null;
            }

            $instruction = [];
            if (!empty($ruleOfSite) && $ruleOfSite !== 'aucune' && $ruleOfSite !== 'No instruction') {
                $instruction[] = $translator->trans('modal.writing_ereferer.drafting_projects.description.rules', ['%rule%' => $ruleOfSite], 'exchange_site_find');
            }

            if (!empty($customerInstruction) && $customerInstruction !== 'Optional') {
                $instruction[] = $translator->trans('modal.writing_ereferer.drafting_projects.description.instructions', ['%instructions%' => $customerInstruction], 'exchange_site_find');
            }

            $instruction = empty($instruction) ? null : implode("\n\n", $instruction);

            $output->writeln('Update project: ' . $project->getId());

            /** @var CopywritingOrder $order */
            $setOrder = false;
            foreach ($project->getOrders() as $order) {
                if ($order->getInstructions()) {
                    $output->writeln("\tOrder " . $order->getId() . ' already has instructions');
                    continue;
                }

                $order->setInstructions($instruction);
                $setOrder = true;
                $output->writeln("\tUpdate order " . $order->getId());
            }

            $project->setDescription($setOrder ? null : $instruction);
        }

        $em->flush();

        $output->writeln('Success');
    }
}
