<?php

namespace CoreBundle\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BaseRepository extends EntityRepository
{

    public const COMPARE_NOT_EQUIV = '!=';
    public const COMPARE_EQUIV = '=';
    public const COMPARE_GT = '>';
    public const COMPARE_LT = '<';
    public const COMPARE_GTE = '>=';
    public const COMPARE_LTE = '<=';

    protected $filters = [];

    /**
     * @param array $filters
     * @param QueryBuilder $qb
     */
    public function prepare($filters, QueryBuilder $qb)
    {
        $root = $qb->getRootAliases()[0];

        foreach ($this->filters as $filter) {
            if (is_array($filter)) {
                $propNameInFilters = isset($filter['filter']) ? $filter['filter'] : $filter['name'];
                $prop = $filter['name'];
                $alias = isset($filter['alias']) ? $filter['alias'] : $root;
                $compare = isset($filter['compare']) ? $filter['compare'] : self::COMPARE_EQUIV;
            } else {
                $propNameInFilters = $filter;
                $prop = $filter;
                $alias = $root;
                $compare = self::COMPARE_EQUIV;
            }

            $propPath = $alias . '.' . $prop;

            if (!key_exists($propNameInFilters, $filters)) {
                continue;
            }

            $value = $filters[$propNameInFilters];
            if (is_null($value)) {
                continue;
            }

            if (is_array($value)) {
                if (array_key_exists('min', $value) || array_key_exists('max', $value)) {
                    if (isset($value['min']) && is_numeric($value['min'])) {
                        $qb->andWhere("{$propPath} >= :{$prop}_min");
                        $qb->setParameter($prop.'_min', $value['min']);
                    }
                    if (isset($value['max']) && is_numeric($value['max'])) {
                        $qb->andWhere("{$propPath} <= :{$prop}_max");
                        $qb->setParameter($prop.'_max', $value['max']);
                    }
                } elseif (!empty($value)) {
                    switch ($compare) {
                        case self::COMPARE_EQUIV:
                            $qb->andWhere($propPath . " IN (:$prop)");
                            break;
                        case self::COMPARE_NOT_EQUIV:
                            $qb->andWhere($propPath . " NOT IN (:$prop)");
                            break;
                    }
                    $qb->setParameter($prop, $value);
                }
            } elseif ($value instanceof \DateTime) {
                $qb->andWhere($propPath . " " . $compare . " :$prop");
                $qb->setParameter($prop, $value, Type::DATETIME);
            } elseif (is_object($value)) {
                $qb->andWhere($propPath . " " . $compare . " :$prop");
                $qb->setParameter($prop, $value, Type::OBJECT);
            } elseif ($value !== '') {
                $qb->andWhere($propPath . " " . $compare ." :$prop");
                $qb->setParameter($prop, $value);
            }
        }
    }
}
