<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Repository\ExchangePropositionRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserBundle\Services\CopywritingArticleProcessor;

class ApplyExpiredPropositionsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:apply-expired-propositions')
            ->setDescription('Apply expired propositions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $monolog = $this->getContainer()->get('monolog.logger.cron');
        $exchangePropositionService = $this->getContainer()->get('core.service.exchange_proposition');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $copywritingArticleProcessor = $this->getContainer()->get('user.copywriting.article_processor');

        /** @var ExchangePropositionRepository $proposalsRepository */
        $proposalsRepository =  $em->getRepository(ExchangeProposition::class);

        $qb = $proposalsRepository->getExpiredPropositions();

        $result = $qb->getQuery()->getResult();

        /** @var ExchangeProposition $item */
        foreach ($result as $item) {
            try {
                $transition = ExchangeProposition::TRANSITION_EXPIRE;
                $newStatus = ExchangeProposition::STATUS_EXPIRED;
                if ($item->getArticleAuthorType() === ExchangeProposition::ARTICLE_AUTHOR_WRITER && $item->getExchangeSite()->hasPlugin()) {
                    $response = $copywritingArticleProcessor->publish($item->getCopywritingOrders()->getArticle(), true);
                    if ($response === ExchangeSite::RESPONSE_CODE_PUBLISH_SUCCESS) {
                        $transition = ExchangeProposition::TRANSITION_PUBLISH;
                        $newStatus = ExchangeProposition::STATUS_PUBLISHED;
                    }
                }

                $message = 'ExchangeProposition: update status for #' . $item->getId() . ' from ' . $item->getStatus() . ' to ' . $newStatus;
                $exchangePropositionService->applyTransition($item, $transition);
                $output->writeln($message);
                $monolog->info($message);
            } catch (\Exception $e) {
            }
        }
    }
}
