<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use UserBundle\Services\BonusCalculator\NetlinkingWriterBonusCalculator;
use UserBundle\Services\WriterService;

class CalculatorNetlinkingPrice
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var WriterService */
    protected $userWriterService;

    /** @var CalculatorPriceService */
    protected $calculatorCopywritingPrice;

    /**
     * @var NetlinkingWriterBonusCalculator
     */
    protected $bonusCalculator;

    /**
     * CalculatorNetlinkingPrice constructor.
     * @param EntityManager $entityManager
     * @param WriterService $userWriterService
     * @param CalculatorPriceService $calculatorCopywritingPrice
     * @param NetlinkingWriterBonusCalculator $bonusCalculator
     */
    public function __construct($entityManager, $userWriterService, $calculatorCopywritingPrice, $bonusCalculator)
    {
        $this->entityManager = $entityManager;
        $this->userWriterService = $userWriterService;
        $this->calculatorCopywritingPrice = $calculatorCopywritingPrice;
        $this->bonusCalculator = $bonusCalculator;
    }

    /**
     * @param ScheduleTask $sheduleTask
     * @param $wordsCount
     * @return float|int
     */
    public function getWebmasterCost($sheduleTask, $wordsCount = 0)
    {
        $netlinkingProject = $sheduleTask->getNetlinkingProject();

        if ($sheduleTask->getDirectory() !== null) {
            $directory = $sheduleTask->getDirectory();
            $settingsRepository = $this->entityManager->getRepository(Settings::class);

            $tariffWebmaster = floatval($netlinkingProject->getUser()->getSpending());
            $customTariffWebmaster = null;

            if (empty($tariffWebmaster)) {
                $tariffWebmaster = floatval($settingsRepository->getSettingValue(Settings::TARIFF_WEB));
            }else{
                $customTariffWebmaster = $tariffWebmaster;
            }

            $wordsCount = $wordsCount ?: $netlinkingProject->getDirectoryList()->getWordsCount();

            $defaultDirectoryZeroWordsCount = intval($settingsRepository->getSettingValue(Settings::DEFAULT_DIRECTORY_ZERO_WORDS_COUNT));
            $directoryMinWordsCnt = $directory->getMinWordsCount()?: $defaultDirectoryZeroWordsCount;

            // getting real extra words count
            if ($directoryMinWordsCnt < $wordsCount){
                $wordsCount -= $directoryMinWordsCnt;
            }
            
            $webmasterExtraCost = $this->calculatorCopywritingPrice->getBasePrice($wordsCount, CalculatorPriceService::TOTAL_KEY, $customTariffWebmaster);
            return $tariffWebmaster + $webmasterExtraCost + $directory->getTariffExtraWebmaster();
        }

        if ($sheduleTask->getExchangeSite() !== null) {
            return $this->calculatorCopywritingPrice->getWritingByErefererPrice($sheduleTask->getExchangeSite());
        }
    }

    /**
     * @param ScheduleTask $scheduleTask
     * @param User $writer
     * @param $wordsCount
     * @return mixed
     */
    public function getWriterCost($scheduleTask, $writer, $wordsCount = 0)
    {
        if ($scheduleTask->getDirectory() !== null) {
            $directory = $scheduleTask->getDirectory();

            $writerTariffRedaction = $this->userWriterService->getCopyWriterRate($writer);

            $writerExtraEarn = $this->calculatorCopywritingPrice->getBasePrice($wordsCount, CalculatorPriceService::WRITER_KEY, $writerTariffRedaction);

            $compensation = $this->userWriterService->getCompensation($writer);

            $compensationWithBonus = $this->bonusCalculator->calculate($writer, $compensation);
            return $compensationWithBonus + $writerExtraEarn + $directory->getTariffExtraSeo();
        } else {
            return 0;
        }
    }
}
