<?php

namespace CoreBundle\Validator;

use Symfony\Component\Validator\Constraints;

/**
 * @Annotation
 */
class Length extends Constraints\Length
{
    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
