<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\User;
use CoreBundle\Entity\WithdrawRequest;
use CoreBundle\Entity\Settings;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\WithdrawRequestRepository;
use CoreBundle\Repository\SettingsRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Form\WithdrawRequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class WithdrawController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \CoreBundle\Exceptions\UnknownTransactionTagNameException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function requestAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isWriterAdmin() && !$this->get('core.service.access_manager')->canManageEarning()) {
            throw $this->createAccessDeniedException();
        }

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getRepository(Settings::class);

        /** @var WithdrawRequestRepository $withdrawRequestRepository */
        $withdrawRequestRepository = $this->getDoctrine()->getRepository(WithdrawRequest::class);

        $countByLastMonth = $withdrawRequestRepository->getCountByLastMonth($this->getUser(), [
            'status' => ['waiting', 'accepted']
        ]);
        $withdrawRequest = new WithdrawRequest();
        $form = $this->createForm(WithdrawRequestType::class, $withdrawRequest);
        $form->handleRequest($request);

        $withdrawPercent = $settingsRepository->getSettingValue(Settings::WITHDRAW_PERCENT);

        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $withdrawRequest->getInvoice();
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            $file->move(
                $this->getParameter('uploaded_invoice_dir'),
                $fileName
            );

            $withdrawRequest->setInvoice($fileName);
            $withdrawRequest->setCommissionPercent($withdrawPercent);
            $withdrawRequest->setUser($user);

            $em->persist($withdrawRequest);

            $user->addPaymentDataFromWithdraw($withdrawRequest);

            //add line in the transaction table
            $credit = $withdrawRequest->getWithdrawAmount();
            $amountWithCommission = $withdrawRequest->getAmountWithCommission();
            $commission = $credit * $withdrawPercent / 100;
            $moreDetails = [
                'ereferer_commission' =>$commission,
                'net_to_pay' => $amountWithCommission,
            ];
            $transactionService = $this->get('core.service.transaction');
            $transactionService->handling(
                $user,
                new TransactionDescriptionModel('account.withdraw_transaction_comment'),
                0,
                $credit,
                $moreDetails,
                [User::TRANSACTION_TAG_WITHDRAW]
            );

            return $this->redirect($this->generateUrl('accepted_request'));
        }

        $filters = [];
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);
        $this->get('core.service.access_manager')->canManageEarning() ?: $filters['user'] = $user;
        $collection = $withdrawRequestRepository->getCollection($filters, $page, $perPage);

        $lastPaymentType = $em->getRepository(WithdrawRequest::class)->getLastByUser($user);

        return $this->render('withdraw/request.html.twig', [
            'form' => $form->createView(),
            'settings' => $settingsRepository->getSettingsByIdentificators([
                'invoice_eurl',
                'invoice_headquarters_address',
                'invoice_postal_code',
                'invoice_area_address',
                'invoice_country',
                'invoice_vat_number',
                Settings::WITHDRAW_PERCENT,
                Settings::MINIMUM_WITHDRAW,
                Settings::WITHDRAW_PER_MONTH,
            ]),
            'userPaymentData' => $user->getPaymentData(),
            'lastPaymentType' => $lastPaymentType ? $lastPaymentType->getType() : 'paypal',
            'countByLastMonth' => $countByLastMonth,
            'collection' => $collection,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function acceptedRequestAction()
    {
        return $this->render('withdraw/accepted_request.html.twig');
    }

    /**
     * @param Request $request
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws EntityNotFoundException
     */
    public function changeStatusAction(Request $request, $id)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->isWriterAdmin() && !$this->get('core.service.access_manager')->canManageEarning()) {
            throw $this->createAccessDeniedException();
        }

        /** @var WithdrawRequest $withdrawRequest */
        $withdrawRequest = $this->getDoctrine()->getRepository(WithdrawRequest::class)->find($id);

        if (is_null($withdrawRequest)) {
            throw new EntityNotFoundException("Withdraw request with $id not found");
        }

        if ($withdrawRequest->getStatus() !== WithdrawRequest::STATUS_WAITING) {
            throw new \LogicException("Can't change status");
        }

        $status = $request->request->get('status');
        $comment = $request->request->get('comment', null);
        $status == true ? $status = WithdrawRequest::STATUS_ACCEPTED : $status = WithdrawRequest::STATUS_REJECTED;

        $withdrawRequest->setStatus($status);
        $withdrawRequest->setReviewComment($comment);
        $withdrawRequest->setReviewedAt(new \DateTime());

        if ($status === WithdrawRequest::STATUS_REJECTED) {
            $transactionService = $this->get('core.service.transaction');
            $transactionService->handling(
                $withdrawRequest->getUser(),
                new TransactionDescriptionModel('account.withdraw_transaction_cancelled'),
                $withdrawRequest->getWithdrawAmount(),
                0,
                ['rejection_reason' => $comment],
                [User::TRANSACTION_TAG_WITHDRAW_REJECT]
            );
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => $this->get('translator')->trans('request.' . $status, [], 'withdraw'),
        ]);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws EntityNotFoundException
     */
    public function viewInvoiceAction($id)
    {
        /** @var WithdrawRequest $withdrawRequest */
        $withdrawRequest = $this->getDoctrine()->getRepository(WithdrawRequest::class)->find($id);

        if ($withdrawRequest === null) {
            throw new EntityNotFoundException("Withdraw request with $id not found");
        }
        $fullPath = $this->getParameter('uploaded_invoice_dir') . DIRECTORY_SEPARATOR . $withdrawRequest->getInvoice();

        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException("Invoice not found");
        }
        return $this->file($fullPath, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
