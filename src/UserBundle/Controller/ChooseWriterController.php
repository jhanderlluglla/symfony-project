<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Candidate;
use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use CoreBundle\Entity\WaitingOrder;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\CopywritingOrderRepository;
use CoreBundle\Repository\WaitingOrderRepository;
use CoreBundle\Services\CalculatorPriceService;
use CoreBundle\Services\ChooseWriterService;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Security\MainVoter;

class ChooseWriterController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function writersAction(Request $request)
    {
        /** @var ChooseWriterService $chooseWriterService */
        $chooseWriterService = $this->get('core.service.choose_writer');

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $language = $request->get('language', Language::EN);

        try {
            $categories = $chooseWriterService->getWritersForChoose($this->getUser(), $language);
        } catch (\Exception $e) {
            return $this->json([
                    'status' => 'error',
                    'message' => $translator->trans('writers.error', [], 'copywriting')
            ]);
        }

        $normalizer = new ObjectNormalizer(null);
        $normalizer->setCircularReferenceLimit(1);
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        $jsonData = $serializer->serialize(
            [
                'status' => 'success',
                'message' => $translator->trans('writers.success', [], 'copywriting'),
                'categories' => $categories
            ],
            'json',
            array('attributes' => array('id', 'fullName'))
        );

        return new JsonResponse($jsonData);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function waitingOrdersAction(Request $request)
    {
        $this->denyAccessUnlessGranted(MainVoter::COPYWRITING_ORDER . '.' . MainVoter::SHOW_LIST, null);

        /** @var User $user */
        $user = $this->getUser();

        $expressOrdersFilters = [
            'status' => CopywritingOrder::STATUS_WAITING,
            'express' => true
        ];

        $waitingOrdersFilters = [
            'user' => $user,
            'status' => WaitingOrder::STATUS_WAITING,
            'with_orders' => true,
            'not_reject' => true
        ];

        if ($user->isWriter()) {
            $waitingOrdersFilters['language'] = $user->getWorkLanguage();
        }

        $perPage = $request->query->get('per-page', 20);
        $waitingPage = $request->query->get('waiting-page', 1);
        $expressPage = $request->query->get('express-page', 1);
        $pendingPage = $request->query->get('pending-page', 1);

        /** @var CopywritingOrderRepository $copywritingOrderRepository */
        $copywritingOrderRepository = $this->getDoctrine()->getRepository(CopywritingOrder::class);
        $expressOrders = $copywritingOrderRepository->getUserCollectionBuilder($expressOrdersFilters, $user);

        /** @var WaitingOrderRepository $waitingOrderRepository */
        $waitingOrderRepository = $this->getDoctrine()->getRepository(WaitingOrder::class);
        $waitingOrders = $waitingOrderRepository->filter($waitingOrdersFilters);

        $waitingOrderCollection = $waitingOrderRepository->createPagerfanta($waitingOrders);
        $waitingOrderCollection->setMaxPerPage($perPage);
        $waitingOrderCollection->setCurrentPage($waitingPage);

        $expressOrderCollection = $waitingOrderRepository->createPagerfanta($expressOrders);
        $expressOrderCollection->setMaxPerPage($perPage);
        $expressOrderCollection->setCurrentPage($expressPage);

        $pendingOrderCollection = [];
        if(count($waitingOrderCollection->getCurrentPageResults()) === 0 &&
           count($expressOrderCollection->getCurrentPageResults()) === 0 &&
           $copywritingOrderRepository->getProgressOrdersCount($user) === 0
        ){
            $pendingOrderFilters = [
                'status' => CopywritingOrder::STATUS_WAITING,
                'express' => false,
            ];
            $pendingOrders = $copywritingOrderRepository->getUserCollectionBuilder($pendingOrderFilters, $user);
            $pendingOrderCollection = $waitingOrderRepository->createPagerfanta($pendingOrders, 1, 10);
            $pendingOrderCollection->setCurrentPage($pendingPage);
            $pendingOrderCollection->setMaxPerPage($perPage);
        }
        $calculatorPriceService = $this->get('core.service.calculator_price_service');
        return $this->render('copywriting_order/waiting_orders.html.twig', [
            'collection' => $waitingOrderCollection,
            'expressOrders' => $expressOrderCollection,
            'pendingOrders' => $pendingOrderCollection,
            'calculatorPriceService' => $calculatorPriceService,
        ]);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function takeToWorkAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var WaitingOrder $waitingOrder */
        $waitingOrder = $this->getDoctrine()->getRepository(WaitingOrder::class)->find($id);

        if(is_null($waitingOrder) || $waitingOrder->getStatus() !== WaitingOrder::STATUS_WAITING){
            throw new EntityNotFoundException("Order with $id not found");
        }

        $copywritingOrder = $waitingOrder->getCopywritingOrder();
        $waitingOrder->setStatus(WaitingOrder::STATUS_ACCEPTED);
        $waitingOrder->getCandidateByUser($this->getUser())->setAction(Candidate::ACTION_ACCEPT);
        $em->persist($waitingOrder);
        $em->flush();

        return $this->redirectToRoute('copywriting_order_take_to_work',[
            'id' => $copywritingOrder->getId()
        ]);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function rejectWorkAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var WaitingOrder $waitingOrder */
        $waitingOrder = $this->getDoctrine()->getRepository(WaitingOrder::class)->find($id);
        if(is_null($waitingOrder)){
            throw new EntityNotFoundException("Order with $id not found");
        }

        $thisCandidate = $waitingOrder->getCandidateByUser($this->getUser());
        $thisCandidate->setAction(Candidate::ACTION_REJECT);

        if($waitingOrder->hasAllRejected()){
            /** @var CalculatorPriceService $calculatorPriceService */
            $calculatorPriceService = $this->get('core.service.calculator_price_service');

            $waitingOrder->setStatus(WaitingOrder::STATUS_REJECTED);
            $copywritingOrder = $waitingOrder->getCopywritingOrder();
            $project = $copywritingOrder->getProject();

            $additionalCosts = $calculatorPriceService->getChooseWriterPrice(
                $copywritingOrder->getWordsNumber(),
                $project->getWriterCategory(),
                CalculatorPriceService::TOTAL_KEY
            );

            $amountMinusBonus = $copywritingOrder->getAmount() - $additionalCosts;
            $copywritingOrder->setAmount($amountMinusBonus);

            /** @var TransactionService $transactionService */
            $transactionService = $this->get('core.service.transaction');

            $transaction = $transactionService->handling(
                $copywritingOrder->getCustomer(),
                new TransactionDescriptionModel(
                    'copywriting_order.reject_order_cashback',
                    [
                        '%order_title%' => $copywritingOrder->getTitle(),
                        '%project_title%' => $project->getTitle(),
                    ]
                ),
                $additionalCosts,
                0,
                null,
                [CopywritingOrder::TRANSACTION_TAG_BUY, CopywritingOrder::TRANSACTION_TAG_FAVORITE_CASHBACK]
            );

            $copywritingOrder->addTransaction($transaction);

            $em->persist($copywritingOrder);
        }

        $em->persist($waitingOrder);
        $em->flush();
        return $this->redirectToRoute('copywriting_order_list');
    }
}
