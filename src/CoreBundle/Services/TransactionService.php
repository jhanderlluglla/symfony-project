<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\TransactionTag;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\UnknownTransactionTagNameException;
use CoreBundle\Model\TransactionDescriptionModel;
use Doctrine\ORM\EntityManager;
use CoreBundle\Entity\Transaction;
use CoreBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Services\NetlinkingSchedule;

/**
 * Class TransactionService
 *
 * @package CoreBundle\Services
 */
class TransactionService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CalculatorPriceService
     */
    private $calculatorPriceService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NetlinkingSchedule
     */
    private $netlinkingSchedule;

    /**
     * TransactionService constructor.
     *
     * @param EntityManager $entityManager
     * @param CalculatorPriceService $calculatorPriceService
     * @param TranslatorInterface $translator
     */
    public function __construct($entityManager, $calculatorPriceService, $translator, $netlinkingSchedule)
    {
        $this->entityManager = $entityManager;
        $this->calculatorPriceService = $calculatorPriceService;
        $this->translator = $translator;
        $this->netlinkingSchedule = $netlinkingSchedule;
    }

    public function translator()
    {
        return $this->translator;
    }

    /**
     * @param User $user
     * @param TransactionDescriptionModel $description - id translation from "app/Resources/translations/transaction.en.yml" description block
     * @param int $debit
     * @param int $credit
     * @param null $details
     * @param string[]|null $tagNames
     * @return Transaction
     *
     * @throws UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handling($user, TransactionDescriptionModel $description, $debit = 0, $credit = 0, $details = null, $tagNames = [])
    {
        if (($debit < 0 && $credit < 0) || ($debit > 0 && $credit > 0)) {
            throw new \LogicException("Impossible values for transaction, Debit: $debit, Credit: $credit");
        }

        $tags = $this->getTagsByNames($tagNames);

        $transaction = new Transaction();

        $transaction
            ->setUser($user)
            ->setDescription($description->getIdTranslate())
            ->setDetails($details)
            ->setTags($tags)
            ->setMarks($description->getMarks())
        ;

        if ($debit > 0) {
            $this->checkIncreasingBalance($user, $debit);
            $transaction->debit($debit);
        } else {
            $transaction->credit($credit);
        }

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    /**
     * @param User $user
     * @param TransactionDescriptionModel $details
     * @param $credit
     * @param null $moreDetails
     * @param array $tagNames
     *
     * @return Transaction
     *
     * @throws UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function creditWithCheck(User $user, TransactionDescriptionModel $details, $credit, $moreDetails = null, $tagNames = [])
    {
        $this->checkMoney($user, $credit);

        return $this->handling($user, $details, 0, $credit, $moreDetails, $tagNames);
    }

    /**
     * @param User $user
     * @param $credit
     * @param bool $exception
     *
     * @return bool
     */
    public function checkMoney(User $user, $credit, $exception = true)
    {
        if ($user->getBalance() < $credit) {
            if ($exception) {
                throw new NotEnoughMoneyDetailException($user->getBalance(), $credit);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Transaction $transaction
     * @param string|array $tagNames
     *
     * @throws UnknownTransactionTagNameException
     */
    public function addTagToTransaction(Transaction $transaction, $tagNames)
    {
        if (!is_array($tagNames)) {
            $tagNames = [$tagNames];
        }

        $tags = $this->getTagsByNames($tagNames);

        foreach ($tags as $tag) {
            $transaction->addTag($tag);
        }
    }

    /**
     * @param array $names
     *
     * @return TransactionTag[]
     *
     * @throws UnknownTransactionTagNameException
     */
    public function getTagsByNames(array $names)
    {
        $tags = $this->entityManager->getRepository(TransactionTag::class)->filter(['name' => $names])->getQuery()->getResult();

        if (count($tags) !== count($names)) {
            foreach ($names as $name) {
                if (!in_array($name, TransactionTag::getAvailableTags())) {
                    throw new UnknownTransactionTagNameException($name);
                }
            }
        }

        return $tags;
    }

    /**
     * @param Transaction $transaction
     * @param string $tagName
     */
    public function removeTagFromTransaction(Transaction $transaction, $tagName)
    {
        /** @var TransactionTag $tag */
        $tag = $this->entityManager->getRepository(TransactionTag::class)->getByName($tagName);

        $transaction->removeTag($tag);
    }

    /**
     * @param CopywritingProject $copywritingProject
     * @return array
     */
    public function getCopywritingProjectTransactionData(CopywritingProject $copywritingProject)
    {
        $totalWords = 0;
        $totalImages = 0;
        $totalExpress = 0;
        $totalExpressCost = 0;
        $totalMetaDescriptions = 0;

        /** @var CopywritingOrder $order */
        foreach ($copywritingProject->getOrders() as $order) {
            $totalWords += $order->getWordsNumber();
            $totalImages += $order->getImagesPerArticleTo();

            if ($order->isExpress()) {
                $totalExpress++;
                $totalExpressCost += $order->getExpressBonus();
            }

            if ($order->isMetaDescription()) {
                ++$totalMetaDescriptions;
            }
        }

        $priceWriterCategory = $this->calculatorPriceService->getChooseWriterPrice($totalWords, $copywritingProject->getWriterCategory(), CalculatorPriceService::TOTAL_KEY);
        $wordsPrice = $this->calculatorPriceService->getBasePrice($totalWords, CalculatorPriceService::TOTAL_KEY);
        $wordsPrice += $priceWriterCategory;
        $metaDescriptionPrice = $this->calculatorPriceService->getMetaDescriptionPrice($totalMetaDescriptions, CalculatorPriceService::TOTAL_KEY);

        $result = [
            'words' => $totalWords,
            'wordsPrice' => $wordsPrice,
            'images' => $totalImages,
            'imagesPrice' => $this->calculatorPriceService->getImagesPrice($totalImages, CalculatorPriceService::TOTAL_KEY),
            'expressArticles' => $totalExpress,
            'expressArticlesPrice' => $totalExpressCost,
            'writerCategory' => $this->translator->trans($copywritingProject->getWriterCategory() . '.title', [], 'copywriting'),
            'priceWriterCategory' => $this->calculatorPriceService->getChooseWriterPrice($totalWords, $copywritingProject->getWriterCategory(), CalculatorPriceService::TOTAL_KEY),
            CopywritingProject::TRANSACTION_DETAIL_NUMBER_OF_ARTICLES => $copywritingProject->getOrders()->count(),
        ];

        if ($totalMetaDescriptions > 0) {
            $result [CopywritingProject::TRANSACTION_DETAIL_PAYMENT_FOR_META_DESCRIPTION] = $metaDescriptionPrice;
            $result [CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION] = $this->entityManager->getRepository(Settings::class)->getSettingValue(Settings::PRICE_FOR_META_DESCRIPTION);
        }

        return $result;
    }

    /**
     * @param Transaction $transaction
     * @param TransactionDescriptionModel $description
     * @param array $tags
     *
     * @return Transaction
     *
     * @throws UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function refund(Transaction $transaction, TransactionDescriptionModel $description, $tags)
    {
        if ($transaction->getDebit() > 0) {
            $debit = 0;
            $credit = $transaction->getDebit();
        } else {
            $debit = $transaction->getCredit();
            $credit = 0;
        }

        $refundTransaction = $this->handling($transaction->getUser(), $description, $debit, $credit, $transaction->getDetails(), $tags);
        $refundTransaction->setParent($transaction);
        $refundTransaction->setHidden($transaction->isHidden());

        $this->entityManager->flush();

        return $refundTransaction;
    }

    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    public function getContext(Transaction $transaction)
    {
        $result = [];

        $copywritingOrders = $this->entityManager->getRepository(CopywritingOrder::class)->findByTransaction($transaction);
        if ($copywritingOrders) {
            $result[CopywritingOrder::class] = $copywritingOrders;
        }

        return $result;
    }

    /**
     * @param  User  $user
     * @param  float $debit
     */
    public function checkIncreasingBalance($user, $debit){
        if ($user->isWebmaster()){
            $this->netlinkingSchedule->checkSchedulesOfStoppedProjects($user, $debit);
        }
    }
}
