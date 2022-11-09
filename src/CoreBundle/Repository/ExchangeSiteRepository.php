<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\ExchangeProposition;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Common\Collections\Criteria;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ExchangeSiteTtfCategory;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\TtfCategory;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Category;

/**
 * Class ExchangeSiteRepository
 *
 * @package CoreBundle\Repository
 */
class ExchangeSiteRepository extends AbstractSiteRepository implements FilterableRepositoryInterface
{
    /** @var array */
    protected $customFilters = [
        'siteType',
        'authorizedAnchor'
    ];

    /** @var array */
    protected $filterBoolean = [
        'googleAnalytics'
    ];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = parent::filter($filters, $count);

        if (!empty($filters['tag'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('es.tags', $qb->expr()->literal('%' . $filters['tag'] . '%')),
                    $qb->expr()->andX(
                        $qb->expr()->like('es.url', $qb->expr()->literal('%' . $filters['tag'] . '%')),
                        $qb->expr()->eq('es.hideUrl', 0)
                    )
                )
            );
        }

        if (!empty($filters['plugin'])) {
            $qb->andWhere("es.pluginStatus = 1");
        }

        if ((isset($filters['price']['min']) && is_numeric($filters['price']['min']))
            || (isset($filters['price']['max']) && is_numeric($filters['price']['max']))
            || (isset($filters['showPrice']) && $filters['showPrice'] === true && !isset($filters['formFilter']))) {
            $settings = $this->getEntityManager()->getRepository(Settings::class)->getSettingsByIdentificators([
                Settings::PRICE_PER_IMAGE,
                Settings::PRICE_PER_100_WORDS,
                Settings::PRICE_FOR_META_DESCRIPTION,
                Settings::WEBMASTER_ADDITIONAL_PAY
            ]);

            $directoriesListCountWords =
                isset($filters['directoriesList']) ? $filters['directoriesList']->getWordsCount() : 0;

            $qb->setParameter('pricePerImage', $settings[Settings::PRICE_PER_IMAGE]);
            $qb->setParameter('pricePer100Words', $settings[Settings::PRICE_PER_100_WORDS]);
            $qb->setParameter('priceForMetaDescription', $settings[Settings::PRICE_FOR_META_DESCRIPTION]);

            if (isset($filters['showPrice']) && $filters['showPrice'] === true && !isset($filters['formFilter'])) {
                $qb->setParameter('directoriesListCountWords', $directoriesListCountWords);
                $qb->addSelect('((CASE WHEN :directoriesListCountWords > es.minWordsNumber THEN :directoriesListCountWords ELSE es.minWordsNumber END) / 100 * :pricePer100Words + :pricePerImage * es.maxImagesNumber + es.credits + (CASE WHEN es.metaDescription = 1 THEN 1 ELSE 0 END) * :priceForMetaDescription) as price');
            }

            if (!isset($filters['enabledFilterPriceByWriting']) || $filters['enabledFilterPriceByWriting'] === false) {
                if (isset($filters['price']['min']) && is_numeric($filters['price']['min'])
                    || (isset($filters['price']['max']) && is_numeric($filters['price']['max']))) {
                    $qb->setParameter('directoriesListCountWords', $directoriesListCountWords);
                }
                if (isset($filters['price']['min']) && is_numeric($filters['price']['min'])) {
                    $qb->andWhere('((CASE WHEN :directoriesListCountWords > es.minWordsNumber THEN :directoriesListCountWords ELSE es.minWordsNumber END) / 100 * :pricePer100Words + :pricePerImage * es.maxImagesNumber + es.credits + (CASE WHEN es.metaDescription = 1 THEN 1 ELSE 0 END) * :priceForMetaDescription) >= :price_min');
                    $qb->setParameter('price_min', $filters['price']['min']);
                }

                if (isset($filters['price']['max']) && is_numeric($filters['price']['max'])) {
                    $qb->andWhere('((CASE WHEN :directoriesListCountWords > es.minWordsNumber THEN :directoriesListCountWords ELSE es.minWordsNumber END) / 100 * :pricePer100Words + :pricePerImage * es.maxImagesNumber + es.credits + (CASE WHEN es.metaDescription = 1 THEN 1 ELSE 0 END) * :priceForMetaDescription) <= :price_max');
                    $qb->setParameter('price_max', $filters['price']['max']);
                }
            } else {
                $filterCountWords = isset($filters['wordsCount'], $filters['wordsCount']['min']) && is_numeric($filters['wordsCount']['min']) ? $filters['wordsCount']['min'] : 0;

                if ((isset($filters['price']['min']) && is_numeric($filters['price']['min']))
                    || (isset($filters['price']['max']) && is_numeric($filters['price']['max']))) {
                    $qb->setParameter('filterCountWords', $filterCountWords);
                    $qb->setParameter('webmasterAdditionalPay', $settings[Settings::WEBMASTER_ADDITIONAL_PAY]);
                }

                if (isset($filters['price']['min']) && is_numeric($filters['price']['min'])) {
                    $qb->andWhere('((((CASE WHEN :filterCountWords > es.minWordsNumber THEN :filterCountWords ELSE es.minWordsNumber END) / 100 * :pricePer100Words + :pricePerImage * es.maxImagesNumber + es.credits + (CASE WHEN es.metaDescription = 1 THEN 1 ELSE 0 END) * :priceForMetaDescription) >= :price_min AND es.acceptEref = 1) OR (es.credits >= :price_min AND es.acceptWeb = 1) OR (es.credits + :webmasterAdditionalPay >= :price_min AND es.acceptSelf = 1))');
                    $qb->setParameter('price_min', $filters['price']['min']);
                }

                if (isset($filters['price']['max']) && is_numeric($filters['price']['max'])) {
                    $qb->andWhere('((((CASE WHEN :filterCountWords > es.minWordsNumber THEN :filterCountWords ELSE es.minWordsNumber END) / 100 * :pricePer100Words + :pricePerImage * es.maxImagesNumber + es.credits + (CASE WHEN es.metaDescription = 1 THEN 1 ELSE 0 END) * :priceForMetaDescription) <= :price_max AND es.acceptEref = 1) OR (es.credits <= :price_max AND es.acceptWeb = 1) OR (es.credits + :webmasterAdditionalPay <= :price_max AND es.acceptSelf = 1))');
                    $qb->setParameter('price_max', $filters['price']['max']);
                }
            }
        }

        if (!empty($filters['query'])) {
            $qb->andWhere(
                $qb->expr()->like('es.url', $qb->expr()->literal('%' .$filters['query']. '%'))
            );
        }

        if (isset($filters['user'])) {
            /** @var User $user */
            $user = $filters['user'];

            if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
                if (key_exists('nonOwner', $filters) && $filters['nonOwner']) {
                    $qb
                        ->andWhere(
                            'es.user != :user'
                        )
                        ->andWhere(
                            $qb->expr()->eq('es.active', ExchangeSite::ACTIVE_YES)
                        );
                } else {
                    $qb->andWhere(
                        'es.user = :user'
                    );
                }

                $qb->setParameter('user', $user, Type::OBJECT);
            }
        }

        if (isset($filters['copywriting_site_filter']['search_query'])) {
            $qb
                ->andWhere('es.url LIKE :search_query')
                ->setParameter('search_query', '%' . addcslashes($filters['copywriting_site_filter']['search_query'], '%_') . '%');
        }

        if (isset($filters['hideUrl']) && $filters['hideUrl'] === true) {
            $qb->andWhere("es.hideUrl = false");
        }

        if (isset($filters['wordsCount']) && isset($filters['wordsCount']['max']) && is_numeric($filters['wordsCount']['max'])) {
            $qb->andWhere("es.minWordsNumber <= :maxWords");
            $qb->setParameter("maxWords", $filters['wordsCount']['max']);
        }

        if (isset($filters['site']) && isset($filters['ageCondition']) && ((isset($filter['ageMonth']) && is_numeric($filters['ageMonth']) ) || (isset($filters['ageYears']) && is_numeric(isset($filters['ageYears']))))){
            $compareAgeSign = ($filters['ageCondition']=='gte') ? '>=' : '<=';
            $monthsDiff = intval($filters['ageMonth']) + intval($filters['ageYear'])*12;

            if (($filters['site'] === 'archiveAge') || ($filters['site'] === 'both')){
                $qb->andWhere("ABS(PERIOD_DIFF(DATE_FORMAT(`es.archive_age`,'%y%m'),DATE_FORMAT(NOW(),'%y%m'))) :compareAgeSign :monthsDiff");
            }

            if (($filters['site'] === 'bwaAge') || ($filters['site'] === 'both')){
                $qb->andWhere("ABS(PERIOD_DIFF(DATE_FORMAT(`es.bwa_age`,'%y%m'),DATE_FORMAT(NOW(),'%y%m'))) :compareAgeSign :monthsDiff");
            }

            $qb->setParameter(":monthsDiff", $monthsDiff);
            $qb->setParameter(':compareAgeSign', $compareAgeSign);
        }

        return $qb;
    }

    /**
     * @param ExchangeSite $exchangeSite
     *
     * @param $ttfCategories
     */
    public function updateTtfCategories(ExchangeSite $exchangeSite, $ttfCategories)
    {
        /** @var TtfCategory $ttfCategory */
        foreach ($ttfCategories as $name => $rate) {
            if ($name && $rate) {
                $ttfCategory = $this->_em->getRepository(TtfCategory::class)->findOneBy(['name' => $name]);

                if (!$ttfCategory) {
                    $ttfCategory = (new TtfCategory())->setName($name);
                }

                $exchangeSiteTtfCategory = $exchangeSite->getTtfCategory($name);

                if (is_null($exchangeSiteTtfCategory)) {
                    $exchangeSiteTtfCategory = new ExchangeSiteTtfCategory();
                    $exchangeSiteTtfCategory->setCategory($ttfCategory);
                    $exchangeSiteTtfCategory->setRate($rate);

                    $exchangeSite->addTtfCategory($exchangeSiteTtfCategory);

                    $this->_em->persist($exchangeSite);
                    $this->_em->flush();
                } else {
                    $exchangeSiteTtfCategory->setRate($rate);
                }
            }
        }

        $this->_em->persist($exchangeSite);
    }

    /**
     * @param User $user
     * @return array
     */
    public function getCount($user)
    {
        $qb = $this->createQueryBuilder('es');

        $qb
            ->select('COUNT(es) as countAll')
            ->leftJoin(ExchangeProposition::class, 'ep', Join::WITH, 'es.id = ep.exchangeSite')
            ->innerJoin('es.user', 'u', Join::WITH, 'u.id = es.user')
            ->andWhere(
                $qb->expr()->neq('ep.type', $qb->expr()->literal(ExchangeProposition::OWN_TYPE))
            )
        ;

        if ($user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_WRITER_ADMIN])) {
            $qb->addSelect("SUM(CASE WHEN ep.status = :ep_status then 1 else 0 end) as receivedProposalCount");
            $qb->setParameter('ep_status', ExchangeProposition::STATUS_ACCEPTED);
        } else {
            $qb->addSelect("SUM(CASE WHEN ep.status in (:status_received) then 1 else 0 end) as receivedProposalCount");
            $qb->addSelect("SUM(CASE WHEN ep.status = :status_published then 1 else 0 end) as finishedProposalCount");
            $qb->andWhere('es.user = :user');
            $qb->setParameter('user', $user);
            $qb->setParameter('status_received', [
                ExchangeProposition::STATUS_AWAITING_WEBMASTER,
                ExchangeProposition::STATUS_ACCEPTED
            ]);
            $qb->setParameter('status_published', Exchangeproposition::STATUS_PUBLISHED);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param User $user
     * @param int $maxSites
     * @return array
     */
    public function getSitesInCategories($user, $maxSites = 5)
    {
        $qb = $this->createQueryBuilder('es');

        $sites = $this->findBy(['user' => $user]);
        $categories = $this->getEntityManager()->getRepository(Category::class)->getCategoriesBySites($sites);

        $qb->innerJoin('es.categories', 'c');
        if (count($categories) > 0) {
            $qb->where($qb->expr()->in('c.name', array_shift($categories)));
        }


        $qb
            ->andWhere('es.user != :user')
            ->setParameter('user', $user, Type::OBJECT)
            ->orderBy('es.createdAt', Criteria::DESC)
            ->setMaxResults($maxSites);

        $result = $qb->getQuery()->getResult();
        if (count($result) == 0) {
            $qb->resetDQLPart('where');
            $qb->andWhere('es.user != :user');
            $result = $qb->getQuery()->getResult();
        }

        return $result;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountAll()
    {
        $qb = $this->createQueryBuilder('es');

        $qb->select($qb->expr()->count('es.id'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $url
     *
     * @return ExchangeSite
     */
    public function checkSiteDuplicate($url)
    {
        $this->getEntityManager()->getFilters()->enable('softdeleteable');

        $qb = $this->createQueryBuilder('es');
        $qb
            ->where(
                $qb->expr()->like('es.url', $qb->expr()->literal('%' .$url. '%'))
            )
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function constraintSiteDuplicate($data)
    {
        if (!empty($data['url']) && (null !== ($findSite = $this->checkSiteDuplicate($data['url'])))) {
            return [$findSite];
        }

        return [];
    }

    /**
     * @return array
     */
    public function findSuccessfulPluginStatus()
    {
        $qb = $this->createQueryBuilder('es');

        $qb->where($qb->expr()->isNotNull('es.pluginUrl'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $filters
     *
     * @return string
     */
    public function countFilterResults($filters)
    {
        return $this->filter($filters, true)->getQuery()->getSingleScalarResult();
    }
}
