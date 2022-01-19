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
     * CreatePaymentIntent constructor.
     * @param StripeAdapterFactory $adapterFactory
     * @param CustomerHelper $customerHelper
     * @param UrlInterface $url
     */
    public function __construct(
        StripeAdapterFactory $adapterFactory,
        CustomerHelper $customerHelper,
        UrlInterface $url
    ) {
        $this->url = $url;
        $this->adapterFactory = $adapterFactory;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param $data
     * @param $quote
     * @param bool $isLoggedIn
     * @return \Stripe\PaymentIntent
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getPaymentIntent($data, $quote, $isLoggedIn = false)
    {
        $payment = $data->paymentMethod;
        $amount = $data->amount;
        $currency = $data->currency;
        $attributes = [
            'payment_method' => $payment->id,
            'metadata' => ['site' => $this->url->getBaseUrl()]
        ];
        $email = $quote->getBillingAddress()->getEmail();
        if ($isLoggedIn) {
            $attributes['email'] = $email;
        } else {
            $attributes['email'] = $email;
            $attributes['description'] = 'guest';
        }
        $stripeAdapter = $this->adapterFactory->create();
        $cs = $stripeAdapter->customer($attributes);
        $this->customerHelper->updateCustomerStripeId($attributes['email'], $cs->id);
        $params = [
            self::CUSTOMER => $cs->id,
            self::AMOUNT => $this->formatPrice($amount),
            self::CURRENCY => $currency,
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual',
            self::CAPTURE_METHOD => 'manual',
            self::SETUP_FUTURE_USAGE => 'off_session'
        ];
        $params[self::PAYMENT_METHOD] = $payment->id;
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
}
