<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AffiliationController
 *
 * @package UserBundle\Controller
 */
class ExceptionController extends Controller
{

    public function pageNotFoundAction()
    {
        throw new NotFoundHttpException();
    }
}
