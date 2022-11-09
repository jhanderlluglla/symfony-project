<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\SecurityController as FosSecurityController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends FosSecurityController
{
    /**
     * SecurityController constructor.
     *
     * @param CsrfTokenManagerInterface|null $tokenManager
     */
    public function __construct(CsrfTokenManagerInterface $tokenManager = null)
    {
        parent::__construct($tokenManager);
    }

    /**
     * Overriding login to add custom logic.
     *
     * @param Request $request
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->get('router')->generate('user_dashboard', []));
        }

        return parent::loginAction($request);
    }
}
