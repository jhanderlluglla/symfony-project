<?php

namespace CoreBundle\Model;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Services\CalculatorPriceService;

/**
 * Class BlogsModel
 *
 * @package CoreBundle\Model
 */
class BlogsModel extends AbstractModelClass
{

    /**
     * @param ExchangeSite $item
     * @param DirectoriesList $directoriesList
     *
     * @return array
     */
    public function transformItem($item, DirectoriesList $directoriesList = null)
    {
        $price = $directoriesList ? $this->getPrice($directoriesList, $item) : $item->getCredits();
        $words = $directoriesList ? $this->getMinWords($directoriesList, $item) : $item->getMinWordsNumber();
        $selected = $directoriesList ? $directoriesList->hasExchangeSite($item) : false;

        return [
            '_type' => 'exchangeSite',
            'id' => $item->getId(),
            'name' => $item->getHiddenHost(),
            'partnership' => $this->translator->trans('table.partnership_string.no', [], 'directory'),
            'price' => $price,
            'tags' => $item->getTags(),
            'selected' => $selected,
            'checkboxName' => 'blogs_id[]',
            'checkboxClass' => 'blogs_checkbox',
            'language' => $item->getLanguage(),
            'plugin' => $item->hasPlugin(ExchangeSite::EXCHANGE_TYPE) ?
                '<span class="glyphicon glyphicon-ok text-info" aria-hidden="true"></span>' :
                '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>',
            'categories' => $item->getCategoriesFormatted(),
            'metrics' => $this->getMetrics($item),
            'publicationRequirements' => [
                'words' => $words,
                'links' => $item->getMaxLinksNumber(),
                'images' => [
                    'min' => $item->getMinImagesNumber(),
                    'max' => $item->getMaxImagesNumber(),
                ],
                'metaTitle' => $item->getMetaTitle(),
                'metaDescription' => $item->getMetaDescription(),
                'h1' => $item->getHeaderOneSet(),
                'h2' => [
                    'min' => $item->getHeaderTwoStart(),
                    'max' => $item->getHeaderTwoEnd(),
                ],
                'h3' => [
                    'min' => $item->getHeaderThreeStart(),
                    'max' => $item->getHeaderThreeEnd(),
                ],
                'boldText' => $item->getBoldText(),
                'quotedText' => $item->getQuotedText(),
                'italicText' => $item->getItalicText(),
                'ulTag' => $item->getUlTag(),
                'authorizedAnchor' => $item->getAuthorizedAnchor(),
                'webmasterAnchor' => $item->getWebmasterAnchor(),
            ]
        ];
    }

    public function priceArray($listData, DirectoriesList $directoriesList)
    {
        $result = [];

        /** @var ExchangeSite $exchangeSite */
        foreach ($listData as $exchangeSite) {
            $result[] = [
                'id' => $exchangeSite->getId(),
                'price' => $this->getPrice($directoriesList, $exchangeSite),
                'selected' => $directoriesList->hasExchangeSite($exchangeSite),
            ];
        }

        return $result;
    }

    public function getBlogPrice($blog, DirectoriesList $directoriesList)
    {
        return $this->getPrice($directoriesList, $blog);
    }

    /**
     * @param DirectoriesList $directoriesList
     * @param ExchangeSite $exchangeSite
     * @return float
     */
    private function getPrice(DirectoriesList $directoriesList, ExchangeSite $exchangeSite)
    {
        $minWords = $this->getMinWords($directoriesList, $exchangeSite);

        $basePrice = $this->calculatorCopywritingPrice->getBasePrice($minWords, CalculatorPriceService::TOTAL_KEY);
        $imagesPrice = $this->calculatorCopywritingPrice->getImagesPrice($exchangeSite->getMaxImagesNumber(), CalculatorPriceService::TOTAL_KEY);
        $metaDescriptionPrice = $this->calculatorCopywritingPrice->getMetaDescriptionPrice($exchangeSite->getMetaDescription(), CalculatorPriceService::TOTAL_KEY);
        $priceForOrder = $basePrice + $imagesPrice + $metaDescriptionPrice;

        return round($priceForOrder + $exchangeSite->getCredits(), 2);
    }

    /**
     * @param DirectoriesList $directoriesList
     * @param ExchangeSite $exchangeSite
     * @return int
     */
    private function getMinWords(DirectoriesList $directoriesList, ExchangeSite $exchangeSite)
    {
        return $directoriesList->getWordsCount() > $exchangeSite->getMinWordsNumber() ?
            $directoriesList->getWordsCount(): $exchangeSite->getMinWordsNumber();
    }
}
