<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\TransactionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Doctrine\ORM\EntityManager;
use CoreBundle\Entity\Category;
use CoreBundle\Entity\Settings;

class RefundExpressOrderCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * @var TransactionService
     */
    private $transactionService;

    protected function configure()
    {
        $this->setName('app:refund:express-order')
            ->setDescription('Refund additional cost of express order.')
            ->addArgument('order-id', InputArgument::REQUIRED, 'Express order to refund.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->transactionService = $this->getContainer()->get('core.service.transaction');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getOption('order-id');
        /** @var CopywritingOrder $order */
        $order = $this->entityManager->getRepository(CopywritingOrder::class)->find($id);

        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        if ($order->isExpress() && !$order->isCompleted()) {
            try {
                $order->setDeadline(null);

                if ($order->isDelayed()) {
                    $order->getCopywriter()->decBalance($order->getWriterExpressBonus());
                }

                /** @var TransactionService $transactionService */
                $transactionService = $this->transactionService;

                $transactionService->handling(
                    $order->getCustomer(),
                    new TransactionDescriptionModel('copywriting_order.express_order_refund', ['%order_title%' => $order->getTitle()]),
                    $order->getExpressBonus(),
                    0,
                    null,
                    [CopywritingOrder::TRANSACTION_TAG_EXPRESS_REFUND]
                );

                $this->entityManager->persist($order);
                $this->entityManager->flush();

                $message = "Order {$order->getTitle()}, ID:{$order->getId()} refunded";
                $output->writeln($message);
                $monolog->info($message);

            }catch (\Exception $exception) {
                $monolog->error("Error refund express order", [
                    'orderId' => $order->getId(),
                    'message' => $exception->getMessage(),
                    'TRACE' => $exception->getTraceAsString()
                ]);
            }
        }
    }
}