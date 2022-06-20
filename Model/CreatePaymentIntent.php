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
namespace TNW\Stripe\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Quote\Model\Quote;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use TNW\Stripe\Helper\Payment\Formatter;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Helper\Customer as CustomerHelper;
use Magento\Framework\UrlInterface;

/**
 * Class CreatePaymentIntent - model used to create payment intent from quote
 */
class CreatePaymentIntent
{
    use Formatter;

    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CONFIRMATION_METHOD = 'confirmation_method';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_TYPES = 'payment_method_types';
    const RECEIPT_EMAIL = 'receipt_email';
    const CUSTOMER = 'customer';
    const CAPTURE_METHOD = 'capture_method';
    const SETUP_FUTURE_USAGE = 'setup_future_usage';

    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    /**
     * @var StripeAdapterFactory
     */
    private $adapterFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var VaultTokenProcessor
     */
    private $vaultTokenProcessor;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param StripeAdapterFactory $adapterFactory
     * @param CustomerHelper $customerHelper
     * @param UrlInterface $url
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param VaultTokenProcessor $vaultTokenProcessor
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StripeAdapterFactory $adapterFactory,
        CustomerHelper $customerHelper,
        UrlInterface $url,
        PaymentTokenManagementInterface $tokenManagement,
        VaultTokenProcessor $vaultTokenProcessor,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->vaultTokenProcessor = $vaultTokenProcessor;
        $this->url = $url;
        $this->adapterFactory = $adapterFactory;
        $this->customerHelper = $customerHelper;
        $this->tokenManagement = $tokenManagement;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param $data
     * @param Quote $quote
     * @param bool $isLoggedIn
     * @return PaymentIntent
     * @throws LocalizedException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     * @throws ApiErrorException
     */
    public function getPaymentIntent($data, $quote, $isLoggedIn = false)
    {
        $customer = $quote->getCustomer();
        if (!$customer->getId() && $quote->getCustomerEmail()
            && $this->isExistingCustomerEmail($quote->getCustomerEmail(), $customer->getWebsiteId())
        ) {
            throw new LocalizedException(
                __('A customer with the same email address already exists in an associated website.')
            );
        }
        $stripeAdapter = $this->adapterFactory->create();
        if (property_exists($data, 'public_hash')) {
            $customerId = $quote->getCustomer()->getId();
            $paymentToken = $this->tokenManagement->getByPublicHash($data->public_hash, $customerId);
            list($payment, $stripeCustomerId) = $this->vaultTokenProcessor->getPaymentMethodByVaultToken($paymentToken);
        } else {
            $payment = $data->paymentMethod->id;
        }
        $amount = $data->amount;
        if (property_exists($data, 'currency')) {
            $currency = $data->currency;
        } else {
            $currency = $quote->getQuoteCurrencyCode();
        }
        $attributes = [
            'payment_method' => $payment,
            'metadata' => ['site' => $this->url->getBaseUrl()]
        ];
        $email = $quote->getCustomerEmail();
        if (!$isLoggedIn) {
            $attributes['description'] = 'guest';
        }
        $attributes['email'] = $email;
        $stripeCustomerId = $stripeCustomerId ?? $stripeAdapter->customer($attributes)->id;
        $this->customerHelper->updateCustomerStripeId($attributes['email'], $stripeCustomerId);
        $params = [
            self::CUSTOMER => $stripeCustomerId,
            self::AMOUNT => $this->formatPrice($amount),
            self::CURRENCY => $currency,
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual',
            self::CAPTURE_METHOD => 'manual',
            self::SETUP_FUTURE_USAGE => 'off_session'
        ];
        $params[self::PAYMENT_METHOD] = $payment;
        if (!$quote->isVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $params['shipping'] = [
                'address' => [
                    'city' => $shippingAddress->getCity(),
                    'country' => $shippingAddress->getCountryId(),
                    'line1' => $shippingAddress->getStreetLine(1),
                    'line2' => $shippingAddress->getStreetLine(2),
                    'postal_code' => $shippingAddress->getPostcode(),
                    'state' => $shippingAddress->getRegion()
                ],
                'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname()
            ];
        }
        return $stripeAdapter->createPaymentIntent($params);
    }

    /**
     * Checks if customer with given email exists
     *
     * @param string $email
     * @param int|null $websiteId
     * @return bool
     * @throws LocalizedException
     */
    public function isExistingCustomerEmail($email, $websiteId = null)
    {
        try {
            $this->customerRepository->get($email, $websiteId);
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
