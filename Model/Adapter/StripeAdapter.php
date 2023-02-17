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
namespace TNW\Stripe\Model\Adapter;

use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;

/**
 * Class StripeAdapter
 * @package TNW\Stripe\Model\Adapter
 */
class StripeAdapter
{
    /**
     * StripeAdapter constructor.
     * @param string $secretKey
     */
    public function __construct($secretKey)
    {
        $this->secretKey($secretKey);
    }

    /**
     * @param string|null $value
     */
    public function secretKey($value = null)
    {
        Stripe::setApiKey($value);
    }

    /**
     * @param $transactionId
     * @param null $amount
     * @return mixed
     * @throws ApiErrorException
     */
    public function refund($transactionId, $amount = null)
    {
        if (strpos($transactionId, 'ch_') !== false) {
            $chId = $transactionId;
        } else {
            $pi = PaymentIntent::retrieve($transactionId);
            $chId = $pi->charges->data[0]->id;
        }

        if (!$chId) {
            throw new \Exception('Charge not found.');
        }
        $refundData = ['charge' => $chId];
        if ($amount) {
            $refundData['amount'] = $amount;
        }
        return Refund::create($refundData);
    }

    /**
     * @param array $attributes
     * @return Charge|PaymentIntent
     * @throws ApiErrorException
     */
    public function sale(array $attributes)
    {
        $needCapture = isset($attributes['capture']) ? true : false;
        if ($needCapture) {
            unset($attributes['capture']);
        }
        // Payment with old saved customer
        if (isset($attributes['customer']) && !isset($attributes['payment_method'])) {
            unset($attributes['payment_method_types']);
            unset($attributes['confirmation_method']);
            return Charge::create($attributes);
        }
        if (empty($attributes['pi'])) {
            $pi = PaymentIntent::create($attributes);
        } else {
            $pi = PaymentIntent::retrieve($attributes['pi']);
            if (isset($attributes['description'])) {
                $pi->update($attributes['pi'], ['description' => $attributes['description']]);
            }
        }
        if ($pi->status == 'requires_confirmation') {
            $pi->confirm();
        }
        if ($needCapture) {
            $pi->capture(['amount' => $attributes['amount']]);
        }
        return $pi;
    }

    /**
     * @param $transactionId
     * @param null $amount
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function capture($transactionId, $amount = null)
    {
        $pi = PaymentIntent::retrieve($transactionId);
        if ($pi->status == 'requires_confirmation') {
            $pi->confirm();
        }
        $pi->capture(['amount' => $amount]);
        return $pi;
    }

    /**
     * @param $transactionId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function void($transactionId)
    {
        return PaymentIntent::retrieve($transactionId)
            ->cancel();
    }

    /**
     * @param array $attributes
     * @return Customer
     * @throws ApiErrorException
     */
    public function customer(array $attributes)
    {
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);
            Customer::update($id, $attributes);
            $cs = Customer::retrieve($id);
            return $cs;
        } elseif (isset($attributes['email'])) {
            foreach (Customer::all() as $customer) {
                if ($attributes['email'] == $customer->email
                    && isset($customer->metadata)
                    && isset($customer->metadata->site)
                    && isset($attributes['metadata'])
                    && isset($attributes['metadata']['site'])
                    && $customer->metadata->site == $attributes['metadata']['site']
                ) {
                    return $customer;
                }
            }
        }
        return Customer::create($attributes);
    }

    /**
     * @param array $attributes
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function createPaymentIntent(array $attributes)
    {
        return PaymentIntent::create($attributes)->confirm();
    }

    /**
     * @param $transactionId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent($transactionId)
    {
        return PaymentIntent::retrieve($transactionId);
    }

    /**
     * @param $paymentIntentId
     * @param $params
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function updatePaymentIntent($paymentIntentId, $params)
    {
        return PaymentIntent::update($paymentIntentId, $params);
    }

    /**
     * @param $transactionId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function cancelPaymentIntent($transactionId)
    {
        return PaymentIntent::retrieve($transactionId)->cancel();
    }

    /**
     * @param $id
     * @param $attributes
     * @return Customer
     * @throws ApiErrorException
     */
    public function updateCustomer($id, $attributes)
    {
        return Customer::update($id, $attributes);
    }

    /**
     * @param $customerId
     * @return Customer
     * @throws ApiErrorException
     */
    public function retrieveCustomer($customerId)
    {
        return Customer::retrieve($customerId);
    }

    /**
     * @param $customerId
     * @return \Stripe\Collection
     * @throws ApiErrorException
     */
    public function retrieveCustomerPaymentMethods($customerId)
    {
        $stripeClient = new StripeClient(Stripe::getApiKey());
        return $stripeClient->paymentMethods->all([
            'customer' => $customerId,
            'type' => 'card',
        ]);
    }

    /**
     * @param $id
     * @param array $customerData
     * @throws ApiErrorException
     */
    public function attachPaymentMethodToCustomer($id, array $customerData)
    {
        $stripeClient = new StripeClient(Stripe::getApiKey());
        $stripeClient->paymentMethods->attach($id, $customerData);
    }

    /**
     * @param string $id
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function retrievePaymentMethod(string $id)
    {
        $stripeClient = new StripeClient(Stripe::getApiKey());
        return $stripeClient->paymentMethods->retrieve($id);
    }

    /**
     * @param string $id
     * @param array $data
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function updatePaymentMethod(string $id, array $data)
    {
        $stripeClient = new StripeClient(Stripe::getApiKey());
        return $stripeClient->paymentMethods->update($id, $data);
    }
}
