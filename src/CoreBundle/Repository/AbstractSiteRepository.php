<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Site;
use Doctrine\ORM\QueryBuilder;

class AbstractSiteRepository extends BaseRepository implements FilterableRepositoryInterface
{
    /** @var array */
    protected $customFilters = [];

    /** @var array */
    protected $commonFilters = [
        'id',
        'language',
        'mozDomainAuthority',
        'mozPageAuthority',
        'alexaRank',
        'majesticTrustFlow',
        'majesticCitation',
        'majesticRefDomains',
        'majesticBacklinks',
        'majesticEduBacklinks',
        'majesticGovBacklinks',
        'semrushTraffic',//WAIT
        'semrushKeyword',//WAIT
        'semrushTrafficCost',//WAIT
        ['filter' => 'directoriesList', 'name' => 'id', 'alias' => 'dl']
    ];

    /** @var array */
    protected $filterBoolean = [];

    protected $impossibleFilters = [];

    /**
     * @param array $filters
     * @param boolean $count
     *
     * @return QueryBuilder|array
     *
     * @throws \Exception
     */
    public function filter(array $filters, $count = false)
    {
        if (empty($this->filters)) {
            $this->filters = array_merge($this->customFilters, $this->commonFilters);
        }

        $alias = $this->getAlias();
        $qb = $this->createQueryBuilder($alias);

        foreach ($this->impossibleFilters as $impossibleFilter) {
            if (array_key_exists($impossibleFilter, $filters)) {
                $qb->andWhere($alias . '.id = 0');
                return $qb;
            }
        }

        $this->prepare($filters, $qb);

        if (!empty($filters['category'])) {
            $qb ->innerJoin($alias . '.categories', 'c')
                ->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['majesticTtfCategories'])) {
            $qb->innerJoin($alias . '.majesticTtfCategories', 'mtc');

            $categoriesConditions = [];
            $parameters = [];

            foreach ($filters['majesticTtfCategories'] as $key => $category) {
                $min = $category['rate']['min'];
                $max = $category['rate']['max'];

                $categoryExpr = $qb->expr()->eq("mtc.category", ":category$key");
                $parameters["category$key"] = $category['category'];

                if (is_numeric($min) && is_numeric($max)) {
                    $categoriesConditions[] = $qb->expr()->andX($categoryExpr, "mtc.rate >= :rateMin$key AND mtc.rate <= :rateMax$key");
                    $parameters["rateMin$key"] = $min;
                    $parameters["rateMax$key"] = $max;
                } elseif (is_numeric($min)) {
                    $categoriesConditions[] = $qb->expr()->andX($categoryExpr, "mtc.rate >= :rateMin$key");
                    $parameters["rateMin$key"] = $min;
                } elseif (is_numeric($max)) {
                    $categoriesConditions[] = $qb->expr()->andX($categoryExpr, "mtc.rate <= :rateMax$key");
                    $parameters["rateMax$key"] = $max;
                } else {
                    $categoriesConditions[] = $categoryExpr;
                }
            }

            $qb->andWhere($qb->expr()->orX()->addMultiple($categoriesConditions));
            foreach ($parameters as $key => $parameter) {
                $qb->setParameter($key, $parameter);
            }
        }

        if (!empty($filters['majesticTrustCitationRatio']) && !empty(array_filter($filters['majesticTrustCitationRatio']))) {
            $min = $filters['majesticTrustCitationRatio']['min'];
            $max = $filters['majesticTrustCitationRatio']['max'];
            if ($min && $max) {
                $qb->andWhere("{$alias}.majesticTrustFlow / {$alias}.majesticCitation >= :minTrustCitationRation AND {$alias}.majesticTrustFlow / {$alias}.majesticCitation <= :maxTrustCitationRation")
                    ->setParameter("minTrustCitationRation", $min)
                    ->setParameter("maxTrustCitationRation", $max);
            } elseif ($min) {
                $qb->andWhere("{$alias}.majesticTrustFlow / {$alias}.majesticCitation >= :minTrustCitationRation")
                    ->setParameter("minTrustCitationRation", $min);
            } elseif ($max) {
                $qb->andWhere("{$alias}.majesticTrustFlow / {$alias}.majesticCitation <= :maxTrustCitationRation")
                    ->setParameter("maxTrustCitationRation", $max);
            }
        }

        if ((isset($filters['ageYears']) && is_numeric($filters['ageYears'])) || (isset($filters['ageMonth']) && is_numeric($filters['ageMonth']))) {
            $month = 0 ;
            if (isset($filters['ageYears']) && is_numeric($filters['ageYears'])) {
                $month += $filters['ageYears'] * 12;
            }
            if (isset($filters['ageMonth']) && is_numeric($filters['ageMonth'])) {
                $month += $filters['ageMonth'];
            }
            $filterDate = new \DateTime();

            $site = $filters['site'];
            $filterDate->modify("- $month month");
            if ($site != 'both') {
                if ($filters['ageCondition'] === "lte") {
                    $qb->andWhere($alias . ".$site >= :filterDate");
                } else {
                    $qb->andWhere($alias . ".$site <= :filterDate");
                }
            } else {
                if ($filters['ageCondition'] === "lte") {
                    $qb->andWhere($alias . ".archiveAge >= :filterDate");
                    $qb->andWhere($alias . ".bwaAge >= :filterDate");
                } else {
                    $qb->andWhere($alias . ".archiveAge <= :filterDate");
                    $qb->andWhere($alias . ".bwaAge <= :filterDate");
                }
            }
            $qb->setParameter('filterDate', $filterDate);
        }

        if (isset($filters['googleNews']) && $filters['googleNews']) {
            $qb->andWhere($alias . ".googleNews > 0");
        }

        if (isset($filters['directoriesList'])) {
            $qb->leftJoin($alias . '.directoriesList', 'dl');
        }

        foreach ($this->filterBoolean as $filter) {
            if (!empty($filters[$filter])) {
                $qb->andWhere($alias . ".$filter = true");
            }
        }

        if (!empty($filters['sidx'])) {
            $sortField = 'id';

            switch ($filters['sidx']) {
                case 'name':
                    $sortField = 'name';
                    break;

                case 'tariff':
                    $sortField = 'tariffExtraWebmaster';
                    break;

                case 'alexa_rank':
                    $sortField = 'alexaRank';
                    break;

                case 'age':
                    $sortField = 'age';
                    break;

                case 'trust_flow':
                    $sortField = 'majesticTrustFlow';
                    break;

                case 'referring_domain':
                    $sortField = 'totalReferringDomain';
                    break;
            }

            $qb->orderBy($alias . '.' . $sortField, $filters['sord']);
        }

        if ($count) {
            $qb->select($qb->expr()->count($alias . '.id'));
        } else {
            $qb->groupBy($alias . '.id');
        }

        return $qb;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this instanceof ExchangeSiteRepository ? 'es' : 'd';
    }

    /**
     * @param $limit
     *
     * @return array
     */
    public function sitesForGoogleNewsUpdate($limit)
    {
        $alias = $this->getAlias();

        $qb = $this->createQueryBuilder($alias);

        $qb->orderBy($alias . '.lastUpdate', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Site $site
     *
     * @return array
     */
    public function getEntitiesBySite(Site $site)
    {
        $qb = $this->createQueryBuilder('e');
        $qb
            ->andWhere('e.site = :site')
            ->setParameter('site', $site)
        ;

        return $qb->getQuery()->getResult();
    }
}
