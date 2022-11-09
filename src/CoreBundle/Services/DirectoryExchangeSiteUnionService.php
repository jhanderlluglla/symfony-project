<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Helpers\DQLToSQLHelper;
use CoreBundle\Repository\AbstractSiteRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class DirectoryExchangeSiteUnionService
 *
 * @package CoreBundle\Services
 */
class DirectoryExchangeSiteUnionService
{

    /** @var EntityManager */
    private $em;

    /**
     * DirectoryExchangeSiteUnionService constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param array $orderBy
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getIdsByFilters(array $filters, $page = 1, $perPage = 20, $orderBy = [])
    {
        $type = isset($filters['_type']) ? $filters['_type'] : null;

        $qbs = [];
        $sortField = array_keys($orderBy)[0];
        $direction = $orderBy[$sortField];
        if (!$type || $type === 'directory') {
            $qb = $this->createSelectForEntity(Directory::class, 'directory', $filters, [$sortField]);
            $qbs[$qb->getRootAliases()[0]] = $qb;
        }

        if (!$type || $type === 'exchange_site') {
            $qb = $this->createSelectForEntity(ExchangeSite::class, 'exchangeSite', $filters, [$sortField]);
            $qbs[$qb->getRootAliases()[0]] = $qb;
        }

        if ($sortField === "bwaAge") {
            if ($direction === "asc") {
                $direction = "desc";
            } else {
                $direction = "asc";
            }
        }

        $sql = self::unionSQLBuilders($qbs, $parameters, $sortField);
        $sql .= " ORDER BY " . $sortField . " $direction";
        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);

        return $this->em->getConnection()->fetchAll($sql, $parameters);
    }

    /**
     * @param $entity
     * @param $filters
     * @param array $selects
     *
     * @return QueryBuilder
     * @throws \Exception
     */
    private function createSelectForEntity($entity, $type, $filters, $selects = [])
    {
        /** @var AbstractSiteRepository $repository */
        $repository = $this->em->getRepository($entity);

        $queryBuilder = $repository->filter($filters);

        $rootAliases = $queryBuilder->getRootAliases()[0];

        $qb = $queryBuilder
            ->select($rootAliases.'.id as id')
            ->addSelect("'".$type."' as type")
        ;

        foreach ($selects as $select) {
            switch ($select) {
                case 'price':
                    continue;
                case 'wordsCount':
                    switch ($entity) {
                        case ExchangeSite::class:
                            $field = 'minWordsNumber';
                            break;
                        default:
                            $field = 'minWordsCount';
                    }
                    $field = $rootAliases.'.'.$field;
                    $directoryListCountWords = isset($filters['directoriesList']) ? $filters['directoriesList']->getWordsCount() : 0;
                    $qb->addSelect('(CASE WHEN '.$directoryListCountWords.' > '.$field.' THEN '.$directoryListCountWords.' ELSE '.$field.' END) as countWords');
                    break;
                default:
                    $qb->addSelect($rootAliases.'.' . $select);
            }
        }

        return $qb;
    }

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param array $orderBy
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getObjectsByFilters(array $filters, $page = 1, $perPage = 20, $orderBy = [])
    {
        $ids = $this->getIdsByFilters($filters, $page, $perPage, $orderBy);
        $separate = self::separate($ids);

        if (count($separate['exchangeSites'])) {
            $exchSiteRepository = $this->em->getRepository(ExchangeSite::class);
            $exchSites = $exchSiteRepository->filter(['id' => $separate['exchangeSites']])->getQuery()->getResult();
        } else {
            $exchSites = [];
        }

        if (count($separate['directories'])) {
            $directoryRepository = $this->em->getRepository(Directory::class);
            $directories = $directoryRepository->filter(['id' => $separate['directories']])->getQuery()->getResult();
        } else {
            $directories = [];
        }

        return self::join($ids, $exchSites, $directories);
    }

    /**
     * @param $array - [$item1, $item2]
     *
     * @return array - [id1 => $item1, id2 => $item2]
     */
    private static function separate($array)
    {
        $result = ['exchangeSites' => [], 'directories' => []];

        foreach ($array as $item) {
            if (!isset($item['type'])) {
                continue;
            }

            switch ($item['type']) {
                case 'directory':
                    $result['directories'][] = $item['id'];
                    break;
                case 'exchangeSite':
                    $result['exchangeSites'][] = $item['id'];
                    break;
            }
        }

        return $result;
    }

    /**
     * @param array $idsByFilters
     * @param ExchangeSite[] $exchangeSites
     * @param Directory[] $directories
     *
     * @return array
     */
    private static function join(array $idsByFilters, $exchangeSites, $directories)
    {
        $result = [];

        $exchangeSites = self::arrayToIdKey($exchangeSites);
        $directories = self::arrayToIdKey($directories);

        foreach ($idsByFilters as $item) {
            if (!isset($directories[$item['id']]) && !isset($exchangeSites[$item['id']])) {
                continue;
            }

            if ($item['type'] === 'directory') {
                $result[] = $directories[$item['id']];
            } else {
                $result[] = $exchangeSites[$item['id']];
            }
        }

        return $result;
    }

    /**
     * @param $items
     *
     * @return array
     */
    private static function arrayToIdKey($items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[$item->getId()] = $item;
        }

        return $result;
    }

    /**
     * @param array $queryBuilders
     * @param $paramsNativeQuery
     * @param $sortByField
     *
     * @return string
     */
    private static function unionSQLBuilders(array $queryBuilders, &$paramsNativeQuery, $sortByField)
    {
        $sqlArray = [];
        $paramsNativeQuery = [];
        foreach ($queryBuilders as $prefix => $queryBuilder) {
            $params = [];
            $sqlArray[] = self::prepareQuery($prefix, $queryBuilder, $params, $sortByField);
            $paramsNativeQuery += $params;
        }

        $unionSQL = '('.implode(') UNION (', $sqlArray).')';

        return $unionSQL;
    }

    /**
     * @param $prefix
     * @param QueryBuilder $query
     * @param array $params
     * @param string $sortByField
     *
     * @return mixed|string|string[]|null
     */
    private static function prepareQuery($prefix, QueryBuilder $query, &$params = [], $sortByField = "createdAt")
    {
        $sqlOld = DQLToSQLHelper::transform($query, $params, $prefix);

        $replacement = "SELECT $1 as id, '$2' as type, $3 as ".$sortByField;

        $replacement .= " FROM";
        $sql = preg_replace("~SELECT (.*?) as .*, '(.*?)' as .*, (.*?) as .*?FROM~ui", $replacement, $sqlOld);

        return $sql;
    }
}
