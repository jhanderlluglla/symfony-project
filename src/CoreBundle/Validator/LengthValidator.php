<?php

namespace CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LengthValidator extends ConstraintValidator
{
    /**
     * @param string $string
     * @param Constraint $constraint
     */
    public function validate($string, Constraint $constraint)
    {
        if (!$constraint instanceof Length) {
            throw new UnexpectedTypeException($constraint, Length::class);
        }

        $validator = new Constraints\LengthValidator();
        $validator->initialize($this->context);
        $validator->validate(str_replace("\r\n", "\n", $string), $constraint);
    }
}
