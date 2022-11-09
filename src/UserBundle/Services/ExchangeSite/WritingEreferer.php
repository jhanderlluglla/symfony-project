<?php

namespace UserBundle\Services\ExchangeSite;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\NotEnoughMoneyException;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\CalculatorPriceService;
use CoreBundle\Services\ExchangePropositionService;
use CoreBundle\Services\Mailer;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WritingEreferer
 *
 * @package UserBundle\Services\ExchangeSite
 */
class WritingEreferer implements ExchangePropositionInterface
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TransactionService */
    protected $transactionService;

    /** @var Mailer */
    protected $mailerService;

    /** @var User */
    protected $user;

    /** @var CalculatorPrice */
    protected $exchangeCalculatorPrice;

    /**  @var ExchangePropositionService */
    protected $exchangePropositionService;

    /** @var CalculatorPriceService  */
    protected $copywritingCalculatorPrice;

    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        TokenStorage $tokenStorage,
        TransactionService $transactionService,
        Mailer $mailerService,
        ExchangePropositionService $exchangePropositionService,
        CalculatorPrice $exchangeCalculatorPrice = null,
        CalculatorPriceService $copywritingCalculatorPrice = null
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->transactionService = $transactionService;
        $this->mailerService = $mailerService;
        $this->exchangeCalculatorPrice = $exchangeCalculatorPrice;
        $this->exchangePropositionService = $exchangePropositionService;
        $this->copywritingCalculatorPrice = $copywritingCalculatorPrice;

        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function handler($exchangeSiteId, $data, $exchangeProposition = null)
    {

        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->entityManager->getRepository(ExchangeSite::class)->find($exchangeSiteId);

        if (is_null($exchangeSite)) {
            throw new BadRequestHttpException($this->translator->trans('modal.site_error', [], 'exchange_site_find'));
        }

        $wordsCount =  $exchangeSite->getMinWordsNumber();
        if (isset($data['countWords']) && is_numeric($data['countWords'])) {
            if ($data['countWords'] < $exchangeSite->getMinWordsNumber()) {
                throw new BadRequestHttpException($this->translator->trans('modal.words_count_error', ['%min_words%' => $exchangeSite->getMinWordsNumber()], 'exchange_site_find'));
            } else {
                $wordsCount = $data['countWords'];
            }
        }

        try {
            $transaction = $this->exchangePropositionService->paymentForExchangeProposal(
                $this->user,
                new TransactionDescriptionModel('proposal.writing_ereferer', ['%url%' => $exchangeSite->getUrl()]),
                ExchangeProposition::ARTICLE_AUTHOR_WRITER,
                $exchangeSite,
                $wordsCount
            );
        } catch (NotEnoughMoneyDetailException $e) {
            throw new NotEnoughMoneyException($this->translator->trans('errors.enough_balance', [], 'exchange_site_find'));
        }

        $exchangeProposition = new ExchangeProposition();
        $priceWithCommission = $this->exchangeCalculatorPrice->getPriceWithCommission($exchangeSite->getCredits());
        $exchangeProposition
            ->setUser($this->user)
            ->setExchangeSite($exchangeSite)
            ->setCredits($priceWithCommission)
            ->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WRITER)
            ->setPrice($transaction->getCredit())
            ->addTransaction($transaction)
        ;

        $this->entityManager->persist($exchangeProposition);
        $this->entityManager->flush();

        $instruction = [];
        if (!empty($exchangeSite->getPublicationRules())) {
            $instruction[] = $this->translator->trans('modal.writing_ereferer.drafting_projects.description.rules', ['%rule%' => $exchangeSite->getPublicationRules()], 'exchange_site_find');
        }

        if (!empty($data['instructions'])) {
             $instruction[] = $this->translator->trans('modal.writing_ereferer.drafting_projects.description.instructions', ['%instructions%' => $data['instructions']], 'exchange_site_find');
        }


        $title = $this->translator->trans('modal.writing_ereferer.drafting_projects.title', [], 'exchange_site_find');

        $urls = array_filter($data['urls'], function ($value) {
            return !is_null($value['url']);
        });

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder
            ->setExchangeProposition($exchangeProposition)
            ->setLinks($urls)
            ->setTitle($title . ' ' . $exchangeProposition->getId())
            ->setWordsNumber($wordsCount)
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
            ->setInstructions(!empty($instruction) ? implode("\n\n", $instruction) : null)
            ->addTransaction($transaction)
        ;

        $copywritingProject = new CopywritingProject();
        $copywritingProject
            ->setCustomer($this->user)
            ->addOrder($copywritingOrder)
            ->setTitle($title . ' ' . $exchangeProposition->getId())
            ->setLanguage($exchangeSite->getLanguage())
        ;

        $this->entityManager->persist($copywritingOrder);
        $this->entityManager->persist($copywritingProject);
        $this->entityManager->flush();

        return [
            'status' => true,
            'message' => $this->translator->trans('modal.writing_ereferer.accepted', [], 'exchange_site_find'),
            'valids' => [],
            'exchangePropositionId' => $exchangeProposition->getId(),
        ];
    }
}
