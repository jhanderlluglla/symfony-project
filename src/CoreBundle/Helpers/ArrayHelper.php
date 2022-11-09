<?php

namespace CoreBundle\Helpers;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class ArrayHelper
 * @package CoreBundle\Helpers
 */
class ArrayHelper
{
    /**
     * @param object[]|array[] $array
     * @param string $field
     *
     * @return array
     */
    public static function toAssoc($array, $field = 'id')
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $result = [];
        foreach ($array as $item) {
            $key = $propertyAccessor->getValue($item, $field);
            $result[$key] = $item;
        }

        return $result;
    }
}
