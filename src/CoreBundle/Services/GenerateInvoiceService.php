<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Invoice;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Settings;
use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateInvoiceService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var GeneratorInterface
     */
    private $generatorPdf;

    /**
     * @var string
     */
    private $invoiceDir;

    /**
     * @var CalculatorVat
     */
    private $calculatorVat;

    /**
     * GenerateInvoiceService constructor.
     * @param EntityManager $em
     * @param EngineInterface $templating
     * @param GeneratorInterface $generatorPdf
     * @param string $invoiceDir
     * @param CalculatorVat $calculatorVat
     */
    public function __construct(
        EntityManager $em,
        EngineInterface $templating,
        GeneratorInterface $generatorPdf,
        $invoiceDir,
        CalculatorVat $calculatorVat
    ) {
        $this->em = $em;
        $this->templating = $templating;
        $this->generatorPdf = $generatorPdf;
        $this->invoiceDir = $invoiceDir;
        $this->calculatorVat = $calculatorVat;
    }

    /**
     * @param $totalAmount
     * @param User $payer
     * @param float $vat
     * @param int|null $fees
     * @param null $service
     * @param null $servicePaymentId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateInvoice($totalAmount, User $payer, $vat = 0.0, $fees = null, $service = null, $servicePaymentId = null)
    {
        $invoice = new Invoice();
        $today = new \DateTime();

        $fileName = $today->format('U') . '.pdf';

        $invoice->setCreatedAt($today);
        $invoice->setAmount($totalAmount);
        $invoice->setFile($fileName);
        $invoice->setUser($payer);
        $invoice->setVat($vat);
        $invoice->setFees($fees);
        $invoice->setNumber($today->format('U'));
        $invoice->setService($service);
        $invoice->setServicePaymentId($servicePaymentId);

        $folder = $this->generateFolder($today) . $fileName;
        $invoice->setFile($folder);

        $this->em->persist($invoice);
        $this->em->flush();

        $this->generateFile($invoice);
    }

    /**
     * @param Invoice $invoice
     * @return string
     */
    public function generateFileFromInvoice($invoice)
    {
        return $this->generateFile($invoice);
    }

    /**
     * @param Invoice $invoice
     * @return string
     */
    private function generateFile($invoice)
    {
        $settings = $this->em->getRepository(Settings::class)->getGroupOfSettings('invoice');
        $invoicePath = $this->invoiceDir . DIRECTORY_SEPARATOR . $invoice->getFile();

        $this->generatorPdf->generateFromHtml(
            $this->templating->render(
                'invoice/print_template.html.twig',
                [
                    'invoice' => $invoice,
                    'payer' => $invoice->getUser(),
                    'vat_percent' => $this->calculatorVat->getVat($invoice->getUser()),
                    'settings' => $settings
                ]
            ),
            $invoicePath
        );

        return $invoicePath;
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    private function generateFolder(\DateTime $date)
    {
        $filesystem = new Filesystem();

        $folder =
            $date->format('Y') . DIRECTORY_SEPARATOR .
            $date->format('F') . DIRECTORY_SEPARATOR;

        $folderWithInvoiceDir = $this->invoiceDir . DIRECTORY_SEPARATOR . $folder;
        if (!$filesystem->exists($folderWithInvoiceDir)) {
            $filesystem->mkdir($folderWithInvoiceDir);
        }

        return $folder;
    }
}
