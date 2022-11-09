<?php

namespace CoreBundle\Model;

use CoreBundle\Entity\AbstractMetricsEntity;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Services\CalculatorPriceService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractModelClass
 *
 * @package CoreBundle\Model
 */
abstract class AbstractModelClass
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /** @var CalculatorPriceService */
    protected $calculatorCopywritingPrice;

    /** @var CalculatorPriceService */
    protected $user;

    /**
     * AbstractModelClass constructor.
     *
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param CalculatorPriceService $calculatorCopywritingPrice
     * @param TokenStorage $tokenStorage
     */
    public function __construct($entityManager, $translator, $calculatorCopywritingPrice, $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->calculatorCopywritingPrice = $calculatorCopywritingPrice;

        $this->user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser():null;
    }

    /**
     * @param |DateTime $age
     *
     * @return array
     */
    protected function getAgeArray($age)
    {
        $ageArray = [];
        if (!is_null($age) && ($age instanceof \DateTimeInterface)) {
            $now = new \DateTime();
            $interval = $age->diff($now);

            if ($interval->y > 0) {
                $ageArray['y'] = $interval->y;
            }

            if ($interval->m > 0) {
                $ageArray['m'] = $interval->m;
            }

            if ($interval->d > 0) {
                $ageArray['d'] = $interval->d;
            }
        }

        return $ageArray;
    }

    /**
     * @param AbstractMetricsEntity $entity
     *
     * @return array
     */
    protected function getMetrics(AbstractMetricsEntity $entity)
    {
        $result = [
            'majestic' => [
                'citation' => $entity->getMajesticCitation(),
                'backlinks' => $entity->getMajesticBacklinks(),
                'govBacklinks' => $entity->getMajesticGovBacklinks(),
                'eduBacklinks' => $entity->getMajesticEduBacklinks(),
                'trustFlow' => $entity->getMajesticTrustFlow(),
                'refDomains' => $entity->getMajesticRefDomains(),
                'categories' => $entity->getMajesticTtfCategoriesFormatted(),
            ],
            'semrush' => [
                'traffic' => $entity->getSemrushTraffic(),
                'keyword' => $entity->getSemrushKeyword(),
                'trafficCost' => $entity->getSemrushTrafficCost(),
            ],
            'moz' => [
                'pageAuthority' => $entity->getMozPageAuthority(),
                'domainAuthority' => $entity->getMozDomainAuthority(),
            ],
            'google' => [
                'news' => $entity->getGoogleNews(),
                'analytics' => $entity->getGoogleAnalytics(),
            ],
            'other' => [
                'alexaRank' => $entity->getAlexaRank(),
            ],
            'age' => [
                'whoisAge' => $this->getAgeArray($entity->getAge()),
                'archiveAge' => $this->getAgeArray($entity->getArchiveAge()),
            ],
        ];

        return $result;
    }

    /**
     * @param $item
     * @param DirectoriesList $directoriesList
     *
     * @return mixed
     */
    abstract public function transformItem($item, DirectoriesList $directoriesList);

    /**
     * @param ExchangeSite[]|Directory[] $items
     * @param DirectoriesList $directoriesList
     */
    public function transformForGrid(array $items, DirectoriesList $directoriesList)
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = $this->transformItem($item, $directoriesList);
        }
    }
}
