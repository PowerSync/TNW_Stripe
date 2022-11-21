<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Model\VaultTokenProcessor;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Class TokenDataBuilder
 */
class TokenDataBuilder implements BuilderInterface
{
    const SOURCE = 'source';
    const CUSTOMER = 'customer';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var VaultTokenProcessor
     */
    private $vaultTokenProcessor;

    /**
     * @param SubjectReader $subjectReader
     * @param VaultTokenProcessor $vaultTokenProcessor
     */
    public function __construct(
        SubjectReader $subjectReader,
        VaultTokenProcessor $vaultTokenProcessor
    ) {
        $this->vaultTokenProcessor = $vaultTokenProcessor;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        $result = [];
        if ($paymentToken instanceof PaymentTokenInterface) {
            list($paymentMethod, $customer) = $this->vaultTokenProcessor->getPaymentMethodByVaultToken($paymentToken);
            $result[self::CUSTOMER] = $customer;
            $result['payment_method'] = $paymentMethod;
        }
        if ($pi = $payment->getAdditionalInformation('payment_method_token')) {
            $result['pi'] = $pi;
            $result['set_pm'] = true;
            if (isset($paymentMethod)) {
                $result[self::SOURCE] = $paymentMethod;
            }
        }
        if ($token = $payment->getAdditionalInformation('cc_token')) {
            $result[self::SOURCE] = $token;
        }
        return $result;
    }
}
