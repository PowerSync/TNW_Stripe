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
use Stripe\Collection;

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
    
    /** @var StripeAdapterFactory  */
    private $adapterFactory;

    /**
     * TokenDataBuilder constructor.
     * @param SubjectReader $subjectReader
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
     * @param array $buildSubject
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
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
            $gatewayToken = $paymentToken->getGatewayToken();
            $compositeGatewayToken = explode('/', $gatewayToken);
            if (count($compositeGatewayToken) > 1) {
                $result[self::CUSTOMER] = $compositeGatewayToken[0];
                $result['payment_method'] = $compositeGatewayToken[1];
            } else {
                $result[self::CUSTOMER] = $paymentToken->getGatewayToken();
                $stripeAdapter = $this->adapterFactory->create();
                $customerPaymentMethods = $stripeAdapter->retrieveCustomerPaymentMethods($result[self::CUSTOMER]);
                $result['payment_method'] = $this->retrieveCurrentPaymentMethodId(
                    $customerPaymentMethods,
                    $paymentToken
                );
            }
        }

        if ($token = $payment->getAdditionalInformation('cc_token')) {
            $result[self::SOURCE] = $token;
        }

        return $result;
    }

    /**
     * @param $customerPaymentMethods
     * @param $paymentToken
     * @return string
     */
    private function retrieveCurrentPaymentMethodId($customerPaymentMethods, $paymentToken)
    {
        $result = '';
        if ($customerPaymentMethods instanceof Collection) {
            $customerPaymentMethods = $customerPaymentMethods->toArray();
            if (array_key_exists('data', $customerPaymentMethods)
                && is_array($customerPaymentMethods['data'])) {
                $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
                list($expirationMonth, $expirationYear) = explode('/', $tokenDetails['expirationDate']);
                foreach ($customerPaymentMethods['data'] as $paymentMethod) {
                    if ($paymentMethod['card']['last4'] == $tokenDetails['maskedCC']
                        && $paymentMethod['card']['exp_month'] == $expirationMonth
                        && $paymentMethod['card']['exp_year'] == $expirationYear
                    ) {
                        $result = $paymentMethod['id'];
                        break;
                    }
                }
            }
        }
        return $result;
    }
}
