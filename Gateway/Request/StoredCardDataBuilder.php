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
use Magento\Vault\Api\PaymentTokenManagementInterface;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Gateway\Http\Client\TransactionCustomer;
use TNW\Stripe\Gateway\Http\TransferFactory;

class StoredCardDataBuilder implements BuilderInterface
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CONFIRMATION_METHOD = 'confirmation_method';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_TYPES = 'payment_method_types';
    const RECEIPT_EMAIL = 'receipt_email';
    const PI = 'pi';
    const CAPTURE_METHOD = 'capture_method';
    const SHIPPING_ADDRESS = 'shipping';
    const STREET_ADDRESS = 'line1';
    const EXTENDED_ADDRESS = 'line2';
    const LOCALITY = 'city';
    const REGION = 'state';
    const POSTAL_CODE = 'postal_code';
    const COUNTRY_CODE = 'country';
    const NAME = 'name';
    const PHONE = 'phone';
    const SOURCE = 'source';
    const CUSTOMER = 'customer';

    /**
     * @var mixed
     */
    private $config;

    /**
     * @var mixed
     */
    private $adapterFactory;

    private $paymentTokenManagement;

    /**
     * @var mixed
     */
    private $customerClient;

    /**
     * @var mixed
     */
    private $transferFactory;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        Config $config,
        StripeAdapterFactory $adapterFactory,
        TransactionCustomer $customerClient,
        TransferFactory $transferFactory
    ) {
        $this->config =$config;
        $this->adapterFactory = $adapterFactory;
        $this->customerClient = $customerClient;
        $this->transferFactory = $transferFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    public function build(array $data)
    {
        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $data['customer'];
        foreach ($customer->getAddresses() as $address) {
            if ($address->isDefaultShipping()) {
                $shippingAddress = $address;
            }
        }
        $token = $data['token'];
        $stripeAdapter = $this->adapterFactory->create();
        $paymentIntent = $stripeAdapter->retrievePaymentIntent($token);
        $storeId = $customer->getStoreId();
        $result = [
            'store_id' => $storeId,
            self::AMOUNT => $this->formatPrice(1),
            self::CURRENCY => $paymentIntent->currency,
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual',
            self::CAPTURE_METHOD => 'manual'
        ];

        $result[self::PI] = $token;
        if (isset($shippingAddress)) {
            $result[self::SHIPPING_ADDRESS] = [
                'address' => [
                    self::STREET_ADDRESS => $shippingAddress->getStreet()[0],
                    self::EXTENDED_ADDRESS => isset($shippingAddress->getStreet()[1])
                        ? $shippingAddress->getStreet()[1]
                        : '',
                    self::LOCALITY => $shippingAddress->getCity(),
                    self::REGION => $shippingAddress->getRegionId(),
                    self::POSTAL_CODE => $shippingAddress->getPostcode(),
                    self::COUNTRY_CODE => $shippingAddress->getCountryId()
                ],
                self::NAME => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                self::PHONE => $shippingAddress->getTelephone()
            ];
        }
        $customerRequestData = ['store_id' => $storeId];
        $pm = $paymentIntent->payment_method;
        $cs = $stripeAdapter->retrieveCustomer($paymentIntent->customer);
        if ($cs && $cs->id) {
            $customerRequestData['id'] = $cs->id;
        }
        $customerRequestData['email'] = $customer->getEmail();
        if (!isset($customerRequestData['id'])) {
            $customerRequestData['payment_method'] = $pm;
        }
        $customerRequestData['invoice_settings'] = ['default_payment_method' => $pm];
        try {
            $this->customerClient->placeRequest($this->transferFactory->create($customerRequestData));
        } catch (\Magento\Payment\Gateway\Http\ClientException $e) {
            $result[self::CUSTOMER] = $customerRequestData['id'];
            $result[self::PAYMENT_METHOD] = $pm;
        }
        return $result;
    }

    /**
     * @param $price
     * @return mixed
     */
    public function formatPrice($price)
    {
        $price = sprintf('%.2F', $price);
        return str_replace('.', '', $price);
    }
}
