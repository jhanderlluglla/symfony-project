<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Transaction;
use CoreBundle\Services\TransactionService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TransactionExtensions extends \Twig_Extension
{

    /** @var TranslatorInterface */
    private $translator;

    /** @var TransactionService */

    private $transactionService;

    /** @var RouterInterface */
    private $router;

    /**
     * TransactionExtensions constructor.
     *
     * @param TranslatorInterface $translator
     * @param TransactionService $transactionService
     * @param RouterInterface $router
     */
    public function __construct(TranslatorInterface $translator, TransactionService $transactionService, RouterInterface $router)
    {
        $this->translator = $translator;
        $this->transactionService = $transactionService;
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'prepareValue',
                [$this, 'prepareValue'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'isShow',
                [$this, 'isShow'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'prepareValueUnit',
                [$this, 'prepareValueUnit'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'transactionGetContextLinks',
                [$this, 'getContextLinks'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param $name
     * @param bool $unitPrice
     *
     * @return \string
     */
    public function isShow($name, $unitPrice = false)
    {
        switch ($name) {
            case CopywritingProject::TRANSACTION_DETAIL_PAYMENT_FOR_META_DESCRIPTION:
                return !$unitPrice;
            case CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION:
                return $unitPrice;
            case ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT:
            case CopywritingProject::TRANSACTION_DETAIL_NUMBER_OF_ARTICLES:
            case CopywritingProject::TRANSACTION_DETAIL_WRITER_MALUS:
                return false;
        }

        return true;
    }

    public static function numberFormat($number)
    {
        return number_format($number, 2, '.', ' ');
    }

    /**
     * @param $name
     * @param $value
     *
     * @param array $allItem
     * @return \string
     */
    public function prepareValue($name, $value, $allItem = [])
    {
        switch ($name) {
            case CopywritingProject::TRANSACTION_DETAIL_PAYMENT_FOR_META_DESCRIPTION:
                if (isset($allItem[CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION])) {
                    return $this->translator->trans(
                        'more_details.paymentForMetaDescriptionValue',
                        [
                            '%articles%' => $value / $allItem[CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION],
                            '%price%' => $allItem[CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION],
                            '%total%' => $value,
                        ],
                        'transaction'
                    );
                } else {
                    return $value . '€';
                }
            case ExchangeProposition::TRANSACTION_DETAIL_COMMISSION:
                if (isset($allItem[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT])) {
                    $addRow = " ({$allItem[ExchangeProposition::TRANSACTION_DETAIL_COMMISSION_PERCENT]}%)";
                } else {
                    $addRow = '';
                }
                return TransactionExtensions::numberFormat($value) . '€'. $addRow;
            case 'wordsPrice':
            case 'imagesPrice':
            case 'expressArticlesPrice':
            case 'priceWriterCategory':
            case 'withdraw':
            case 'ereferer_commission':
            case 'earningForWords':
            case 'earningForImages':
            case 'earningForExpress':
            case 'earningForWriterCategory':
            case 'net_to_pay':
            case 'earningMalus':
            case CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION:
            case ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE:
            case ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY:
            case CopywritingProject::TRANSACTION_DETAIL_REWARD_FOR_META_DESCRIPTION:
                return mb_strpos($value, '€') === false ? TransactionExtensions::numberFormat($value) . '€' : $value;
            case CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE:
                return TransactionExtensions::numberFormat($value).'€ '. (count($allItem) > 1 ? $this->translator->trans('with_sub_details', [], 'transaction') : '');
        }

        return $value;
    }

    /**
     * @param $name
     * @param $value
     * @param array $allItem
     *
     * @return float|int|string
     */
    public function prepareValueUnit($name, $value, $allItem = [])
    {
        switch ($name) {
            case CopywritingProject::TRANSACTION_DETAIL_PRICE_FOR_META_DESCRIPTION:
                break;
            default:
                $value = $value / $allItem[CopywritingProject::TRANSACTION_DETAIL_NUMBER_OF_ARTICLES];
        }

        switch ($name) {
            case 'imagesPrice':
            case 'images':
                $value = round($value, 1);
        }

        $value = $this->prepareValue($name, $value, $allItem);

        return $value;
    }

    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    public function getContextLinks(Transaction $transaction)
    {
        $links = [];
        $context = $this->transactionService->getContext($transaction);

        $labels = [
            'article' => $this->translator->trans('article', [], 'copywriting'),
        ];

        foreach ($context as $arrayEntities) {
            foreach ($arrayEntities as $entity) {
                if ($entity instanceof CopywritingOrder) {
                    $links[] = [
                        'url' => $this->router->generate('copywriting_order_show', ['id' => $entity->getId()]),
                        'name' => $entity->getTitle(),
                        'label' => $labels['article'],
                    ];
                }
            }
        }

        return $links;
    }
}
