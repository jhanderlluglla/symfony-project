<?php

namespace CoreBundle\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnknownNotificationName extends HttpException
{
    public function __construct($notificationName)
    {
        parent::__construct('Undefined notification name: ' . $notificationName, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
