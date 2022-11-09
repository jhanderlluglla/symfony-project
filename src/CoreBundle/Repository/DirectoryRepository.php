<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\DirectoryTtfCategory;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\TtfCategory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class DirectoryRepository
 *
 * @package CoreBundle\Repository
 */
class DirectoryRepository extends AbstractSiteRepository implements FilterableRepositoryInterface
{

    /** @var array  */
    protected $filterBoolean = [
        'googleAnalytics',
    ];

    protected $impossibleFilters = [
        'authorizedAnchor',
        'plugin'
    ];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = parent::filter($filters, $count);

        if (!empty($filters['tag'])) {
            $qb->andWhere(
                $qb->expr()->like('d.name', $qb->expr()->literal('%' . $filters['tag'] . '%'))
            );
        }

        if (isset($filters['price']['min']) && is_numeric($filters['price']['min'])
            || isset($filters['price']['max']) && is_numeric($filters['price']['max'])
            || isset($filters['showPrice']) && $filters['showPrice'] === true && !isset($filters['formFilter'])) {
            $settings = $this->getEntityManager()->getRepository(Settings::class)->getSettingsByIdentificators([
                Settings::TARIFF_WEB,
                Settings::PRICE_PER_100_WORDS
            ]);
            $directoriesListCountWords = isset($filters['directoriesList']) ? $filters['directoriesList']->getWordsCount() : 0;
            if (isset($filters['user']) && $filters['user'] && $filters['user']->getSpending()) {
                $tariffWeb = $filters['user']->getSpending();
            } else {
                $tariffWeb = $settings[Settings::TARIFF_WEB];
            }

            $qb->setParameter('tariffWeb', $tariffWeb);
            $qb->setParameter('pricePer100Words', $settings[Settings::PRICE_PER_100_WORDS]);
            $qb->setParameter('directoriesListCountWords', $directoriesListCountWords);

            if (isset($filters['showPrice']) && $filters['showPrice'] === true && !isset($filters['formFilter'])) {
                $qb->addSelect('((CASE WHEN :directoriesListCountWords > d.minWordsCount THEN :directoriesListCountWords - d.minWordsCount ELSE 0 END) / 100 * :pricePer100Words + :tariffWeb + d.tariffExtraWebmaster) as price');
            }

            if (isset($filters['price']['min']) && is_numeric($filters['price']['min'])) {
                $qb->andWhere('((CASE WHEN :directoriesListCountWords > d.minWordsCount THEN :directoriesListCountWords - d.minWordsCount ELSE 0 END) / 100 * :pricePer100Words + :tariffWeb + d.tariffExtraWebmaster) >= :price_min');
                $qb->setParameter('price_min', $filters['price']['min']);
            }

            if (isset($filters['price']['max']) && is_numeric($filters['price']['max'])) {
                $qb->andWhere('((CASE WHEN :directoriesListCountWords > d.minWordsCount THEN :directoriesListCountWords - d.minWordsCount ELSE 0 END) / 100 * :pricePer100Words + :tariffWeb + d.tariffExtraWebmaster) <= :price_max');
                $qb->setParameter('price_max', $filters['price']['max']);
            }
        }

        if (isset($filters['wordsCount']) && isset($filters['wordsCount']['max']) && is_numeric($filters['wordsCount']['max'])) {
            $qb->andWhere("d.minWordsCount <= :maxWords");
            $qb->setParameter("maxWords", $filters['wordsCount']['max']);
        }


        return $qb;
    }

    /**
     * @param Directory $directory
     *
     * @param $ttfCategories
     */
    public function updateTtfCategories(Directory $directory, $ttfCategories)
    {
        /** @var TtfCategory $ttfCategory */
        foreach ($ttfCategories as $name => $rate) {
            if ($name && $rate) {
                $ttfCategory = $this->_em->getRepository(TtfCategory::class)->findOneBy(['name' => $name]);

                if (!$ttfCategory) {
                    $ttfCategory = (new TtfCategory())->setName($name);
                }

                $directoryTtfCategory = $directory->getTtfCategory($name);

                if (is_null($directoryTtfCategory)) {
                    $directoryTtfCategory = new DirectoryTtfCategory();
                    $directoryTtfCategory->setCategory($ttfCategory);
                    $directoryTtfCategory->setRate($rate);

                    $directory->addTtfCategory($directoryTtfCategory);

                    $this->_em->persist($directory);
                    $this->_em->flush();
                } else {
                    $directoryTtfCategory->setRate($rate);
                }
            }
        }

        $this->_em->persist($directory);
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountAll()
    {
        $qb = $this->createQueryBuilder('d');

        $qb->select($qb->expr()->count('d.id'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $filters
     *
     * @return string
     */
    public function countFilterResults($filters)
    {
        try {
            return $this->filter($filters, true)->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
