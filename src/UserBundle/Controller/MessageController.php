<?php

namespace UserBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use UserBundle\Form\MessageType;
use CoreBundle\Entity\Message;
use CoreBundle\Entity\User;
use UserBundle\Security\MessageVoter;

class MessageController extends AbstractCRUDController
{
    /**
     * {@inheritdoc}
     */
    protected function getForm($entity, $options = [])
    {
        $messageService = $this->get('user.message');

        return $this->createForm(MessageType::class, $entity, [
            'recipient' => $messageService->getFormRecipientsList(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Message::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Message();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('message_all');
    }

    /**
     * @param Request $request
     * @param object  $entity
     */
    public function beforeInsert(Request $request, $entity)
    {
        $entity->setSendUser($this->getUser());

    }

    /**
     * {@inheritdoc}
     */
    protected function processSubmit(Request $request, $entity, Form $form)
    {
        $data = $request->request->get('message');

        $messageService = $this->get('user.message');
        $recipients = $messageService->getRecipientsList($data['recipient']);
        $em = $this->getDoctrine()->getManager();

        /** @var User $recipient */
        foreach ($recipients as $recipient) {
            /** @var Message $messageEntity */
            $messageEntity = clone $entity;
            $messageEntity->setReceiveUser($recipient);

            $em->persist($messageEntity);
            $em->flush();

            $messageService->sendMessage($messageEntity, isset($data['sendMessage']));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $mode = $request->get('mode');
        switch ($mode){
            case Message::MESSAGE_TYPE_INCOMING:  {
                $filters = ['mode' => 'receiveUser'];
                break;
            }
            case Message::MESSAGE_TYPE_OUTGOING:  {
                $filters = ['mode' => 'sendUser'];
                break;
            }
            default:  {
                $filters = [];
                break;
            }
        }

        $filters['user'] = $this->getUser();

        if ($this->get('core.service.access_manager')->canAnswerMessage()) {
            $filters['adminMessageWebmaster'] = true;
        }

        return parent::getCollectionData($request, $filters);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     */
    public function viewAction(Request $request, $id)
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Message $entity */
        $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        $this->checkAccess(MessageVoter::ACTION_SHOW, $entity);

        if ($entity->isUserReceiver($user) || ($user->isWriterAdmin() && $entity->getReceiveUser()->isSuperAdmin())) {
            $this->saveIsRead($request, $entity);
        }

        return $this->render('message/view.html.twig', [
            'message' => $entity
        ]);
    }

    /**
     * @param Request $request
     * @param integer $id
     */
    public function replyAction(Request $request, $id)
    {
        /** @var Message $message */
        $message = $this->getDoctrine()->getRepository($this->getEntity())->find($id);
        if (is_null($message)) {
            throw new EntityNotFoundException();
        }

        $this->checkAccess(MessageVoter::ACTION_REPLY, $message);

        $entity = $this->getEntityObject();

        $entity
            ->setParentMessageId($message)
            ->setSubject('Re: ' . $message->getSubject())
        ;

        $form = $this->getForm($entity);
        $form->remove('recipient');

        $messageService = $this->get('user.message');
        if ($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->beforeInsert($request, $entity);
                $message->setAnswered(true);

                $entity->setReceiveUser($message->getSendUser());
                $entity->setTaken($entity->getParentMessageId()->isTaken() || ($this->getUser() !== $entity->getParentMessageId()->getReceiveUser() &&  $this->getUser() !== $entity->getParentMessageId()->getSendUser()));
                $em->persist($entity);
                $em->flush();

                $data = $request->request->get('message');

                $messageService->reply($message, $entity, isset($data['sendMessage']));

                $this->afterInsert($request, $entity);

                return $this->getRedirectToRoute($entity, 'replay');
            }
        }

        return $this->render('message/reply.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * @param Request $request
     * @param object  $entity
     */
    protected function saveIsRead(Request $request, $entity)
    {
        // set flag read
        $entity->setIsRead(Message::READ_YES);
        $entity->setReadAt(new \DateTime());
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param Message $entity
     */
    public function deleteMessage($entity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function ajaxDeleteAction(Request $request)
    {
        $translator = $this->get('translator');

        $response = [
            'message' => $translator->trans('ajax.delete.bad_request', [], 'message'),
        ];

        $responseStatus = Response::HTTP_BAD_REQUEST;

        $messages = $request->request->get('messages');

        $user = $this->getUser();
        if (!empty($messages)) {
            foreach ($messages as $message) {
                $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($message);
                if ($entity) {
                    if($entity->isUserSender($user) || $user->hasRole(User::ROLE_SUPER_ADMIN)) {
                        $this->deleteMessage($entity);
                    }
                }
            }

            $response = [
                'message' => $translator->trans('ajax.delete.success', [], 'message'),
            ];

            $responseStatus = Response::HTTP_OK;
        }

        return $this->json($response, $responseStatus);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxReadAction(Request $request)
    {
        $translator = $this->get('translator');

        $response = [
            'message' => $translator->trans('ajax.read.bad_request', [], 'message'),
        ];

        $responseStatus = Response::HTTP_BAD_REQUEST;

        $messages = $request->request->get('messages');
        if ($messages) {
            foreach ($messages as $message) {
                $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($message);
                if ($entity) {
                    $this->saveIsRead($request, $entity);
                }

            }

            $response = [
                'message' => $translator->trans('ajax.read.success', [], 'message'),
            ];

            $responseStatus = Response::HTTP_OK;
        }

        return $this->json($response, $responseStatus);
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return Message::class;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getAdditionalData(Request $request)
    {
        $additionalData = [
            'showIsAnswered' => false
        ];

        if ($request->attributes->has('mode')) {
            $additionalData['showIsAnswered'] = $request->attributes->get('mode') === Message::MESSAGE_TYPE_INCOMING && $this->get('core.service.access_manager')->canAnswerMessage();
        }

        return $additionalData;
    }
}
