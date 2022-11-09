<?php

namespace CoreBundle\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotEnoughMoneyException extends HttpException
{

    public function __construct($message = 'Not enough money')
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }
}
