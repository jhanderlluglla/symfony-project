<?php

namespace CoreBundle\Factory;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class PagerfantaAdapterFactory
 *
 * @package CoreBundle\Factory
 */
class PagerfantaAdapterFactory
{
    const PER_PAGE = 20;

    /**
     * @param QueryBuilder|array $queryBuilder
     *
     * @return ArrayAdapter|DoctrineORMAdapter
     */
    public static function getAdapterInstance($queryBuilder)
    {
        if ($queryBuilder instanceof QueryBuilder) {
            $adapter = new DoctrineORMAdapter($queryBuilder, false);
        } else {
            $adapter = new ArrayAdapter($queryBuilder);
        }

        return $adapter;
    }

    /**
     * @param $queryBuider
     * @param $page
     * @param int $perPage
     * @return Pagerfanta
     */
    public static function getPagerfantaInstance($queryBuider, $page, $perPage = self::PER_PAGE)
    {
        $pagerfanta = new Pagerfanta(self::getAdapterInstance($queryBuider));

        $pagerfanta
            ->setCurrentPage($page)
            ->setMaxPerPage($perPage)
        ;

        return $pagerfanta;
    }
}
