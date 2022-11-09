<?php

namespace CoreBundle\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class UnknownTransactionTagNameException extends \Exception
{
    public function __construct($unknownName)
    {
        parent::__construct('Unknown TransactionTag name: '. $unknownName, Response::HTTP_UNPROCESSABLE_ENTITY, null);
    }
}
