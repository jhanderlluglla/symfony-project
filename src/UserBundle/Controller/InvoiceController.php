<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Invoice;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Services\CalculatorVat;
use CoreBundle\Services\GenerateInvoiceService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class InvoiceController extends AbstractCRUDController
{

    /**
     * @param $id
     * @return Response
     * @throws EntityNotFoundException
     */
    public function viewAction($id)
    {
        $invoice = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if ($invoice == null) {
            throw new EntityNotFoundException("Invoice with id:$id not found");
        }

        $this->denyAccessUnlessGranted('view', $invoice);

        $settings = $this->getDoctrine()->getRepository(Settings::class)->getGroupOfSettings('invoice');

        /** @var CalculatorVat $calculatorVat */
        $calculatorVat = $this->get('core.service.calculate_vat_service');

        return $this->render('invoice/view.html.twig', [
            'invoice' => $invoice,
            'payer' => $invoice->getUser(),
            'vat_percent' => $calculatorVat->getVat($invoice->getUser()),
            'settings' => $settings
        ]);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws EntityNotFoundException
     */
    public function downloadAction($id)
    {
        /** @var Invoice $invoice */
        $invoice = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if ($invoice === null) {
            throw new EntityNotFoundException("Invoice with id:$id not found");
        }

        $this->denyAccessUnlessGranted('download', $invoice);
        $fullPath = $this->getParameter('invoice_dir') . DIRECTORY_SEPARATOR . $invoice->getFile();

        if (file_exists($fullPath)) {
            return $this->file($fullPath);
        } else {
            /** @var GenerateInvoiceService $generateInvoiceService */
            $generateInvoiceService = $this->get('core.service.generate_invoice_service');

            return $this->file($generateInvoiceService->generateFileFromInvoice($invoice));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $filters = [
            'user' => $this->getUser(),
        ];

        return parent::getCollectionData($request, $filters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getForm($entity, $options = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Invoice::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Invoice();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('invoice_list');
    }
}
