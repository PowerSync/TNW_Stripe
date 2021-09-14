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
 * @copyright Copyright (c) 2017-2021
 * @license   Open Software License (OSL 3.0)
 */
namespace TNW\Stripe\Plugin\Quote\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Stripe\Exception\ApiErrorException;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;

class CartManagement
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /** @var StripeAdapterFactory  */
    private $adapterFactory;

    /**
     * @param LoggerInterface $logger
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param StripeAdapterFactory $adapterFactory
     */
    public function __construct(
        LoggerInterface $logger,
        PaymentMethodManagementInterface $paymentMethodManagement,
        StripeAdapterFactory $adapterFactory
    ) {
        $this->logger = $logger;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @param CartManagementInterface $subject
     * @param callable $proceed
     * @param int $cartId
     * @param PaymentInterface|null $paymentMethod
     * @return mixed
     * @throws NoSuchEntityException|ApiErrorException
     */
    public function aroundPlaceOrder(
        CartManagementInterface $subject,
        callable $proceed,
        $cartId,
        PaymentInterface $paymentMethod = null
    ) {
        try {
            return $proceed($cartId, $paymentMethod);
        } catch (Exception $exception) {
            try {
                $payment = $this->paymentMethodManagement->get($cartId);
                if ($payment->getMethod() == 'tnw_stripe') {
                    $token = $payment->getAdditionalInformation('cc_token');
                    if (strpos($token, 'pi_') !== false) {
                        $stripeAdapter = $this->adapterFactory->create();
                        $paymentIntent = $stripeAdapter->cancelPaymentIntent($token);
                        if ($paymentIntent->getLastResponse()->code !== 200) {
                            $this->logger->error('Stripe payment rollback failed. PI: ' . $token);
                        }
                    }
                }
            } catch (Exception $rollbackException) {
                $this->logger->error('Stripe payment rollback error: ' . $rollbackException->getMessage());
            }
            throw $exception;
        }
    }
}
