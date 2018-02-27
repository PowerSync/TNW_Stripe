<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Stripe\Gateway\Request;

use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Helper\Payment\Formatter;
use Magento\Sales\Model\Order\Payment;

/**
 * Class VaultCaptureDataBuilder
 */
class VaultCaptureDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        return [
            PaymentDataBuilder::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            PaymentDataBuilder::CURRENCY => $this->config->getCurrency(),
            PaymentDataBuilder::CUSTOMER => $paymentToken->getGatewayToken(),
        ];
    }
}
