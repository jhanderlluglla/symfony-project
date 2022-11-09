<?php

namespace CoreBundle\Helpers;

use Doctrine\ORM\QueryBuilder;

/**
 * Class DQLToSQLHelper
 *
 * @package CoreBundle\Helpers
 */
class DQLToSQLHelper
{
    /**
     * @param QueryBuilder $query
     * @param array $params
     * @param $prefix
     *
     * @return mixed|string|string[]|null
     */
    public static function transform(QueryBuilder $query, &$params = [], $prefix = '')
    {
        $queryParams = [];

        foreach ($query->getParameters() as $doctrineQueryParams) {
            $queryParams[$doctrineQueryParams->getName()] = $doctrineQueryParams;
        }

        $sql = $query->getQuery()->getSQL();

        if (preg_match_all('~:([a-z0-9_-]*)~ui', $query->getDQL(), $matches)) {
            $i = 0;
            foreach ($matches[1] as $match) {
                if (empty($match)) {
                    continue;
                }

                $value = $queryParams[$match]->getValue();
                $name = $prefix.'_'.$queryParams[$match]->getName();

                if (is_array($value)) {
                    $marks = [];
                    $j = 0;
                    foreach ($value as $v) {
                        $marks[] = ':'.$name.'_'.$j;
                        $params[$name.'_'.$j] = $v;
                        ++$j;
                    }
                    $replacement = implode(', ', $marks);
                } else {
                    if (is_object($value)) {
                        if ($value instanceof \DateTime) {
                            /** @var \DateTime $value */
                            $params[$name] = $value->format('Y-m-d H:i:s');
                        } else {
                            $params[$name] = $value->getId();
                        }
                    } else {
                        $params[$name] = $value;
                    }
                    $replacement = ':'.$name;
                }

                $sql = substr_replace($sql, $replacement, strpos($sql, '?'), 1);
                ++$i;
            }
        }

        return $sql;
    }

    /**
     * @param string $sql
     * @param array $params
     *
     * @return string
     */
    public static function replaceParams($sql, $params)
    {
        foreach ($params as $k => $v) {
            $sql = str_replace(':'.$k, "'".$v."'", $sql);
        }

        return $sql;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return string
     */
    public static function getSql(QueryBuilder $qb)
    {
        return self::replaceParams(self::transform($qb, $params), $params);
    }
}
