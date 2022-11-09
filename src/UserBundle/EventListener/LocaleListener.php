<?php

namespace UserBundle\EventListener;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\User;
use CoreBundle\Services\LanguageService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class KernelListener
 *
 * @package UserBundle\EventListener
 */
class LocaleListener
{
    public const COOKIE_LOCALE_NAME = '_locale';

    /** @var EntityManager */
    private $entityManager;

    /** @var TokenStorage */
    private $tokenStorage;

    /** @var LanguageService */
    private $languageService;

    /** @var $env */
    private $env;

    /**
     * LocaleListener constructor.
     *
     * @param EntityManager $entityManager
     * @param TokenStorage $tokenStorage
     * @param LanguageService $languageService
     * @param string $env
     */
    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        LanguageService $languageService,
        $env
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->languageService = $languageService;
        $this->entityManager = $entityManager;
        $this->env = $env;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeLocaleListener(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request) {
            return;
        }

        $request->setLocale($this->languageService->getLanguageFromUrl());
    }

    /**
     * @param $locale
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateUserLocale($locale)
    {
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            if ($user->getLocale() !== $locale) {
                $user->setLocale($locale);
                $this->entityManager->flush();
            }
        }
    }


    /**
     * @param Request $request
     *
     * @return RedirectResponse|null
     */
    private function redirectToUserLocal(Request $request)
    {
        if ($request->cookies->get(self::COOKIE_LOCALE_NAME) && Language::validate($request->cookies->get(self::COOKIE_LOCALE_NAME))) {
            $locale = $request->cookies->get(self::COOKIE_LOCALE_NAME);
        } else {
            $locale = $this->languageService->getClientLanguage();
        }

        return $this->checkLocaleInCookie($request, $locale, true);
    }

    /**
     * @param Request $request
     * @param $locale
     * @param bool $forceRedirect
     *
     * @return RedirectResponse|null
     */
    private function checkLocaleInCookie(Request $request, $locale, $forceRedirect = false)
    {
        $response = null;

        if ($forceRedirect === true || $request->cookies->get(self::COOKIE_LOCALE_NAME) === null || $request->cookies->get(self::COOKIE_LOCALE_NAME) !== $locale) {
            $redirectTo = $this->languageService->prepareUrlForLanguage($request->getUri(), $locale);

            $response = new RedirectResponse($redirectTo);

            $response->headers->setCookie(
                new Cookie(
                    self::COOKIE_LOCALE_NAME,
                    $locale,
                    time() + 2592000, // 2592000 s = 30 d
                    '/',
                    '.' . $this->languageService->host()
                )
            );
        }

        return $response;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = null;

        if (!$request || $this->env === 'test') {
            return;
        }

        $userAgent = $request->headers->get('user-agent');
        if ($userAgent === 'Symfony BrowserKit' || $userAgent === null) {
            return;
        }

        $locale = $this->languageService->getLanguageFromUrl(false);
        // Redirect from main domain (ereferer.com) to locale domain (en.ereferer.com)
        if ($locale === null) {
            $response = $this->redirectToUserLocal($request);
        } else {
            $response = $this->checkLocaleInCookie($request, $locale);
            $this->updateUserLocale($locale);
        }

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}
