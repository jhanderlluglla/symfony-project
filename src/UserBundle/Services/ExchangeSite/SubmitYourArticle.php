<?php

namespace UserBundle\Services\ExchangeSite;

use CoreBundle\Entity\User;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\NotEnoughMoneyException;
use CoreBundle\Model\TransactionDescriptionModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Services\ExchangePropositionService;
use CoreBundle\Services\Mailer;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Services\ArticleStatisticService;
use UserBundle\Services\ExchangePropositionProcessor;

/**
 * Class SubmitYourArticle
 *
 * @package UserBundle\Services\ExchangeSite
 */
class SubmitYourArticle implements ExchangePropositionInterface
{
    const VALID_EXTENSIONS = ['docx', 'doc', 'odt'];

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

    /** @var ArticleStatisticService */
    protected $articleStatisticService;

    /** @var CalculatorPrice */
    protected $exchangeCalculatorPrice;

    /** @var ExchangePropositionService */
    protected $exchangePropositionService;

    /**  @var ExchangePropositionProcessor */
    protected $exchangePropositionProcessor;

    /**
     * SubmitYourArticle constructor.
     *
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param TokenStorage $tokenStorage
     * @param TransactionService $transactionService
     * @param Mailer $mailerService
     * @param ExchangePropositionService $exchangePropositionService
     * @param ArticleStatisticService $articleStatisticService
     * @param CalculatorPrice $exchangeCalculatorPrice
     * @param ExchangePropositionProcessor $exchangePropositionProcessor
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        TokenStorage $tokenStorage,
        TransactionService $transactionService,
        Mailer $mailerService,
        ExchangePropositionService $exchangePropositionService,
        ArticleStatisticService $articleStatisticService,
        CalculatorPrice $exchangeCalculatorPrice,
        ExchangePropositionProcessor $exchangePropositionProcessor
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->transactionService = $transactionService;
        $this->mailerService = $mailerService;
        $this->articleStatisticService = $articleStatisticService;
        $this->exchangeCalculatorPrice = $exchangeCalculatorPrice;
        $this->exchangePropositionService = $exchangePropositionService;
        $this->exchangePropositionProcessor = $exchangePropositionProcessor;

        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function handler($exchangeSiteId, $data, $exchangeProposition = null)
    {
        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = null;

        if (!is_null($exchangeProposition)) {
            $exchangeSite = $exchangeProposition->getExchangeSite();
        } else {
            $exchangeSite = $this->entityManager->getRepository(ExchangeSite::class)->find($exchangeSiteId);
        }

        if (is_null($exchangeSite)) {
            throw new BadRequestHttpException($this->translator->trans('modal.site_error', [], 'exchange_site_find'));
        }

        $ts = md5(uniqid());
        /** @var UploadedFile $file */
        $file = $data['article'];
        $fileName = $ts . '.' . $file->getClientOriginalExtension();

        if (!in_array($file->getClientOriginalExtension(), self::VALID_EXTENSIONS)) {
            throw new BadRequestHttpException($this->translator->trans('modal.invalid_extension', [
                '%valid_extensions%' => implode(',', self::VALID_EXTENSIONS),
                '%your_extension%' => $file->getClientOriginalExtension()
            ], 'exchange_site_find'));
        }

        $fileNewPath = $this->articleStatisticService->getUploadDocsDir() . DIRECTORY_SEPARATOR . $fileName;
        $fs = new Filesystem();
        $fs->copy($file->getPathName(), $fileNewPath);

        $htmlStatistics = $this->articleStatisticService->convertToHtml($fileName);

        if ($htmlStatistics instanceof JsonResponse) {
            $fs->remove([$fileNewPath]);
            return $htmlStatistics;
        }

        $errors = [];
        if ($exchangeSite->getMinWordsNumber() > $htmlStatistics['words']) {
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.words', [
                '%has_words%' => $htmlStatistics['words'],
                '%minimum_words%' => $exchangeSite->getMinWordsNumber()
            ], 'exchange_site_find');
        }

        if ($exchangeSite->getMaxLinksNumber() < $htmlStatistics['links']) {
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.links', [
                '%has_links%' => $htmlStatistics['links'],
                '%maximum_links%' => $exchangeSite->getMaxLinksNumber()
            ], 'exchange_site_find');
        }

        if ($exchangeSite->getHeaderOneSet() && $htmlStatistics['headers']['h1'] === 0) {
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.header_one', [], 'exchange_site_find');
        }

        if ($exchangeSite->getHeaderTwoStart() > $htmlStatistics['headers']['h2'] || $exchangeSite->getHeaderTwoEnd() < $htmlStatistics['headers']['h2']) {
            $parameters = [
                '%has_headers%' => $htmlStatistics['headers']['h2'],
                '%minimum_headers%' => $exchangeSite->getHeaderTwoStart(),
                '%maximum_headers%' => $exchangeSite->getHeaderTwoEnd()
            ];
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.header_two', $parameters, 'exchange_site_find');
        }

        if ($exchangeSite->getHeaderThreeStart() > $htmlStatistics['headers']['h3'] || $exchangeSite->getHeaderThreeEnd() < $htmlStatistics['headers']['h3']) {
            $parameters = [
                '%has_headers%' => $htmlStatistics['headers']['h3'],
                '%minimum_headers%' => $exchangeSite->getHeaderThreeStart(),
                '%maximum_headers%' => $exchangeSite->getHeaderThreeEnd()
            ];
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.header_three', $parameters, 'exchange_site_find');
        }

        if (($exchangeSite->getBoldText() && $htmlStatistics['bold'] === 0) || ($exchangeSite->getBoldText() === false && $htmlStatistics['bold'] > 0)) {
            $parameters = [
                '%has_bold%' => $htmlStatistics['bold'],
            ];
            if ($exchangeSite->getBoldText()) {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.bold_required', $parameters, 'exchange_site_find');
            } else {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.bold_is_not_required', $parameters, 'exchange_site_find');
            }
        }

        if (($exchangeSite->getItalicText() && $htmlStatistics['italic'] === 0) || ($exchangeSite->getItalicText() === false && $htmlStatistics['italic'] > 0)) {
            $parameters = [
                '%has_italic%' => $htmlStatistics['italic'],
            ];
            if ($exchangeSite->getItalicText()) {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.italic_required', $parameters, 'exchange_site_find');
            } else {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.italic_is_not_required', $parameters, 'exchange_site_find');
            }
        }

        if ($exchangeSite->getMinImagesNumber() > $htmlStatistics['images'] || $exchangeSite->getMaxImagesNumber() < $htmlStatistics['images']) {
            $parameters = [
                '%has_images%' => $htmlStatistics['images'],
                '%minimum_images%' => $exchangeSite->getMinImagesNumber(),
                '%maximum_images%' => $exchangeSite->getMaxImagesNumber()
            ];
            $errors[] = $this->translator->trans('modal.submit_your_article.errors.images', $parameters, 'exchange_site_find');
        }

        if (($exchangeSite->getUlTag() && $htmlStatistics['ul_tag'] === 0) || ($exchangeSite->getUlTag() === false && $htmlStatistics['ul_tag'] > 0)) {
            $parameters = [
                '%has_ul%' => $htmlStatistics['ul_tag'],
            ];
            if ($exchangeSite->getUlTag()) {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.ul_is_required', $parameters, 'exchange_site_find');
            } else {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.ul_is_not_required', $parameters, 'exchange_site_find');
            }
        }

        if (($exchangeSite->getQuotedText() && $htmlStatistics['quote'] === 0) || ($exchangeSite->getQuotedText() === false && $htmlStatistics['quote'] > 0)) {
            $parameters = [
                '%has_quote%' => $htmlStatistics['quote'],
            ];
            if ($exchangeSite->getQuotedText()) {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.quote_is_required', $parameters, 'exchange_site_find');
            } else {
                $errors[] = $this->translator->trans('modal.submit_your_article.errors.quote_is_not_required', $parameters, 'exchange_site_find');
            }
        }

        if (count($errors) > 0) {
            $fs->remove([$fileNewPath]);
            return [
                'status' => false,
                'message' => $errors
            ];
        }

        $isNewExchangeProposition = false;
        if (is_null($exchangeProposition)) {
            try {
                $transaction = $this->exchangePropositionService->paymentForExchangeProposal(
                    $this->user,
                    new TransactionDescriptionModel('proposal.writing_ereferer', ['%url%' => $exchangeSite->getUrl()]),
                    ExchangeProposition::ARTICLE_AUTHOR_BUYER,
                    $exchangeSite
                );
            } catch (NotEnoughMoneyDetailException $e) {
                throw new NotEnoughMoneyException($this->translator->trans('modal.proposal_credit_error', [
                    '%balance%' => $e->getAvailableMoney()
                ], 'exchange_site_find'));
            }

            $exchangeProposition = new ExchangeProposition();
            $isNewExchangeProposition = true;

            $exchangeProposition
                ->setStatus(ExchangeProposition::STATUS_AWAITING_WEBMASTER)
                ->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_BUYER)
            ;

            if ($transaction) {
                $exchangeProposition->addTransaction($transaction);
                $exchangeProposition->setPrice($transaction->getCredit());
            }
        }

        $priceWithCommission = $this->exchangeCalculatorPrice->getPriceWithCommission($exchangeSite->getCredits());
        $exchangeProposition
            ->setUser($this->user)
            ->setExchangeSite($exchangeSite)
            ->setCredits($priceWithCommission)
            ->setDocumentLink($fileName)
            ->setWordsNumber($htmlStatistics['words'])
            ->setLinksNumber($htmlStatistics['links'])
            ->setImagesNumber($htmlStatistics['images'])
            ->setCheckLinks($htmlStatistics['links_href'])
            ->setPlaintext($htmlStatistics['plaintext'])
        ;

        $this->exchangePropositionProcessor->updateArticleImage($exchangeProposition, $ts . '.html');

        if (!$isNewExchangeProposition) {
            $this->exchangePropositionService->applyTransition($exchangeProposition, ExchangeProposition::TRANSITION_ACCEPT_CHANGES);
        }

        $this->entityManager->persist($exchangeProposition);
        $this->entityManager->flush();

        $this->mailerService->sendToUser(User::NOTIFICATION_NEW_PROPOSAL, $exchangeSite->getUser());

        return [
            'status'  => true,
            'message' => $this->translator->trans('modal.submit_your_article.accepted', [], 'exchange_site_find'),
            'valids'  => [
                $this->translator->trans('modal.submit_your_article.valid.words', [
                    '%has_words%' => $htmlStatistics['words']
                ], 'exchange_site_find'),
                $this->translator->trans('modal.submit_your_article.valid.links', [
                    '%has_links%' => $htmlStatistics['links']
                ], 'exchange_site_find'),
                $this->translator->trans('modal.submit_your_article.valid.images', [
                    '%has_images%' => $htmlStatistics['images']
                ], 'exchange_site_find'),
            ],
        ];
    }
}
