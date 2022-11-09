<?php
namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Repository\SettingsRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Exception\Exception;

class CalculatorPriceService
{
    const TOTAL_KEY = 'total';
    const WRITER_KEY = 'writer';
    const CORRECTOR_KEY = 'corrector';
    const REDUCED_CORRECTOR_KEY = 'reduced_corrector';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * CalculatorPriceService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
        $this->settingsRepository = $this->entityManager->getRepository(Settings::class);
    }

    /**
     * @param int $countWords
     * @param string|null $key
     * @param float|null $customRate
     * @return array|mixed
     */
    public function getBasePrice($countWords, $key = null, $customRate = null)
    {
        if ($customRate !== null) {
            return $this->calculatePrice($countWords / 100, [], $key, $customRate);
        }
        $identificators = [
            self::TOTAL_KEY => Settings::PRICE_PER_100_WORDS,
            self::WRITER_KEY => Settings::WRITER_PRICE_PER_100_WORDS,
            self::CORRECTOR_KEY => Settings::CORRECTOR_PRICE_PER_100_WORDS,
            self::REDUCED_CORRECTOR_KEY => Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS,
        ];
        return $this->calculatePrice($countWords / 100, $identificators, $key);
    }

    /**
     * @param int $countWords
     * @param string|null $key
     * @return array|mixed
     */
    public function getExpressPrice($countWords, $key = null)
    {
        $identificators = [
            self::TOTAL_KEY => Settings::EXPRESS_RATE,
            self::WRITER_KEY => Settings::WRITER_EXPRESS_RATE,
            self::CORRECTOR_KEY => Settings::CORRECTOR_EXPRESS_RATE
        ];

        return $this->calculatePrice($countWords / 100, $identificators, $key);
    }

    /**
     * @param int $countImages
     * @param string|null $key
     * @return array|mixed
     */
    public function getImagesPrice($countImages, $key = null)
    {
        $identificators = [
            self::TOTAL_KEY => Settings::PRICE_PER_IMAGE,
            self::WRITER_KEY => Settings::WRITER_PRICE_PER_IMAGE,
        ];

        return $this->calculatePrice($countImages, $identificators, $key);
    }

    /**
     * @param int $countWords
     * @param string $writerCategory
     * @param string $key
     * @return array|int|mixed
     */
    public function getChooseWriterPrice($countWords, $writerCategory, $key)
    {
        switch ($writerCategory) {
            case CopywritingProject::YOU_LIKE_WRITERS:
                $identificators = [
                    self::TOTAL_KEY => Settings::PRICE_YOU_LIKE_WRITERS,
                    self::WRITER_KEY => Settings::WRITER_PRICE_YOU_LIKE_WRITERS,
                ];
                break;

            case CopywritingProject::TOP_WRITERS:
                $identificators = [
                    self::TOTAL_KEY => Settings::PRICE_TOP_WRITERS,
                    self::WRITER_KEY => Settings::WRITER_PRICE_TOP_WRITERS,
                ];
                break;

            case CopywritingProject::BEST_WRITERS:
                $identificators = [
                    self::TOTAL_KEY => Settings::PRICE_BEST_WRITERS,
                    self::WRITER_KEY => Settings::WRITER_PRICE_BEST_WRITERS,
                ];
                break;

            default:
                if ($key !== null) {
                    return 0;
                }
                return [self::TOTAL_KEY => 0, self::WRITER_KEY => 0];
        }

        return $this->calculatePrice($countWords / 100, $identificators, $key);
    }

    /**
     * @param ExchangeSite $exchangeSite
     * @return float|int
     */
    public function getWritingByErefererPrice(ExchangeSite $exchangeSite)
    {
        $basePrice = $this->getBasePrice($exchangeSite->getMinWordsNumber(), CalculatorPriceService::TOTAL_KEY);
        $imagesPrice = $this->getImagesPrice($exchangeSite->getMaxImagesNumber(), CalculatorPriceService::TOTAL_KEY);
        $priceForOrder = $basePrice + $imagesPrice;

        return $priceForOrder + $exchangeSite->getCredits();
    }

    /**
     * @param int $countMetaDescription
     * @param null $key
     *
     * @return array|mixed
     */
    public function getMetaDescriptionPrice($countMetaDescription, $key = null)
    {
        if ($countMetaDescription === true) {
            $countMetaDescription = 1;
        } elseif ($countMetaDescription === false) {
            $countMetaDescription = 0;
        }

        $identificators = [
            self::TOTAL_KEY => Settings::PRICE_FOR_META_DESCRIPTION,
            self::WRITER_KEY => Settings::WRITER_REWARD_FOR_META_DESCRIPTION
        ];

        return $this->calculatePrice($countMetaDescription, $identificators, $key);
    }

    /**
     * @param $oneElement
     * @param array $identificators
     * @param string|null $key
     * @param float|null $customRate
     * @return array|mixed
     */
    private function calculatePrice($oneElement, $identificators, $key = null, $customRate = null)
    {
        if ($customRate !== null) {
            return round($oneElement * $customRate, 2);
        }

        $settings = $this->settingsRepository->getSettingsByIdentificators($identificators);
        $result = [];

        foreach ($identificators as $keyType => $identificator) {
            $result[$keyType] = round($settings[$identificator] * $oneElement, 2);
        }

        if ($key !== null) {
            if (array_key_exists($key, $result)) {
                return $result[$key];
            } else {
                throw new Exception("Key not found $key");
            }
        }

        return $result;
    }
}
