<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;

class TokenDataBuilder implements BuilderInterface
{
    const SOURCE = 'source';
    const CUSTOMER = 'customer';

    /**
     * @var SubjectReader
     */
    private $subjectReader;
    
    /** @var StripeAdapterFactory  */
    private $adapterFactory;
    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        StripeAdapterFactory $adapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $result = [];

        if ($paymentToken instanceof PaymentTokenInterface) {
            $result[self::CUSTOMER] = $paymentToken->getGatewayToken();
            $stripeAdapter = $this->adapterFactory->create();
            $customer = $stripeAdapter->retrieveCustomer($result[self::CUSTOMER]);
            $pm = $customer->invoice_settings->default_payment_method;
            $result['payment_method'] = $pm;
        }

        if ($token = $payment->getAdditionalInformation('cc_token')) {
            $result[self::SOURCE] = $token;
        }

        return $result;
    }
}
