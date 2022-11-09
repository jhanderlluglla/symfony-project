<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Constant\Language;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use CoreBundle\Entity\EmailTemplates;
use CoreBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Mailer
 *
 * @package CoreBundle\Services
 */
class Mailer implements MailerInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $noreplyEmail;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var string */
    private $siteUrl;

    /** @var TranslatorInterface */
    private $translator;

    /** @var LanguageService */
    private $languageService;

    /** @var string */
    private $baseTemplate;

    /**
     * Mailer constructor.
     *
     * @param EntityManager $entityManager
     * @param \Swift_Mailer $mailer
     * @param string $noreplyEmail
     * @param UrlGeneratorInterface $router
     * @param string $siteUrl
     * @param TranslatorInterface $translator
     * @param LanguageService $languageService
     * @param $baseTemplate
     */
    public function __construct($entityManager, $mailer, $noreplyEmail, UrlGeneratorInterface $router, $siteUrl, TranslatorInterface $translator, LanguageService $languageService, $baseTemplate)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->noreplyEmail = $noreplyEmail;
        $this->router = $router;
        $this->siteUrl = $siteUrl;
        $this->translator = $translator;
        $this->languageService = $languageService;
        $this->baseTemplate = $baseTemplate;
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function router()
    {
        return $this->router;
    }

    public function translator()
    {
        return $this->translator;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $replace
     * @param array $options
     *
     * @return int
     */
    public function send($to, $subject, $body, $replace = [], $options = [])
    {
        if (!isset($options['contentType'])) {
            $options['contentType'] = 'text/html';
        }
        if (!isset($options['from'])) {
            $options['from'] = $this->noreplyEmail;
        }
        if (!isset($options['language'])) {
            $options['language'] = Language::EN;
        }

        $replace =
            [
                '%baseUrl%' => $this->languageService->prepareUrlForLanguage($this->siteUrl, $options['language'])
            ]
            + $replace;

        $body = strtr($body, $replace);
        $subject = strtr($subject, $replace);

        $body = str_replace('%body%', $body, file_get_contents($this->baseTemplate));

        $message = (new \Swift_Message($subject))
            ->setFrom($options['from'])
            ->setTo($to)
            ->setBody($body, $options['contentType'])
            ->addPart(self::getPlainText($body), 'text/plain')
        ;

        return $this->mailer->send($message);
    }

    /**
     * @param string $idNotification
     * @param User $user
     * @param array $replace
     * @param bool $checkedNotificationEnabled
     * @param null $idTemplate
     *
     * @return boolean
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function sendToUser($idNotification, User $user, $replace = [], $checkedNotificationEnabled = true, $idTemplate = null)
    {
        if ($checkedNotificationEnabled && !$user->isNotificationEnabled($idNotification)) {
            return false;
        }

        $replace = ['%userName%' => $user->getFullName()] + $replace;

        if ($idTemplate === null) {
            $idTemplate = $idNotification;
        }

        return $this->sendToEmail($idTemplate, $user->getEmailCanonical(), $replace, $user->getLocale());
    }


    /**
     * @param string $idTemplate
     * @param string $email
     * @param array $replace
     * @param string $locale
     *
     * @return int
     */
    public function sendToEmail($idTemplate, $email, $replace = [], $locale = 'en')
    {
        /** @var EmailTemplates $emailTemplates */
        $emailTemplates = $this->entityManager->getRepository(EmailTemplates::class)->getEmailTemplate($idTemplate, $locale);

        if (is_null($emailTemplates)) {
            return false;
        }

        $subject = $emailTemplates->getSubject();
        $body = $emailTemplates->getEmailContent();

        return $this->send($email, $subject, $body, $replace, ['language' => $locale]);
    }

    /**
     * @param UserInterface $user
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        $replace = [
            '%confirmationUrl%' => $this->router->generate('fos_user_registration_confirm', ['token' => $user->getConfirmationToken()]),
        ];

        $this->sendToUser(User::LETTER_CONFIRMATION_EMAIL, $user, $replace, false);
    }

    /**
     * Send an email to a user to confirm the password reset.
     *
     * @param UserInterface $user
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {
        $replace = [
            '%confirmationUrl%' => $this->router->generate('fos_user_resetting_reset', ['token' => $user->getConfirmationToken()]),
        ];

        $this->sendToUser(User::LETTER_RESET_PASSWORD, $user, $replace, false);
    }

    /**
     * @param $html
     *
     * @return string|string[]|null
     */
    private static function getPlainText($html)
    {
        $result = preg_replace('~<br\s*/?>~ui', "\n", $html);
        $result = strip_tags($result);

        return $result;
    }
}
