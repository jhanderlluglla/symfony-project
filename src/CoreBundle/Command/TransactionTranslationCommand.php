<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Transaction;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TransactionTranslationCommand extends ContainerAwareCommand
{
    /** @var EntityManager */
    private $em;

    private $multiProposals = [];

    private $multiProposalsCashBack = [];

    /** @var array */
    private $badTransactions = [];

    protected function configure()
    {
        $this
            ->setName('app:transaction_translation')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;

        $repository = $em->getRepository(Transaction::class);
        $qb = $repository->createQueryBuilder('t');

        $qb->andWhere($qb->expr()->notIn('t.description', ['copywriting_order.multi_project_payment']));

        /** @var Transaction $transaction */
        foreach ($qb->getQuery()->getResult() as $transaction) {
            if (preg_match('~(<strong>)?Number of articles: (\d+);(</strong>)? ?Project title : (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $marks = ['%articles_count%' => $matches[2], '%project_title%' => $matches[4]];
                $transaction->setDescription('copywriting_order.multi_project_payment');
                $transaction->setMarks($marks);
                continue;
            }

            if (preg_match('~Rechargement PayPal~ui', $transaction->getDescription())) {
                $transaction->setDescription('account.replenish_paypal');
                continue;
            }

            if (preg_match('~Get (\d+) credits~ui', $transaction->getDescription())) {
                $transaction->setDescription('account.withdraw_transaction_comment');
                continue;
            }

            if (preg_match('~Writing an article for the blog (.*)( \(credits\))?~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.writing_article');
                $transaction->setMarks(['%order_title%' => $matches[1]]);
                continue;
            }

            if (preg_match('~Submission complete on site.*~ui', $transaction->getDescription())) {
                $transaction->setDescription('job.writerReward');
                continue;
            }

            if (preg_match('~Create exchange proposition (.*)~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('proposal.pay_for_proposition');
                $transaction->setMarks(['%url%' => $matches[1]]);
                continue;
            }

            if (preg_match('~(Writing an article for order|Rédaction d\'un article pour la commande) (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.writing_article');
                $transaction->setMarks(['%order_title%' => $matches[2]]);
                continue;
            }

            if (preg_match('~(Delete order|Supprimer la commande ): (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.delete');
                $transaction->setMarks(['%order_title%' => $matches[2]]);
                continue;
            }

            if (preg_match('~Payment for job in project (http.*)~ui', $transaction->getDescription(), $matches)) {
                $project = $this->getNetlinkingByPaymentTransaction($transaction, $matches[1]);
                if (!$project) {
                    continue;
                }
                $transaction->setDescription('job.payment');
                $transaction->setMarks(['%projectUrl%' => $matches[1], '%projectId%' => $project->getId()]);
                continue;
            }

            if (preg_match('~Refund cost for proposition on site (http.*?)(,|$)~', $transaction->getDescription(), $matches)) {
                $proposals = $this->getProposalsByTransaction($transaction, $matches[1]);
                $proposal = $this->filterByUse($proposals, $this->multiProposals);
                if (!$proposal) {
                    $this->addBadTransaction($transaction, 'There are no free proposal for transaction #' . $transaction->getId());
                    continue;
                }
                $transaction->setDescription('proposal.refund_cost');
                $transaction->setMarks(['%url%' => $matches[1]]);
                $proposal->addTransaction($transaction);
                continue;
            }

            if ($transaction->getDescription() === 'Affiliation') {
                $transaction->setDescription('affiliation.affiliation');
                continue;
            }

            if (preg_match('~Pay for proposition on site (http.*)~', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('proposal.pay_for_proposition');
                $transaction->setMarks(['%url%' => $matches[1]]);
                continue;
            }

            if (preg_match('~Returning cost for images for order|Remboursement pour les images non utilisées sur la commande~', $transaction->getDescription())) {
                $order = $this->getOrderByImageCashbackTransaction($transaction, $parse);
                if (!$order && $parse['url']) {
                    $transaction->setMark('%order_title%', $parse['url']);
                }

                if ($order || $parse['date']) {
                    $transaction->setMark('%order_date%', isset($parse['date']) ? $parse['date'] : $order->getCreatedAt()->format('m/d/Y'));
                }

                if ($order) {
                    $transaction->setMark('%order_title%', $order ? $order->getTitle() : $parse['url']);
                    $order->addTransaction($transaction);
                }

                if (isset($transaction->getMarks()['%order_title%'])) {
                    $transaction->setDescription('copywriting_order.images_cashback');
                } else {
                    $this->addBadTransaction($transaction, 'Order for #'.$transaction->getId().' transaction not found ('.$transaction->getDescription().')');
                }
                continue;
            }

            if (preg_match('~(Edit order|Modifier la commande ): (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.edit');
                $transaction->setMark('%order_title%', $matches[2]);
                continue;
            }

            if (preg_match('~Reject job in project (http.*)~ui', $transaction->getDescription(), $matches)) {
                $netlinkingProjects = $this->getNetlinkingProjectByUrl($matches[1]);
                if (count($netlinkingProjects) === 1) {
                    $transaction->setDescription('job.reject');
                    $transaction->setMarks(['%projectUrl%' => $matches[1], '%projectId%' => $netlinkingProjects[0]->getId()]);
                } else {
                    $this->badTransactions[$transaction->getId()] = 'Number of Netlinking Project for transaction must be one: ' . count($netlinkingProjects);
                }
                continue;
            }

            if (preg_match('~Money from proposal on exchange site (http.*)~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('proposal.money_from_proposal');
                $transaction->setMark('%url%', $matches[1]);
                continue;
            }

            if ($transaction->getDescription() === 'Rechargement Wire transfer') {
                $transaction->setDescription('account.replenish_wire_transfer');
                continue;
            }

            if ($transaction->getDescription() === 'Added manually by admin') {
                $transaction->setDescription('account.modify_balance_add');
                continue;
            }
            if ($transaction->getDescription() === 'Removed manually by admin' || $transaction->getDescription() === 'Fonds retirés manuellement par l\'administrateur') {
                $transaction->setDescription('account.modify_balance_remove');
                continue;
            }

            if ($transaction->getDescription() === 'Withdrawal of funds') {
                $transaction->setDescription('account.withdraw_transaction_comment');
                continue;
            }

            if ($transaction->getDescription() === 'Transaction cancelled') {
                $transaction->setDescription('account.withdraw_transaction_cancelled');
                continue;
            }

            if ($transaction->getDescription() === 'Payment') {
                $transaction->setDescription('account.payment_writer');
                continue;
            }

            if (preg_match('~(Checking an article for order|Vérification de l\'article) (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.checking_article');
                $transaction->setMark('%order_title%', $matches[2]);
                continue;
            }

            if (preg_match('~Article for (http.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('proposal.writing_ereferer');
                $transaction->setMark('%url%', $matches[1]);
                continue;
            }

            if (preg_match('~(Project title|Titre du projet) : (.*)\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.project_payment');
                $transaction->setMark('%project_title%', $matches[2]);
                continue;
            }

            if (preg_match('~(Rejected article for order|Rejet de l\'article) #(\d+) "(.*)"\.~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.article_declined');
                $transaction->setMark('%order_id%', $matches[2]);
                $transaction->setMark('%order_title%', $matches[3]);
                continue;
            }

            if (preg_match('~Delete writing by ereferer for (http.*)~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('proposal.writing_ereferer_del');
                $transaction->setMark('%url%', $matches[1]);
                continue;
            }

            if (preg_match('~Rejected article for order #(\d+) "(.*)": task impossible~ui', $transaction->getDescription(), $matches)) {
                $transaction->setDescription('copywriting_order.article_impossible');
                $transaction->setMark('%order_id%', $matches[1]);
                $transaction->setMark('%order_title%', $matches[2]);
                continue;
            }

            if (
                preg_match('~Returning additional cost for writer category of order: (.*), project: (.*)\.~', $transaction->getDescription(), $matches) ||
                preg_match("~Remboursement du coût supplémentaire suite à l'indisponibilité du rédacteur choisi : (.*), projet: (.*)\.~", $transaction->getDescription(), $matches)
            ) {
                $transaction->setDescription('copywriting_order.reject_order_cashback');
                $transaction->setMark('%order_title%', $matches[1]);
                $transaction->setMark('%project_title%', $matches[2]);
                continue;
            }

            if (!preg_match('~[a-z_]+\.[a-z_]+~ui', $transaction->getDescription())) {
                $this->addBadTransaction($transaction, 'Unknown description: '.$transaction->getDescription());
            }
        }
        $em->flush();

        echo "\n\nBad transactions [".count($this->badTransactions)."]:\n";
        echo "\n\n", implode(', ', array_keys($this->badTransactions)), "\n\n";
        foreach ($this->badTransactions as $transactionId => $comment) {
            echo $transactionId, ': ', $comment, "\n";
        }

        if (count($this->badTransactions)) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("\e[0;31mDelete bad transaction ? (yes):\e[0m", false, '~^yes$~');
            if ($helper->ask($input, $output, $question)) {
                $output->writeln('Run delete process');
                foreach ($this->badTransactions as $transactionId => $comment) {
                    $this->em->remove($repository->find($transactionId));
                }
                $this->em->flush();
            }
        }
    }

    /**
     * @param Transaction $transaction
     * @param $message
     * @param bool $force
     */
    public function addBadTransaction(Transaction $transaction, $message, $force = false)
    {
        if (!isset($this->badTransactions[$transaction->getId()]) || $force) {
            $this->badTransactions[$transaction->getId()] = $message;
        }
    }


    /**
     * @param Transaction $transaction
     * @param $url
     *
     * @return ExchangeProposition[]|null
     */
    public function getProposalsByTransaction(Transaction $transaction, $url)
    {
        $proposals = $this->getProposalByExchangeSiteUrl($url, ['buyer' => $transaction->getUser()]);
        if ($proposals === '#no_sites') {
            $this->addBadTransaction($transaction, 'Site  "'.$url.'" from #' . $transaction->getId() . ' transaction not found');

            return null;
        }

        if (empty($proposals)) {
            $this->addBadTransaction($transaction, 'Proposals for transaction #' . $transaction->getId() . ' not found');

            return null;
        }

        return $proposals;
    }

    /**
     * @param Transaction $transaction
     * @param $url
     *
     * @return NetlinkingProject|null
     */
    public function getNetlinkingByPaymentTransaction(Transaction $transaction, $url)
    {
        $netlinkingProject = $this->em->getRepository(NetlinkingProject::class);

        $project = $netlinkingProject->findOneBy(['url' => $url]);

        if (!$project) {
            $this->addBadTransaction($transaction, 'Netlinking project by url "'.$url.'" for #' . $transaction->getId() . ' transaction not found');

            return null;
        }

        return $project;
    }

    /**
     * @param Transaction $transaction
     *
     * @param array $parse
     *
     * @return CopywritingOrder|null
     */
    public function getOrderByImageCashbackTransaction(Transaction $transaction, &$parse = [])
    {
        $order = null;
        if (preg_match('~Article exchange proposal (\d+)~', $transaction->getDescription(), $matches)) {
            $proposalId = $matches[1];

            $proposalRepository = $this->em->getRepository(ExchangeProposition::class);

            if ($proposal = $proposalRepository->find($proposalId)) {
                $order = $proposal->getCopywritingOrders();

                return $order;
            }
        }

        if (!$order && preg_match('~(Returning cost for images for order|Remboursement pour les images non utilisées sur la commande) (http.*) \(Date (\d\d/\d\d/\d{4})\)~', $transaction->getDescription(), $matches)) {
            $exchangeSiteUrl = $matches[2];

            $proposalRepository = $this->em->getRepository(ExchangeProposition::class);

            $proposals = $proposalRepository->filter(['url' => $exchangeSiteUrl, 'buyer' => $transaction->getUser()])->getQuery()->getResult();
            $parse = ['date' => $matches[3], 'url' => $matches[2]];
            /** @var ExchangeProposition $proposal */
            foreach ($proposals as $proposal) {
                if (!in_array($proposal->getId(), $this->multiProposalsCashBack)) {
                    $order = $proposal->getCopywritingOrders();
                    if ($order && $order->getArticle() && $order->getArticle()->getImagesNumber() === $transaction->getDetails('imagesUsed')) {
                        $this->multiProposalsCashBack[] = $proposal->getId();
                        $parse['url'] = $order->getTitle();
                    }
                    return $order;
                }
            }
        }

        return $order;
    }

    /**
     * @param $url
     *
     * @return array|NetlinkingProject[]
     */
    public function getNetlinkingProjectByUrl($url)
    {
        return $this->em->getRepository(NetlinkingProject::class)->findBy(['url' => $url]);
    }

    /**
     * @param $url
     *
     * @return array|ExchangeSite[]
     */
    public function getExchangeSiteByUrl($url)
    {
        return $this->em->getRepository(ExchangeSite::class)->findBy(['url' => $url]);
    }

    /**
     * @param $url
     * @param $filters
     *
     * @return string|array|ExchangeSite[]
     */
    public function getProposalByExchangeSiteUrl($url, $filters)
    {
        $sites = $this->getExchangeSiteByUrl($url);

        if (!$sites) {
            return '#no_sites';
        }

        $sitesIds = [];
        foreach ($sites as $site) {
            $sitesIds[] = $site->getId();
        }

        return $this->em->getRepository(ExchangeProposition::class)->filter(['exchangeSite' => $sitesIds] + $filters)->getQuery()->getResult();
    }

    /**
     * @param object[] $array
     * @param $usedArray
     *
     * @return object|null
     */
    public function filterByUse($array, $usedArray)
    {
        if ($array === null) {
            return null;
        }
        $use = null;
        foreach ($array as $item) {
            if (!in_array($item->getId(), $usedArray)) {
                $use = $item;
                break;
            }
        }

        return $use;
    }
}
