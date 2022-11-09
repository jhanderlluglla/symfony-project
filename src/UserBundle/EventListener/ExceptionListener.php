<?php

namespace UserBundle\EventListener;

use CoreBundle\Entity\EmailTemplates;
use CoreBundle\Services\Mailer;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ExceptionListener
 *
 * @package UserBundle\EventListener
 */
class ExceptionListener
{
    /** @var string */
    private $env;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Mailer */
    private $mailer;

    /** @var array */
    private $emails;

    /**
     * ExceptionListener constructor.
     *
     * @param string $env
     * @param TranslatorInterface $translator
     * @param Mailer $mailer
     * @param array $emails
     */
    public function __construct($env, TranslatorInterface $translator, Mailer $mailer, $emails)
    {
        $this->env = $env;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->emails = $emails;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /** @var HttpException $exception */
        $exception = $event->getException();

        if ($exception instanceof EntityNotFoundException) {
            throw new NotFoundHttpException($exception->getMessage(), $exception->getPrevious());
        }

        $code = $exception instanceof HttpException ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($this->env === 'dev' && !in_array($code, [400, 401, 403, 422])) {
            return;
        }

        if ($this->env === "prod" && $code === 500 && $this->emails) {
            $replace = [
                '%message%' => $exception->getMessage(),
                '%file%' => $exception->getFile(),
            ];

            foreach ($this->emails as $email) {
                $this->mailer->sendToEmail(EmailTemplates::NOTIFICATION_CRITICAL_ERROR, $email, $replace);
            }
        }

        if ($event->getRequest()->isXmlHttpRequest()) {
            $exceptionMessage = $exception->getMessage();
            if (empty($exceptionMessage)) {
                $idTranslate = 'error_'.$exception->getStatusCode().'.title';
                $message = $this->translator->trans($idTranslate, [], 'errors');
                if ($message !== $idTranslate) {
                    $exceptionMessage = $message;
                }
            }
            $event->setResponse(new JsonResponse(['status' => false, 'message' => $exceptionMessage], $code));
        }
    }
}
