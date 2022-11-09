<?php

namespace UserBundle\Services;

use CoreBundle\Services\AccessManager;
use CoreBundle\Services\Mailer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use CoreBundle\Entity\User;
use CoreBundle\Entity\Message;

/**
 * Class MessageService
 *
 * @package UserBundle\Services
 */
class MessageService
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Mailer
     */
    private $mailer;

    /** @var RouterInterface $router */
    private $router;

    /** @var AccessManager */
    private $accessManager;

    /**
     * MessageService constructor.
     *
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param TokenStorage $tokenStorage
     * @param Mailer $mailer
     * @param RouterInterface $router
     * @param AccessManager $accessManager
     */
    public function __construct($entityManager, $translator, $tokenStorage, Mailer $mailer, RouterInterface $router, AccessManager $accessManager)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->accessManager = $accessManager;

        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * @return array|null
     */
    public function getFormRecipientsList()
    {
        $hideEmail = true;
        if ($this->accessManager->canManageWriterUser() || $this->accessManager->canManageWebmasterUser()) {
            $recipient = [];
            $hideEmail = false;

            if ($this->user->isSuperAdmin()) {
                $recipient[Message::RECIPIENT_GROUP_TYPE_ALL] = $this->translator->trans('recipient_list.all', [], 'message');
                $recipient[Message::RECIPIENT_GROUP_TYPE_ADMINISTRATOR] = $this->translator->trans('recipient_list.administrator', [], 'message');
            }

            $roles = [];
            if ($this->accessManager->canManageWriterUser()) {
                $recipient[Message::RECIPIENT_GROUP_TYPE_SEO] = $this->translator->trans('recipient_list.seo', [], 'message');
                $roles[] = User::ROLE_WRITER;
                $roles[] = User::ROLE_WRITER_NETLINKING;
                $roles[] = User::ROLE_WRITER_COPYWRITING;
            }

            if ($this->accessManager->canManageWebmasterUser()) {
                $recipient[Message::RECIPIENT_GROUP_TYPE_WEBMASTER] = $this->translator->trans('recipient_list.webmaster', [], 'message');
                $roles[] = User::ROLE_WEBMASTER;
            }

            $recipient += $this->entityManager->getRepository(User::class)->getAllUsersAsKeyAndValue($this->user, $this->user->isWriterAdmin() ? $roles : null);
        } else {
            $recipient = $this->entityManager->getRepository(User::class)->getSuperUsersAsKeyAndValue();
        }

        /** @var User $value */
        foreach ($recipient as $key => $value) {
            if ($value instanceof User) {
                $recipient[$key] = $value->getFullName() . ' (' . ($hideEmail ? $value->getHiddenEmail() : $value->getEmail()) . ')';
            }
        }

        return array_flip($recipient);
    }

    /**
     * @param string|integer $recipient
     *
     * @return array
     */
    public function getRecipientsList($recipient)
    {
        $recipients = [];
        switch ($recipient) {
            case Message::RECIPIENT_GROUP_TYPE_ALL:
                $recipients = $this->entityManager->getRepository(User::class)->getAllUsersExcludeCurrent($this->user, true);
                break;

            case Message::RECIPIENT_GROUP_TYPE_ADMINISTRATOR:
                $recipients = $this->entityManager->getRepository(User::class)->getAllWriterAdmin(true);
                break;

            case Message::RECIPIENT_GROUP_TYPE_SEO:
                $recipients = $this->entityManager->getRepository(User::class)->getAllSeo(true);
                break;

            case Message::RECIPIENT_GROUP_TYPE_WEBMASTER:
                $recipients = $this->entityManager->getRepository(User::class)->getAllWebmaster(true);
                break;

            default:
                $user = $this->entityManager->getRepository(User::class)->find((int) $recipient);
                if ($user) {
                    $recipients[] = $user;
                }
                break;
        }

        return $recipients;
    }

    /**
     * @param Message $message
     * @param boolean $isMessage
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function sendMessage($message, $isMessage = false)
    {
        $replace = [
            '%fromName%' => $message->getSendUser()->getFullName(),
            '%message%' => $message->getContent(),
            '%messageLink%' => $this->router->generate('message_view', ['id' => $message->getId()]),
        ];

        $this->mailer->sendToUser(
            User::NOTIFICATION_NEW_MESSAGE,
            $message->getReceiveUser(),
            $replace,
            true,
            $isMessage ? User::LETTER_NEW_MESSAGE_WITH_CONTENT : User::LETTER_NEW_MESSAGE
        );
    }

    /**
     * @param Message $originMessage
     * @param Message $currentMessage
     * @param bool $isMessage
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function reply($originMessage, $currentMessage, $isMessage = false)
    {
        $this->sendMessage($currentMessage, $isMessage);
    }
}
