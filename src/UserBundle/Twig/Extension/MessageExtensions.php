<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Message;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Services\AccessManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

class MessageExtensions extends \Twig_Extension
{
    /** @var User */
    private $user;

    /** @var AccessManager */
    private $accessManager;

    /** @var TokenStorage */
    private $tokenStorage;

    /**
     * TransactionExtensions constructor.
     *
     * @param TokenStorage $tokenStorage
     * @param AccessManager $accessManager
     */
    public function __construct(TokenStorage $tokenStorage, AccessManager $accessManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessManager = $accessManager;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'messageHideReceiveUser',
                [$this, 'hideReceiveUser'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'messageHideSendUser',
                [$this, 'hideSendUser'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param Message $message
     * @param string $string
     *
     * @return \string
     */
    public function hideReceiveUser(Message $message, $string)
    {
        if ($message->getReceiveUser() === $this->getUser() || ($message->getSendUser() === $this->getUser() && !$message->isTaken()) || $this->accessManager->canManageWebmasterUser() || $message->getReceiveUser()->isSuperAdmin() || !$this->getUser()->isWriterAdmin()) {
            return $string;
        } else {
            return '***';
        }
    }

    /**
     * @param Message $message
     * @param string $string
     *
     * @return \string
     */
    public function hideSendUser(Message $message, $string)
    {
        if ($message->getSendUser() === $this->getUser() || ($message->getReceiveUser() === $this->getUser() && !$message->isTaken()) || $this->accessManager->canManageWebmasterUser() || $message->getSendUser()->isSuperAdmin() || !$this->getUser()->isWriterAdmin()) {
            return $string;
        } else {
            return '***';
        }
    }
}
