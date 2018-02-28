<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
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

    /**
     * @var StripeAdapterFactory
     */
    private $stripeAdapterFactory;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param StripeAdapterFactory $stripeAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        StripeAdapterFactory $stripeAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->stripeAdapterFactory = $stripeAdapterFactory;
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

        $token = $payment->getAdditionalInformation('cc_token');
        if ($payment->getAdditionalInformation('is_active_payment_token_enabler')) {
            $customer = $this->stripeAdapterFactory->create($payment->getOrder()->getStoreId())
                ->customer($payment->getOrder()->getCustomerEmail(), $token);

            $result[self::CUSTOMER] = $customer['id'];
        } else {
            $result[self::SOURCE] = $token;
        }

        return $result;
    }
}
