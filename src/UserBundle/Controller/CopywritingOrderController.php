<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\CopywritingArticleComment;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingKeyword;
use CoreBundle\Entity\CopywritingImage;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Transaction;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\CopywritingOrderRepository;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Form\CopywritingArticleDeclineType;
use UserBundle\Form\CopywritingAssignOrderType;
use UserBundle\Form\CopywritingOrderType;
use Pagerfanta\Pagerfanta;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\User;
use UserBundle\Form\Filters\CopywritingOrderFilterType;
use UserBundle\Form\Filters\StatisticsYearFilterType;
use UserBundle\Security\MainVoter;

/**
 * Class CopywritingOrderController
 *
 * @package UserBundle\Controller
 */
class CopywritingOrderController extends AbstractCRUDController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->checkAccess(MainVoter::SHOW_LIST, null);

        $filters = $request->query->all();

        if ($this->getUser()->hasRole(User::ROLE_SUPER_ADMIN) || $this->getUser()->hasRole(User::ROLE_WRITER_ADMIN)) {
            $filterForm = $this->createForm(CopywritingOrderFilterType::class);

            if (key_exists('status', $filters) && $filters['status'] != CopywritingOrder::STATUS_COMPLETED) {
                $filterForm->remove('rating');
            }

            $filterForm->handleRequest($request);
            $filterFormData = $filterForm->getData();

            if ($filterFormData) {
                $filters = array_merge($filters, $filterFormData);
            }

            $orderBy = ['takenAt' => 'asc'];
            if (key_exists('status', $filters)) {
                if ($filters['status'] === CopywritingOrder::STATUS_WAITING) {
                    $filters['exclude_waiting'] = true;
                    $orderBy = ['createdAt' => 'asc'];
                }
                if ($filters['status'] === CopywritingOrder::STATUS_COMPLETED) {
                    $orderBy = ['approvedAt' => 'desc'];
                }
            }
            $queryBuilder = $this->getCollectionData($request, $filters, $orderBy);

            $page = $request->query->get('page', 1);
            $perPage = $request->query->get('per-page', 20);

            $adapter = PagerfantaAdapterFactory::getAdapterInstance($queryBuilder);

            $pagerfanta = new Pagerfanta($adapter);

            $pagerfanta
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page)
            ;

            return $this->render('copywriting_order/admin_index.html.twig', [
                'collection' => $pagerfanta,
                'assign_form' => $this->createForm(CopywritingAssignOrderType::class),
                'filter_form' => $filterForm->createView(),
                'status' => $filters['status']
            ]);
        } else {
            return parent::indexAction($request);
        }
    }

    /**
     * @param int $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function showAction($id)
    {
        /** @var CopywritingOrder $order */
        $order = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($order)) {
            throw new EntityNotFoundException();
        }
        $this->denyAccessUnlessGranted('copywritingOrder.show', $order);

        if ($order->getArticle() && !$order->getArticle()->isConsulted()) {
            $order->getArticle()->setConsulted(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($order->getArticle());
            $em->flush();
        }

        $declineUrl = $this->generateUrl('copywriting_order_decline', ['id' => $order->getId()]);
        $declineForm = $this->createForm(
            CopywritingArticleDeclineType::class,
            null,
            [
                'action' => $declineUrl,
            ]
        );

        return $this->render(
            $this->prepareShowTemplate(),
            [
                'decline_form' => $declineForm->createView(),
                'order' => $order,
                'article' => $order->getArticle(),
                'id' => $id,
            ]
        );
    }


    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     * @throws BadRequestHttpException
     * @Security("[has_role('ROLE_WRITER') || has_role('ROLE_WRITER_COPYWRITING')]")
     */
    public function takeToWorkAction(Request $request, $id)
    {
        /** @var CopywritingOrder $order */
        $order = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($order)) {
            throw new EntityNotFoundException();
        }

        $orderWorkflow = $this->get('workflow.registry')->get($order);
        if($orderWorkflow->can($order, CopywritingOrder::TRANSITION_TAKE_TO_WORK)) {
            $orderWorkflow->apply($order, CopywritingOrder::TRANSITION_TAKE_TO_WORK);
        } else {
            throw new BadRequestHttpException();
        }

        $user = $this->getUser();
        $order->setCopywriter($user);

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('copywriting_order_list',['status' => ['declined', 'progress']]);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     * @throws BadRequestHttpException
     * @Security("has_role('ROLE_WEBMASTER') || has_role('ROLE_SUPER_ADMIN') || has_role('ROLE_WRITER_ADMIN')")
     */
    public function declineAction(Request $request, $id)
    {
        $translator = $this->get('translator');

        /** @var CopywritingOrder $order */
        $order = $this->getDoctrine()->getRepository($this->getEntity())->find($id);
        $orderWorkflow = $this->get('workflow.registry')->get($order);

        $declineComment = new CopywritingArticleComment();
        $declineComment->setUser($this->getUser());

        $declineForm = $this->createForm(CopywritingArticleDeclineType::class, $declineComment);
        $declineForm->handleRequest($request);

        if (is_null($order)) {
            throw new EntityNotFoundException();
        }

        if ($declineForm->isSubmitted() && $declineForm->isValid() && $orderWorkflow->can($order, CopywritingOrder::TRANSITION_DECLINE_TRANSITION)) {
            $copywritingOrderService = $this->get('core.service.copywriting_order');
            $copywritingOrderService->decline($order, $declineComment);
        } else {
            $response = [
                'status' => 'error',
                'message' => $translator->trans('ajax.send.bad_request', [], 'message')
            ];

            if (!$declineForm->isValid()) {
                $errors = $declineForm->getErrors(true);
                $response['message'] = "";
                foreach ($errors as $error) {
                    $response['message'] .= $error->getMessage();
                }
            }

            return $this->json($response);
        }

        $status = [
            CopywritingOrder::STATUS_DECLINED,
            CopywritingOrder::STATUS_PROGRESS
        ];

        if ($this->getUser()->isAdmin()) {
            $status = CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN;
        }
        if ($this->getUser()->isWebmaster()) {
            $status = CopywritingOrder::STATUS_COMPLETED;
        }

        return $this->json([
            'status' => 'success',
            'message' => $translator->trans('declined', [], 'copywriting'),
            'location' => $this->generateUrl('copywriting_order_list', ['status' => $status])
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_SUPER_ADMIN') || has_role('ROLE_WRITER_ADMIN')")
     */
    public function statisticsAction(Request $request)
    {
        if (!$this->get('core.service.access_manager')->canManageCopywritingProject()) {
            throw new AccessDeniedHttpException();
        }

        $year = date("Y");

        $form = $this->createForm(StatisticsYearFilterType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $year = $data['year'];
        }

        $statistics = $this->get('user.copywriting.statistics_builder')->build($year);

        return $this->render('copywriting_order/statistics.html.twig', [
            'statistics' => $statistics,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     * @Security("has_role('ROLE_SUPER_ADMIN') || has_role('ROLE_WRITER_ADMIN')")
     */
    public function ajaxAssignAction(Request $request)
    {
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        $workflowRegistry = $this->get('workflow.registry');

        $orderIds = $request->request->get('orderIds');
        $copywriterId = $request->request->get('copywriter');
        $writers = [];
        try {
            $orders = $this->getDoctrine()->getRepository(CopywritingOrder::class)->findBy(['id' => $orderIds]);
            /** @var User $copywriter */
            $copywriter = $this->getDoctrine()->getRepository(User::class)->find($copywriterId);

            if (count($orders) === 0 || is_null($copywriter)) {
                throw new EntityNotFoundException();
            }

            /** @var CopywritingOrder $order */
            foreach ($orders as $order) {
                $order->setCopywriter($copywriter);
                $writers[$order->getId()] = [
                    'fullName' => $copywriter->getFullName(),
                    'editWriterUrl' => $this->generateUrl('user_edit', ['id' => $copywriter->getId()])
                ];
                if ($workflowRegistry->get($order)->can($order, CopywritingOrder::TRANSITION_TAKE_TO_WORK)) {
                    $workflowRegistry->get($order)->apply($order, CopywritingOrder::TRANSITION_TAKE_TO_WORK);
                } else {
                    $order
                        ->setTimeInProgress(0)
                        ->setTakenAt(new \DateTime())
                    ;
                }
            }

            $em->flush();
        } catch (\Exception $e) {
            return $this->json(
                [
                    'result' => 'fail',
                    'message' => $translator->trans('ajax.assign.bad_request', [], 'copywriting'),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json([
            'result' => 'success',
            'message' => $translator->trans('ajax.assign.success', [], 'copywriting'),
            'writers' => $writers,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     * @Security("has_role('ROLE_WEBMASTER')")
     */
    public function ajaxChangeRatingAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $translator = $this->get('translator');

        /** @var CopywritingOrder $order */
        $order = $em->getRepository($this->getEntity())->find($id);

        if (!$order || !$request->request->has('rating')) {
            $response = [
                'message' => $translator->trans('ajax.rating.bad_request', [], 'copywriting'),
            ];

            $responseStatus = Response::HTTP_BAD_REQUEST;

            return new JsonResponse($response, $responseStatus);
        }

        $rating = $request->request->get('rating');
        $rating = $rating == '' ? null : filter_var($rating, FILTER_VALIDATE_BOOLEAN);
        $comment = $request->request->get('comment');

        if ($order->getRating()) {
            if (is_null($rating)) {
                $em->remove($order->getRating());
                $order->setRating(null);
            } else {
                $order->getRating()->setValue($rating);
                $order->getRating()->setComment($comment);
            }
        } else {
            $ratingEntity = new CopywritingArticleRating();
            $ratingEntity->setValue($rating);
            $ratingEntity->setComment($comment);
            $order->setRating($ratingEntity);
        }

        $em->persist($order);
        $em->flush();

        $response = [
            'message' => $translator->trans('ajax.rating.success', [], 'copywriting'),
        ];

        $responseStatus = Response::HTTP_OK;

        return new JsonResponse($response, $responseStatus);
    }

    /**
     * @param ExchangeSite $entity
     * @param array        $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getForm($entity, $options = [])
    {
        $options['calculator_price_service'] = $this->get('core.service.calculator_price_service');

        return $this->createForm(CopywritingOrderType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return CopywritingOrder::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new CopywritingOrder();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'copywriting_order';
    }

    /**
     * @param object $entity
     * @param string $action
     * @param Request $request
     *
     * @return RedirectResponse
     */
    protected function getRedirectToRoute($entity, $action, $request = null)
    {
        if ($request) {
            return $this->redirect($request->headers->get('referer'));
        }

        return $this->redirectToRoute('copywriting_order_list', ['status' => 'waiting']);
    }

    /**
     * @param Request $request
     * @param array $filters
     * @param array $orderBy
     * @return QueryBuilder|array
     */
    protected function getCollectionData(Request $request, $filters = [], $orderBy = [])
    {
        $user = $this->getUser();
        /** @var CopywritingOrderRepository $repository */
        $repository = $this->getDoctrine()->getRepository(CopywritingOrder::class);

        return $repository->getUserCollectionBuilder($filters, $user, $orderBy);
    }

    /**
     * @param Request $request
     * @param object $oldOrder
     * @param CopywritingOrder $order
     */
    protected function afterUpdate(Request $request, $oldOrder, $order)
    {
        $oldAmount = $oldOrder->getAmount();
        $newAmount = $order->getAmount();
        $customer = $order->getProject()->getCustomer();
        if ($oldAmount != $newAmount) {
            $amountChange = $newAmount - $oldAmount;

            $credit = $amountChange > 0 ? $amountChange : 0;
            $debit = $amountChange < 0 ? abs($amountChange) : 0;

            /** @var Transaction $transaction */
            $transactionService = $this->get('core.service.transaction');
            $transaction = $transactionService->handling(
                $customer,
                new TransactionDescriptionModel('copywriting_order.edit', ['%order_title%' => $order->getTitle()]),
                $debit,
                $credit,
                null,
                [CopywritingOrder::TRANSACTION_TAG_BUY, CopywritingOrder::TRANSACTION_TAG_EDIT]
            );

            $order->addTransaction($transaction);
        }
    }

    /**
     * @param Request $request
     * @param CopywritingOrder $order
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     */
    protected function beforeDelete(Request $request, $order)
    {
        if ($order->getExchangeProposition() && $order->getExchangeProposition()->getType() === ExchangeProposition::EXTERNAL_TYPE) {
            throw new BadRequestHttpException('Deletion of CopywritingOrder with ExchangeProposition is not possible', null, Response::HTTP_BAD_REQUEST);
        }
//        !!!!! This code is needed, in case you are allowed to delete orders with offers! (exception at the beginning of the function) !!!!!
//
//        if ($order->getExchangeProposition()) {
//            $exchangePropositionService = $this->get('core.service.exchange_proposition');
//            $order->getExchangeProposition()->setImpossibleComment($translator->trans('transaction_details.delete_order_comment', [], 'exchange_site_proposals'));
//            $exchangePropositionService->applyTransition($order->getExchangeProposition(), ExchangeProposition::TRANSITION_IMPOSSIBLE);

        $transactionService = $this->get('core.service.transaction');
        $transactionService->handling(
            $order->getProject()->getCustomer(),
            new TransactionDescriptionModel('copywriting_order.delete', ['%order_title%' => $order->getTitle()]),
            $order->getAmount(),
            0,
            null,
            [CopywritingOrder::TRANSACTION_TAG_BUY, CopywritingOrder::TRANSACTION_TAG_DELETE]
        );
    }

    /**
     * @param Request          $request
     * @param CopywritingOrder $oldEntity
     * @param CopywritingOrder $entity
     */
    protected function beforeUpdate(Request $request, $oldEntity, $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $request->request->get('copywriting_order');

        $originalImages = new ArrayCollection();
        foreach ($oldEntity->getImages() as $image) {
            $originalImages->add($image);
        }

        if (!empty($data['images'])) {
            foreach ($originalImages as $image) {
                if (false === $entity->getImages()->contains($image)) {
                    $em->remove($image);
                }
            }

            /** @var CopywritingImage $image */
            foreach ($entity->getImages() as $image) {
                $image->setOrder($entity);
            }
        } else {
            foreach ($originalImages as $image) {
                $em->remove($image);
            }

            $entity->setImages([]);
        }

        $originalKeywords = new ArrayCollection();
        foreach ($oldEntity->getKeywords() as $keyword) {
            $originalKeywords->add($keyword);
        }

        if (!empty($data['keywords'])) {
            foreach ($originalKeywords as $keyword) {
                if (false === $entity->getKeywords()->contains($keyword)) {
                    $em->remove($keyword);
                }
            }

            /** @var CopywritingKeyword $keyword */
            foreach ($entity->getKeywords() as $keyword) {
                $keyword->setOrder($entity);
            }
        } else {
            foreach ($originalKeywords as $keyword) {
                $em->remove($keyword);
            }

            $entity->setKeywords([]);
        }
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::COPYWRITING_ORDER;
    }
}
