<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Invoice;
use CoreBundle\Entity\ReplenishRequest;
use CoreBundle\Entity\Settings;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\ReplenishRequestRepository;
use CoreBundle\Repository\SettingsRepository;
use CoreBundle\Services\CalculatorVat;
use CoreBundle\Services\GenerateInvoiceService;
use Doctrine\ORM\EntityNotFoundException;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use CoreBundle\Services\TransactionService;
use CoreBundle\Services\ReplenishAccountService;
use CoreBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Form\ReplenishType;

/**
 * Class ReplenishAccountController
 *
 * @package UserBundle\Controller
 */
class ReplenishAccountController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->get('status') === 'success') {
            return $this->render('replenish_account/wire_transfer_success.html.twig', [
                'response' => [
                    'status' => 'success',
                ]
            ]);
        }

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getManager()->getRepository(Settings::class);

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        /** @var CalculatorVat $calculatorVat */
        $calculatorVat = $this->get('core.service.calculate_vat_service');

        $replenishRequest = new ReplenishRequest();

        $form = $this->createForm(ReplenishType::class, $replenishRequest);

        $form->handleRequest($request);
        $replenishRequest->setUser($user);

        if ($form->isSubmitted() && $form->isValid()) {
            $replenishAmount = $replenishRequest->getAmount();

            $replenishType = $replenishRequest->getRequestType();
            switch ($replenishType) {
                case ReplenishRequest::PAYPAL_TYPE:
                    $fees = $calculatorVat->getPaypalFees($replenishAmount);
                    $amountWithFees = $replenishAmount + $fees;
                    $vat = $calculatorVat->calculateVat($amountWithFees, $user);

                    $replenishRequest->setVat($vat);
                    $replenishRequest->setPaypalFees($fees);

                    /** @var ReplenishAccountService $replenishAccountService */
                    $replenishAccountService = $this->get('core.service.replenish_account');
                    $result = $replenishAccountService
                        ->setReturnUrl($this->generateUrl('admin_replenish_account_success', [], UrlGeneratorInterface::ABSOLUTE_URL))
                        ->setCancelUrl($this->generateUrl('admin_replenish_account_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL))
                        ->handling(
                            $amountWithFees,
                            $vat,
                            $translator->trans('description.account.replenish_description', [
                                '%name%' => $user->getFullName()
                            ], 'transaction')
                        );

                    if ($result['status'] == ReplenishAccountService::REPLENISH_STATUS_SUCCESS) {
                        $replenishRequest->setPaymentId($result['id']);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($replenishRequest);
                        $em->flush();
                        return new RedirectResponse($result['data']);
                    }
                    break;

                case ReplenishRequest::WIRE_TRANSFER_TYPE:
                    $vat = $calculatorVat->calculateVat($replenishAmount, $user);
                    $replenishRequest->setVat($vat);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($replenishRequest);
                    $em->flush();

                    return new RedirectResponse($this->generateUrl('admin_replenish_account', ['status' => 'success']));
                default:
                    throw new \LogicException("This code should not be reached, type: $replenishType");
            }
        }

        return $this->render('replenish_account/index.html.twig', [
            'form' => $form->createView(),
            'vat_percent' => $calculatorVat->getVat($user),
            'settings' => $settingsRepository->getSettingsByIdentificators([
                Settings::INVOICE_IBAN,
                Settings::INVOICE_BIC_SWIFT,
                Settings::INVOICE_EURL,
                Settings::INVOICE_HEADQUARTES_ADDRESS,
                Settings::INVOICE_AREA_ADDRESS,
                Settings::INVOICE_POSTAL_CODE,
                Settings::INVOICE_COUNTRY,
            ]),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function successAction(Request $request)
    {
        $paymentId = $request->query->get('paymentId');
        $payerId = $request->query->get('PayerID');

        /** @var ReplenishAccountService $replenishAccountService */
        $replenishAccountService = $this->get('core.service.replenish_account');
        $result = $replenishAccountService->complete($paymentId, $payerId);

        /** @var ReplenishRequestRepository $replenishRequestRepository */
        $replenishRequestRepository =$this->getDoctrine()->getManager()->getRepository(ReplenishRequest::class);

        /** @var ReplenishRequest $replenishRequest */
        $replenishRequest = $replenishRequestRepository->findOneBy(['paymentId' => $paymentId]);
        if ($result['status'] == ReplenishAccountService::REPLENISH_STATUS_SUCCESS) {

            /** @var User $user */
            $user = $this->getUser();

            $replenishRequest->setStatus(ReplenishRequest::STATUS_ACCEPTED);
            $amount = $replenishRequest->getAmount() + $replenishRequest->getPaypalFees();
            try {
                /** @var GenerateInvoiceService $generateInvoiceService */
                $generateInvoiceService = $this->get('core.service.generate_invoice_service');
                $generateInvoiceService->generateInvoice($amount, $user, $replenishRequest->getVat(), $replenishRequest->getPaypalFees(), Invoice::SERVICE_PAYPAL, $paymentId);
            } catch (\Exception $exception) {
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error("Error with generate invoice" . $exception->getMessage());
            }

            /** @var TransactionService $transactionService */
            $transactionService = $this->get('core.service.transaction');
            $transactionService->handling(
                $user,
                new TransactionDescriptionModel('account.replenish_paypal'),
                $replenishRequest->getAmount(),
                0,
                null,
                [User::TRANSACTION_TAG_REPLENISH]
            );

            $response = [
                'status' => ReplenishAccountService::REPLENISH_STATUS_SUCCESS,
                'amount' => $replenishRequest->getAmount(),
            ];
        } else {
            $response = [
                'status' => ReplenishAccountService::REPLENISH_STATUS_FAIL,
            ];
        }

        return $this->render('replenish_account/success.html.twig', [
            'response' => $response
        ]);
    }

    /**
     * @return Response
     */
    public function cancelAction()
    {
        return $this->render('replenish_account/cancel.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showRequestsAction(Request $request)
    {
        $page = $request->request->get('page', 1);
        $perPage = $request->request->get('per-page', 20);
        $replenishRequestRepository = $this->getDoctrine()->getRepository(ReplenishRequest::class);

        $queryBuilder = $replenishRequestRepository->filter([
            'requestType' => ReplenishRequest::WIRE_TRANSFER_TYPE
        ]);

        return $this->render('replenish_account/requests.html.twig', [
            'collection' => PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $page, $perPage),
            'calculatorVat' => $this->get('core.service.calculate_vat_service'),
        ]);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeStatusAction(Request $request, int $id)
    {
        /** @var ReplenishRequest $replenishRequest */
        $replenishRequest = $this->getDoctrine()->getRepository(ReplenishRequest::class)->find($id);

        if (is_null($replenishRequest)) {
            throw new EntityNotFoundException();
        }
        $user = $replenishRequest->getUser();
        if ($replenishRequest->getStatus() !== ReplenishRequest::STATUS_WAITING) {
            throw new \LogicException("Can't change status");
        }

        $status = $request->get('status');
        switch ($status) {
            case ReplenishRequest::STATUS_ACCEPTED:
                $replenishRequest->setStatus(ReplenishRequest::STATUS_ACCEPTED);

                $generateInvoiceService = $this->get('core.service.generate_invoice_service');
                $generateInvoiceService->generateInvoice($replenishRequest->getAmount(), $user, $replenishRequest->getVat(), null, Invoice::SERVICE_WIRE_TRANSFER);

                /** @var TransactionService $transactionService */
                $transactionService = $this->get('core.service.transaction');
                $transactionService->handling(
                    $user,
                    new TransactionDescriptionModel('account.replenish_wire_transfer'),
                    $replenishRequest->getAmount(),
                    0,
                    null,
                    [User::TRANSACTION_TAG_REPLENISH]
                );

                break;
            case ReplenishRequest::STATUS_REJECTED:
                $replenishRequest->setStatus(ReplenishRequest::STATUS_REJECTED);
                $this->getDoctrine()->getManager()->flush();
                break;
            default:
                throw new \LogicException("This code should not be reached, status: $status");
        }

        return $this->redirectToRoute('admin_replenish_requests');
    }
}
