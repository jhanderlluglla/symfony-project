<?php
namespace CoreBundle\Services;

use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReplenishAccountService
 *
 * @package CoreBundle\Services
 */
class ReplenishAccountService
{

    const REPLENISH_STATUS_SUCCESS = 'success';
    const REPLENISH_STATUS_FAIL    = 'fail';

    const PAYPAL_STATE_CREATED             = 'created';
    const PAYPAL_STATE_APPROVED            = 'approved';
    const PAYPAL_STATE_FAILED              = 'failed';
    const PAYPAL_STATE_PARTIALLY_COMPLETED = 'partially_completed';
    const PAYPAL_STATE_IN_PROGRESS         = 'in_progress';

    /**
     * @var ApiContext
     */
    private $apiContext;

    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var string
     */
    private $cancelUrl;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AffiliationService constructor.
     *
     * @param string $paypalClientId
     * @param string $paypalSecretId
     * @param TranslatorInterface $translator
     * @param string $kernelEnvironment
     */
    public function __construct($paypalClientId, $paypalSecretId, $translator, $kernelEnvironment)
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $paypalClientId,
                $paypalSecretId
            )
        );

        $mode = "sandbox";
        if ($kernelEnvironment === 'prod') {
            $mode = "live";
        }

        $this->apiContext->setConfig([
            'log.LogEnabled' => true,
            'log.FileName' => "../var/logs/$kernelEnvironment/PayPal.log",
            'log.LogLevel' => 'DEBUG',
            'mode' => $mode
        ]);

        $this->translator = $translator;
    }

    /**
     * @param string $returnUrl
     *
     * @return ReplenishAccountService
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * @param string $cancelUrl
     *
     * @return ReplenishAccountService
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    /**
     * @param float $replenishWithTax
     * @param float $tax
     * @param string $description
     * @param string $currency
     *
     * @return array
     */
    public function handling($replenishWithTax, $tax, $description, $currency = 'EUR')
    {
        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // Set redirect urls
        $redirectUrls = new RedirectUrls();
        $redirectUrls
            ->setReturnUrl($this->returnUrl)
            ->setCancelUrl($this->cancelUrl)
        ;

        $item = new Item();
        $item
            ->setName($this->translator->trans('recharge', [], 'replenish_account'))
            ->setCurrency($currency)
            ->setQuantity(1)
            ->setPrice($replenishWithTax)
        ;

        $itemList = new ItemList();
        $itemList->addItem($item);

        $details = new Details();
        $details
            ->setTax($tax)
            ->setSubtotal($replenishWithTax)
        ;

        $amount = new Amount();
        $amount
            ->setCurrency($currency)
            ->setTotal($replenishWithTax + $tax)
            ->setDetails($details)
        ;

        $transaction = new Transaction();
        $transaction
            ->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($description)
        ;

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($this->apiContext);

            return [
                'status' => self::REPLENISH_STATUS_SUCCESS,
                'data' => $payment->getApprovalLink(),
                'id' => $payment->getId(),
            ];
        } catch (PayPalConnectionException $ex) {
            return [
                'status' => self::REPLENISH_STATUS_FAIL,
                'data' => $ex->getData(),
            ];
        } catch (\Exception $ex) {
            return [
                'status' => self::REPLENISH_STATUS_FAIL,
                'data' => $ex->getMessage(),
            ];
        }
    }

    /**
     * @param integer $paymentId
     * @param integer $payerId
     *
     * @return array
     */
    public function complete($paymentId, $payerId)
    {
        // Get payment object by passing paymentId
        $payment = Payment::get($paymentId, $this->apiContext);

        // Execute payment with payer id
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            /** @var Payment $paymentResult */
            $paymentResult = $payment->execute($execution, $this->apiContext);

            /** @var Transaction[] $transactions */
            $transactions = $paymentResult->getTransactions();

            $result = [];

            if (!empty($transactions[0])) {
                $state = $paymentResult->getState();

                switch ($state) {
                    case self::PAYPAL_STATE_APPROVED:
                        /** @var Amount $amount */
                        $amount =  $transactions[0]->getAmount();

                        $result = [
                            'status' => self::REPLENISH_STATUS_SUCCESS,
                            'total' => $amount->getTotal(),
                        ];
                        break;

                    case self::PAYPAL_STATE_FAILED:
                        $result = [
                            'status' => self::REPLENISH_STATUS_FAIL,
                            'data' => '',
                        ];
                        break;
                }
            }

            return $result;
        } catch (PayPalConnectionException $ex) {
            return [
                'status' => self::REPLENISH_STATUS_FAIL,
                'data' => $ex->getData(),
            ];
        } catch (\Exception $ex) {
            return [
                'status' => self::REPLENISH_STATUS_FAIL,
                'data' => $ex->getMessage(),
            ];
        }
    }
}
