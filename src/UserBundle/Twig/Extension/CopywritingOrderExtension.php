<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use CoreBundle\Services\CalculatorPriceService;

class CopywritingOrderExtension extends \Twig_Extension
{
    private $priceCalculator;

    /**
     * ExchangeSiteExtension constructor.
     *
     * @param CalculatorPriceService $priceCalculator
     */
    public function __construct($priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }


    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'calculate_potential_earning',
                [$this, 'calculatePotentialEarning'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @param User $writer
     * @return float
     */
    public function calculatePotentialEarning($copywritingOrder, $writer)
    {
        $writerKey = CalculatorPriceService::WRITER_KEY;
        $writerCategory = $copywritingOrder->getProject()->getWriterCategory();

        $baseEarning = $this->priceCalculator->getBasePrice($copywritingOrder->getWordsNumber(), $writerKey, $writer->getCopyWriterRate());
        $imagesEarning = $this->priceCalculator->getImagesPrice($copywritingOrder->getImagesPerArticleTo(), $writerKey);

        $expressEarning = 0;
        if ($copywritingOrder->isExpress() && $copywritingOrder->getDeadline() > new \DateTime()) {
            $expressEarning = $this->priceCalculator->getExpressPrice($copywritingOrder->getWordsNumber(), $writerKey);
        }

        $chooseWriterEarning = $this->priceCalculator->getChooseWriterPrice($copywritingOrder->getWordsNumber(), $writerCategory, $writerKey);

        return $baseEarning + $imagesEarning + $expressEarning + $chooseWriterEarning;
    }
}