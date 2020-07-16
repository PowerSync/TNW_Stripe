<?php
/**
 * TNW_Stripe extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  TNW
 * @package   TNW_Stripe
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */
namespace TNW\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;

/**
 * Class CustomerDataBuilder
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /** @var StripeAdapterFactory  */
    private $adapterFactory;

    /**
     * CaptureDataBuilder constructor.
     * @param SubjectReader        $subjectReader
     * @param StripeAdapterFactory $adapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        StripeAdapterFactory $adapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDataObject->getPayment();
        $token = $payment->getAdditionalInformation('cc_token');
        $pm = '';
        
        if (strpos($token, 'pm_') !== false) {
            $pm = $token;
        } else {
            $stripeAdapter = $this->adapterFactory->create();
            if (!$token && $payment->getCcTransId()) {
                $paymentIntent = $stripeAdapter->retrievePaymentIntent($payment->getCcTransId());
            } else {
                $paymentIntent = $stripeAdapter->retrievePaymentIntent($token);
            }
            $pm = $paymentIntent->payment_method;
            $cs = $stripeAdapter->retrieveCustomer($paymentIntent->customer);
            if ($cs && $cs->id) {
                return[
                    'email' => $payment->getOrder()->getCustomerEmail(),
                    'invoice_settings' => ['default_payment_method' => $pm],
                    'id' => $cs->id
                ];
            }
        }

        return [
            'email' => $payment->getOrder()->getCustomerEmail(),
            'payment_method' => $pm,
            'invoice_settings' => ['default_payment_method' => $pm]
        ];
    }
}
