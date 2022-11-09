<?php

namespace UserBundle\Services\ExchangeSite;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\NotEnoughMoneyException;
use CoreBundle\Model\TransactionDescriptionModel;
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
 * Class WritingWebmaster
 *
 * @package UserBundle\Services\ExchangeSite
 */
class WritingWebmaster implements ExchangePropositionInterface
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

    /** @var ArticleStatisticService */
    protected $articleStatisticService;

    /** @var CalculatorPrice */
    protected $exchangeCalculatorPrice;

    /** @var ExchangePropositionService */
    protected $exchangePropositionService;

    /**
     * WritingWebmaster constructor.
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param TokenStorage $tokenStorage
     * @param TransactionService $transactionService
     * @param Mailer $mailerService
     * @param ExchangePropositionService $exchangePropositionService
     * @param ArticleStatisticService $articleStatisticService
     * @param CalculatorPrice $exchangeCalculatorPrice
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        TokenStorage $tokenStorage,
        TransactionService $transactionService,
        Mailer $mailerService,
        ExchangePropositionService $exchangePropositionService,
        ArticleStatisticService $articleStatisticService,
        CalculatorPrice $exchangeCalculatorPrice
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->transactionService = $transactionService;
        $this->mailerService = $mailerService;
        $this->articleStatisticService = $articleStatisticService;
        $this->exchangeCalculatorPrice = $exchangeCalculatorPrice;
        $this->exchangePropositionService = $exchangePropositionService;

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

        try {
            $transaction = $this->exchangePropositionService->paymentForExchangeProposal(
                $this->user,
                new TransactionDescriptionModel('proposal.pay_for_proposition', ['%url%' => $exchangeSite->getUrl()]),
                ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER,
                $exchangeSite
            );
        } catch (NotEnoughMoneyDetailException $e) {
            throw new NotEnoughMoneyException($this->translator->trans('errors.enough_balance', [], 'exchange_site_find'));
        }

        $urls = array_filter($data['urls'], function ($value) {
            return !is_null($value['url']);
        });

        $instructions = $data['instructions'] . PHP_EOL;
        $instructions.= $this->translator->trans('modal.writing_webmaster.instructions.links_made', [], 'exchange_site_find') . PHP_EOL;

        foreach ($urls as $url) {
            $instructions.= $this->translator->trans('modal.writing_webmaster.instructions.link', ['%url%' => $url['url'], '%anchor%' => $url['anchor']], 'exchange_site_find') . PHP_EOL;
        }


        $webmasterAdditionalPay = $this->entityManager->getRepository(Settings::class)->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY);
        $priceWithCommission = $this->exchangeCalculatorPrice->getPriceWithCommission($exchangeSite->getCredits());

        $exchangeProposition = new ExchangeProposition();
        $exchangeProposition
            ->setUser($this->user)
            ->setExchangeSite($exchangeSite)
            ->setCredits($priceWithCommission + $webmasterAdditionalPay)
            ->setStatus(ExchangeProposition::STATUS_AWAITING_WEBMASTER)
            ->setIsSelf(true)
            ->setInstructions($instructions)
            ->setCheckLinks($urls)
            ->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WEBMASTER)
            ->setPrice($transaction->getCredit())
            ->addTransaction($transaction)
        ;

        $this->entityManager->persist($exchangeProposition);
        $this->entityManager->flush();

        $this->mailerService->sendToUser(User::NOTIFICATION_NEW_PROPOSAL, $exchangeSite->getUser());

        return [
            'status' => true,
            'message' => $this->translator->trans('modal.writing_webmaster.accepted', [], 'exchange_site_find'),
            'valids' => [],
            'exchangePropositionId' => $exchangeProposition->getId()
        ];
    }
}
