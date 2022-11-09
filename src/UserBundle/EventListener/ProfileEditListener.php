<?php

namespace UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Services\AffiliationService;
use CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class ProfileEditListener implements EventSubscriberInterface
{

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var AffiliationService
     */
    private $affiliationService;

    /** @var AuthorizationChecker */
    private $authorizationChecker;

    /** @var EntityManager */
    private $em;

    /**
     * ProfileEditListener constructor.
     *
     * @param UrlGeneratorInterface $router
     * @param AffiliationService $affiliationService
     * @param AuthorizationChecker $authorizationChecker
     * @param EntityManager $em
     */
    public function __construct(
        UrlGeneratorInterface $router,
        AffiliationService $affiliationService,
        AuthorizationChecker $authorizationChecker,
        EntityManager $em
    ) {
        $this->router = $router;
        $this->affiliationService = $affiliationService;
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess',
            FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
            FOSUserEvents::REGISTRATION_INITIALIZE => 'onRegistrationInitialize',
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'onResetSuccess',
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => 'onResetInitialize',
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onProfileEditSuccess(FormEvent $event)
    {
        $url = $this->router->generate('user_profile');

        $event->setResponse(new RedirectResponse($url));
    }

    /**
     * @param FilterUserResponseEvent $event
     */
    public function onRegistrationCompleted(FilterUserResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        /** @var User $user */
        $user = $event->getUser();
        if ($session->has('affiliation')) {
            $this->affiliationService->handling($session->get('affiliation'), $user);
            $session->remove('affiliation');
        }

        $url = $this->router->generate('fos_user_profile_edit');

        $event->setResponse(new RedirectResponse($url));
    }

    /**
     * @param GetResponseUserEvent $event
     */
    public function onRegistrationInitialize(GetResponseUserEvent $event)
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $event->setResponse(new RedirectResponse($this->router->generate('user_dashboard', [])));
            return;
        }

        /** @var User $user */
        $user = $event->getUser();
        $user->addRole(User::ROLE_WEBMASTER);
    }

    /**
     * @param FormEvent $event
     */
    public function onResetSuccess(FormEvent $event)
    {
        $event->setResponse(new RedirectResponse($this->router->generate('user_dashboard')));
    }

    /**
     * @param GetResponseNullableUserEvent $event
     */
    public function onResetInitialize(GetResponseNullableUserEvent $event)
    {
        $username = $event->getRequest()->get('username');
        $user = $this->em->getRepository(User::class)->findByUsernameOrEmail($username);

        $redirectResponse = new RedirectResponse($this->router->generate('fos_user_resetting_request', [
            'username' => $username
        ]));

        if (!$user) {
            $event->setResponse($redirectResponse);
        }
    }
}
