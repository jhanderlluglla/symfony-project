<?php

namespace CoreBundle\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class UnknownUserSetting extends \Exception
{
    public function __construct($userSettingName)
    {
        parent::__construct('Undefined UserSetting name: ' . $userSettingName, Response::HTTP_UNPROCESSABLE_ENTITY, null);
    }
}
