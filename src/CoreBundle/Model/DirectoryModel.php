<?php

namespace CoreBundle\Model;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\Settings;
use CoreBundle\Services\CalculatorPriceService;

/**
 * Class DirectoryModel
 *
 * @package CoreBundle\Model
 */
class DirectoryModel extends AbstractModelClass
{

    /**
     * @param Directory $item
     * @param DirectoriesList $directoriesList
     *
     * @return array
     */
    public function transformItem($item, DirectoriesList $directoriesList)
    {
        $vipText = $item->getVipText();
        if (!empty($vipText)) {
            $partnershipText = '<a href="#" data-toggle="popover" data-content="' .$vipText. '">' .$this->translator->trans('table.partnership_string.yes', [], 'directory'). '</a>';
        } else {
            $partnershipText = $this->translator->trans('table.partnership_string.no', [], 'directory');
        }

        return [
            '_type' => 'directory',
            'id' => $item->getId(),
            'name' => $item->getHost(),
            'partnership' => $partnershipText,
            'price' => $this->getPrice($directoriesList, $item),
            'validationTime' => $item->getValidationTime(),
            'validationRate' => $item->getValidationRate(),
            'referringDomains' => $item->getTotalReferringDomain(),
            'categories' => $item->getCategoriesFormatted(),
            'selected' => $directoriesList->hasDirectory($item),
            'checkboxName' => 'directory_id[]',
            'checkboxClass' => 'directory_checkbox',
            'subDomain' => $item->getAcceptInnerPages(),
            'legalInfo' => $item->getAcceptLegalInfo(),
            'words' => $directoriesList->getWordsCount() > $item->getMinWordsCount() ? $directoriesList->getWordsCount() : $item->getMinWordsCount(),
            'metrics' => $this->getMetrics($item)
        ];
    }

    public function priceArray($listData, DirectoriesList $directoriesList)
    {
        $result = [];

        /** @var Directory $directory */
        foreach ($listData as $directory) {
            $result[] = [
                'id' => $directory->getId(),
                'price' => $this->getPrice($directoriesList, $directory),
                'selected' => $directoriesList->hasDirectory($directory),

            ];
        }

        return $result;
    }

    public function getDirectoryPrice($directory, DirectoriesList $directoriesList)
    {
        return $this->getPrice($directoriesList, $directory);
    }

    /**
     * @param DirectoriesList $directoriesList
     * @param Directory $directory
     * @return float
     */
    private function getPrice(DirectoriesList $directoriesList, Directory $directory)
    {
        $tariffWebmaster = $this->user->getSpending();
        if (empty($tariffWebmaster)) {
            $tariffWebmaster = floatval($this->entityManager->getRepository(Settings::class)->getSettingValue(Settings::TARIFF_WEB));
        }

        $differenceCountWords = $directoriesList->getWordsCount() > $directory->getMinWordsCount() ?
            $directoriesList->getWordsCount() - $directory->getMinWordsCount() : 0;

        $priceForDifference = $this->calculatorCopywritingPrice->getBasePrice($differenceCountWords, CalculatorPriceService::TOTAL_KEY);
        return round($tariffWebmaster + $priceForDifference + $directory->getTariffExtraWebmaster(), 2);
    }
}
