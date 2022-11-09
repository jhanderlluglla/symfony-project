<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Anchor;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CreateExchangePropositionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:create-exchange-propositions')
            ->setDescription('Create proposition from netlinking project')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var LoggerInterface $monolog */
        $monolog = $this->getContainer()->get("monolog.logger.cron");

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var TransactionService $transactionService */
        $exchangeCalculatorPrice = $this->getContainer()->get('user.exchange.calculator_price');

        $exchangePropositionService = $this->getContainer()->get('core.service.exchange_proposition');

        $scheduleTasks = $em->getRepository(ScheduleTask::class)->getExchangeSitesTasks();

        $anchorRepository = $em->getRepository(Anchor::class);

        $transactionService = $this->getContainer()->get('core.service.transaction');

        /** @var ScheduleTask $task */
        foreach ($scheduleTasks as $task) {
            try {
                $exchangeSite = $task->getExchangeSite();
                $netlinkingProject = $task->getNetlinkingProject();
                $anchor = $anchorRepository->findOneBy(['netlinkingProject' => $netlinkingProject, 'exchangeSite' => $exchangeSite]);
                $clientUser = $netlinkingProject->getUser();
                $directoryListWordsCount = $netlinkingProject->getWordsCount();
                $taskWords = $directoryListWordsCount > $exchangeSite->getMinWordsNumber() ? $directoryListWordsCount : $exchangeSite->getMinWordsNumber();

                $links = [
                    [
                        'url' => $netlinkingProject->getUrl(),
                        'anchor' => $anchor !== null ? $anchor->getName() : $netlinkingProject->getUrl(),
                    ]
                ];

                try {
                    $transaction = $exchangePropositionService->paymentForExchangeProposal(
                        $clientUser,
                        new TransactionDescriptionModel('proposal.pay_for_proposition', ['%url%' => $exchangeSite->getUrl()]),
                        ExchangeProposition::ARTICLE_AUTHOR_WRITER,
                        $exchangeSite
                    );
                } catch (NotEnoughMoneyDetailException $e) {
                    continue;
                }

                $transactionService->addTagToTransaction($transaction, [CopywritingOrder::TRANSACTION_TAG_BUY, Job::TRANSACTION_TAG_BUY]);

                $job = new Job();
                $job
                    ->setNetlinkingProject($netlinkingProject)
                    ->setScheduleTask($task)
                    ->setCreatedAt($task->getStartAt())
                    ->setCostWebmaster($transaction->getCredit())
                    ->addTransaction($transaction)
                ;

                $exchangeProposition = new ExchangeProposition();
                $priceWithCommission = $exchangeCalculatorPrice->getPriceWithCommission($exchangeSite->getCredits());
                $exchangeProposition
                    ->setUser($clientUser)
                    ->setExchangeSite($exchangeSite)
                    ->setCredits($priceWithCommission)
                    ->setCheckLinks($links)
                    ->setJob($job)
                    ->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WRITER)
                    ->addTransaction($transaction)
                ;

                $em->persist($exchangeProposition);
                $em->flush();

                $instruction = [];

                if (!empty($exchangeSite->getPublicationRules())) {
                    $instruction[] = $translator->trans('modal.writing_ereferer.drafting_projects.description.rules', ['%rule%' => $exchangeSite->getPublicationRules()], 'exchange_site_find');
                }

                if (!empty($netlinkingProject->getComment())) {
                    $instruction[] = $translator->trans('modal.writing_ereferer.drafting_projects.description.instructions', ['%instructions%' => $netlinkingProject->getComment()], 'exchange_site_find');
                }

                $title = $translator->trans('modal.writing_ereferer.drafting_projects.title', [], 'exchange_site_find');

                $copywritingOrder = new CopywritingOrder();
                $copywritingOrder
                    ->setExchangeProposition($exchangeProposition)
                    ->setLinks($links)
                    ->setTitle($title . ' ' . $exchangeProposition->getId())
                    ->setWordsNumber($taskWords)
                    ->setMetaTitle($exchangeSite->getMetaTitle())
                    ->setMetaDescription($exchangeSite->getMetaDescription())
                    ->setHeaderOneSet($exchangeSite->getHeaderOneSet())
                    ->setHeaderTwoStart($exchangeSite->getHeaderTwoStart())
                    ->setHeaderTwoEnd($exchangeSite->getHeaderTwoEnd())
                    ->setHeaderThreeStart($exchangeSite->getHeaderThreeStart())
                    ->setHeaderThreeEnd($exchangeSite->getHeaderThreeEnd())
                    ->setBoldText($exchangeSite->getBoldText())
                    ->setItalicText($exchangeSite->getItalicText())
                    ->setQuotedText($exchangeSite->getQuotedText())
                    ->setUlTag($exchangeSite->getUlTag())
                    ->setImagesPerArticleFrom($exchangeSite->getMinImagesNumber())
                    ->setImagesPerArticleTo($exchangeSite->getMaxImagesNumber())
                    ->setAmount($transaction->getDetails(CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE))
                    ->setOptimized(true)
                    ->setStatus(CopywritingOrder::STATUS_WAITING)
                    ->setInstructions(!empty($instruction) ? implode("\n\n", $instruction) : null)
                    ->addTransaction($transaction)
                ;

                $copywritingProject = new CopywritingProject();
                $copywritingProject
                    ->setCustomer($clientUser)
                    ->addOrder($copywritingOrder)
                    ->setLanguage($exchangeSite->getLanguage())
                    ->setTitle($title . ' ' . $exchangeProposition->getId());

                $directoryBacklinks = new DirectoryBacklinks();
                $directoryBacklinks
                    ->setJob($job)
                    ->setStatus(DirectoryBacklinks::STATUS_NOT_FOUND_YET)
                    ->setStatusType(DirectoryBacklinks::STATUS_TYPE_AUTO)
                    ->setDateChecked(new \DateTime())
                    ->setDateCheckedFirst(new \DateTime());

                $em->persist($copywritingOrder);
                $em->persist($copywritingProject);
                $em->persist($job);
                $em->persist($directoryBacklinks);
                $em->flush();

                $message = "Exchange proposition with order {$copywritingOrder->getTitle()}, ID:{$copywritingOrder->getId()} created";
                $output->writeln($message);
                $monolog->info($message);
            } catch (\Exception $exception) {
                $monolog->error("Error with create exchange proposition", [
                    'taskId' => $task->getId(),
                    'message' => $exception->getMessage(),
                    'TRACE' => $exception->getTraceAsString()
                ]);
            }
        }
    }
}
