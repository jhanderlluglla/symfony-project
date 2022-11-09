<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Candidate;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\WaitingOrder;
use CoreBundle\Repository\WaitingOrderRepository;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyWaitingOrderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:apply-waiting-order')
            ->setDescription('Apply expired orders to candidate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var WaitingOrderRepository $waitingOrderRepository */
        $waitingOrderRepository = $em->getRepository(WaitingOrder::class);
        $expiredOrders = $waitingOrderRepository->getExpiredOrders();

        if(count($expiredOrders) > 0) {
            $candidates = $em->getRepository(Candidate::class)->getCandidatesByIds(
                array_column($expiredOrders, 'candidateId')
            );

            /** @var Candidate $candidate */
            foreach ($candidates as $candidate) {
                try {
                    $waitingOrder = $candidate->getWaitingOrder();
                    $waitingOrder->setStatus(WaitingOrder::STATUS_ASSIGNED);
                    $candidate->setAction(Candidate::ACTION_ASSIGNED_EXPIRED);
                    $copywritingOrder = $waitingOrder->getCopywritingOrder();

                    $orderWorkflow = $this->getContainer()->get('workflow.registry')->get($copywritingOrder);
                    if (!$orderWorkflow->can($copywritingOrder, CopywritingOrder::TRANSITION_TAKE_TO_WORK)) {
                        throw new \LogicException("Bad data, can not take order waitingOrderId: {$waitingOrder->getId()}, copywritingOrderId: {$copywritingOrder->getId()}");
                    }

                    $orderWorkflow->apply($copywritingOrder, CopywritingOrder::TRANSITION_TAKE_TO_WORK);
                    $copywritingOrder->setCopywriter($candidate->getUser());

                    $em->persist($waitingOrder);
                    $em->persist($candidate);
                    $em->persist($copywritingOrder);
                }catch (\Exception $exception){
                    $monolog->error('Error with assign waiting order', [
                        'copywritingOrderId' => $copywritingOrder->getId(),
                        'waitingOrderId' => $waitingOrder->getId(),
                        'message' => $exception->getMessage(),
                        'TRACE' => $exception->getTraceAsString()
                    ]);
                }
            }

            $em->flush();

            $ids = array_column($expiredOrders, 'waitingOrderId');
            $stringIds = implode(',', $ids);
            $message = "Modify orders with id: {$stringIds}";
        }else{
            $message = "Not found expired orders";
        }

        $output->writeln($message);
        $monolog->info($message);
    }
}