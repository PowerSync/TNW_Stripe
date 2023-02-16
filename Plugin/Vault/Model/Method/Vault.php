<?php

namespace TNW\Stripe\Plugin\Vault\Model\Method;

use LogicException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Method\Vault as VaultPaymentMethod;
use Psr\Log\LoggerInterface;
use TNW\Stripe\Model\Adapter\StripeAdapter;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;

/**
 * Plugin class for Vault payment method.
 */
class Vault
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var StripeAdapterFactory
     */
    private $stripeAdapterFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Vault constructor.
     *
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param StripeAdapterFactory $stripeAdapterFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        StripeAdapterFactory $stripeAdapterFactory,
        LoggerInterface $logger
    ) {
        $this->tokenManagement = $tokenManagement;
        $this->stripeAdapterFactory = $stripeAdapterFactory;
        $this->logger = $logger;
    }

    /**
     * @param VaultPaymentMethod $subject
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
    public function beforeAuthorize(VaultPaymentMethod $subject, InfoInterface $payment, $amount): array
    {
        $this->processPayment($payment);
        return [$payment, $amount];
    }

    /**
     * @param VaultPaymentMethod $subject
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
    public function beforeCapture(VaultPaymentMethod $subject, InfoInterface $payment, $amount): array
    {
        $this->processPayment($payment);
        return [$payment, $amount];
    }

    /**
     * @param InfoInterface $payment
     * @return void
     */
    private function processPayment(InfoInterface $payment)
    {
        /** @var Payment $payment */
        if ($payment->getMethod() === 'tnw_stripe_vault') {
            $this->updatePaymentMethod($payment);
        }
    }

    /**
     * Updates saved payment method on Stripe side.
     *
     * @param Payment $payment
     * @return void
     */
    private function updatePaymentMethod(Payment $payment)
    {
        $order = $payment->getOrder();
        if ($order->getBillingAddress()) {
            try {
                $paymentMethodId = $this->getPaymentMethodId($payment);
                $stripeAdapter = $this->getStripeAdapter($order->getStoreId());
                $requestData = $this->buildRequestData($order);
                $stripeAdapter->updatePaymentMethod($paymentMethodId, $requestData);
            } catch (\Throwable $exception) {
                $this->logger->error('Unable to update Stripe payment method.', compact('exception'));
            }
        }
    }

    /**
     * Builds request data for update payment method request.
     *
     * @param Order $order
     * @return array
     */
    private function buildRequestData(Order $order): array
    {
        $billingAddress = $order->getBillingAddress();
        return [
            'billing_details' => [
                'address' => $this->buildBillingAddress($billingAddress),
                'name' => sprintf(
                    '%s %s',
                    $billingAddress->getFirstname(),
                    $billingAddress->getLastname()
                ),
                'email' => $billingAddress->getEmail(),
                'phone' => $billingAddress->getTelephone()
            ]
        ];
    }

    /**
     * Builds billing adress array for update payment method request.
     *
     * @param OrderAddressInterface $billingAddress
     * @return array
     */
    private function buildBillingAddress(OrderAddressInterface $billingAddress): array
    {
        return [
            'city' => $billingAddress->getCity(),
            'country' => $billingAddress->getCountryId(),
            'line1' => $billingAddress->getStreetLine(1),
            'line2' => $billingAddress->getStreetLine(2),
            'postal_code' => $billingAddress->getPostcode(),
            'state' => $billingAddress->getRegionCode()
        ];
    }

    /**
     * Returns Stripe adapter.
     *
     * @param int $storeId
     * @return StripeAdapter
     */
    private function getStripeAdapter(int $storeId): StripeAdapter
    {
        return $this->stripeAdapterFactory->create($storeId);
    }

    /**
     * Returns vault payment token.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return PaymentTokenInterface
     */
    private function getPaymentToken(OrderPaymentInterface $orderPayment): PaymentTokenInterface
    {
        $additionalInformation = $orderPayment->getAdditionalInformation();
        if (empty($additionalInformation[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new LogicException('Public hash should be defined');
        }
        $customerId = $additionalInformation[PaymentTokenInterface::CUSTOMER_ID] ?? null;
        $publicHash = $additionalInformation[PaymentTokenInterface::PUBLIC_HASH];
        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);
        if ($paymentToken === null) {
            throw new LogicException("No token found");
        }
        return $paymentToken;
    }

    /**
     * Returns Stripe payment method ID.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     */
    private function getPaymentMethodId(OrderPaymentInterface $orderPayment): string
    {
        $paymentToken = $this->getPaymentToken($orderPayment);
        [$customer, $paymentMethodId] = explode('/', $paymentToken->getGatewayToken());
        return $paymentMethodId;
    }
}
